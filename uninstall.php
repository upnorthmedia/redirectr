<?php
/**
 * Uninstall Redirectr
 *
 * Removes all plugin data when the plugin is deleted.
 *
 * @package Redirectr
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Clear scheduled events.
wp_clear_scheduled_hook( 'redirectr_daily_cleanup' );

// Only delete data if the constant is defined (safety measure).
// To completely remove all data, add this to wp-config.php:
// define( 'REDIRECTR_REMOVE_ALL_DATA', true );
if ( defined( 'REDIRECTR_REMOVE_ALL_DATA' ) && REDIRECTR_REMOVE_ALL_DATA ) {

	// Drop custom tables.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}redirectr_redirects" ); // phpcs:ignore
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}redirectr_404_logs" ); // phpcs:ignore

	// Delete options.
	delete_option( 'redirectr_options' );
	delete_option( 'redirectr_version' );
	delete_option( 'redirectr_db_version' );

	// Clear any cached data.
	wp_cache_delete( 'redirectr_new_404_count' );
}
