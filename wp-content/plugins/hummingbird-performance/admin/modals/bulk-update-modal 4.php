<?php
/**
 * Bulk update for asset optimization.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-sm">
	<div role="dialog" class="sui-modal-content" id="bulk-update-modal" aria-modal="true" aria-labelledby="bulkUpdate" aria-describedby="dialogDescription">
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button class="sui-button-icon sui-button-float--right" data-modal-close="" id="dialog-close-div">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this dialog window', 'wphb' ); ?></span>
				</button>
				<h3 class="sui-box-title sui-lg" id="bulkUpdate"><?php esc_html_e( 'Bulk Update', 'wphb' ); ?></h3>
				<p class="sui-description" id="dialogDescription">
					<?php esc_html_e( 'Choose what bulk update actions you’d like to apply to the selected files. You still have to publish your changes before they will be set live.', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-body sui-row sui-no-margin-bottom">
				<div class="sui-col">
					<div class="sui-form-field">
						<label for="filter-minify" class="sui-checkbox sui-checkbox-stacked">
							<input type="checkbox" id="filter-minify" aria-labelledby="checkbox-label-filter-minify">
							<span aria-hidden="true"></span>
							<span id="checkbox-label-filter-minify"><?php esc_html_e( 'Compress', 'wphb' ); ?></span>
						</label>
						<label for="filter-combine" class="sui-checkbox sui-checkbox-stacked">
							<input type="checkbox" id="filter-combine" aria-labelledby="checkbox-label-filter-combine">
							<span aria-hidden="true"></span>
							<span id="checkbox-label-filter-combine"><?php esc_html_e( 'Combine', 'wphb' ); ?></span>
						</label>
						<label for="filter-inline" class="sui-checkbox sui-checkbox-stacked">
							<input type="checkbox" id="filter-inline" aria-labelledby="checkbox-label-filter-inline">
							<span aria-hidden="true"></span>
							<span id="checkbox-label-filter-inline"><?php esc_html_e( 'Inline', 'wphb' ); ?></span>
						</label>
						<label for="filter-async" class="sui-checkbox sui-checkbox-stacked">
							<input type="checkbox" id="filter-async" aria-labelledby="checkbox-label-filter-async">
							<span aria-hidden="true"></span>
							<span id="checkbox-label-filter-async"><?php esc_html_e( 'Async', 'wphb' ); ?></span>
						</label>
					</div>
				</div>

				<div class="sui-col">
					<div class="sui-form-field">
						<label for="filter-position-footer" class="sui-checkbox sui-checkbox-stacked">
							<input type="checkbox" id="filter-position-footer" aria-labelledby="checkbox-label-filter-position-footer">
							<span aria-hidden="true"></span>
							<span id="checkbox-label-filter-position-footer"><?php esc_html_e( 'Move to Footer', 'wphb' ); ?></span>
						</label>
						<label for="filter-preload" class="sui-checkbox sui-checkbox-stacked">
							<input type="checkbox" id="filter-preload" aria-labelledby="checkbox-label-filter-preload">
							<span aria-hidden="true"></span>
							<span id="checkbox-label-filter-preload"><?php esc_html_e( 'Preload', 'wphb' ); ?></span>
						</label>
						<label for="filter-defer" class="sui-checkbox sui-checkbox-stacked">
							<input type="checkbox" id="filter-defer" aria-labelledby="checkbox-label-filter-defer">
							<span aria-hidden="true"></span>
							<span id="checkbox-label-filter-defer"><?php esc_html_e( 'Defer', 'wphb' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<div class="sui-box-footer sui-flatten sui-content-center sui-no-padding-top">
				<button class="sui-button sui-button-ghost" data-modal-close="" id="bulk-update-cancel">
					<?php esc_html_e( 'Cancel', 'wphb' ); ?>
				</button>

				<button class="sui-button" data-modal-close="" onclick="WPHB_Admin.minification.processBulkUpdateSelections()">
					<?php esc_html_e( 'Apply', 'wphb' ); ?>
				</button>
			</div>

			<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
				<img class="sui-image" alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-minify-modal-warning@1x.png' ); ?>"
					srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-minify-modal-warning@1x.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-minify-modal-warning@2x.png' ); ?> 2x">
			<?php endif; ?>
		</div>
	</div>
</div>
