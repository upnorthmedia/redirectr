<?php
/**
 * Redirectr helper functions.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize a URL by stripping query parameters.
 *
 * @param string $url The URL to normalize.
 * @return string Normalized URL (path only).
 */
function redirectr_normalize_url( $url ) {
	$parsed = wp_parse_url( $url );
	return isset( $parsed['path'] ) ? $parsed['path'] : '/';
}

/**
 * Get count of new (unhandled) 404 errors.
 *
 * @return int
 */
function redirectr_get_new_404_count() {
	global $wpdb;

	// Cache the count for performance.
	$count = wp_cache_get( 'redirectr_new_404_count' );

	if ( false === $count ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, cached below.
		$count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->redirectr_404_logs} WHERE status = 'new'"
		);
		wp_cache_set( 'redirectr_new_404_count', $count, '', 300 ); // Cache for 5 minutes.
	}

	return $count;
}

/**
 * Clear the 404 count cache.
 */
function redirectr_clear_404_count_cache() {
	wp_cache_delete( 'redirectr_new_404_count' );
}

/**
 * Get total count of redirects.
 *
 * @param string $status Optional status filter ('active', 'inactive', or empty for all).
 * @return int
 */
function redirectr_get_redirect_count( $status = '' ) {
	global $wpdb;

	$where = '';
	if ( $status ) {
		$where = $wpdb->prepare( ' WHERE status = %s', $status );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, $where is prepared above.
	return (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->redirectr_redirects}{$where}" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	);
}

/**
 * Get total count of 404 logs.
 *
 * @param string $status Optional status filter ('new', 'ignored', 'redirected', or empty for all).
 * @return int
 */
function redirectr_get_404_log_count( $status = '' ) {
	global $wpdb;

	$where = '';
	if ( $status ) {
		$where = $wpdb->prepare( ' WHERE status = %s', $status );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table, $where is prepared above.
	return (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->redirectr_404_logs}{$where}" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	);
}

/**
 * Delete old 404 logs based on retention setting.
 */
function redirectr_cleanup_old_logs() {
	global $wpdb;

	$retention_days = Redirectr::get_option( 'log_retention_days', 30 );

	if ( $retention_days > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, cleanup operation.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->redirectr_404_logs}
				WHERE last_seen < DATE_SUB(NOW(), INTERVAL %d DAY)
				AND status = 'ignored'",
				$retention_days
			)
		);
	}
}

/**
 * Format a URL for display (truncate if too long).
 *
 * @param string $url The URL to format.
 * @param int    $max_length Maximum length before truncation.
 * @return string
 */
function redirectr_format_url_for_display( $url, $max_length = 50 ) {
	if ( strlen( $url ) <= $max_length ) {
		return esc_html( $url );
	}

	return esc_html( substr( $url, 0, $max_length - 3 ) . '...' );
}

/**
 * Get redirect types available.
 *
 * @return array
 */
function redirectr_get_redirect_types() {
	return array(
		301 => __( '301 - Permanent', 'redirectr' ),
		302 => __( '302 - Temporary', 'redirectr' ),
		307 => __( '307 - Temporary (Strict)', 'redirectr' ),
	);
}

/**
 * Get match types available.
 *
 * @return array
 */
function redirectr_get_match_types() {
	return array(
		'exact' => __( 'Exact Match', 'redirectr' ),
		'regex' => __( 'Regular Expression', 'redirectr' ),
	);
}

/**
 * Validate regex pattern for safety (ReDoS protection).
 *
 * @param string $pattern The regex pattern to validate.
 * @return bool|WP_Error True if safe, WP_Error if problematic.
 */
function redirectr_validate_regex_pattern( $pattern ) {
	// Test that pattern is valid.
	if ( @preg_match( $pattern, '' ) === false ) {
		return new WP_Error( 'invalid_regex', __( 'Invalid regular expression pattern.', 'redirectr' ) );
	}

	// Check for dangerous patterns that could cause catastrophic backtracking.
	$dangerous_patterns = array(
		'/(\+|\*)\+/',               // Nested quantifiers like .++ or .*+
		'/(\+|\*)\?(\+|\*)/',        // Nested quantifiers
		'/\([^)]*(\+|\*)[^)]*\)\+/', // Group with quantifier followed by +
		'/\([^)]*(\+|\*)[^)]*\)\*/', // Group with quantifier followed by *
	);

	foreach ( $dangerous_patterns as $check ) {
		if ( preg_match( $check, $pattern ) ) {
			return new WP_Error(
				'dangerous_regex',
				__( 'This regex pattern may cause performance issues. Please simplify the pattern.', 'redirectr' )
			);
		}
	}

	// Limit pattern length.
	if ( strlen( $pattern ) > 500 ) {
		return new WP_Error( 'regex_too_long', __( 'Regex pattern is too long (max 500 characters).', 'redirectr' ) );
	}

	return true;
}

/**
 * Clear redirect caches.
 */
function redirectr_clear_redirect_cache() {
	wp_cache_delete( 'redirectr_exact_redirects' );
	wp_cache_delete( 'redirectr_regex_redirects' );
}

/**
 * Get total sum of all 404 hits.
 *
 * @return int
 */
function redirectr_get_total_404_hits() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, aggregate query.
	return (int) $wpdb->get_var(
		"SELECT COALESCE(SUM(hit_count), 0) FROM {$wpdb->redirectr_404_logs}"
	);
}

/**
 * Get total saved visits (sum of hit counts for active 301 redirects).
 *
 * @return int
 */
function redirectr_get_saved_visits() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, aggregate query.
	return (int) $wpdb->get_var(
		"SELECT COALESCE(SUM(hit_count), 0)
		FROM {$wpdb->redirectr_redirects}
		WHERE redirect_type = 301 AND status = 'active'"
	);
}

/**
 * Get total unhandled 404 hits (sum of hit counts for new 404s).
 *
 * @return int
 */
function redirectr_get_unhandled_404_hits() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, aggregate query.
	return (int) $wpdb->get_var(
		"SELECT COALESCE(SUM(hit_count), 0)
		FROM {$wpdb->redirectr_404_logs}
		WHERE status = 'new'"
	);
}

/**
 * Get traffic recovery rate percentage.
 *
 * @return int Percentage (0-100).
 */
function redirectr_get_recovery_rate() {
	$saved     = redirectr_get_saved_visits();
	$unhandled = redirectr_get_unhandled_404_hits();
	$total     = $saved + $unhandled;

	return $total > 0 ? (int) round( ( $saved / $total ) * 100 ) : 0;
}

/**
 * Validate a redirect before saving.
 *
 * @param array $data Redirect data.
 * @return array|WP_Error Validated data or WP_Error on failure.
 */
function redirectr_validate_redirect( $data ) {
	$errors = array();

	// Source URL is required.
	if ( empty( $data['source_url'] ) ) {
		$errors[] = __( 'Source URL is required.', 'redirectr' );
	}

	// Destination URL is required.
	if ( empty( $data['destination_url'] ) ) {
		$errors[] = __( 'Destination URL is required.', 'redirectr' );
	}

	// Validate regex if match type is regex (includes ReDoS protection).
	if ( 'regex' === $data['match_type'] && ! empty( $data['source_url'] ) ) {
		$regex_check = redirectr_validate_regex_pattern( $data['source_url'] );
		if ( is_wp_error( $regex_check ) ) {
			$errors[] = $regex_check->get_error_message();
		}
	}

	// Check for redirect loops (source equals destination).
	if ( $data['source_url'] === $data['destination_url'] ) {
		$errors[] = __( 'Source and destination URLs cannot be the same.', 'redirectr' );
	}

	if ( ! empty( $errors ) ) {
		return new WP_Error( 'validation_error', implode( ' ', $errors ) );
	}

	// Sanitize and return.
	return array(
		'source_url'      => sanitize_text_field( $data['source_url'] ),
		'destination_url' => esc_url_raw( $data['destination_url'] ),
		'match_type'      => in_array( $data['match_type'], array( 'exact', 'regex' ), true ) ? $data['match_type'] : 'exact',
		'redirect_type'   => in_array( (int) $data['redirect_type'], array( 301, 302, 307 ), true ) ? (int) $data['redirect_type'] : 301,
		'status'          => ! empty( $data['status'] ) ? 'active' : 'inactive',
	);
}
