<?php
/**
 * Data & Settings meta box.
 *
 * @since 2.0.0
 * @package Hummingbird
 *
 * @var array $settings  Settings for the module.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$this->modal( 'reset-settings' );
?>

<form method="post" class="settings-frm">
	<p>
		<?php esc_html_e( 'Control what to do with your settings and data. Settings are each module’s configuration options, Data includes the stored information like cached pages, statistics and other pieces of information stored over time.', 'wphb' ); ?>
	</p>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'Uninstallation', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php esc_html_e( 'When you uninstall this plugin, what do you want to do with your settings and stored data?', 'wphb' ); ?>
			</span>
		</div><!-- end col-third -->
		<div class="sui-box-settings-col-2">
			<strong><?php esc_html_e( 'Settings', 'wphb' ); ?></strong>
			<span class="sui-description">
				<?php esc_html_e( 'Choose whether to save your settings for next time, or reset them.', 'wphb' ); ?>
			</span>
			<div class="sui-side-tabs">
				<div class="sui-tabs-menu">
					<label for="remove_settings-false" class="sui-tab-item <?php echo $settings['remove_settings'] ? '' : 'active'; ?>">
						<input type="radio" name="remove_settings" value="0" id="remove_settings-false" <?php checked( ! $settings['remove_settings'] ); ?>>
						<?php esc_html_e( 'Preserve', 'wphb' ); ?>
					</label>

					<label for="remove_settings-true" class="sui-tab-item <?php echo $settings['remove_settings'] ? 'active' : ''; ?>">
						<input type="radio" name="remove_settings" value="1" id="remove_settings-true" <?php checked( $settings['remove_settings'] ); ?>>
						<?php esc_html_e( 'Reset', 'wphb' ); ?>
					</label>
				</div>
			</div>

			<strong><?php esc_html_e( 'Data', 'wphb' ); ?></strong>
			<span class="sui-description">
				<?php esc_html_e( 'Choose whether to keep or remove transient data.', 'wphb' ); ?>
			</span>
			<div class="sui-side-tabs">
				<div class="sui-tabs-menu">
					<label for="remove_data-false" class="sui-tab-item <?php echo $settings['remove_data'] ? '' : 'active'; ?>">
						<input type="radio" name="remove_data" value="0" id="remove_data-false" <?php checked( ! $settings['remove_data'] ); ?>>
						<?php esc_html_e( 'Keep', 'wphb' ); ?>
					</label>

					<label for="remove_data-true" class="sui-tab-item <?php echo $settings['remove_data'] ? 'active' : ''; ?>">
						<input type="radio" name="remove_data" value="1" id="remove_data-true" <?php checked( $settings['remove_data'] ); ?>>
						<?php esc_html_e( 'Remove', 'wphb' ); ?>
					</label>
				</div>
			</div>
		</div>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'Reset Settings', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php esc_html_e( 'Needing to start fresh? Use this button to roll back to the default settings.', 'wphb' ); ?>
			</span>
		</div><!-- end col-third -->
		<div class="sui-box-settings-col-2">
			<a href="#" class="sui-button sui-button-ghost sui-button-icon-left" data-modal-open="wphb-reset-settings-modal" data-modal-open-focus="dialog-close-div" data-modal-mask="true">
				<span class="sui-icon-undo" aria-hidden="true"></span>
				<?php esc_html_e( 'Reset Settings', 'wphb' ); ?>
			</a>
			<span class="sui-description">
				<?php esc_html_e( 'Note: This will instantly revert all settings to their default states but will leave your data intact.', 'wphb' ); ?>
			</span>
		</div>
	</div>
</form>
