<?php
/*
* Kicks off site loading and adds in all resources
*/
namespace Engage\Managers;

class Theme {
	protected $managers = [];
	function __construct($managers) {
		foreach($managers as $manager) {
			$manager->run();
		}
		
		// add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		
		add_theme_support( 'menus' );
		add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );
		
		/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
		add_theme_support( 'title-tag' );
		add_filter( 'timber/context', [ $this, 'addToContext' ] );
		add_filter('body_class', [$this, 'bodyClass']);
		
		// images
		add_image_size('featured-post', 510, 310, true);
		add_image_size('featured-image', 600, 0, false);
		add_image_size('carousel-image', 1280, 720, true);
		add_image_size('small', 100, 0, false);
		
		add_action('widgets_init', [$this, 'widgetsInit']);
		
		$this->cleanup();
		
		
		// Only add styles and scripts on the site, not in the admin panel
		if(!is_admin()) {
			add_action( 'wp_enqueue_scripts', [$this, 'enqueueStyles'] );
			add_action( 'wp_head', [$this, 'enqueueScripts'] );
			// for removing styles
			add_action( 'wp_print_styles', [$this, 'dequeueStyles'], 100 );
			// TODO Preload critical main navigation images
			add_action('wp_head', function () {
				echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/img/brandbar-logo.webp" as="image" type="image/webp">';
				echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/img/brandbar-logo-2.webp" as="image" type="image/webp">';
			});
		} else {
			add_action( 'admin_init', [$this, 'enqueueStylesEditor'] );
		}
		
	}
	/**
	* Register sidebars
	*/
	public function widgetsInit() {
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
			'before_widget' => '<section class="widget %1$s %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget__title">',
			'after_title'   => '</h3>'
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
	
	// public function enqueueStyles() {
	// 	wp_enqueue_style('google/LibreFont', 'https://fonts.googleapis.com/css?family=Libre+Franklin:400,700', false, null);
	// 	wp_enqueue_style('google/AntonFont', 'https://fonts.googleapis.com/css?family=Anton:400', false, null);
	// 	wp_enqueue_style('engage/css', get_stylesheet_directory_uri().'/dist/css/app.css', false, null);
	// }

	public function enqueueStyles() {
		if (!is_admin()) {
        // Add preload for Google Fonts
        add_action('wp_head', function() {
					echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;700&display=swap" as="style" crossorigin="anonymous" onload="this.onload=null;this.rel=\'stylesheet\'">';
					// Fallback for users with JavaScript disabled.
					echo '<noscript><link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;700&display=swap" rel="stylesheet"></noscript>';
			});
			// wp_register_style('google_fonts', '//fonts.googleapis.com/css?family=Libre+Franklin:400,700|Anton:400', array(), null, 'all');
			wp_register_style('google_fonts', '//fonts.googleapis.com/css2?family=Anton&family=Libre+Franklin:wght@400;700&display=swap', array(), null, 'all');
			wp_enqueue_style('google_fonts');
			wp_enqueue_style('engage_css', get_stylesheet_directory_uri().'/dist/css/app.css', false, null);
		}
	}
	
	public function enqueueStylesEditor() {
		if (is_admin()) {
			add_editor_style('/dist/css/editor-style.css');
		}
	}
	
	public function dequeueStyles() {
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
	
	
	public function enqueueScripts() {
		if (is_single() && comments_open() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}
		
		// We could only enqueue this for homepage, chart, and quiz pages if we'd like
		wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js');
		
		if(is_front_page()) {
			wp_enqueue_script('flickity/js', get_stylesheet_directory_uri(). '/dist/js/flickity.js', ['jquery']);
			wp_enqueue_script('homepage/js', get_stylesheet_directory_uri().'/dist/js/homepage.js', ['jquery'], false, false);
		}
		
		if(is_singular('research')) {
			wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js');
			wp_enqueue_script('Chart/js', get_stylesheet_directory_uri().'/dist/js/Chart.bundle.min.js', ['jquery'], false, false);
		}
		
		wp_enqueue_script('engage/js', get_stylesheet_directory_uri().'/dist/js/app.js', [], false, true);
	}
	
	public function addToContext( $context ) {
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
		return $context;
	}
	
	public function bodyClass($classes) {
		$vertical = get_query_var('verticals');
		if($vertical) {
			$vertical = get_term_by('slug', $vertical, 'verticals');
		} elseif(is_singular()) {
			$verticals = get_the_terms(get_the_ID(), 'verticals');
			if($verticals) {
				$vertical = $verticals[0];
			}
		} elseif(is_tax('verticals')) {
			$vertical = get_queried_object();
		}
		
		if($vertical) {
			$classes[] = 'vertical--'.$vertical->slug;
		}
		
		
		// if we're on a vertical base page (/vertical/{{verticalTerm}})
		if(get_query_var('vertical_base')) {
			$classes[] = 'vertical-base';
		}
		
		return $classes;
	}
	
	public function cleanup() {
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
}
