<?php
/**
 * Admin functionality for Redirectr.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirectr_Admin Class.
 */
class Redirectr_Admin {

	/**
	 * Initialize admin functionality.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_form_submissions' ) );

		// Screen options.
		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );

		// AJAX handlers.
		add_action( 'wp_ajax_redirectr_delete_redirect', array( __CLASS__, 'ajax_delete_redirect' ) );
		add_action( 'wp_ajax_redirectr_toggle_status', array( __CLASS__, 'ajax_toggle_status' ) );
		add_action( 'wp_ajax_redirectr_delete_404', array( __CLASS__, 'ajax_delete_404' ) );
		add_action( 'wp_ajax_redirectr_ignore_404', array( __CLASS__, 'ajax_ignore_404' ) );
		add_action( 'wp_ajax_redirectr_convert_404', array( __CLASS__, 'ajax_convert_404' ) );
	}

	/**
	 * Add admin menu pages.
	 */
	public static function add_menu_pages() {
		// Main menu.
		$hook_main = add_menu_page(
			__( 'Redirectr', 'redirectr' ),
			__( 'Redirectr', 'redirectr' ),
			'manage_options',
			'redirectr',
			array( __CLASS__, 'page_redirects' ),
			'dashicons-randomize',
			80
		);

		// Submenu: All Redirects (same as main).
		add_submenu_page(
			'redirectr',
			__( 'All Redirects', 'redirectr' ),
			__( 'All Redirects', 'redirectr' ),
			'manage_options',
			'redirectr',
			array( __CLASS__, 'page_redirects' )
		);

		// Hidden page for editing (no menu item).
		add_submenu_page(
			null, // No parent = hidden from menu.
			__( 'Edit Redirect', 'redirectr' ),
			__( 'Edit Redirect', 'redirectr' ),
			'manage_options',
			'redirectr-edit',
			array( __CLASS__, 'page_add_redirect' )
		);

		// Submenu: Broken Links.
		$hook_404 = add_submenu_page(
			'redirectr',
			__( 'Broken Links', 'redirectr' ),
			__( 'Broken Links', 'redirectr' ),
			'manage_options',
			'redirectr-broken-links',
			array( __CLASS__, 'page_404_logs' )
		);

		// Submenu: Settings.
		add_submenu_page(
			'redirectr',
			__( 'Settings', 'redirectr' ),
			__( 'Settings', 'redirectr' ),
			'manage_options',
			'redirectr-settings',
			array( __CLASS__, 'page_settings' )
		);

		// Register screen options for list pages.
		add_action( 'load-' . $hook_main, array( __CLASS__, 'add_redirects_screen_options' ) );
		add_action( 'load-' . $hook_404, array( __CLASS__, 'add_404_screen_options' ) );
	}

	/**
	 * Add screen options for redirects list.
	 */
	public static function add_redirects_screen_options() {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Redirects per page', 'redirectr' ),
				'default' => 20,
				'option'  => 'redirectr_redirects_per_page',
			)
		);
	}

	/**
	 * Add screen options for 404 logs list.
	 */
	public static function add_404_screen_options() {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( '404 logs per page', 'redirectr' ),
				'default' => 20,
				'option'  => 'redirectr_logs_per_page',
			)
		);
	}

	/**
	 * Set screen option value.
	 *
	 * @param mixed  $status Screen option status.
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return mixed
	 */
	public static function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, array( 'redirectr_redirects_per_page', 'redirectr_logs_per_page' ), true ) ) {
			return absint( $value );
		}
		return $status;
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_scripts( $hook ) {
		// Only load on our pages.
		if ( strpos( $hook, 'redirectr' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'redirectr-admin',
			REDIRECTR_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			REDIRECTR_VERSION
		);

		wp_enqueue_script(
			'redirectr-admin',
			REDIRECTR_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			REDIRECTR_VERSION,
			true
		);

		wp_localize_script(
			'redirectr-admin',
			'redirectr_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'redirectr_admin_nonce' ),
				'strings'  => array(
					'confirm_delete'     => __( 'Are you sure you want to delete this item?', 'redirectr' ),
					'enter_destination'  => __( 'Please enter a destination URL.', 'redirectr' ),
					'redirected'         => __( 'Redirected', 'redirectr' ),
					'ignored'            => __( 'Ignored', 'redirectr' ),
					'active'             => __( 'Active', 'redirectr' ),
					'inactive'           => __( 'Inactive', 'redirectr' ),
				),
			)
		);
	}

	/**
	 * Handle form submissions.
	 *
	 * Note: Nonce verification occurs in each individual handler method,
	 * not in this dispatcher. This is intentional to keep nonce actions specific.
	 */
	public static function handle_form_submissions() {
		// Handle redirect save.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in save_redirect().
		if ( isset( $_POST['redirectr_save_redirect'] ) ) {
			self::save_redirect();
		}

		// Handle settings save.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in save_settings().
		if ( isset( $_POST['redirectr_save_settings'] ) ) {
			self::save_settings();
		}

		// Handle bulk actions.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_bulk_action().
		if ( isset( $_POST['redirectr_bulk_action'] ) ) {
			self::handle_bulk_action();
		}
	}

	/**
	 * Save a redirect.
	 */
	private static function save_redirect() {
		// Verify nonce.
		if ( ! isset( $_POST['redirectr_redirect_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['redirectr_redirect_nonce'] ) ), 'redirectr_save_redirect' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'redirectr' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'redirectr' ) );
		}

		global $wpdb;

		$data = array(
			'source_url'      => isset( $_POST['source_url'] ) ? sanitize_text_field( wp_unslash( $_POST['source_url'] ) ) : '',
			'destination_url' => isset( $_POST['destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['destination_url'] ) ) : '',
			'match_type'      => isset( $_POST['match_type'] ) ? sanitize_text_field( wp_unslash( $_POST['match_type'] ) ) : 'exact',
			'redirect_type'   => isset( $_POST['redirect_type'] ) ? absint( $_POST['redirect_type'] ) : 301,
			'status'          => isset( $_POST['redirect_status'] ) ? 'active' : 'inactive',
		);

		// Validate.
		$validated = redirectr_validate_redirect( $data );

		if ( is_wp_error( $validated ) ) {
			add_settings_error( 'redirectr', 'validation_error', $validated->get_error_message(), 'error' );
			return;
		}

		$redirect_id = isset( $_POST['redirect_id'] ) ? absint( $_POST['redirect_id'] ) : 0;

		if ( $redirect_id ) {
			// Update existing.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
			$wpdb->update(
				$wpdb->redirectr_redirects,
				$validated,
				array( 'id' => $redirect_id ),
				array( '%s', '%s', '%s', '%d', '%s' ),
				array( '%d' )
			);
			$message = __( 'Redirect updated successfully.', 'redirectr' );
		} else {
			// Insert new.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table, write operation.
			$wpdb->insert(
				$wpdb->redirectr_redirects,
				$validated,
				array( '%s', '%s', '%s', '%d', '%s' )
			);
			$redirect_id = $wpdb->insert_id;
			$message     = __( 'Redirect created successfully.', 'redirectr' );
		}

		// Clear redirect caches.
		redirectr_clear_redirect_cache();

		// Redirect to list page with success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'redirectr',
					'message' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Save settings.
	 */
	private static function save_settings() {
		// Verify nonce.
		if ( ! isset( $_POST['redirectr_settings_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['redirectr_settings_nonce'] ) ), 'redirectr_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'redirectr' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'redirectr' ) );
		}

		$options = array(
			'enable_404_logging'      => isset( $_POST['enable_404_logging'] ) ? 1 : 0,
			'log_retention_days'      => isset( $_POST['log_retention_days'] ) ? absint( $_POST['log_retention_days'] ) : 30,
			'exclude_patterns'        => isset( $_POST['exclude_patterns'] ) ? sanitize_textarea_field( wp_unslash( $_POST['exclude_patterns'] ) ) : '',
			'auto_delete_on_redirect' => isset( $_POST['auto_delete_on_redirect'] ) ? 1 : 0,
		);

		update_option( 'redirectr_options', $options );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'redirectr-settings',
					'message' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle bulk actions.
	 */
	private static function handle_bulk_action() {
		// Verify nonce.
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bulk-redirects' ) && ! wp_verify_nonce( $nonce, 'bulk-404logs' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'redirectr' ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'redirectr' ) );
		}

		global $wpdb;

		$action = isset( $_POST['action'] ) && '-1' !== $_POST['action']
			? sanitize_text_field( wp_unslash( $_POST['action'] ) )
			: ( isset( $_POST['action2'] ) ? sanitize_text_field( wp_unslash( $_POST['action2'] ) ) : '' );

		$ids = isset( $_POST['redirect_ids'] ) ? array_map( 'absint', $_POST['redirect_ids'] ) : array();
		if ( empty( $ids ) ) {
			$ids = isset( $_POST['log_ids'] ) ? array_map( 'absint', $_POST['log_ids'] ) : array();
		}

		if ( empty( $ids ) || empty( $action ) || '-1' === $action ) {
			return;
		}

		$redirects_table    = $wpdb->prefix . 'redirectr_redirects';
		$broken_links_table = $wpdb->prefix . 'redirectr_404_logs';

		$is_broken_links = strpos( $action, '404' ) !== false || isset( $_POST['log_ids'] );
		$table           = $is_broken_links ? $broken_links_table : $redirects_table;

		switch ( $action ) {
			case 'delete':
				// Get source URLs before deleting (to reset broken link statuses).
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, used immediately.
				$source_urls  = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT source_url FROM {$redirects_table} WHERE id IN ($placeholders)", // phpcs:ignore
						$ids
					)
				);

				// Delete the redirects.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$redirects_table} WHERE id IN ($placeholders)", // phpcs:ignore
						$ids
					)
				);

				// Reset broken links with these source URLs back to 'new'.
				if ( ! empty( $source_urls ) ) {
					$url_placeholders = implode( ',', array_fill( 0, count( $source_urls ), '%s' ) );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$broken_links_table} SET status = 'new' WHERE url IN ($url_placeholders) AND status = 'redirected'", // phpcs:ignore
							$source_urls
						)
					);
				}
				redirectr_clear_404_count_cache();
				redirectr_clear_redirect_cache();
				break;

			case 'delete_404':
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$broken_links_table} WHERE id IN ($placeholders)", // phpcs:ignore
						$ids
					)
				);
				redirectr_clear_404_count_cache();
				break;

			case 'activate':
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->redirectr_redirects} SET status = 'active' WHERE id IN ($placeholders)", // phpcs:ignore
						$ids
					)
				);
				redirectr_clear_redirect_cache();
				break;

			case 'deactivate':
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->redirectr_redirects} SET status = 'inactive' WHERE id IN ($placeholders)", // phpcs:ignore
						$ids
					)
				);
				redirectr_clear_redirect_cache();
				break;

			case 'ignore_404':
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->redirectr_404_logs} SET status = 'ignored' WHERE id IN ($placeholders)", // phpcs:ignore
						$ids
					)
				);
				redirectr_clear_404_count_cache();
				break;
		}
	}

	/**
	 * AJAX: Delete a redirect.
	 */
	public static function ajax_delete_redirect() {
		check_ajax_referer( 'redirectr_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'redirectr' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'redirectr' ) ) );
		}

		global $wpdb;
		$redirects_table   = $wpdb->prefix . 'redirectr_redirects';
		$broken_links_table = $wpdb->prefix . 'redirectr_404_logs';

		// Get the source URL before deleting (to reset broken link status).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, used immediately.
		$source_url = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT source_url FROM {$redirects_table} WHERE id = %d",
				$id
			)
		);

		// Delete the redirect.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
		$result = $wpdb->delete(
			$redirects_table,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( $result ) {
			// Reset any broken links with this source URL back to 'new'.
			if ( $source_url ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
				$wpdb->update(
					$broken_links_table,
					array( 'status' => 'new' ),
					array(
						'url'    => $source_url,
						'status' => 'redirected',
					),
					array( '%s' ),
					array( '%s', '%s' )
				);
				redirectr_clear_404_count_cache();
			}

			// Clear redirect caches.
			redirectr_clear_redirect_cache();

			wp_send_json_success( array( 'message' => __( 'Redirect deleted.', 'redirectr' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete redirect.', 'redirectr' ) ) );
		}
	}

	/**
	 * AJAX: Toggle redirect status.
	 */
	public static function ajax_toggle_status() {
		check_ajax_referer( 'redirectr_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'redirectr' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'redirectr' ) ) );
		}

		global $wpdb;

		// Get current status.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, used immediately.
		$current = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT status FROM {$wpdb->redirectr_redirects} WHERE id = %d",
				$id
			)
		);

		$new_status = 'active' === $current ? 'inactive' : 'active';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
		$wpdb->update(
			$wpdb->redirectr_redirects,
			array( 'status' => $new_status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		// Clear redirect caches.
		redirectr_clear_redirect_cache();

		wp_send_json_success(
			array(
				'message'    => __( 'Status updated.', 'redirectr' ),
				'new_status' => $new_status,
			)
		);
	}

	/**
	 * AJAX: Delete a 404 log entry.
	 */
	public static function ajax_delete_404() {
		check_ajax_referer( 'redirectr_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'redirectr' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'redirectr' ) ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
		$result = $wpdb->delete(
			$wpdb->redirectr_404_logs,
			array( 'id' => $id ),
			array( '%d' )
		);

		redirectr_clear_404_count_cache();

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( '404 log deleted.', 'redirectr' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete 404 log.', 'redirectr' ) ) );
		}
	}

	/**
	 * AJAX: Ignore a 404 log entry.
	 */
	public static function ajax_ignore_404() {
		check_ajax_referer( 'redirectr_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'redirectr' ) ) );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ID.', 'redirectr' ) ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
		$wpdb->update(
			$wpdb->redirectr_404_logs,
			array( 'status' => 'ignored' ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		redirectr_clear_404_count_cache();

		wp_send_json_success(
			array(
				'message'    => __( '404 marked as ignored.', 'redirectr' ),
				'new_status' => 'ignored',
			)
		);
	}

	/**
	 * AJAX: Convert a 404 to a redirect.
	 */
	public static function ajax_convert_404() {
		check_ajax_referer( 'redirectr_admin_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'redirectr' ) ) );
		}

		$id          = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$destination = isset( $_POST['destination'] ) ? esc_url_raw( wp_unslash( $_POST['destination'] ) ) : '';

		if ( ! $id || ! $destination ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'redirectr' ) ) );
		}

		global $wpdb;

		// Get the 404 log entry.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, used immediately.
		$log = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->redirectr_404_logs} WHERE id = %d",
				$id
			)
		);

		if ( ! $log ) {
			wp_send_json_error( array( 'message' => __( '404 log not found.', 'redirectr' ) ) );
		}

		// Create the redirect.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table, write operation.
		$inserted = $wpdb->insert(
			$wpdb->redirectr_redirects,
			array(
				'source_url'      => $log->url,
				'destination_url' => $destination,
				'match_type'      => 'exact',
				'redirect_type'   => 301,
				'status'          => 'active',
			),
			array( '%s', '%s', '%s', '%d', '%s' )
		);

		if ( $inserted ) {
			// Update 404 log status.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
			$wpdb->update(
				$wpdb->redirectr_404_logs,
				array( 'status' => 'redirected' ),
				array( 'id' => $id ),
				array( '%s' ),
				array( '%d' )
			);

			// Optionally delete the 404 log.
			if ( Redirectr::get_option( 'auto_delete_on_redirect', 0 ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, write operation.
				$wpdb->delete(
					$wpdb->redirectr_404_logs,
					array( 'id' => $id ),
					array( '%d' )
				);
			}

			redirectr_clear_404_count_cache();
			redirectr_clear_redirect_cache();

			wp_send_json_success(
				array(
					'message'     => __( 'Redirect created successfully.', 'redirectr' ),
					'redirect_id' => $wpdb->insert_id,
					'new_status'  => 'redirected',
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to create redirect.', 'redirectr' ) ) );
		}
	}

	/**
	 * Page: Redirects list.
	 */
	public static function page_redirects() {
		require_once REDIRECTR_PLUGIN_DIR . 'admin/class-redirectr-list-table.php';
		include REDIRECTR_PLUGIN_DIR . 'admin/views/page-redirects.php';
	}

	/**
	 * Page: Add/edit redirect.
	 */
	public static function page_add_redirect() {
		include REDIRECTR_PLUGIN_DIR . 'admin/views/page-add-redirect.php';
	}

	/**
	 * Page: 404 logs.
	 */
	public static function page_404_logs() {
		require_once REDIRECTR_PLUGIN_DIR . 'admin/class-redirectr-logs-list-table.php';
		include REDIRECTR_PLUGIN_DIR . 'admin/views/page-404-logs.php';
	}

	/**
	 * Page: Settings.
	 */
	public static function page_settings() {
		include REDIRECTR_PLUGIN_DIR . 'admin/views/page-settings.php';
	}
}
