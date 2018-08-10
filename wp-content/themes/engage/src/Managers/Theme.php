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

		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );

		add_theme_support( 'menus' );
		add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
		add_filter( 'timber_context', array( $this, 'addToContext' ) );
		add_filter('body_class', [$this, 'bodyClass']);

		// images
		add_image_size('featured-post', 510, 310, true);
		add_image_size('featured-image', 600, 0, false);
		
		$this->cleanup();


		if(!is_admin()) {
			add_action( 'init', array( $this, 'enqueue_styles' ) );
			add_action( 'init', array( $this, 'enqueue_scripts' ) );
		}
		
	}

	
	public function enqueue_styles() {
		wp_enqueue_style('google/LibreFont', 'https://fonts.googleapis.com/css?family=Libre+Franklin:400,700', false, null);
		wp_enqueue_style('engage/css', get_stylesheet_directory_uri().'/dist/css/app.css', false, null);
  	}


	public function enqueue_scripts() {
		if (is_single() && comments_open() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}

		wp_enqueue_script('engage/css', get_stylesheet_directory_uri().'/dist/js/app.js', false, false);
	}

	public function addToContext( $context ) {
		// $context['stuff'] = 'I am a value set in your functions.php file';
		// $context['notes'] = 'These values are available everytime you call Timber::get_context();';
		$context['mainMenu'] = new \Timber\Menu('main-menu');
		$context['secondaryMenu'] = new \Timber\Menu('secondary-menu');
		$context['quickLinks'] = new \Timber\Menu('quick-links');
		$context['site'] = new \Timber\Site();
		return $context;
	}

    public function bodyClass($classes) {
    	$vertical = false;

    	if($_GET['vertical']) { 
    		$vertical = get_term_by('slug', $_GET['vertical'], 'verticals');
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