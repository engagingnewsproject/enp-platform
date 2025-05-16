<?php
/**
 * Modifications to the login process. Provides customization for the WordPress login process, including logo styling, 
 * URL replacements in the navigation menu, and adding Google Analytics to the login page
 */

// Define a namespace for the class to avoid conflicts
namespace Engage\Managers;

class Login {

	public function __construct() {
	}

	// The main method to run the actions and filters
	public function run() {
		// Add custom actions and filters for login customization
		add_action( 'login_enqueue_scripts', [$this, 'loginLogo']);
		add_filter('login_headerurl', [$this, 'loginLogoURL']);
		add_action( 'login_enqueue_scripts', [$this, 'enqueueScript']);

		// Redirects
		add_action('login_redirect', [$this, 'redirect_to_quiz_dashboard'], 10, 1);
		add_action('registration_redirect', [$this, 'redirect_to_quiz_dashboard'], 10, 1);
		add_action('template_redirect', [$this, 'redirect_to_quiz_dashboard_from_marketing']);

		// Add a filter to replace specific menu item URLs with dynamic links
		add_filter( 'wp_setup_nav_menu_item', [$this, 'enp_setup_nav_menu_item' ]);

		// Add this new line to remove upload capability
		add_action('init', [$this, 'remove_upload_capability']);
	}
	
	// redirect to quiz creator dashboard on login
	public function redirect_to_quiz_dashboard($redirect_to) {
		// Include the file containing the is_plugin_active function
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}	
		
		if(is_plugin_active( 'enp-quiz/enp_quiz.php' ) && defined( 'ENP_QUIZ_DASHBOARD_URL' )) {
			$redirect_to = ENP_QUIZ_DASHBOARD_URL.'user';
		}
		return $redirect_to;
	}

	// redirect to quiz dashboard if logged in and trying to get to the quiz creator
		public function redirect_to_quiz_dashboard_from_marketing() {
			
			// Include the file containing the is_plugin_active function
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}	
			
			$plugin_active = is_plugin_active( 'enp-quiz/enp_quiz.php' );
			$logged_in = is_user_logged_in();
			$on_quiz_creator = is_page( 'quiz-creator' );
			$dashboard_defined = defined( 'ENP_QUIZ_DASHBOARD_URL' );

			if ( $plugin_active && $logged_in && $on_quiz_creator && $dashboard_defined ) {
					$redirect_to = ENP_QUIZ_DASHBOARD_URL . 'user';
					wp_redirect( $redirect_to );
					exit;
			}
		}


	/** 
	 * Main code to replace #keyword# with correct links
	 * nonce ect
	 */
	public function enp_setup_nav_menu_item( $item ) {
		global $pagenow;
		
		// Check if not in nav-menus.php, not doing AJAX, and URL contains #enp
		if ( $pagenow != 'nav-menus.php' && ! defined( 'DOING_AJAX' ) && isset( $item->url ) && strstr( $item->url, '#enp' ) != '' ) {
			$item_url = substr( $item->url, 0, strpos( $item->url, '#', 1 ) ) . '#';
			
			// Switch based on the item URL to replace specific keywords
			switch ( $item_url ) {

				case '#enplogin#' :     $item->url = is_user_logged_in() ? wp_logout_url() : wp_login_url();
										$item->title = is_user_logged_in() ? 'Log out' : 'Login';
				// break;
				// case '#enpquizcreator#' :   $item->url = is_user_logged_in() ? ENP_QUIZ_DASHBOARD_URL.'user' : site_url('quiz-creator');
				//                             $item->title = 'Quiz Creator';

				break;

			}
			$item->url = esc_url( $item->url );
		}
		return $item;
	}

	/*
	 * Customize Login Logo
	 */
	public function loginLogo() { 
		// Output custom styles for login page
		?>
		<style type="text/css">
			/* ... Custom CSS for the login page styling ... */
			body {
				background: #ecf5f2!important;
			}
			body:before {
				content: '';
				background: #fff;
				position: absolute;
				height: 100px;
				top: -100px;
				left: 0;
				right: 0;
				transform: skewY(-8deg);
				z-index: -1;
			}
			body:after {
				content: '';
				background: #fff;
				position: absolute;
				height: 100px;
				top: -100px;
				left: 0;
				right: 0;
				transform: skewY(8deg);
				z-index: -1;
			}
			.login h1 a {
				background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/assets/img/cme-logo.png) !important;
				background-size: 315px !important;
				background-position: center center !important;
				width: 315px!important;
			}
		</style>
		<?php 
	}
	
	// Method to set the login logo URL
	public function loginLogoURL() {
		return home_url();
	}

	/**
	 * Add Google Analytics to Login page
	 */
	function enqueueScript() {
	// Output JavaScript code to enqueue Google Analytics script on the login page
	  ?>
	  <script>
		// ... Google Analytics script ...
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-52471115-4', 'auto');
		ga('send', 'pageview');
	  </script>

	  <?php
	}
	
	// Add this method to the Login class
	public function remove_upload_capability() {
		$subscriber = get_role('subscriber');
		if ($subscriber) {
			$subscriber->remove_cap('upload_files');
		}
	}
}