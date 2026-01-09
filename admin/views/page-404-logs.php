<?php
/**
 * Admin page: 404 Logs.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

$redirectr_list_table = new Redirectr_Logs_List_Table();
$redirectr_list_table->prepare_items();
?>
<div class="wrap redirectr-wrap">
	<h1><?php esc_html_e( 'Broken Links', 'redirectr' ); ?></h1>
	<hr class="wp-header-end">

	<p class="description">
		<?php esc_html_e( 'Pages visitors tried to access that no longer exist. Click "Create Redirect" to send them to a working page instead.', 'redirectr' ); ?>
	</p>

	<form method="post" id="redirectr-logs-form">
		<?php
		$redirectr_list_table->views();
		$redirectr_list_table->search_box( __( 'Search', 'redirectr' ), 'brokenlink' );
		$redirectr_list_table->display();
		?>
		<input type="hidden" name="redirectr_bulk_action" value="1" />
	</form>
</div>

<!-- Convert 404 to Redirect Modal -->
<div id="redirectr-convert-modal" class="redirectr-modal" style="display: none;">
	<div class="redirectr-modal-content">
		<div class="redirectr-modal-header">
			<h2><?php esc_html_e( 'Create Redirect', 'redirectr' ); ?></h2>
			<button type="button" class="redirectr-modal-close" aria-label="<?php esc_attr_e( 'Close', 'redirectr' ); ?>">&times;</button>
		</div>
		<div class="redirectr-modal-body">
			<p>
				<strong><?php esc_html_e( 'Source URL:', 'redirectr' ); ?></strong>
				<code id="redirectr-convert-source"></code>
			</p>
			<p>
				<label for="redirectr-convert-destination">
					<strong><?php esc_html_e( 'Redirect to:', 'redirectr' ); ?></strong>
				</label>
				<input type="url"
					   id="redirectr-convert-destination"
					   class="regular-text"
					   placeholder="https://example.com/new-page"
					   style="width: 100%;" />
			</p>
			<input type="hidden" id="redirectr-convert-id" value="" />
		</div>
		<div class="redirectr-modal-footer">
			<button type="button" class="button button-primary redirectr-convert-submit">
				<?php esc_html_e( 'Create Redirect', 'redirectr' ); ?>
			</button>
			<button type="button" class="button redirectr-modal-close">
				<?php esc_html_e( 'Cancel', 'redirectr' ); ?>
			</button>
		</div>
	</div>
</div>
