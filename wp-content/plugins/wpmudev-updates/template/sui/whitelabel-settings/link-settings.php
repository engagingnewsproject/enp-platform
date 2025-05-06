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

?>
<div
	id="project-icon-link-<?php echo esc_attr( $pid ); ?>-content"
	class="sui-tab-content sui-tab-boxed wpmudev-whitelabel-link-logo <?php echo 'link' === $settings['icon_type'] ? 'active' : ''; ?>"
	data-tab-content="project-icon-link-<?php echo esc_attr( $pid ); ?>-content"
>
	<div
		class="sui-form-field"
		id="project-icon-link-<?php echo esc_attr( $pid ); ?>-form-field"
	>
		<label
			class="sui-label"
			for="project-icon-link-<?php echo esc_attr( $pid ); ?>-url"
			id="project-icon-link-<?php echo esc_attr( $pid ); ?>-label"
		>
			<?php esc_html_e( 'Insert icon from URL', 'wpmudev' ); ?>
		</label>
		<div class="sui-upload">
			<div class="sui-upload-image" aria-hidden="true">
				<div class="sui-image-mask"></div>
				<div
					class="sui-image-link-preview <?php echo '' === esc_url( $settings['icon_url'] ) ? '' : 'has-logo-image'; ?>"
					id="project-icon-link-<?php echo esc_attr( $pid ); ?>-preview"
					style="background-image: url('<?php echo esc_url( $settings['icon_url'] ); ?>');"
				></div>
			</div>
			<div class="sui-with-button sui-with-button-icon">
				<input
					id="project-icon-link-<?php echo esc_attr( $pid ); ?>-link"
					name="labels_config[<?php echo esc_attr( $pid ); ?>][icon_url]"
					class="sui-form-control wp-link-media"
					data-preview-id="project-icon-link-<?php echo esc_attr( $pid ); ?>-preview"
					data-clear-btn-id="project-icon-link-<?php echo esc_attr( $pid ); ?>-clear"
					data-form-field-id="project-icon-link-<?php echo esc_attr( $pid ); ?>-form-field"
					data-tab-type-name="labels_config[<?php echo esc_attr( $project->pid ); ?>][icon_type]"
					aria-labelledby="project-icon-link-<?php echo esc_attr( $pid ); ?>-label"
					aria-describedby="project-icon-link-<?php echo esc_attr( $pid ); ?>-desc"
					value="<?php echo esc_url( $settings['icon_url'] ); ?>"
					placeholder="<?php esc_html_e( 'Paste Image URL', 'wpmudev' ); ?>"
				/>
				<button
					type="button"
					class="sui-button-icon js-clear-link <?php echo empty( $settings['icon_url'] ) ? 'hidden-clear-link' : ''; ?>"
					id="project-icon-link-<?php echo esc_attr( $pid ); ?>-clear"
					data-input-id="project-icon-link-<?php echo esc_attr( $pid ); ?>-link"
				>
					<span aria-hidden="true" class="sui-icon-close"></span>
					<span class="sui-screen-reader-text">
					<?php esc_html_e( 'Remove file', 'wpmudev' ); ?>
				</span>
				</button>
			</div>
		</div>
		<span
			id="project-icon-link-<?php echo esc_attr( $pid ); ?>-error"
			class="sui-error-message"
			role="alert"
		>
		<?php esc_attr_e( 'Invalid image URL. Please, enter a valid one.', 'wpmudev' ); ?>
	</span>
		<span
			id="project-icon-link-<?php echo esc_attr( $pid ); ?>-desc"
			class="sui-description"
		>
		<?php esc_html_e( 'Insert an icon to override the default menu item icon. The recommended size is 20x20.', 'wpmudev' ); ?>
	</span>
	</div>
</div>