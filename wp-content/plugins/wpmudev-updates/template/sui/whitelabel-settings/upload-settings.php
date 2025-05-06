<?php
/**
 * The whitelabel menu icon link configuration content for a project.
 *
 * @var array $project  Project data.
 * @var array $settings Settings.
 *
 * @package WPMUDEV_Dashboard
 * @since   4.11.1
 */

defined( 'WPINC' ) || die();

// Project ID.
$pid = $project->pid;

// Thumb URL.
$thumb_url = empty( $settings['thumb_id'] ) ? '' : wp_get_attachment_image_url( $settings['thumb_id'], 'thumbnail', true );

?>
<div
	id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-content"
	class="sui-tab-content sui-tab-boxed <?php echo 'upload' === $settings['icon_type'] ? 'active' : ''; ?>"
	data-tab-content="project-icon-upload-<?php echo esc_attr( $pid ); ?>-content"
>
	<div class="sui-form-field">
		<label class="sui-label">
			<?php esc_html_e( 'Upload Icon', 'wpmudev' ); ?>
		</label>
		<div
			id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-wrapper"
			class="sui-upload <?php echo ! empty( $thumb_url ) ? 'sui-has_file' : ''; ?>"
		>
			<div class="sui-hidden">
				<input
					type="text"
					id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-url"
					readonly="readonly"
					value="<?php echo esc_attr( $thumb_url ); ?>"
				>
			</div>
			<input
				type="hidden"
				name="labels_config[<?php echo esc_attr( $pid ); ?>][thumb_id]"
				id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-thumb-id"
				readonly="readonly"
				value="<?php echo esc_attr( $settings['thumb_id'] ); ?>"
			>
			<div class="sui-upload-image" aria-hidden="true">
				<div class="sui-image-mask"></div>
				<div
					role="button"
					class="sui-image-preview wp-browse-media"
					data-frame-title="<?php esc_html_e( 'Select or Upload Media for Icon', 'wpmudev' ); ?>"
					data-button-text="<?php esc_html_e( 'Use this as icon', 'wpmudev' ); ?>"
					data-input-id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-url"
					data-preview-id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-preview"
					data-upload-wrapper-id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-wrapper"
					data-input-id-container="project-icon-upload-<?php echo esc_attr( $pid ); ?>-thumb-id"
					data-text-id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-text"
					id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-preview"
					style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');"
				>
				</div>
			</div>
			<button
				class="sui-upload-button wp-browse-media"
				data-frame-title="<?php esc_html_e( 'Select or Upload Media for Icon', 'wpmudev' ); ?>"
				data-button-text="<?php esc_html_e( 'Use this as icon', 'wpmudev' ); ?>"
				data-input-id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-url"
				data-preview-id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-preview"
				data-upload-wrapper-id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-wrapper"
				data-input-id-container="project-icon-upload-<?php echo esc_attr( $pid ); ?>-thumb-id"
				data-text-id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-text"
			>
				<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Upload image', 'wpmudev' ); ?>
			</button>
			<div class="sui-upload-file">
			<span id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-text">
				<?php echo esc_url( $thumb_url ); ?>
			</span>
				<button
					class="js-clear-image"
					aria-label="<?php esc_attr_e( 'Remove', 'wpmudev' ); ?>"
					data-media-button-id="project-icon-upload-<?php echo esc_attr( $pid ); ?>-preview"
				>
					<i class="sui-icon-close" aria-hidden="true"></i>
				</button>
			</div>
		</div>
		<span class="sui-description"><?php esc_html_e( 'Upload an icon to override the default menu item icon. The recommended size is 20x20.', 'wpmudev' ); ?></span>
	</div>
</div>