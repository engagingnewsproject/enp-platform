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
		add_filter( 'timber_context', [ $this, 'addToContext' ] );
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
        'name'          => __('Footer', 'sage'),
        'id'            => 'sidebar-footer',
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
    }

	public function enqueueStyles() {
		wp_enqueue_style('google/LibreFont', 'https://fonts.googleapis.com/css?family=Libre+Franklin:400,700', false, null);
		wp_enqueue_style('engage/css', get_stylesheet_directory_uri().'/dist/css/app.css', false, null);
    // Add the lighstlider CSS on the homepage
    if(is_front_page()) {
    	wp_enqueue_style('lightslider/css', get_stylesheet_directory_uri().'/dist/css/lightslider.css', false, null);
		}
  }

  public function dequeueStyles() {
      // twitter plugin styles
      wp_dequeue_style('wptt_front');
      wp_deregister_style('wptt_front');
  }


	public function enqueueScripts() {
		if (is_single() && comments_open() && get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
    }

    // We could only enqueue this for homepage, chart, and quiz pages if we'd like
    wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js');

		if(is_front_page()) {
      wp_enqueue_script('lightslider/js', get_stylesheet_directory_uri().'/dist/js/lightslider.js', ['jquery'], false, false);
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
		// $context['notes'] = 'These values are available everytime you call Timber::get_context();';
		$context['mainMenu'] = new \Timber\Menu('main-menu');
		$context['secondaryMenu'] = new \Timber\Menu('secondary-menu');
		$context['quickLinks'] = new \Timber\Menu('quick-links');
		$context['site'] = new \Timber\Site();
    $context['footerMenu'] = new \Timber\Menu('footer-menu');
    $context['footerWidgets'] = \Timber::get_widgets('sidebar-footer');
		if (is_singular('research') || is_singular('page')) {
			$context['newsletter'] = \Timber::get_widgets('newsletter');
		}
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
