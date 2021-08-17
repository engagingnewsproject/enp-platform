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

			<div class="sui-box-body">
				<div class="checkbox-group">
					<input type="checkbox" class="toggle-checkbox filter-toggles filter-minify" name="filter-minify" id="filter-minify" aria-label="<?php esc_attr_e( 'Compress', 'wphb' ); ?>">
					<label for="filter-minify" class="toggle-label sui-tooltip" data-tooltip="<?php esc_attr_e( 'Compress', 'wphb' ); ?>" aria-hidden="true">
						<span class="sui-icon-arrows-in" aria-hidden="true"></span>
					</label>

					<input type="checkbox" class="toggle-checkbox filter-toggles filter-combine" name="filter-combine" id="filter-combine" aria-label="<?php esc_attr_e( 'Combine', 'wphb' ); ?>">
					<label for="filter-combine" class="toggle-label sui-tooltip" data-tooltip="<?php esc_attr_e( 'Combine', 'wphb' ); ?>" aria-hidden="true">
						<span class="sui-icon-combine" aria-hidden="true"></span>
					</label>

					<input type="checkbox" class="toggle-checkbox filter-toggles filter-position-footer" name="filter-position" id="filter-position-footer" aria-label="<?php esc_attr_e( 'Footer', 'wphb' ); ?>">
					<label for="filter-position-footer" class="toggle-label sui-tooltip" data-tooltip="<?php esc_attr_e( 'Move to Footer', 'wphb' ); ?>" aria-hidden="true">
						<span class="sui-icon-movefooter" aria-hidden="true"></span>
					</label>

					<input type="checkbox" class="toggle-checkbox filter-toggles filter-defer" name="filter-defer" id="filter-defer" aria-label="<?php esc_attr_e( 'Defer', 'wphb' ); ?>">
					<label for="filter-defer" class="toggle-label sui-tooltip" data-tooltip="<?php esc_attr_e( 'Defer JavaScript', 'wphb' ); ?>" aria-hidden="true">
						<span class="sui-icon-defer" aria-hidden="true"></span>
					</label>

					<input type="checkbox" class="toggle-checkbox filter-toggles filter-inline" name="filter-inline" id="filter-inline" aria-label="<?php esc_attr_e( 'Inline', 'wphb' ); ?>">
					<label for="filter-inline" class="toggle-label sui-tooltip" data-tooltip="<?php esc_attr_e( 'Inline CSS', 'wphb' ); ?>" aria-hidden="true">
						<span class="sui-icon-inlinecss" aria-hidden="true"></span>
					</label>
				</div><!-- end checkbox-group -->

			</div>
			<div class="sui-box-footer sui-flatten sui-content-center sui-no-padding-top">
				<button class="sui-button sui-button-ghost" data-modal-close="">
					<?php esc_html_e( 'Cancel', 'wphb' ); ?>
				</button>

				<button class="save-batch sui-button" data-modal-close="">
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

<script type="text/javascript">
	jQuery('label[for^="filter-"]').on('click', function() {
		jQuery(this).toggleClass('toggle-label-background');
	});

	jQuery('.save-batch').on('click', function() {
		var filesCollection = WPHB_Admin.minification.rowsCollection;

		var modal = jQuery( '#bulk-update-modal' );
		// Get the selected batch status
		var minify = modal.find( 'input.filter-minify' ).prop( 'checked' ),
			combine = modal.find( 'input.filter-combine').prop('checked'),
			footer = modal.find( 'input.filter-position-footer' ).prop( 'checked' ),
			defer = modal.find( 'input.filter-defer' ).prop( 'checked' ),
			inline = modal.find( 'input.filter-inline' ).prop( 'checked' ),
			selectedFiles = filesCollection.getSelectedItems();

		for ( var i in selectedFiles ) {
			selectedFiles[i].change( 'minify', minify );
			selectedFiles[i].change( 'combine', combine );
			selectedFiles[i].change( 'footer', footer );
			selectedFiles[i].change( 'defer', defer );
			selectedFiles[i].change( 'inline', inline );
		}

		// Unset all the values in bulk update checkboxes.
		modal.find('input.filter-minify').prop('checked', false);
		modal.find('input.filter-combine').prop('checked', false);
		modal.find('input.filter-position-footer').prop('checked', false);
		modal.find('input.filter-defer').prop('checked', false);
		modal.find('input.filter-inline').prop('checked', false);

		// Remove background class.
		modal.find('label[for="filter-minify"]').removeClass('toggle-label-background');
		modal.find('label[for="filter-combine"]').removeClass('toggle-label-background');
		modal.find('label[for="filter-position-footer"]').removeClass('toggle-label-background');
		modal.find('label[for="filter-defer"]').removeClass('toggle-label-background');
		modal.find('label[for="filter-inline"]').removeClass('toggle-label-background');

		// Enable the Publish Changes button.
		jQuery('input[type=submit]').removeClass('disabled');
	});
</script>
