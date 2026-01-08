<?php
/**
 * 404 Logs List Table.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Redirectr_Logs_List_Table Class.
 */
class Redirectr_Logs_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => '404log',
				'plural'   => '404logs',
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
			'cb'         => '<input type="checkbox" />',
			'url'        => __( 'URL', 'redirectr' ),
			'referrer'   => __( 'Referrer', 'redirectr' ),
			'hit_count'  => __( 'Hits', 'redirectr' ),
			'first_seen' => __( 'First Seen', 'redirectr' ),
			'last_seen'  => __( 'Last Seen', 'redirectr' ),
			'status'     => __( 'Status', 'redirectr' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'url'        => array( 'url', false ),
			'hit_count'  => array( 'hit_count', true ),
			'first_seen' => array( 'first_seen', false ),
			'last_seen'  => array( 'last_seen', true ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete_404' => __( 'Delete', 'redirectr' ),
			'ignore_404' => __( 'Mark as Ignored', 'redirectr' ),
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

		$table_name   = $wpdb->prefix . 'redirectr_404_logs';
		$per_page     = $this->get_items_per_page( 'redirectr_logs_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Sorting - default to hit_count DESC (most hits first).
		$orderby_options = array( 'url', 'hit_count', 'first_seen', 'last_seen' );
		$orderby         = isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], $orderby_options, true )
			? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) )
			: 'hit_count';
		$order           = isset( $_REQUEST['order'] ) && 'asc' === strtolower( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) )
			? 'ASC'
			: 'DESC';

		// Search.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$where  = '';

		if ( $search ) {
			$where = $wpdb->prepare(
				' WHERE url LIKE %s OR referrer LIKE %s',
				'%' . $wpdb->esc_like( $search ) . '%',
				'%' . $wpdb->esc_like( $search ) . '%'
			);
		}

		// Filter by status.
		$status_filter = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';
		if ( $status_filter && in_array( $status_filter, array( 'new', 'ignored', 'redirected' ), true ) ) {
			$where .= $where ? ' AND ' : ' WHERE ';
			$where .= $wpdb->prepare( 'status = %s', $status_filter );
		}

		// Get items.
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore
				$per_page,
				$offset
			)
		);

		// Get total count.
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
			'<input type="checkbox" name="log_ids[]" value="%d" />',
			$item->id
		);
	}

	/**
	 * Column: url.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_url( $item ) {
		$actions = array(
			'convert' => sprintf(
				'<a href="#" class="redirectr-convert-404" data-id="%d" data-url="%s">%s</a>',
				$item->id,
				esc_attr( $item->url ),
				__( 'Create Redirect', 'redirectr' )
			),
			'ignore'  => sprintf(
				'<a href="#" class="redirectr-ignore-404" data-id="%d">%s</a>',
				$item->id,
				__( 'Ignore', 'redirectr' )
			),
			'delete'  => sprintf(
				'<a href="#" class="redirectr-delete-404" data-id="%d">%s</a>',
				$item->id,
				__( 'Delete', 'redirectr' )
			),
		);

		// Don't show convert/ignore if already handled.
		if ( 'redirected' === $item->status ) {
			unset( $actions['convert'], $actions['ignore'] );
		} elseif ( 'ignored' === $item->status ) {
			unset( $actions['ignore'] );
		}

		// Build test link icon for redirected items.
		$test_link = '';
		if ( 'redirected' === $item->status ) {
			$test_url  = home_url( $item->url );
			$test_link = sprintf(
				'<a href="%s" target="_blank" class="redirectr-test-link" title="%s"><span class="dashicons dashicons-external"></span></a>',
				esc_url( $test_url ),
				esc_attr__( 'Test redirect', 'redirectr' )
			);
		}

		return sprintf(
			'<strong title="%s">%s</strong>%s%s',
			esc_attr( $item->url ),
			esc_html( redirectr_format_url_for_display( $item->url, 50 ) ),
			$test_link,
			$this->row_actions( $actions )
		);
	}

	/**
	 * Column: referrer.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_referrer( $item ) {
		if ( empty( $item->referrer ) ) {
			return '<span class="redirectr-muted">' . esc_html__( 'Direct', 'redirectr' ) . '</span>';
		}

		return sprintf(
			'<a href="%s" target="_blank" title="%s">%s</a>',
			esc_url( $item->referrer ),
			esc_attr( $item->referrer ),
			esc_html( redirectr_format_url_for_display( $item->referrer, 30 ) )
		);
	}

	/**
	 * Column: hit_count.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_hit_count( $item ) {
		$class = '';
		if ( $item->hit_count > 100 ) {
			$class = ' high';
		} elseif ( $item->hit_count > 10 ) {
			$class = ' medium';
		}

		return sprintf(
			'<span class="redirectr-hit-count%s">%s</span>',
			$class,
			number_format_i18n( $item->hit_count )
		);
	}

	/**
	 * Column: first_seen.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_first_seen( $item ) {
		$timestamp = strtotime( $item->first_seen );
		return sprintf(
			'<abbr title="%s">%s</abbr>',
			esc_attr( gmdate( 'Y-m-d H:i:s', $timestamp ) ),
			esc_html( gmdate( 'M j, Y', $timestamp ) )
		);
	}

	/**
	 * Column: last_seen.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_last_seen( $item ) {
		$timestamp = strtotime( $item->last_seen );
		return sprintf(
			'<abbr title="%s">%s</abbr>',
			esc_attr( gmdate( 'Y-m-d H:i:s', $timestamp ) ),
			esc_html( human_time_diff( $timestamp, time() ) . ' ' . __( 'ago', 'redirectr' ) )
		);
	}

	/**
	 * Column: status.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_status( $item ) {
		$classes = array(
			'new'        => 'redirectr-404-status-new',
			'ignored'    => 'redirectr-404-status-ignored',
			'redirected' => 'redirectr-404-status-redirected',
		);

		$labels = array(
			'new'        => __( 'New', 'redirectr' ),
			'ignored'    => __( 'Ignored', 'redirectr' ),
			'redirected' => __( 'Redirected', 'redirectr' ),
		);

		$class = isset( $classes[ $item->status ] ) ? $classes[ $item->status ] : '';
		$label = isset( $labels[ $item->status ] ) ? $labels[ $item->status ] : $item->status;

		return sprintf(
			'<span class="redirectr-status %s">%s</span>',
			$class,
			esc_html( $label )
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
		$current = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';

		$total      = redirectr_get_404_log_count();
		$new        = redirectr_get_404_log_count( 'new' );
		$ignored    = redirectr_get_404_log_count( 'ignored' );
		$redirected = redirectr_get_404_log_count( 'redirected' );

		$base_url = admin_url( 'admin.php?page=redirectr-broken-links' );

		$views = array(
			'all' => sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
				esc_url( $base_url ),
				empty( $current ) ? 'current' : '',
				__( 'All', 'redirectr' ),
				number_format_i18n( $total )
			),
		);

		if ( $new > 0 ) {
			$views['new'] = sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
				esc_url( add_query_arg( 'status', 'new', $base_url ) ),
				'new' === $current ? 'current' : '',
				__( 'New', 'redirectr' ),
				number_format_i18n( $new )
			);
		}

		if ( $ignored > 0 ) {
			$views['ignored'] = sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
				esc_url( add_query_arg( 'status', 'ignored', $base_url ) ),
				'ignored' === $current ? 'current' : '',
				__( 'Ignored', 'redirectr' ),
				number_format_i18n( $ignored )
			);
		}

		if ( $redirected > 0 ) {
			$views['redirected'] = sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
				esc_url( add_query_arg( 'status', 'redirected', $base_url ) ),
				'redirected' === $current ? 'current' : '',
				__( 'Redirected', 'redirectr' ),
				number_format_i18n( $redirected )
			);
		}

		return $views;
	}

	/**
	 * Display when no items.
	 */
	public function no_items() {
		esc_html_e( 'No broken links found. Great job!', 'redirectr' );
	}
}
