<?php
/**
 * Admin page: Redirects list.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

$list_table = new Redirectr_List_Table();
$list_table->prepare_items();
$has_items = $list_table->has_items();
?>
<div class="wrap redirectr-wrap">
	<h1><?php esc_html_e( 'Redirects', 'redirectr' ); ?></h1>
	<hr class="wp-header-end">

	<?php
	// Display success message.
	if ( isset( $_GET['message'] ) && 'saved' === $_GET['message'] ) {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Redirect saved successfully.', 'redirectr' ); ?></p>
		</div>
		<?php
	}
	?>

	<!-- Add New Redirect Form (hidden by default) -->
	<div id="redirectr-add-form-wrapper" class="redirectr-add-form-wrapper" style="display: none;">
		<div class="redirectr-settings-card">
			<h2><?php esc_html_e( 'Add New Redirect', 'redirectr' ); ?></h2>
			<form method="post" action="" class="redirectr-inline-form">
				<?php wp_nonce_field( 'redirectr_save_redirect', 'redirectr_redirect_nonce' ); ?>

				<table class="form-table redirectr-form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="source_url"><?php esc_html_e( 'Source URL', 'redirectr' ); ?></label>
							</th>
							<td>
								<input type="text"
									   name="source_url"
									   id="source_url"
									   value=""
									   class="regular-text code"
									   placeholder="/old-page"
									   required />
								<p class="description">
									<?php esc_html_e( 'The URL path to redirect from (e.g., /old-page)', 'redirectr' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="destination_url"><?php esc_html_e( 'Destination URL', 'redirectr' ); ?></label>
							</th>
							<td>
								<input type="url"
									   name="destination_url"
									   id="destination_url"
									   value=""
									   class="regular-text code"
									   placeholder="https://example.com/new-page"
									   required />
								<p class="description">
									<?php esc_html_e( 'The full URL to redirect to', 'redirectr' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="match_type"><?php esc_html_e( 'Match Type', 'redirectr' ); ?></label>
							</th>
							<td>
								<select name="match_type" id="match_type">
									<?php foreach ( redirectr_get_match_types() as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>">
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<select name="redirect_type" id="redirect_type" style="margin-left: 10px;">
									<?php foreach ( redirectr_get_redirect_types() as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, 301 ); ?>>
											<?php echo esc_html( $label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<label style="margin-left: 15px;">
									<input type="checkbox" name="redirect_status" value="1" checked />
									<?php esc_html_e( 'Active', 'redirectr' ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit" style="margin-top: 0; padding-top: 0;">
					<input type="submit"
						   name="redirectr_save_redirect"
						   class="button button-primary"
						   value="<?php esc_attr_e( 'Add Redirect', 'redirectr' ); ?>" />
					<button type="button" class="button" id="redirectr-cancel-add">
						<?php esc_html_e( 'Cancel', 'redirectr' ); ?>
					</button>
				</p>
			</form>
		</div>
	</div>

	<form method="post" id="redirectr-redirects-form">
		<?php $list_table->views(); ?>

		<div class="tablenav top redirectr-tablenav-top">
			<div class="alignleft actions bulkactions">
				<?php if ( $has_items ) : ?>
					<?php $list_table->bulk_actions( 'top' ); ?>
				<?php endif; ?>
			</div>
			<button type="button" class="button button-primary redirectr-add-new-btn" id="redirectr-toggle-add-form">
				<?php esc_html_e( '+ Add New Redirect', 'redirectr' ); ?>
			</button>
			<?php if ( $has_items ) : ?>
				<?php $list_table->search_box( __( 'Search', 'redirectr' ), 'redirect' ); ?>
			<?php endif; ?>
			<br class="clear" />
		</div>

		<?php if ( $has_items ) : ?>
			<table class="wp-list-table widefat fixed striped table-view-list redirects">
				<thead>
					<tr>
						<?php $list_table->print_column_headers(); ?>
					</tr>
				</thead>
				<tbody id="the-list">
					<?php $list_table->display_rows(); ?>
				</tbody>
				<tfoot>
					<tr>
						<?php $list_table->print_column_headers( false ); ?>
					</tr>
				</tfoot>
			</table>

			<div class="tablenav bottom">
				<div class="alignleft actions bulkactions">
					<?php $list_table->bulk_actions( 'bottom' ); ?>
				</div>
				<?php $list_table->pagination( 'bottom' ); ?>
				<br class="clear" />
			</div>
		<?php else : ?>
			<div class="redirectr-empty-state">
				<p><?php esc_html_e( 'No redirects yet. Click "+ Add New Redirect" to create your first redirect.', 'redirectr' ); ?></p>
			</div>
		<?php endif; ?>

		<input type="hidden" name="redirectr_bulk_action" value="1" />
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	$('#redirectr-toggle-add-form').on('click', function() {
		$('#redirectr-add-form-wrapper').slideToggle(200);
		$('#source_url').focus();
	});

	$('#redirectr-cancel-add').on('click', function() {
		$('#redirectr-add-form-wrapper').slideUp(200);
	});
});
</script>
