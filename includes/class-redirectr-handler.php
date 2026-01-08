<?php
/**
 * Redirect handler for front-end redirect matching and 404 logging.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirectr_Handler Class.
 */
class Redirectr_Handler {

	/**
	 * Check for matching redirect and perform redirect if found.
	 * Called at priority 1 on template_redirect.
	 */
	public static function maybe_redirect() {
		// Don't run in admin.
		if ( is_admin() ) {
			return;
		}

		$request_uri = self::get_request_uri();

		// Try to find a matching redirect.
		$redirect = self::find_redirect( $request_uri );

		if ( $redirect ) {
			self::increment_redirect_hit_count( $redirect->id );

			// Handle regex replacement if needed.
			$destination = $redirect->destination_url;
			if ( 'regex' === $redirect->match_type ) {
				$destination = preg_replace( $redirect->source_url, $redirect->destination_url, $request_uri );
			}

			// Perform the redirect.
			wp_redirect( $destination, (int) $redirect->redirect_type );
			exit;
		}
	}

	/**
	 * Log 404 errors.
	 * Called at priority 99999 on template_redirect (after WordPress determines 404).
	 */
	public static function log_404() {
		// Only log actual 404s.
		if ( ! is_404() ) {
			return;
		}

		// Check if logging is enabled.
		if ( ! Redirectr::get_option( 'enable_404_logging', 1 ) ) {
			return;
		}

		global $wpdb;

		$url = self::get_request_uri();

		// Check exclude patterns.
		if ( self::is_excluded( $url ) ) {
			return;
		}

		$referrer   = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$ip_hash    = hash( 'sha256', sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ) . wp_salt() );

		// Check if this URL already exists (upsert logic).
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->redirectr_404_logs} WHERE url = %s",
				$url
			)
		);

		if ( $existing ) {
			// Update existing entry - increment hit count.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->redirectr_404_logs}
					SET hit_count = hit_count + 1,
						last_seen = NOW(),
						referrer = %s,
						user_agent = %s,
						ip_hash = %s
					WHERE id = %d",
					$referrer,
					$user_agent,
					$ip_hash,
					$existing->id
				)
			);
		} else {
			// Insert new entry.
			$wpdb->insert(
				$wpdb->redirectr_404_logs,
				array(
					'url'        => $url,
					'referrer'   => $referrer,
					'user_agent' => $user_agent,
					'ip_hash'    => $ip_hash,
					'hit_count'  => 1,
					'first_seen' => current_time( 'mysql' ),
					'last_seen'  => current_time( 'mysql' ),
					'status'     => 'new',
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * Get the normalized request URI (path only, no query params).
	 *
	 * @return string
	 */
	private static function get_request_uri() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		// Normalize: strip query parameters.
		return redirectr_normalize_url( $request_uri );
	}

	/**
	 * Find a matching redirect for the given URL.
	 *
	 * @param string $request_uri The request URI to match.
	 * @return object|null Redirect object or null if not found.
	 */
	private static function find_redirect( $request_uri ) {
		global $wpdb;

		// Try cache first for exact matches.
		$cached_redirects = wp_cache_get( 'redirectr_exact_redirects' );

		if ( false === $cached_redirects ) {
			// Load all active exact-match redirects into cache.
			$exact_redirects = $wpdb->get_results(
				"SELECT * FROM {$wpdb->redirectr_redirects}
				WHERE status = 'active' AND match_type = 'exact'"
			);

			// Index by source_url for O(1) lookup.
			$cached_redirects = array();
			foreach ( $exact_redirects as $r ) {
				$cached_redirects[ $r->source_url ] = $r;
			}

			wp_cache_set( 'redirectr_exact_redirects', $cached_redirects, '', HOUR_IN_SECONDS );
		}

		// Check exact match from cache.
		if ( isset( $cached_redirects[ $request_uri ] ) ) {
			return $cached_redirects[ $request_uri ];
		}

		// Fall back to regex matches (these are harder to cache).
		$regex_redirects = wp_cache_get( 'redirectr_regex_redirects' );

		if ( false === $regex_redirects ) {
			$regex_redirects = $wpdb->get_results(
				"SELECT * FROM {$wpdb->redirectr_redirects}
				WHERE status = 'active' AND match_type = 'regex'"
			);
			wp_cache_set( 'redirectr_regex_redirects', $regex_redirects, '', HOUR_IN_SECONDS );
		}

		foreach ( $regex_redirects as $redirect ) {
			if ( self::safe_preg_match( $redirect->source_url, $request_uri ) ) {
				return $redirect;
			}
		}

		return null;
	}

	/**
	 * Safely execute a regex match with timeout protection.
	 *
	 * @param string $pattern The regex pattern.
	 * @param string $subject The string to match against.
	 * @return bool Whether the pattern matches.
	 */
	private static function safe_preg_match( $pattern, $subject ) {
		// Set a lower backtrack limit for this operation.
		$old_limit = ini_get( 'pcre.backtrack_limit' );
		ini_set( 'pcre.backtrack_limit', 10000 ); // phpcs:ignore

		$result = @preg_match( $pattern, $subject );

		// Restore original limit.
		ini_set( 'pcre.backtrack_limit', $old_limit ); // phpcs:ignore

		return 1 === $result;
	}

	/**
	 * Increment the hit count for a redirect.
	 *
	 * @param int $redirect_id Redirect ID.
	 */
	private static function increment_redirect_hit_count( $redirect_id ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->redirectr_redirects}
				SET hit_count = hit_count + 1
				WHERE id = %d",
				$redirect_id
			)
		);
	}

	/**
	 * Check if URL should be excluded from logging.
	 *
	 * @param string $url The URL to check.
	 * @return bool
	 */
	private static function is_excluded( $url ) {
		// Always exclude WordPress core directories.
		$core_exclusions = array(
			'/wp-content/',
			'/wp-admin/',
			'/wp-includes/',
			'/wp-json/',
		);

		foreach ( $core_exclusions as $exclusion ) {
			if ( strpos( $url, $exclusion ) === 0 ) {
				return true;
			}
		}

		// Also exclude common file extensions that aren't pages.
		$excluded_extensions = array( '.map', '.php', '.xml', '.txt', '.ico', '.css', '.js' );
		foreach ( $excluded_extensions as $ext ) {
			if ( substr( $url, -strlen( $ext ) ) === $ext ) {
				return true;
			}
		}

		// Check user-defined exclude patterns.
		$exclude_patterns = Redirectr::get_option( 'exclude_patterns', '' );

		if ( empty( $exclude_patterns ) ) {
			return false;
		}

		$patterns = array_filter( array_map( 'trim', explode( "\n", $exclude_patterns ) ) );

		foreach ( $patterns as $pattern ) {
			// Check if pattern matches (supports wildcards).
			$regex = str_replace( '\*', '.*', preg_quote( $pattern, '/' ) );
			if ( preg_match( '/^' . $regex . '$/i', $url ) ) {
				return true;
			}
		}

		return false;
	}
}
