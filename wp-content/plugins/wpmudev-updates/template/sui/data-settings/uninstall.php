<?php
/**
 * The data settings template.
 *
 * @var bool $keep_data         Keep settings on uninstall.
 * @var bool $preserve_settings Preserve data on uninstall.
 *
 * @since   4.11.4
 * @package WPMUDEV_Dashboard
 */

defined( 'WPINC' ) || die();

?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label sui-dark">
			<?php esc_html_e( 'Uninstallation', 'wpmudev' ); ?>
		</span>
		<span class="sui-description">
			<?php esc_html_e( 'When you uninstall this plugin, what do you want to do with your settings and stored data?', 'wpmudev' ); ?>
		</span>
		<span class="sui-description">
			<?php esc_html_e( 'Note: The settings or data changes you make here will not affect any other WPMU DEV plugins you may have installed and activated.', 'wpmudev' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<h4 class="sui-settings-label"><?php esc_html_e( 'Settings', 'wpmudev' ); ?></h4>
			<span class="sui-description sui-spacing-bottom--10">
				<?php esc_html_e( 'Choose whether to save your settings for next time, or reset them.', 'wpmudev' ); ?>
			</span>
			<div class="sui-side-tabs sui-tabs">
				<div data-tabs>
					<label class="sui-tab-item <?php echo empty( $preserve_settings ) ? '' : 'active'; ?>">
						<input
							type="radio"
							value="1"
							name="uninstall_preserve_settings"
							<?php checked( ! empty( $preserve_settings ) ); ?>
						/>
						<?php esc_html_e( 'Preserve', 'wpmudev' ); ?>
					</label>
					<label class="sui-tab-item <?php echo empty( $preserve_settings ) ? 'active' : ''; ?>">
						<input
							type="radio"
							value="0"
							name="uninstall_preserve_settings"
							<?php checked( empty( $preserve_settings ) ); ?>
						/>
						<?php esc_html_e( 'Reset', 'wpmudev' ); ?>
					</label>
				</div>
			</div>
		</div>
		<div class="sui-form-field">
			<h4 class="sui-settings-label"><?php esc_html_e( 'Data', 'wpmudev' ); ?></h4>
			<span class="sui-description sui-spacing-bottom--10">
				<?php esc_html_e( 'Choose whether to keep or remove plugin\'s data.', 'wpmudev' ); ?>
			</span>
			<div class="sui-side-tabs sui-tabs">
				<div data-tabs>
					<label class="sui-tab-item <?php echo empty( $keep_data ) ? '' : 'active'; ?>">
						<input
							type="radio"
							value="1"
							name="uninstall_keep_data"
							<?php checked( ! empty( $keep_data ) ); ?>
						/>
						<?php esc_html_e( 'Keep', 'wpmudev' ); ?>
					</label>
					<label class="sui-tab-item <?php echo empty( $keep_data ) ? 'active' : ''; ?>">
						<input
							type="radio"
							value="0"
							name="uninstall_keep_data"
							<?php checked( empty( $keep_data ) ); ?>
						/>
						<?php esc_html_e( 'Remove', 'wpmudev' ); ?>
					</label>
				</div>
			</div>
		</div>
	</div>
</div>