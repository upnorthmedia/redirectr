<?php
/**
 * Admin page: Add/Edit Redirect.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

// Get redirect if editing.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display operation, ID is sanitized with absint.
$redirectr_redirect_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$redirectr_redirect    = null;

if ( $redirectr_redirect_id ) {
	global $wpdb;
	$redirectr_table_name = $wpdb->prefix . 'redirectr_redirects';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, used immediately for form display.
	$redirectr_redirect   = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$redirectr_table_name} WHERE id = %d",
			$redirectr_redirect_id
		)
	);
}

$redirectr_page_title = $redirectr_redirect ? __( 'Edit Redirect', 'redirectr' ) : __( 'Add Redirect', 'redirectr' );

// Default values.
$redirectr_source_url      = $redirectr_redirect ? $redirectr_redirect->source_url : '';
$redirectr_destination_url = $redirectr_redirect ? $redirectr_redirect->destination_url : '';
$redirectr_match_type      = $redirectr_redirect ? $redirectr_redirect->match_type : 'exact';
$redirectr_redirect_type   = $redirectr_redirect ? $redirectr_redirect->redirect_type : 301;
$redirectr_status = $redirectr_redirect ? $redirectr_redirect->status : 'active';
?>
<div class="wrap redirectr-wrap">
	<h1><?php echo esc_html( $redirectr_page_title ); ?></h1>

	<?php settings_errors( 'redirectr' ); ?>

	<form method="post" action="" class="redirectr-form">
		<?php wp_nonce_field( 'redirectr_save_redirect', 'redirectr_redirect_nonce' ); ?>

		<?php if ( $redirectr_redirect_id ) : ?>
			<input type="hidden" name="redirect_id" value="<?php echo esc_attr( $redirectr_redirect_id ); ?>" />
		<?php endif; ?>

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
							   value="<?php echo esc_attr( $redirectr_source_url ); ?>"
							   class="regular-text code"
							   placeholder="/old-page"
							   required />
						<p class="description">
							<?php esc_html_e( 'The URL path to redirect from (e.g., /old-page). For regex, use a valid pattern.', 'redirectr' ); ?>
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
							   value="<?php echo esc_attr( $redirectr_destination_url ); ?>"
							   class="regular-text code"
							   placeholder="https://example.com/new-page"
							   required />
						<p class="description">
							<?php esc_html_e( 'The full URL to redirect to. Can be internal or external.', 'redirectr' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="match_type"><?php esc_html_e( 'Match Type', 'redirectr' ); ?></label>
					</th>
					<td>
						<select name="match_type" id="match_type">
							<?php foreach ( redirectr_get_match_types() as $redirectr_value => $redirectr_label ) : ?>
								<option value="<?php echo esc_attr( $redirectr_value ); ?>" <?php selected( $redirectr_match_type, $redirectr_value ); ?>>
									<?php echo esc_html( $redirectr_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Exact match for simple URLs. Use regex for patterns like /product/(.*).', 'redirectr' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="redirect_type"><?php esc_html_e( 'Redirect Type', 'redirectr' ); ?></label>
					</th>
					<td>
						<select name="redirect_type" id="redirect_type">
							<?php foreach ( redirectr_get_redirect_types() as $redirectr_value => $redirectr_label ) : ?>
								<option value="<?php echo esc_attr( $redirectr_value ); ?>" <?php selected( $redirectr_redirect_type, $redirectr_value ); ?>>
									<?php echo esc_html( $redirectr_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( '301 is recommended for permanent redirects (best for SEO).', 'redirectr' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'redirectr' ); ?></th>
					<td>
						<label for="redirect_status">
							<input type="checkbox"
								   name="redirect_status"
								   id="redirect_status"
								   value="1"
								   <?php checked( $redirectr_status, 'active' ); ?> />
							<?php esc_html_e( 'Active', 'redirectr' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Uncheck to disable this redirect without deleting it.', 'redirectr' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit"
				   name="redirectr_save_redirect"
				   class="button button-primary"
				   value="<?php echo esc_attr( $redirectr_redirect ? __( 'Update Redirect', 'redirectr' ) : __( 'Add Redirect', 'redirectr' ) ); ?>" />
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=redirectr' ) ); ?>" class="button">
				<?php esc_html_e( 'Cancel', 'redirectr' ); ?>
			</a>
		</p>
	</form>
</div>
