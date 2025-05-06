<?php
/**
 * The whitelabel label configuration content for a project.
 *
 * @var array $project  Project data.
 * @var array $settings Settings.
 *
 * @package WPMUDEV_Dashboard
 * @since   4.11.1
 */

defined( 'WPINC' ) || die();

$icon_types = array(
	'default'  => __( 'Default', 'wpmudev' ),
	'dashicon' => __( 'Dashicon', 'wpmudev' ),
	'upload'   => __( 'Upload Icon', 'wpmudev' ),
	'link'     => __( 'Link Icon', 'wpmudev' ),
	'none'     => __( 'None', 'wpmudev' ),
);

// Default settings.
$defaults = array(
	'name'       => '',
	'icon_type'  => 'default',
	'thumb_id'   => false,
	'icon_url'   => '',
	'icon_class' => '',
);

// Get settings values.
$settings = isset( $settings['labels_config'][ $project->pid ] ) ? (array) $settings['labels_config'][ $project->pid ] : array();
// Merge with default values.
$settings = wp_parse_args( $settings, $defaults );

?>
<div class="sui-form-field">
	<label
		for="project-name-<?php echo esc_attr( $project->pid ); ?>"
		id="project-name-label-<?php echo esc_attr( $project->pid ); ?>"
		class="sui-label"
	>
		<?php esc_html_e( 'Plugin Name', 'wpmudev' ); ?>
	</label>
	<input
		placeholder="<?php echo esc_attr( $project->name ); ?>"
		id="project-name-<?php echo esc_attr( $project->pid ); ?>"
		class="sui-form-control"
		aria-labelledby="project-name-label-<?php echo esc_attr( $project->pid ); ?>"
		name="labels_config[<?php echo esc_attr( $project->pid ); ?>][name]"
		value="<?php echo esc_attr( $settings['name'] ); ?>"
	/>
</div>

<div class="sui-form-field">
	<label
		id="project-icon-label-<?php echo esc_attr( $project->pid ); ?>"
		class="sui-label"
	>
		<?php esc_html_e( 'Icon', 'wpmudev' ); ?>
	</label>
	<div class="sui-side-tabs">
		<div class="sui-tabs-menu">
			<?php foreach ( $icon_types as $icon_type => $icon_label ) : ?>
				<label
					for="project-icon-<?php echo esc_attr( $icon_type ); ?>-<?php echo esc_attr( $project->pid ); ?>"
					class="sui-tab-item <?php echo $icon_type === $settings['icon_type'] ? 'active' : ''; ?>"
				>
					<input
						type="radio"
						name="labels_config[<?php echo esc_attr( $project->pid ); ?>][icon_type]"
						value="<?php echo esc_attr( $icon_type ); ?>"
						id="project-icon-<?php echo esc_attr( $icon_type ); ?>-<?php echo esc_attr( $project->pid ); ?>"
						data-checked="<?php echo $icon_type === $settings['icon_type'] ? 'true' : 'false'; ?>"
						data-tab-menu="project-icon-<?php echo esc_attr( $icon_type ); ?>-<?php echo esc_attr( $project->pid ); ?>-content"
						<?php checked( $icon_type, $settings['icon_type'] ); ?>
					/>
					<?php echo esc_attr( $icon_label ); ?>
				</label>
			<?php endforeach; ?>
		</div>
		<div class="sui-tabs-content">
			<?php
			$this->render(
				'sui/whitelabel-settings/dashicon-settings',
				array(
					'project'  => $project,
					'settings' => $settings,
				)
			);
			$this->render(
				'sui/whitelabel-settings/upload-settings',
				array(
					'project'  => $project,
					'settings' => $settings,
				)
			);
			$this->render(
				'sui/whitelabel-settings/link-settings',
				array(
					'project'  => $project,
					'settings' => $settings,
				)
			);
			?>
		</div>
	</div>
</div>
