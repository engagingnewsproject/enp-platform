<?php
/**
 * Import & Export meta box.
 *
 * @since 2.6.0
 * @package Hummingbird
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Import', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Use this tool to import the Manual Asset Optimization configuration from another site.', 'wphb' ); ?>
		</span>
	</div><!-- end col-third -->
	<div class="sui-box-settings-col-2">
		<strong><?php esc_html_e( 'Import configurations', 'wphb' ); ?></strong>
		<span class="sui-description" style="margin-bottom: 10px;">
			<?php esc_html_e( 'Import an exported Hummingbird settings file to apply the Manual Asset Optimization configuration.', 'wphb' ); ?>
		</span>
		<form id="wphb-import-frm" method="post" enctype="multipart/form-data">
			<div class="sui-form-field">
				<div class="sui-upload" id="wphb-import-upload-wrap">
					<input id="wphb-import-file-input" class="wphb-file-input" type="file" value="" readonly="readonly" accept=".json">
					<label class="sui-upload-button" type="button" for="wphb-import-file-input">
						<span class="sui-icon-upload-cloud" aria-hidden="true"></span> 
						<?php esc_html_e( 'Upload file', 'wphb' ); ?>
					</label>
					<div class="sui-upload-file">
						<span id="wphb-import-file-name"></span>
						<button type="button" id="wphb-import-remove-file" aria-label="Remove file">
							<span class="sui-icon-close" aria-hidden="true"></span>
						</button>
					</div>
					<span type="button" id="wphb-import-btn" class="sui-button sui-button-blue" aria-live="polite" data-modal-open="settings-import-modal" style="margin-left: 10px; padding-top:10px;" disabled>
						<span class="sui-button-text-default">
							<span class="sui-icon-download-cloud" aria-hidden="true"></span>
							<?php esc_html_e( 'Import', 'wphb' ); ?>
						</span>
					</span>

				</div>
				<span class="sui-description" style="margin-top: 10px;"><?php esc_html_e( 'Choose a JSON(.json) file to import the configuration.', 'wphb' ); ?></span>
			</div>
		</form>
	</div>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Export', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Export your Hummingbird configuration as a JSON file to use on other sites.', 'wphb' ); ?>
		</span>
	</div><!-- end col-third -->
	<div class="sui-box-settings-col-2">
		<strong><?php esc_html_e( 'Export configurations', 'wphb' ); ?></strong>
		<div class="sui-description" style="padding: 30px; border: 1px solid #e6e6e6; margin-bottom: 10px; border-radius: 5px;">
			<strong><?php esc_html_e( 'Asset Optimization/Manual', 'wphb' ); ?></strong><br>
			<?php esc_html_e( 'Export Asset Optimization manual configurations and use it on other sites by simply importing the file.', 'wphb' ); ?>
		</div>
		<a href="#" class="sui-button sui-button-icon-left" id="wphb-export-btn">
			<span class="sui-icon-download-cloud" aria-hidden="true"></span>
			<?php esc_html_e( 'Export', 'wphb' ); ?>
		</a>
	</div>
</div>
<?php $this->modal( 'settings-import' ); ?>
