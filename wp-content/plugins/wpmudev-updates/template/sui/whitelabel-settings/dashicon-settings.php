<?php
/**
 * The whitelabel icon class configuration content for a project.
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

// Dashicons array.
$dashicons = array(
	array(
		'title' => __( 'Admin Menu', 'wpmudev' ),
		'icons' => array(
			'menu',
			'admin-site',
			'dashboard',
			'admin-post',
			'admin-media',
			'admin-links',
			'admin-page',
			'admin-comments',
			'admin-appearance',
			'admin-plugins',
			'admin-users',
			'admin-tools',
			'admin-settings',
			'admin-network',
			'admin-home',
			'admin-generic',
			'admin-collapse',
			'filter',
			'admin-customizer',
			'admin-multisite',
		),
	),
	array(
		'title' => __( 'Welcome Screen', 'wpmudev' ),
		'icons' => array(
			'welcome-write-blog',
			'welcome-edit-page',
			'welcome-add-page',
			'welcome-view-site',
			'welcome-widgets-menus',
			'welcome-comments',
			'welcome-learn-more',
		),
	),
	array(
		'title' => __( 'Post Formats', 'wpmudev' ),
		'icons' => array(
			'format-standard',
			'format-aside',
			'format-image',
			'format-gallery',
			'format-video',
			'format-status',
			'format-quote',
			'format-links',
			'format-chat',
			'format-audio',
			'camera',
			'images-alt',
			'images-alt2',
			'video-alt',
			'video-alt2',
			'video-alt3',

		),
	),
	array(
		'title' => __( 'Media', 'wpmudev' ),
		'icons' => array(
			'media-archive',
			'media-audio',
			'media-code',
			'media-default',
			'media-document',
			'media-interactive',
			'media-spreadsheet',
			'media-text',
			'media-video',
			'playlist-audio',
			'playlist-video',
			'controls-play',
			'controls-pause',
			'controls-forward',
			'controls-skipforward',
			'controls-back',
			'controls-skipback',
			'controls-repeat',
			'controls-volumeon',
			'controls-volumeoff',

		),
	),
	array(
		'title' => __( 'Image Editing', 'wpmudev' ),
		'icons' => array(
			'image-crop',
			'image-rotate',
			'image-rotate-left',
			'image-rotate-right',
			'image-flip-vertical',
			'image-flip-horizontal',
			'image-filter',
			'undo',
			'redo',
		),
	),
	array(
		'title' => __( 'TinyMCE', 'wpmudev' ),
		'icons' => array(
			'editor-bold',
			'editor-italic',
			'editor-ul',
			'editor-ol',
			'editor-quote',
			'editor-alignleft',
			'editor-aligncenter',
			'editor-alignright',
			'editor-insertmore',
			'editor-spellcheck',
			'editor-distractionfree',
			'editor-expand',
			'editor-contract',
			'editor-kitchensink',
			'editor-underline',
			'editor-justify',
			'editor-textcolor',
			'editor-paste-word',
			'editor-paste-text',
			'editor-removeformatting',
			'editor-video',
			'editor-customchar',
			'editor-outdent',
			'editor-indent',
			'editor-help',
			'editor-strikethrough',
			'editor-unlink',
			'editor-rtl',
			'editor-break',
			'editor-code',
			'editor-paragraph',
			'editor-table',

		),
	),
	array(
		'title' => __( 'Posts Screen', 'wpmudev' ),
		'icons' => array(
			'align-left',
			'align-right',
			'align-center',
			'align-none',
			'lock',
			'unlock',
			'calendar',
			'calendar-alt',
			'visibility',
			'hidden',
			'post-status',
			'edit',
			'trash',
			'sticky',
		),
	),
	array(
		'title' => __( 'Sorting', 'wpmudev' ),
		'icons' => array(
			'external',
			'arrow-up',
			'arrow-down',
			'arrow-right',
			'arrow-left',
			'arrow-up-alt',
			'arrow-down-alt',
			'arrow-right-alt',
			'arrow-left-alt',
			'arrow-up-alt2',
			'arrow-down-alt2',
			'arrow-right-alt2',
			'arrow-left-alt2',
			'sort',
			'leftright',
			'randomize',
			'list-view',
			'exerpt-view',
			'grid-view',
			'move',

		),
	),
	array(
		'title' => __( 'Social', 'wpmudev' ),
		'icons' => array(
			'share',
			'share-alt',
			'share-alt2',
			'twitter',
			'rss',
			'email',
			'email-alt',
			'facebook',
			'facebook-alt',
			'googleplus',
			'networking',

		),
	),
	array(
		'title' => __( 'WordPress.org Specific: Jobs, Profiles, WordCamps', 'wpmudev' ),
		'icons' => array(
			'hammer',
			'art',
			'migrate',
			'performance',
			'universal-access',
			'universal-access-alt',
			'tickets',
			'nametag',
			'clipboard',
			'heart',
			'megaphone',
			'schedule',
		),
	),
	array(
		'title' => __( 'Products', 'wpmudev' ),
		'icons' => array(
			'wordpress',
			'wordpress-alt',
			'pressthis',
			'update',
			'screenoptions',
			'info',
			'cart',
			'feedback',
			'cloud',
			'translation',

		),
	),
	array(
		'title' => __( 'Taxonomies', 'wpmudev' ),
		'icons' => array(
			'tag',
			'category',
		),
	),
	array(
		'title' => __( 'Widgets', 'wpmudev' ),
		'icons' => array(
			'archive',
			'tagcloud',
			'text',
		),
	),
	array(
		'title' => __( 'Notifications', 'wpmudev' ),
		'icons' => array(
			'yes',
			'no',
			'no-alt',
			'plus',
			'plus-alt',
			'minus',
			'dismiss',
			'marker',
			'star-filled',
			'star-half',
			'star-empty',
			'flag',
			'warning',
		),
	),
	array(
		'title' => __( 'Misc', 'wpmudev' ),
		'icons' => array(
			'location',
			'location-alt',
			'vault',
			'shield',
			'shield-alt',
			'sos',
			'search',
			'slides',
			'analytics',
			'chart-pie',
			'chart-bar',
			'chart-line',
			'chart-area',
			'groups',
			'businessman',
			'id',
			'id-alt',
			'products',
			'awards',
			'forms',
			'testimonial',
			'portfolio',
			'book',
			'book-alt',
			'download',
			'upload',
			'backup',
			'clock',
			'lightbulb',
			'microphone',
			'desktop',
			'laptop',
			'tablet',
			'smartphone',
			'phone',
			'index-card',
			'carrot',
			'building',
			'store',
			'album',
			'palmtree',
			'tickets-alt',
			'money',
			'smiley',
			'thumbs-up',
			'thumbs-down',
			'layout',
			'paperclip',
		),
	),
);

?>
<div
	id="project-icon-dashicon-<?php echo esc_attr( $pid ); ?>-content"
	class="sui-tab-content sui-tab-boxed wpmudev-dashicon-picker <?php echo 'dashicon' === $settings['icon_type'] ? 'active' : ''; ?>"
	data-tab-content="project-icon-dashicon-<?php echo esc_attr( $pid ); ?>-content"
	data-search-id="wpmudev-dashicon-<?php echo esc_attr( $pid ); ?>-search-field"
	data-input-id="wpmudev-dashicon-<?php echo esc_attr( $pid ); ?>-input"
>
	<div class="sui-form-field">
		<label
			for="wpmudev-dashicon-search-field"
			id="wpmudev-dashicon-<?php echo esc_attr( $pid ); ?>-search-field-label"
			class="sui-screen-reader-text"
		></label>
		<div class="sui-control-with-icon">
				<span
					class="sui-icon-magnifying-glass-search"
					aria-hidden="true"
				></span>
			<input
				type="text"
				placeholder="<?php esc_attr_e( 'Search icon', 'wpmudev' ); ?>"
				class="sui-form-control"
				id="wpmudev-dashicon-<?php echo esc_attr( $pid ); ?>-search-field"
				aria-labelledby="wpmudev-dashicon-<?php echo esc_attr( $pid ); ?>-search-field-label"
			/>
		</div>
	</div>
	<div class="wpmudev-dashicon-picker-icons">
		<input
			type="hidden"
			id="wpmudev-dashicon-<?php echo esc_attr( $pid ); ?>-input"
			name="labels_config[<?php echo esc_attr( $pid ); ?>][icon_class]"
			value="<?php echo esc_attr( $settings['icon_class'] ); ?>"
		/>
		<?php foreach ( $dashicons as $group ) : ?>
			<div class="wpmudev-dashicon-picker-group">
				<label class="sui-label">
					<?php echo esc_html( $group['title'] ); ?>
				</label>
				<div class="wpmudev-dashicon-picker-group-inner">
					<?php foreach ( $group['icons'] as $class ) : ?>
						<span
							data-icon="<?php echo esc_attr( $class ); ?>"
							class="wpmudev-dashicons dashicons dashicons-<?php echo esc_attr( $class ); ?> <?php echo esc_attr( $settings['icon_class'] ) === $class ? 'active' : ''; ?>"
						>
							</span>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
