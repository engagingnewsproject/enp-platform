<?php
/*
* Kicks off site loading and adds in all resources
*/

namespace Engage\Managers;

class Theme
{
	protected $managers = [];
	function __construct($managers)
	{
		foreach ($managers as $manager) {
			$manager->run();
		}

		// add_theme_support( 'post-formats' );
		add_theme_support('post-thumbnails');

		add_theme_support('menus');
		add_theme_support('html5', array('comment-list', 'comment-form', 'search-form', 'gallery', 'caption'));
		// Add default posts and comments RSS feed links to head.
		add_theme_support('automatic-feed-links');
		add_action('acf/init', [$this, 'addOptionsPage']);
		/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
		add_theme_support('title-tag');
		add_filter('timber/context', [$this, 'addToContext']);
		add_filter('body_class', [$this, 'bodyClass']);
		add_filter('wp_nav_menu_args', [$this, 'modifyNavMenuArgs']);

		// Enhance image widget accessibility
		add_filter('image_widget_image_html', [$this, 'enhance_image_widget_accessibility'], 10, 2);
		add_filter('render_block_core/image', [$this, 'enhance_image_block_accessibility'], 10, 2);

		// images
		add_image_size('featured-image', 600, 0, false); // Featured image
		add_image_size('carousel-image', 1280, 0, false); // Homepage slider image
		add_image_size('grid-large', 404, 240, true); // Tile grid image
		// Others not used
		// add_image_size('featured-post', 510, 310, true); // use 'medium' instead
		// add_image_size('small', 100, 0, false); // not used
		
		add_filter('intermediate_image_sizes_advanced', [$this, 'disable_large_wp_image_sizes']);
		add_filter('big_image_size_threshold', '__return_false');
		add_filter('image_size_names_choose', [$this, 'remove_image_size_options']);
		add_action('widgets_init', [$this, 'widgetsInit']);

		// Add these new optimizations
		add_filter('wp_calculate_image_srcset', [$this, 'limit_srcset_sizes'], 10, 2);

		$this->cleanup();


		// Only add styles and scripts on the site, not in the admin panel
		if (!is_admin()) {
			add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
			add_action('wp_head', [$this, 'enqueueScripts']);
			add_action('wp_head', [$this, 'preloadFirstSliderImage']);
			// for removing styles
			add_action('wp_print_styles', [$this, 'dequeueStyles'], 100);
			// TODO Preload critical main navigation images
			add_action('wp_head', function () {
				echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/img/brandbar/brandbar-logo-ut.webp" as="image" type="image/webp">';
				echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/img/brandbar/brandbar-logo-moody.webp" as="image" type="image/webp">';
				echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/img/logo/center-for-media-engagement.webp" as="image" type="image/webp">';
				// Preload dots.svg background image
				echo '<link rel="preload" fetchpriority="high" href="' . get_template_directory_uri() . '/assets/img/dots.webp" as="image" type="image/webp" />';
			});
		} else {
			add_action('admin_init', [$this, 'enqueueStylesEditor']);
			add_filter('manage_pages_columns', [$this, 'addTemplateColumn']);
			add_action('manage_pages_custom_column', [$this, 'displayTemplateColumn'], 10, 2);
			add_filter('manage_edit-page_sortable_columns', [$this, 'sortableTemplateColumn']);
		}
		
	}
	/**
	 * Disables WordPress's redundant image sizes while maintaining high quality options
	 * 
	 * @param array $sizes Array of image sizes
	 * @return array Modified array of image sizes
	 */
	public function disable_large_wp_image_sizes($sizes) {
		// Keep only the absolute minimum sizes we need
		$allowed_sizes = [
			'thumbnail',    // 150x150 - for admin thumbnails
			'medium',       // 300x300 - for general use
			'carousel-image', // 1280x720 - for homepage slider
			'grid-large'    // 404x240 - for grid layouts
		];
		
		// Remove all sizes except our allowed ones
		foreach ($sizes as $size => $settings) {
			if (!in_array($size, $allowed_sizes)) {
				unset($sizes[$size]);
			}
		}
		
		return $sizes;
	}
	/**
	 * Removes redundant image sizes from the image size selection dropdown
	 * while keeping high quality options available
	 * 
	 * @param array $sizes Array of image size options
	 * @return array Modified array of image size options
	 */
	public function remove_image_size_options($sizes) {
		unset($sizes['medium_large']);
		unset($sizes['large']);
		unset($sizes['1536x1536']);
		return $sizes;
	}
	/**
	 * Register sidebars
	 */
	public function widgetsInit()
	{
		register_sidebar([
			'name'          => __('Primary', 'sage'),
			'id'            => 'sidebar-primary',
			'before_widget' => '<section class="widget %1$s %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		]);

		register_sidebar([
			'name'          => __('Research Sidebar', 'sage'),
			'id'            => 'sidebar-research',
			'before_widget' => '<section class="widget %1$s %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		]);

		register_sidebar([
			'name'          => __('Homepage Hero', 'sage'),
			'id'            => 'sidebar-home',
			'before_widget' => '<section class="widget %1$s %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		]);

		register_sidebar([
			'name'          => __('Top Footer', 'sage'),
			'id'            => 'top-footer',
			'before_widget' => '<section class="widget %1$s %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		]);

		register_sidebar([
			'name'          => __('Left Footer', 'sage'),
			'id'            => 'left-footer',
			'before_widget' => '<section class="widget %1$s %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		]);

		register_sidebar([
			'name'          => __('Center Footer', 'sage'),
			'id'            => 'center-footer',
			'before_widget' => '<section class="widget %1$s %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		]);

		register_sidebar([
			'name'          => __('Right Footer', 'sage'),
			'id'            => 'right-footer',
			'before_widget' => '<nav class="widget %1$s %2$s" role="navigation" aria-label="Footer Navigation"><div class="menu-container" role="presentation">',
			'after_widget'  => '</div></nav>',
			'before_title'  => '<h3 class="widget__title" id="footer-nav-title">',
			'after_title'   => '</h3>',
			'show_in_rest' => true,
		]);

		register_sidebar([
			'name'          => 'Newsletter',
			'id'            => 'newsletter',
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '<h4 class="widget__title">',
			'after_title'   => '</h4>',
		]);

		register_sidebar([
			'name'          => __('MEI Sidebar', 'sage'),
			'id'            => 'mei-sidebar',
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
		]);
	}

	/**
	 * Add ACF Options Page
	 */
	public function addOptionsPage()
	{
		acf_add_options_page(array(
			'page_title' => 'Site Options',
			'menu_slug'  => 'site-options',
			'position'   => '',
			'redirect'   => false,
			'menu_icon'  => array(
				'type'  => 'dashicons',
				'value' => 'dashicons-admin-generic',
			),
			'autoload'   => true,
			'icon_url'   => 'dashicons-admin-generic',
		));
	}

	public function preloadFirstSliderImage()
	{
		if (is_front_page()) {
			$slider_posts = get_field('slider_posts', get_option('page_on_front'));
			if (!empty($slider_posts) && is_array($slider_posts)) {
				// Get the first post ID
				$first_post_id = $slider_posts[0]; // First post ID

				// Get the featured image URL for the first post
				$thumbnail_url = get_the_post_thumbnail_url($first_post_id, 'carousel-image'); // Replace 'carousel-image' with your image size

				if ($thumbnail_url) {
					// Add the preload link
					echo '<link rel="preload" as="image" href="' . esc_url($thumbnail_url) . '" />';
				} else {
					error_log('No featured image found for post ID: ' . $first_post_id);
				}
			} else {
				error_log('No posts found in the ACF relationship field.');
			}
		}
	}

	public function enqueueStyles()
	{
		if (!is_admin()) {
			// Add preload for Google Fonts
			add_action('wp_head', function () {
				echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;700&display=swap" as="style" crossorigin="anonymous" onload="this.onload=null;this.rel=\'stylesheet\'">';
				echo '<noscript><link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;700&display=swap" rel="stylesheet"></noscript>';
			});
			
			wp_register_style('google_fonts', '//fonts.googleapis.com/css2?family=Anton&family=Libre+Franklin:wght@400;700&display=swap', array(), null, 'all');
			wp_enqueue_style('google_fonts');
			
			wp_enqueue_style('engage_css', mix('css/app.css'), false, null);
		}
	}

	public function enqueueStylesEditor()
	{
		if (is_admin()) {
			// Editor styles
			add_editor_style(mix('css/editor-style.css'));
		}
	}

	public function dequeueStyles()
	{
		// remove styles
		if (!is_admin()) {
			wp_dequeue_style('wptt_front');
			wp_deregister_style('wptt_front');
			wp_dequeue_style('safe-svg-svg-icon-style');
			wp_deregister_style('safe-svg-svg-icon-style');
			wp_dequeue_style('nf-font-awesome');
			wp_deregister_style('nf-font-awesome');
			wp_dequeue_style('rank-math-toc-block-style');
			wp_deregister_style('rank-math-toc-block-style');
			wp_dequeue_style('classic-theme-styles');
			wp_deregister_style('classic-theme-styles');
		}
	}

	public function enqueueScripts()
	{
		$footer_defer = array(
			'in_footer' => true,
			'strategy'  => 'defer',
		);

		if (is_single() && comments_open() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}

		wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js');

		// Homepage JS (if used)
		if (is_front_page()) {
			wp_enqueue_script('homepage/js', mix('js/homepage.js'), ['jquery'], null, false);
		}

		if (is_singular('research')) {
			wp_enqueue_script('Chart/js', get_stylesheet_directory_uri() . '/dist/js/Chart.bundle.min.js', ['jquery'], false, false);
		}

		wp_enqueue_script('engage/js', mix('js/app.js'), [], null, $footer_defer);

	}

	public function addToContext($context)
	{
		// $context['stuff'] = 'I am a value set in your functions.php file';
		// $context['notes'] = 'These values are available everytime you call Timber::context();';
		$context['mainMenu'] = \Timber::get_menu('main-menu');
		$context['secondaryMenu'] = \Timber::get_menu('secondary-menu');
		$context['quickLinks'] = \Timber::get_menu('quick-links');
		$context['searchMenu'] = \Timber::get_menu('search-menu');
		$context['site'] = new \Timber\Site();
		$context['footerMenu'] = \Timber::get_menu('footer-menu');
		$context['topFooterWidgets'] = \Timber::get_widgets('top-footer');
		$context['leftFooterWidgets'] = \Timber::get_widgets('left-footer');
		$context['centerFooterWidgets'] = \Timber::get_widgets('center-footer');
		$context['rightFooterWidgets'] = \Timber::get_widgets('right-footer');
		$context['newsletter'] = \Timber::get_widgets('newsletter');
		$context['meiSidebar'] = \Timber::get_widgets('mei-sidebar');
		// Add ACF options to context
		$context['options'] = get_fields('option');
		return $context;
	}

	public function bodyClass($classes)
	{
		// No vertical-specific body classes needed anymore.
		return $classes;
	}

	// Add the 'Template' column to the Pages list
	public function addTemplateColumn($columns)
	{
		$columns['template'] = __('Template');
		return $columns;
	}

	// Display the page template name in the 'Template' column
	public function displayTemplateColumn($column, $post_id)
	{
		if ($column === 'template') {
			$template = get_page_template_slug($post_id); // Get the page template slug
			if (!$template) {
				echo __('Default Template'); // If no template is set
			} else {
				// Fetch all available templates
				$templates = wp_get_theme()->get_page_templates();

				// Match the file name to the human-readable name
				$template_name = $templates[$template] ?? $template;

				// Display the human-readable name or fallback to the file name
				echo esc_html($template_name);
			}
		}
	}

	// Make the 'Template' column sortable
	public function sortableTemplateColumn($columns)
	{
		$columns['template'] = 'template';
		return $columns;
	}

	public function cleanup()
	{
		remove_action('template_redirect', 'rest_output_link_header', 11, 0);
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
		remove_action('wp_head', 'feed_links', 2);
		remove_action('wp_head', 'feed_links_extra', 3);
		remove_action('wp_head', 'noindex', 1);
		remove_action('wp_head', 'parent_post_rel_link');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_head', 'rel_canonical');
		remove_action('wp_head', 'rest_output_link_wp_head');
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'start_post_rel_link');
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'wp_oembed_add_discovery_links');
		remove_action('wp_head', 'wp_oembed_add_host_js');
		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'wp_resource_hints', 2);
		remove_action('wp_print_styles', 'print_emoji_styles');
	}

	// Limit srcset sizes to prevent too many variations
	public function limit_srcset_sizes($sources, $size_array) {
		// Only keep sources up to 2000px wide
		foreach ($sources as $width => $source) {
			if ($width > 2000) {
				unset($sources[$width]);
			}
		}
		return $sources;
	}

	/**
	 * Modify navigation menu arguments for better accessibility
	 *
	 * @param array $args The menu arguments
	 * @return array Modified menu arguments
	 */
	public function modifyNavMenuArgs($args) {
		// Only modify footer menu
		if (strpos($args['menu_class'], 'menu-footer-menu') !== false) {
			// Add ARIA attributes to the menu ul element
			$args['container'] = 'div';
			$args['container_class'] = 'menu-container';
			$args['items_wrap'] = '<ul id="%1$s" class="%2$s" role="menu">%3$s</ul>';
			
			// Clean up menu item classes and add proper ARIA roles
			add_filter('nav_menu_css_class', function($classes, $item, $args, $depth) {
				// Keep only essential classes
				$essential_classes = array_filter($classes, function($class) {
					return strpos($class, 'menu-item') === 0;
				});
				return array_merge(['menu-item'], $essential_classes);
			}, 10, 4);

			// Add role="menuitem" to li elements
			add_filter('nav_menu_item_attributes', function($atts, $item, $args, $depth) {
				$atts['role'] = 'menuitem';
				return $atts;
			}, 10, 4);

			// Clean up text nodes
			add_filter('nav_menu_item_title', function($title, $item, $args, $depth) {
				return trim($title);
			}, 10, 4);
		}
		
		return $args;
	}

	/**
	 * Enhances accessibility of image widgets by adding proper ARIA attributes
	 * and ensuring alt text is present
	 *
	 * @param string $html The image widget HTML
	 * @param array $instance The widget instance settings
	 * @return string Modified HTML
	 */
	public function enhance_image_widget_accessibility($html, $instance) {
		// Add role="none" to figure element and ensure alt text is present
		$html = preg_replace(
			'/<figure class="([^"]*)"/',
			'<figure $1 role="none">',
			$html
		);

		return $html;
	}

	/**
	 * Enhances accessibility of image blocks by adding role="none" to figure elements
	 *
	 * @param string $block_content The block content about to be rendered
	 * @param array  $block         The full block, including name and attributes
	 * @return string Modified block content
	 */
	public function enhance_image_block_accessibility($block_content, $block) {
		if (strpos($block_content, '<figure') !== false) {
			$block_content = preg_replace(
				'/<figure([^>]*)>/',
				'<figure$1 role="none">',
				$block_content
			);
		}
		return $block_content;
	}
}
