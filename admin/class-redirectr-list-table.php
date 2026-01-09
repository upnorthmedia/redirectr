<?php
/**
 * Redirects List Table.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Redirectr_List_Table Class.
 */
class Redirectr_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'redirect',
				'plural'   => 'redirects',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'              => '<input type="checkbox" />',
			'source_url'      => __( 'Source URL', 'redirectr' ),
			'destination_url' => __( 'Destination URL', 'redirectr' ),
			'redirect_type'   => __( 'Type', 'redirectr' ),
			'hit_count'       => __( 'Hits', 'redirectr' ),
			'status'          => __( 'Status', 'redirectr' ),
			'updated_at'      => __( 'Last Updated', 'redirectr' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'source_url' => array( 'source_url', false ),
			'hit_count'  => array( 'hit_count', true ),
			'updated_at' => array( 'updated_at', true ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete'     => __( 'Delete', 'redirectr' ),
			'activate'   => __( 'Activate', 'redirectr' ),
			'deactivate' => __( 'Deactivate', 'redirectr' ),
		);
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items() {
		global $wpdb;

		// Set up column headers.
		$this->_column_headers = array(
			$this->get_columns(),
			array(), // Hidden columns.
			$this->get_sortable_columns(),
		);

		$table_name   = $wpdb->prefix . 'redirectr_redirects';
		$per_page     = $this->get_items_per_page( 'redirectr_redirects_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Sorting.
		$orderby_options = array( 'source_url', 'hit_count', 'updated_at' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display operation, values validated.
		$orderby         = isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], $orderby_options, true )
			? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) )
			: 'updated_at';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display operation, values validated.
		$order           = isset( $_REQUEST['order'] ) && 'asc' === strtolower( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) )
			? 'ASC'
			: 'DESC';

		// Search.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display operation.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$where  = '';

		if ( $search ) {
			$where = $wpdb->prepare(
				' WHERE source_url LIKE %s OR destination_url LIKE %s',
				'%' . $wpdb->esc_like( $search ) . '%',
				'%' . $wpdb->esc_like( $search ) . '%'
			);
		}

		// Filter by status.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display operation, values validated.
		$status_filter = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';
		if ( $status_filter && in_array( $status_filter, array( 'active', 'inactive' ), true ) ) {
			$where .= $where ? ' AND ' : ' WHERE ';
			$where .= $wpdb->prepare( 'status = %s', $status_filter );
		}

		// Get items.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, pagination prevents effective caching.
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore
				$per_page,
				$offset
			)
		);

		// Get total count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, count for pagination.
		$total_items = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} {$where}" // phpcs:ignore
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Column: checkbox.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="redirect_ids[]" value="%d" />',
			$item->id
		);
	}

	/**
	 * Column: source_url.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_source_url( $item ) {
		$edit_url = add_query_arg(
			array(
				'page' => 'redirectr-edit',
				'id'   => $item->id,
			),
			admin_url( 'admin.php' )
		);

		// Build test URL using the source URL on this site.
		$test_url = home_url( $item->source_url );

		$actions = array(
			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				__( 'Edit', 'redirectr' )
			),
			'test'   => sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $test_url ),
				__( 'Test', 'redirectr' )
			),
			'delete' => sprintf(
				'<a href="#" class="redirectr-delete" data-id="%d">%s</a>',
				$item->id,
				__( 'Delete', 'redirectr' )
			),
			'toggle' => sprintf(
				'<a href="#" class="redirectr-toggle-status" data-id="%d">%s</a>',
				$item->id,
				'active' === $item->status ? __( 'Deactivate', 'redirectr' ) : __( 'Activate', 'redirectr' )
			),
		);

		$match_badge = '';
		if ( 'regex' === $item->match_type ) {
			$match_badge = ' <span class="redirectr-badge redirectr-badge-regex">regex</span>';
		}

		return sprintf(
			'<strong><a href="%s" title="%s">%s</a></strong>%s%s',
			esc_url( $edit_url ),
			esc_attr( $item->source_url ),
			esc_html( redirectr_format_url_for_display( $item->source_url, 40 ) ),
			$match_badge,
			$this->row_actions( $actions )
		);
	}

	/**
	 * Column: destination_url.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_destination_url( $item ) {
		return sprintf(
			'<a href="%s" target="_blank" title="%s">%s</a>',
			esc_url( $item->destination_url ),
			esc_attr( $item->destination_url ),
			esc_html( redirectr_format_url_for_display( $item->destination_url, 40 ) )
		);
	}

	/**
	 * Column: redirect_type.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_redirect_type( $item ) {
		return esc_html( $item->redirect_type );
	}

	/**
	 * Column: hit_count.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_hit_count( $item ) {
		$class = $item->hit_count > 100 ? ' high' : '';
		return sprintf(
			'<span class="redirectr-hit-count%s">%s</span>',
			$class,
			number_format_i18n( $item->hit_count )
		);
	}

	/**
	 * Column: status.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_status( $item ) {
		$class = 'active' === $item->status ? 'redirectr-status-active' : 'redirectr-status-inactive';
		$label = 'active' === $item->status ? __( 'Active', 'redirectr' ) : __( 'Inactive', 'redirectr' );

		return sprintf(
			'<span class="redirectr-status %s">%s</span>',
			$class,
			esc_html( $label )
		);
	}

	/**
	 * Column: updated_at.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_updated_at( $item ) {
		$timestamp = strtotime( $item->updated_at );
		return sprintf(
			'<abbr title="%s">%s</abbr>',
			esc_attr( gmdate( 'Y-m-d H:i:s', $timestamp ) ),
			esc_html( human_time_diff( $timestamp, time() ) . ' ' . __( 'ago', 'redirectr' ) )
		);
	}

	/**
	 * Default column.
	 *
	 * @param object $item        Item.
	 * @param string $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}

	/**
	 * Get views for filtering.
	 *
	 * @return array
	 */
	protected function get_views() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display operation.
		$current = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';

		$total    = redirectr_get_redirect_count();
		$active   = redirectr_get_redirect_count( 'active' );
		$inactive = redirectr_get_redirect_count( 'inactive' );

		$base_url = admin_url( 'admin.php?page=redirectr' );

		$views = array(
			'all' => sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
				esc_url( $base_url ),
				empty( $current ) ? 'current' : '',
				__( 'All', 'redirectr' ),
				number_format_i18n( $total )
			),
		);

		if ( $active > 0 ) {
			$views['active'] = sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
				esc_url( add_query_arg( 'status', 'active', $base_url ) ),
				'active' === $current ? 'current' : '',
				__( 'Active', 'redirectr' ),
				number_format_i18n( $active )
			);
		}

		if ( $inactive > 0 ) {
			$views['inactive'] = sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
				esc_url( add_query_arg( 'status', 'inactive', $base_url ) ),
				'inactive' === $current ? 'current' : '',
				__( 'Inactive', 'redirectr' ),
				number_format_i18n( $inactive )
			);
		}

		return $views;
	}

	/**
	 * Display when no items.
	 */
	public function no_items() {
		esc_html_e( 'No redirects found.', 'redirectr' );
	}
}
