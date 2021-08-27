<?php
/**
 * Asset optimization settings meta box.
 *
 * @package Hummingbird
 *
 * @var bool   $cdn_status    CDN status.
 * @var array  $cdn_excludes  A list of assets excluded from CDN.
 * @var string $download_url  Download logs URL.
 * @var string $file_path     Path to store files.
 * @var bool   $is_member     Member status.
 * @var bool   $logging       Logging status.
 * @var string $logs_link     Link to log file.
 * @var string $path_url      URL to the log file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_site_cdn_enabled = $cdn_status && $is_member;
?>

<?php if ( ! is_multisite() ) : ?>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'File Location', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php esc_html_e( 'Choose where Hummingbird should store your modified assets.', 'wphb' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<?php
			if ( $cdn_status ) {
				$this->admin_notices->show_inline(
					esc_html__( 'This feature is inactive when you’re using the WPMU DEV CDN.', 'wphb' ),
					'warning'
				);
			}
			?>
			<label for="file_path">
				<input type="text" class="sui-form-control" name="file_path" id="file_path" placeholder="/wp-content/uploads/hummingbird-assets/" value="<?php echo esc_attr( $file_path ); ?>" <?php disabled( $cdn_status ); ?>>
			</label>
			<span class="sui-description">
				<?php esc_html_e( 'Leave this blank to use the default folder, or define your own as a relative path. Note: changing the directory will clear out all the generated assets.', 'wphb' ); ?>
			</span>
		</div>
	</div>
<?php endif; ?>

<div class="sui-box-settings-row <?php echo ( ! $is_member ) ? ' sui-disabled' : ''; ?>">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Super-compress my files', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Compress your files up to 2x more with our enhanced optimization engine.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<?php if ( $is_member ) : ?>
			<span class="sui-tag sui-tag-disabled"><?php esc_html_e( 'Auto-enabled', 'wphb' ); ?></span>
		<?php else : ?>
			<div class="sui-form-field">
				<label for="super_minify_files" class="sui-toggle">
					<input type="checkbox" name="super_minify_files" id="super_minify_files" aria-labelledby="super_minify_files-label" disabled>
					<span class="sui-toggle-slider" aria-hidden="true"></span>
					<span id="super_minify_files-label" class="sui-toggle-label">
						<?php esc_html_e( 'Enable super-compression', 'wphb' ); ?>
					</span>
				</label>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php if ( ! is_multisite() || $is_site_cdn_enabled ) : ?>
	<div class="sui-box-settings-row <?php echo ( ! $is_member ) ? ' sui-disabled' : ''; ?>">
		<div class="sui-box-settings-col-1">
			<?php if ( is_multisite() ) : ?>
				<span class="sui-settings-label"><?php esc_html_e( 'Exclude files from using CDN ', 'wphb' ); ?></span>
			<?php else : ?>
				<span class="sui-settings-label"><?php esc_html_e( 'Enable WPMU DEV CDN', 'wphb' ); ?></span>
				<span class="sui-description">
					<?php esc_html_e( 'Host your files on WPMU DEV’s secure and hyper fast CDN.', 'wphb' ); ?>
				</span>
			<?php endif; ?>
		</div>
		<div class="sui-box-settings-col-2">
			<?php if ( is_multisite() ) : ?>
				<?php if ( $is_site_cdn_enabled ) : ?>
					<span class="sui-description">
						<?php esc_html_e( 'Hummingbird will serve your CSS, JS and other compatible files from our external CDN, effectively taking the load off your server so that pages load faster for your visitors.', 'wphb' ); ?>
					</span>
				<?php endif; ?>
			<?php else : ?>
				<div class="sui-form-field">
					<label for="use_cdn" class="sui-toggle">
						<input type="checkbox" name="use_cdn" id="use_cdn" aria-labelledby="use_cdn-label" <?php checked( $is_site_cdn_enabled ); ?> <?php disabled( ! $is_member ); ?>>
						<span class="sui-toggle-slider" aria-hidden="true"></span>
						<span id="use_cdn-label" class="sui-toggle-label">
							<?php esc_html_e( 'Host my files on the WPMU DEV CDN', 'wphb' ); ?>
						</span>
					</label>

					<span class="sui-description sui-toggle-description">
						<?php esc_html_e( 'Enabling this setting will serve your CSS, JS and other compatible files from our external CDN, effectively taking the load off your server so that pages load faster for your visitors.', 'wphb' ); ?>
					</span>
				</div>
			<?php endif; ?>

			<?php
			$cdn_exclude_classes = array( 'sui-description' );
			if ( ! is_multisite() ) {
				$cdn_exclude_classes[] = 'sui-toggle-description';
			}
			if ( ! $is_site_cdn_enabled ) {
				$cdn_exclude_classes[] = 'sui-hidden';
			}
			?>
			<span class="<?php echo implode( ' ', $cdn_exclude_classes ); ?>" style="margin-top: 10px" id="cdn_file_exclude">
				<?php esc_html_e( 'Note that some externally hosted files can cause issues when trying to serve them from the CDN. You can exclude these files by listing them below.', 'wphb' ); ?>

				<label class="sui-label" for="cdn_exclude" style="margin-top: 15px">
					<?php esc_html_e( 'Exclude files from using CDN ', 'wphb' ); ?>
				</label>

				<select class="sui-select sui-select-lg" multiple="multiple" name="excluded_items[]" id="cdn_exclude" data-placeholder="<?php esc_attr_e( 'Start typing handle name...', 'wphb' ); ?>">
					<?php
					$collection = Hummingbird\Core\Modules\Minify\Sources_Collector::get_collection();

					if ( isset( $collection['scripts'] ) ) {
						foreach ( $collection['scripts'] as $script ) {
							$handle = isset( $script['handle'] ) ? $script['handle'] : false;
							$source = isset( $script['src'] ) ? basename( $script['src'] ) : false;

							if ( ! $handle || ! $source ) {
								continue;
							}

							echo '<option value="' . esc_attr( $handle ) . '" data-type="scripts" ' . selected( in_array( $handle, $cdn_excludes['scripts'], true ) ) . '>' . esc_html( $handle ) . ' (file - ' . esc_html( $source ) . ')</option>';
						}
					}

					if ( isset( $collection['styles'] ) ) {
						foreach ( $collection['styles'] as $style ) {
							$handle = isset( $style['handle'] ) ? $style['handle'] : false;
							$source = isset( $style['src'] ) ? basename( $style['src'] ) : false;

							if ( ! $handle || ! $source ) {
								continue;
							}

							echo '<option value="' . esc_attr( $handle ) . '" data-type="styles" ' . selected( in_array( $handle, $cdn_excludes['styles'], true ) ) . '>' . esc_html( $handle ) . ' (file - ' . esc_html( $source ) . ')</option>';
						}
					}
					?>
				</select>
			</span>
		</div>
	</div>
<?php endif; ?>

<?php if ( ! $is_member ) : ?>
	<div class="sui-box-settings-row sui-upsell-row">
		<img class="sui-image sui-upsell-image"
			src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-upsell-minify.png' ); ?>"
			srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-upsell-minify@2x.png' ); ?> 2x"
			alt="<?php esc_attr_e( 'WP Smush free installed', 'wphb' ); ?>">
		<?php
		$this->admin_notices->show_inline(
			sprintf( /* translators: %s: upsell modal href link */
				__( "With our pro version of Hummingbird you can super-compress your files and then host them on our blazing-fast CDN. Get CDN as part of a WPMU DEV membership with 24/7 support and lots of handy site management tools.  <a href='%s' target='_blank'>Try Pro for FREE today!</a>", 'wphb' ),
				\Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_assetoptimization_settings_upsell_link' )
			),
			'sui-upsell-notice'
		);
		?>
	</div><!-- end sui-upsell-row -->
<?php endif; ?>

<?php if ( ! is_multisite() || ( is_multisite() && $logging ) ) : ?>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'Debug', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php esc_html_e( 'Turn on the debug log to get insight into any issues you’re having.', 'wphb' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<?php if ( ! is_multisite() ) : ?>
					<label for="debug_log" class="sui-toggle">
						<input type="checkbox" name="debug_log" id="debug_log" aria-labelledby="debug_log-label" <?php checked( $logging ); ?>>
						<span class="sui-toggle-slider" aria-hidden="true"></span>
						<span id="debug_log-label" class="sui-toggle-label">
							<?php esc_html_e( 'Enable debug log', 'wphb' ); ?>
						</span>
					</label>
				<?php endif; ?>

				<div class="sui-description sui-border-frame with-padding wphb-logging-box <?php echo $logging ? '' : 'sui-hidden'; ?>">
					<?php esc_html_e( 'Debug logging is active. Logs are stored for 30 days, you can download the log file below.', 'wphb' ); ?>

					<div class="wphb-logging-buttons">
						<a href="<?php echo esc_url( $download_url ); ?>" class="sui-button sui-button-ghost" <?php disabled( ! $logs_link, true ); ?>>
							<span class="sui-icon-download" aria-hidden="true"></span>
							<?php esc_html_e( 'Download Logs', 'wphb' ); ?>
						</a>
						<a href="#" class="sui-button sui-button-ghost sui-button-red wphb-logs-clear" data-module="minify" <?php disabled( ! $logs_link, true ); ?>>
							<span class="sui-icon-trash" aria-hidden="true"></span>
							<?php esc_html_e( 'Clear', 'wphb' ); ?>
						</a>
					</div>

					<?php if ( $path_url ) : ?>
						<a href="<?php echo esc_url( $download_url ); ?>"><?php echo esc_url( $path_url ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Reset to defaults', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Use this button to wipe any existing settings and return to defaults.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'reset', 'true' ), 'wphb-reset-minification' ) ); ?>" class="sui-button sui-button-ghost">
			<?php esc_html_e( 'Reset', 'wphb' ); ?>
		</a>
		<span class="sui-description"><?php esc_html_e( 'Note: This will clear all your settings for the module and run a new file check.', 'wphb' ); ?></span>
	</div>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Deactivate', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'No longer need Asset Optimization? This will completely deactivate this feature.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'disable', 'true' ), 'wphb-disable-minification' ) ); ?>" class="sui-button sui-button-ghost" onclick="WPHB_Admin.Tracking.disableFeature( 'Asset Optimization' )">
			<?php esc_html_e( 'Deactivate', 'wphb' ); ?>
		</a>
		<span class="sui-description"><?php esc_html_e( 'Note: This will not remove any files, they will just go back to their original, unoptimized state.', 'wphb' ); ?></span>
	</div>
</div>
