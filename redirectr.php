<?php
/**
 * Plugin Name: Redirectr
 * Plugin URI: https://ghostguns.com
 * Description: Manage 301 redirects and monitor 404 errors with a clean admin interface.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Ghost Guns
 * Text Domain: redirectr
 * Domain Path: /languages
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants (with guards to prevent redefinition errors).
if ( ! defined( 'REDIRECTR_VERSION' ) ) {
	define( 'REDIRECTR_VERSION', '1.0.0' );
}
if ( ! defined( 'REDIRECTR_DB_VERSION' ) ) {
	define( 'REDIRECTR_DB_VERSION', '1.0.0' );
}
if ( ! defined( 'REDIRECTR_PLUGIN_FILE' ) ) {
	define( 'REDIRECTR_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'REDIRECTR_PLUGIN_DIR' ) ) {
	define( 'REDIRECTR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'REDIRECTR_PLUGIN_URL' ) ) {
	define( 'REDIRECTR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'REDIRECTR_PLUGIN_BASENAME' ) ) {
	define( 'REDIRECTR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// Include the main class.
require_once REDIRECTR_PLUGIN_DIR . 'includes/class-redirectr.php';

/**
 * Returns the main instance of Redirectr.
 *
 * @return Redirectr
 */
function redirectr() {
	return Redirectr::instance();
}

// Global for backwards compatibility.
$GLOBALS['redirectr'] = redirectr();

// Activation hook.
register_activation_hook( __FILE__, array( 'Redirectr_Install', 'install' ) );

// Deactivation hook.
register_deactivation_hook( __FILE__, array( 'Redirectr_Install', 'deactivate' ) );
