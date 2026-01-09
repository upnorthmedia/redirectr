<?php
/**
 * Main Redirectr class.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Redirectr Class.
 */
final class Redirectr {

	/**
	 * The single instance of the class.
	 *
	 * @var Redirectr
	 */
	protected static $instance = null;

	/**
	 * Main Redirectr Instance.
	 *
	 * Ensures only one instance of Redirectr is loaded or can be loaded.
	 *
	 * @return Redirectr - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->define_tables();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define custom database table names.
	 */
	private function define_tables() {
		global $wpdb;
		$wpdb->redirectr_redirects = $wpdb->prefix . 'redirectr_redirects';
		$wpdb->redirectr_404_logs  = $wpdb->prefix . 'redirectr_404_logs';
	}

	/**
	 * Include required core files.
	 */
	private function includes() {
		// Core includes.
		require_once REDIRECTR_PLUGIN_DIR . 'includes/class-redirectr-install.php';
		require_once REDIRECTR_PLUGIN_DIR . 'includes/functions.php';
		require_once REDIRECTR_PLUGIN_DIR . 'includes/class-redirectr-handler.php';

		// Admin includes.
		if ( is_admin() ) {
			require_once REDIRECTR_PLUGIN_DIR . 'admin/class-redirectr-admin.php';
		}
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Check for DB updates.
		add_action( 'init', array( 'Redirectr_Install', 'maybe_update_tables' ) );

		// Initialize redirect handler (very early priority).
		add_action( 'template_redirect', array( 'Redirectr_Handler', 'maybe_redirect' ), 1 );

		// Log 404s (very late priority, after WordPress determines it's a 404).
		add_action( 'template_redirect', array( 'Redirectr_Handler', 'log_404' ), 99999 );

		// Cron: Daily cleanup of old logs.
		add_action( 'redirectr_daily_cleanup', 'redirectr_cleanup_old_logs' );

		// Initialize admin.
		if ( is_admin() ) {
			add_action( 'init', array( 'Redirectr_Admin', 'init' ) );
		}
	}

	/**
	 * Get plugin options.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_option( $key, $default = false ) {
		$options = get_option( 'redirectr_options', array() );

		if ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		}

		return $default;
	}

	/**
	 * Update plugin option.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 */
	public static function update_option( $key, $value ) {
		$options         = get_option( 'redirectr_options', array() );
		$options[ $key ] = $value;
		update_option( 'redirectr_options', $options );
	}
}
