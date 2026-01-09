<?php
/**
 * Admin page: Settings.
 *
 * @package Redirectr
 */

defined( 'ABSPATH' ) || exit;

// Get current settings.
$redirectr_enable_404_logging      = Redirectr::get_option( 'enable_404_logging', 1 );
$redirectr_log_retention_days      = Redirectr::get_option( 'log_retention_days', 30 );
$redirectr_exclude_patterns        = Redirectr::get_option( 'exclude_patterns', '' );
$redirectr_auto_delete_on_redirect = Redirectr::get_option( 'auto_delete_on_redirect', 0 );
?>
<div class="wrap redirectr-wrap">
	<h1><?php esc_html_e( 'Redirectr Settings', 'redirectr' ); ?></h1>

	<?php
	// Display success message.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display operation.
	if ( isset( $_GET['message'] ) && 'saved' === $_GET['message'] ) {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully.', 'redirectr' ); ?></p>
		</div>
		<?php
	}
	?>

	<form method="post" action="">
		<?php wp_nonce_field( 'redirectr_save_settings', 'redirectr_settings_nonce' ); ?>

		<div class="redirectr-settings-card">
			<h2><?php esc_html_e( 'Broken Link Tracking', 'redirectr' ); ?></h2>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Tracking', 'redirectr' ); ?></th>
						<td>
							<label for="enable_404_logging">
								<input type="checkbox"
									   name="enable_404_logging"
									   id="enable_404_logging"
									   value="1"
									   <?php checked( $redirectr_enable_404_logging, 1 ); ?> />
								<?php esc_html_e( 'Track broken links visitors try to access', 'redirectr' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, broken links will be logged so you can create redirects for them.', 'redirectr' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="log_retention_days"><?php esc_html_e( 'Log Retention', 'redirectr' ); ?></label>
						</th>
						<td>
							<input type="number"
								   name="log_retention_days"
								   id="log_retention_days"
								   value="<?php echo esc_attr( $redirectr_log_retention_days ); ?>"
								   min="1"
								   max="365"
								   class="small-text" />
							<?php esc_html_e( 'days', 'redirectr' ); ?>
							<p class="description">
								<?php esc_html_e( 'Ignored broken links older than this will be automatically deleted. Set to 0 to keep forever.', 'redirectr' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-delete on Redirect', 'redirectr' ); ?></th>
						<td>
							<label for="auto_delete_on_redirect">
								<input type="checkbox"
									   name="auto_delete_on_redirect"
									   id="auto_delete_on_redirect"
									   value="1"
									   <?php checked( $redirectr_auto_delete_on_redirect, 1 ); ?> />
								<?php esc_html_e( 'Remove broken link entry when a redirect is created for it', 'redirectr' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When you create a redirect from a broken link, automatically remove it from the list.', 'redirectr' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="redirectr-settings-card">
			<h2><?php esc_html_e( 'Default Exclusions', 'redirectr' ); ?></h2>
			<p class="description" style="margin-bottom: 15px;">
				<?php esc_html_e( 'The following paths and file types are automatically excluded from broken link tracking:', 'redirectr' ); ?>
			</p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Type', 'redirectr' ); ?></th>
						<th><?php esc_html_e( 'Excluded', 'redirectr' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'WordPress Core', 'redirectr' ); ?></strong></td>
						<td><code>/wp-content/*</code>, <code>/wp-admin/*</code>, <code>/wp-includes/*</code>, <code>/wp-json/*</code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'File Extensions', 'redirectr' ); ?></strong></td>
						<td><code>.map</code>, <code>.php</code>, <code>.xml</code>, <code>.txt</code>, <code>.ico</code>, <code>.css</code>, <code>.js</code></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="redirectr-settings-card">
			<h2><?php esc_html_e( 'Additional Exclusions', 'redirectr' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="exclude_patterns"><?php esc_html_e( 'Custom Patterns', 'redirectr' ); ?></label>
						</th>
						<td>
							<textarea name="exclude_patterns"
									  id="exclude_patterns"
									  rows="4"
									  class="large-text code"
									  placeholder="/custom-path/*&#10;/another-path/*"><?php echo esc_textarea( $redirectr_exclude_patterns ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'One pattern per line. Use * as wildcard. These are in addition to the default exclusions above.', 'redirectr' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="redirectr-settings-card">
			<h2><?php esc_html_e( 'Statistics', 'redirectr' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Total Redirects', 'redirectr' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( redirectr_get_redirect_count() ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Active Redirects', 'redirectr' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( redirectr_get_redirect_count( 'active' ) ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Total Broken Links', 'redirectr' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( redirectr_get_404_log_count() ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Unhandled Broken Links', 'redirectr' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( redirectr_get_new_404_count() ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<p class="submit">
			<input type="submit"
				   name="redirectr_save_settings"
				   class="button button-primary"
				   value="<?php esc_attr_e( 'Save Settings', 'redirectr' ); ?>" />
		</p>
	</form>
</div>
