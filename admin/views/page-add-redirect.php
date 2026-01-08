<?php
/**
 * Admin page: Add/Edit Redirect.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

// Get redirect if editing.
$redirect_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$redirect    = null;

if ( $redirect_id ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'redirectr_redirects';
	$redirect   = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE id = %d",
			$redirect_id
		)
	);
}

$page_title = $redirect ? __( 'Edit Redirect', 'redirectr' ) : __( 'Add Redirect', 'redirectr' );

// Default values.
$source_url      = $redirect ? $redirect->source_url : '';
$destination_url = $redirect ? $redirect->destination_url : '';
$match_type      = $redirect ? $redirect->match_type : 'exact';
$redirect_type   = $redirect ? $redirect->redirect_type : 301;
$status          = $redirect ? $redirect->status : 'active';
?>
<div class="wrap redirectr-wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>

	<?php settings_errors( 'redirectr' ); ?>

	<form method="post" action="" class="redirectr-form">
		<?php wp_nonce_field( 'redirectr_save_redirect', 'redirectr_redirect_nonce' ); ?>

		<?php if ( $redirect_id ) : ?>
			<input type="hidden" name="redirect_id" value="<?php echo esc_attr( $redirect_id ); ?>" />
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
							   value="<?php echo esc_attr( $source_url ); ?>"
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
							   value="<?php echo esc_attr( $destination_url ); ?>"
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
							<?php foreach ( redirectr_get_match_types() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $match_type, $value ); ?>>
									<?php echo esc_html( $label ); ?>
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
							<?php foreach ( redirectr_get_redirect_types() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $redirect_type, $value ); ?>>
									<?php echo esc_html( $label ); ?>
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
								   <?php checked( $status, 'active' ); ?> />
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
				   value="<?php echo esc_attr( $redirect ? __( 'Update Redirect', 'redirectr' ) : __( 'Add Redirect', 'redirectr' ) ); ?>" />
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=redirectr' ) ); ?>" class="button">
				<?php esc_html_e( 'Cancel', 'redirectr' ); ?>
			</a>
		</p>
	</form>
</div>
