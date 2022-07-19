<?php
/**
 * Dashboard popup template: Ask for FTP credentials before updating/installing.
 *
 * This is only loaded if direct FS access is not possible.
 *
 */

?>
<div class="sui-modal sui-modal-sm">
	<div
	role="dialog"
	id="update-translation-modal"
	class="sui-modal-content"
	aria-modal="true"
	aria-labelledby="update-translation-modal-title"
	aria-describedby="update-translation-modal-desc"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">

				<button class="sui-button-icon sui-button-float--right" data-modal-close="">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog.', 'wpmudev' ); ?></span>
				</button>

				<h3 id="update-translation-modal-title" class="sui-box-title sui-lg"><?php esc_html_e( 'Update Translations', 'wpmudev' ); ?></h3>

				<p id="update-translation-modal-desc" class="sui-description"><?php esc_html_e( 'Choose which translations you want to update today.', 'wpmudev' ); ?></p>

			</div>

			<div class="sui-box-body">
				<?php foreach ( $translation_update as $key => $value ) { ?>

					<div class="sui-form-field" style="margin-bottom:5px;">
						<label for="translation-bulk-action-<?php echo esc_attr( $value['slug'] ); ?>" class="sui-checkbox" style="width: 200px">
							<input
							type="checkbox"
							name="translations[]"
							value="<?php echo esc_attr( $value['slug'] ); ?>"
							id="translation-bulk-action-<?php echo esc_attr( $value['slug'] ); ?>" data-plugin-name="<?php echo esc_attr( $value['name'] ); ?>"
							class="js-plugin-check translation-projects">
							<span aria-hidden="true"></span>
							<span style="font-size:13px;"> <?php echo esc_html( $value['name'] ); ?></span>
						</label>
					</div>
				<?php } ?>
			</div>

			<div class="sui-box-footer sui-space-between" style="border-top: 1px solid #e6e6e6; padding:30px">

				<button class="sui-button sui-button-ghost" data-modal-close="" data-a11y-dialog-hide="translation-details"><?php esc_html_e( 'Cancel', 'wpmudev' ); ?></button>
				<button
				id="update-selected-translations"
				data-modal-open="bulk-action-translation-modal"
				data-modal-mask="true"
				data-replace="true"
				data-trigger="wpmudev:startTranslation"
				class="sui-button modal-open"
				disabled="disabled"
				>
					<span class="sui-loading-text">
						<i class="sui-icon-update" aria-hidden="true"></i>
						<?php esc_html_e( 'Update', 'wpmudev' ); ?>
					</span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</button>

			</div>

		</div>

	</div>

</div>

<?php // bulk action. ?>
<div class="sui-modal sui-modal-sm">
	<div
	role="dialog"
	id="bulk-action-translation-modal"
	class="sui-modal-content"
	aria-modal="true"
	aria-labelledby="wpmudev-update-translation-title"
	aria-describedby="wpmudev-update-translation-desc"
	>
		<div class="sui-box">

			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">

				<h3 id="wpmudev-update-translation-title" class="sui-box-title sui-lg"><?php esc_html_e( 'Updating Translations', 'wpmudev' ); ?></h3>

				<p id="wpmudev-update-translation-desc" class="sui-description"><?php esc_html_e( 'Please wait while we download and install those translations for you.', 'wpmudev' ); ?></p>

			</div>
			<div class="sui-box-body">
				<div class="sui-notice sui-notice-warning js-bulk-errors" style="text-align:left"></div>

				<div class="sui-progress-block">

					<div class="sui-progress">

						<span class="sui-progress-icon js-bulk-actions-loader-icon" aria-hidden="true">
							<i class="sui-icon-loader sui-loading"></i>
						</span>

						<span class="sui-progress-text">
							<span>0%</span>
						</span>

						<div class="sui-progress-bar" aria-hidden="true">
							<span style="width: 0%" class="js-bulk-actions-progress"></span>
						</div>
					</div>
				</div>

				<div class="sui-progress-state">
					<span class="js-bulk-actions-state"></span>
				</div>

				<div class="sui-hidden js-bulk-hash" data-translation-update="<?php echo esc_attr( wp_create_nonce( 'translation-update' ) ); ?>"></div>

			</div>
		</div>
	</div>
</div>
