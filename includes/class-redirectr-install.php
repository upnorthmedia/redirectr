<?php
/**
 * Installation related functions and actions.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirectr_Install Class.
 */
class Redirectr_Install {

	/**
	 * Install Redirectr.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		self::create_tables();
		self::create_options();
		self::update_version();
		self::schedule_cron_events();
	}

	/**
	 * Deactivate Redirectr.
	 */
	public static function deactivate() {
		// Clear scheduled events.
		$timestamp = wp_next_scheduled( 'redirectr_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'redirectr_daily_cleanup' );
		}
	}

	/**
	 * Schedule cron events.
	 */
	public static function schedule_cron_events() {
		if ( ! wp_next_scheduled( 'redirectr_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'redirectr_daily_cleanup' );
		}
	}

	/**
	 * Create database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$tables = "
CREATE TABLE {$wpdb->prefix}redirectr_redirects (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  source_url varchar(2048) NOT NULL,
  destination_url varchar(2048) NOT NULL,
  match_type varchar(20) NOT NULL DEFAULT 'exact',
  redirect_type smallint(3) NOT NULL DEFAULT 301,
  hit_count bigint(20) unsigned NOT NULL DEFAULT 0,
  status varchar(20) NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY source_url (source_url(191)),
  KEY status (status),
  KEY match_type (match_type)
) $charset_collate;

CREATE TABLE {$wpdb->prefix}redirectr_404_logs (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  url varchar(2048) NOT NULL,
  referrer varchar(2048) DEFAULT NULL,
  user_agent varchar(512) DEFAULT NULL,
  ip_hash varchar(64) DEFAULT NULL,
  hit_count bigint(20) unsigned NOT NULL DEFAULT 1,
  first_seen datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status varchar(20) NOT NULL DEFAULT 'new',
  PRIMARY KEY  (id),
  UNIQUE KEY url_hash (url(191)),
  KEY status (status),
  KEY last_seen (last_seen),
  KEY hit_count (hit_count)
) $charset_collate;
";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $tables );
	}

	/**
	 * Create default options.
	 */
	private static function create_options() {
		$default_options = array(
			'enable_404_logging'    => 1,
			'log_retention_days'    => 30,
			'exclude_patterns'      => '',
			'auto_delete_on_redirect' => 0,
		);

		if ( ! get_option( 'redirectr_options' ) ) {
			add_option( 'redirectr_options', $default_options );
		}
	}

	/**
	 * Update plugin version.
	 */
	private static function update_version() {
		update_option( 'redirectr_version', REDIRECTR_VERSION );
		update_option( 'redirectr_db_version', REDIRECTR_DB_VERSION );
	}

	/**
	 * Check if tables need to be updated.
	 */
	public static function maybe_update_tables() {
		$installed_version = get_option( 'redirectr_db_version' );

		if ( $installed_version !== REDIRECTR_DB_VERSION ) {
			self::create_tables();
			self::update_version();
		}
	}
}
