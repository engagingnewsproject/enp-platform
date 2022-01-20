<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Helper\HTTP;
use Calotes\Helper\Route;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller2;
use Calotes\Component\Response;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\Permission;
use WP_User;

/**
 * This going to mask the login url & signup url and prevent directly access in those cases:
 * 1. visit wp-login.php & signup.php or any url with those as suffix.
 * However, we will expose the mask url in:
 * 1. Every login & signup links on frontend, if normal user click on the link, they shouldn't get block
 * 2. Every email sends from WP which contains the login URL, should not get block.
 *
 * Instead of detect if the user logged in or not, we should have a hash of user id and salt for cookies,
 * this way when user direct from other source like back from HUB or so, they won't get lockout.
 *
 * The condition for trigger is when user visit the right mask login, then we will generate.
 *
 * Class Mask_Login
 * @package WP_Defender\Controller
 */
class Mask_Login extends Controller2 {
	use IO, Permission;

	/**
	 * Use for cache.
	 * @var \WP_Defender\Model\Setting\Mask_Login
	 */
	protected $model;

	/**
	 * @var \WP_Defender\Component\Mask_Login
	 */
	protected $service;

	/**
	 * @var array
	 */
	protected $compatibility_notices = array();

	public function __construct() {
		add_filter( 'wp_defender_advanced_tools_data', array( &$this, 'script_data' ) );
		// Internal cache, so we don't need to query many times.
		$this->model   = wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class );
		$this->service = wd_di()->get( \WP_Defender\Component\Mask_Login::class );
		$this->register_routes();

		if ( $this->get_model()->is_active() ) {
			$auth_component = wd_di()->get( \WP_Defender\Component\Two_Fa::class );
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$is_jetpack_sso = $auth_component->is_jetpack_sso();
			$is_tml         = $auth_component->is_tml();
			if ( ! $is_jetpack_sso && ! $is_tml ) {
				// Monitor wp-admin, wp-login.php.
				add_action( 'init', array( &$this, 'handle_login_request' ), 99 );
				add_filter( 'wp_redirect', array( &$this, 'filter_wp_redirect' ), 10 );
				// Filter site_url & network_site_url so people won't get block screen.
				add_filter( 'site_url', array( &$this, 'filter_site_url' ), 100, 2 );
				add_filter( 'network_site_url', array( &$this, 'filter_site_url' ), 100, 2 );
				// If this is enabled, then we should filter all the email links.
				add_filter( 'wp_mail', array( &$this, 'replace_login_url_in_email' ), 10 );
				// For prevent admin redirect.
				remove_action( 'template_redirect', 'wp_redirect_admin_locations' );
				// If Pro site is activated and user email is not defined, we need to update the email to match the new login URL.
				add_filter( 'update_welcome_email', array( &$this, 'update_welcome_email_prosite_case', 10, 6 ) );
				// Change password link for new user.
				add_filter( 'wp_new_user_notification_email', array( &$this, 'change_new_user_notification_email' ), 10, 3 );
				add_filter( 'lostpassword_redirect', array( &$this, 'change_lostpassword_redirect' ), 10 );
				// Log links in email.
				add_filter( 'report_email_logs_link', array( &$this, 'update_report_logs_link', 10, 2 ) );
				if ( class_exists( 'bbPress' ) ) {
					add_filter( 'bbp_redirect_login', array( &$this, 'make_sure_wpadmin_after_login' ), 10, 3 );
				}

				if ( 'flywheel' === \WP_Defender\Component\Security_Tweaks\Servers\Server::get_current_server() ) {
					if ( ! is_user_logged_in() ) {
						add_action( 'login_form_rp', array( $this, 'handle_password_reset' ) );
						add_action( 'login_form_resetpass', array( $this, 'handle_password_reset' ) );
					}
					add_filter( 'retrieve_password_message', array( &$this, 'flywheel_change_password_message' ), 10, 4 );
				} else {
					// Change password link for exist user.
					add_filter( 'retrieve_password_message', array( &$this, 'change_password_message' ), 10, 4 );
				}
			} else {
				if ( $is_jetpack_sso ) {
					$this->compatibility_notices[] = __( "We've detected a conflict with Jetpack's Wordpress.com Log In feature. Please disable it and return to this page to continue setup.", 'wpdef' );
				}
				if ( $is_tml ) {
					$this->compatibility_notices[] = __( "We've detected a conflict with Theme my login. Please disable it and return to this page to continue setup.", 'wpdef' );
				}
			}
		}
	}

	/**
	 * For fixing the issue when bbPress enable, after login, users redirect to home.
	 *
	 * @param string $url
	 * @param string $raw_url
	 * @param object $user
	 *
	 * @return string
	 */
	public function make_sure_wpadmin_after_login( $url, $raw_url, $user ) {
		if ( home_url() === $url ) {
			$url = admin_url();
		}

		return apply_filters( 'defender_redirect_login', $url, $raw_url, $user );
	}

	/**
	 * We need to filter emails and replace the normal login URL with masked one.
	 *
	 * @param $attrs
	 *
	 * @return mixed
	 */
	public function replace_login_url_in_email( $attrs ) {
		if ( ! is_array( $attrs ) || ! isset( $attrs['message'] ) ) {
			return $attrs;
		}

		$message  = $attrs['message'];
		$site_url = str_replace( '/', '\/', HTTP::strips_protocol( site_url() ) );
		$pattern  = '/https?:\/\/' . $site_url . '\/wp-login\.php?[^\s]+/';

		if ( preg_match_all( $pattern, $message, $matches ) ) {
			foreach ( $matches as $match ) {
				foreach ( $match as $url ) {
					$query = wp_parse_url( $url, PHP_URL_QUERY );
					$query = null !== $query ? $query : '';
					parse_str( $query, $queries );
					if ( is_array( $queries ) && count( $queries ) ) {
						$new_url = add_query_arg( $queries, $this->get_model()->get_new_login_url() );
					} else {
						$new_url = $this->get_model()->get_new_login_url();
					}
					$message = str_replace( $url, $new_url, $message );
				}
			}
		}
		$attrs['message'] = $message;

		return $attrs;
	}

	/**
	 * Show login page.
	 */
	public function show_login_page() {
		global $error, $interim_login, $action, $user_login, $user, $redirect_to;
		require_once ABSPATH . 'wp-login.php';
		die;
	}

	/**
	 * If it is request to wp-admin, wp-login.php and similar slugs, we block for sure,
	 * if no,then follow the wp flown.
	 *
	 * @return mixed|void
	 */
	public function handle_login_request() {
		// Doesn't need to handle the login request for bots and crawlers.
		if ( $this->service->is_bot_request() ) {
			return;
		}
		// Need to check if the current request is for signup, login.
		// If it is not the slug, then we redirect to the 404 redirect, or 403 wp die.
		$requested_path               = $this->service->get_request_path();
		$requested_path_without_slash = ltrim( $requested_path, '/' );
		if ( ! $requested_path_without_slash ) {
			return;
		}

		if ( '/' . ltrim( $this->get_model()->mask_url, '/' ) === $requested_path ) {
			// We need to redirect this one to wp-login and open it.
			return $this->show_login_page();
		}
		/**
		 * Allowed if:
		 * it's AJAX,
		 * the user is logged in,
		 * it's an admin post request.
		*/
		if (
			defined( 'DOING_AJAX' )
			|| is_user_logged_in()
			|| $this->is_allowed_path( $requested_path_without_slash )
		) {
			// Do nothing.
			return;
		}

		// If user is not logged in but login cookie is set.
		if ( isset( $_COOKIE[ LOGGED_IN_COOKIE ] ) && ! is_user_logged_in() ) {
			$user_id = wp_validate_auth_cookie( $_COOKIE[ LOGGED_IN_COOKIE ], 'logged_in' );

			if ( $user_id ) {
				// Cookie is valid so login the user.
				wp_set_current_user( $user_id );
				// Return from here because of valid user found.
				return;
			}
		}

		$ticket = HTTP::get( 'ticket', false );
		if ( false !== $ticket && $this->service->redeem_ticket( $ticket ) ) {
			// Allow to pass.
			return;
		}

		// If current is same then we show the login screen.
		if ( $this->service->is_land_on_masked_url( $this->model->mask_url ) ) {
			return $this->show_login_page();
		}

		// If it's the verification link to change Network Admin Email.
		$is_multisite = is_multisite();
		if (
			$is_multisite
			&& false !== strpos( parse_url( $requested_path, PHP_URL_QUERY ), 'network_admin_hash' )
		) {
			$logs_url = add_query_arg(
				'redirect_to',
				urlencode( $requested_path ),
				$this->get_model()->get_new_login_url()
			);
			wp_safe_redirect( $logs_url );
			die;
		}
		/**
		 * Block if it's:
		 * 1) no MU but there is an attempt to load the 'wp-signup.php' page,
		 * 2) from the list of forbidden slugs.
		*/
		if (
			( ! $is_multisite && 'wp-signup.php' === $requested_path_without_slash )
			|| $this->service->is_on_login_page( $requested_path_without_slash )
		) {
			// If they are here and the flow getting here, then just lock.
			return $this->maybe_lock();
		}
	}

	/**
	 * Store settings into db.
	 * @defender_route
	 */
	public function save_settings( Request $request ) {
		$data = $request->get_data_by_model( $this->model );
		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array_merge(
					array(
						'message' => __( 'Your settings have been updated.', 'wpdef' ),
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			// Merge stored data to avoid errors.
			array_merge( array( 'message' => $this->model->get_formatted_errors() ), $this->data_frontend() )
		);
	}

	/**
	 * Filter every admin/login URL to return the masked one.
	 *
	 * @param string $site_url The complete URL.
	 * @param string $path     The submitted path.
	 *
	 * @return string
	 */
	public function filter_site_url( $site_url, $path ) {
		return $this->alter_url( $site_url, $path );
	}

	/**
	 * @param string $location
	 *
	 * @return string
	 */
	public function filter_wp_redirect( $location ) {
		return $this->alter_url( $location, $location );
	}

	/**
	 * @param string $current_url
	 * @param string $path
	 *
	 * @return string
	 */
	public function alter_url( $current_url, $path = '' ) {
		// Doesn't need to alter the URL for bots.
		// We will not unveil the masked login URL for them, instead show default login URL.
		if ( $this->service->is_bot_request() ) {
			return $current_url;
		}

		if ( is_user_logged_in() && false === stripos( $current_url, 'wp-login.php' ) ) {
			// Do nothing.
			return $current_url;
		}

		if ( false !== stripos( $current_url, 'wp-login.php' ) ) {
			// This is URL go to old wp-login.php.
			$query = wp_parse_url( $current_url, PHP_URL_QUERY );
			$query = null !== $query ? $query : '';
			parse_str( $query, $params );

			return add_query_arg( $params, $this->get_model()->get_new_login_url( $this->get_site_url() ) );
		} else {
			// This case when admin map a domain into subsite, we need to update the new domain/masked-login into the list.
			if ( ! function_exists( 'get_current_screen' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/screen.php' );
			}
			$screen = get_current_screen();

			if ( ! is_object( $screen ) ) {
				return $current_url;
			}
			if ( 'sites-network' === $screen->id ) {
				// Case URLs inside sites list, need to check those with custom domain cause when it's redirect, it will require re-login.
				$requested_path = $this->service->get_request_path( $current_url );
				if ( '/wp-admin' === $requested_path ) {
					$current_domain = $_SERVER['HTTP_HOST'];
					$sub_domain     = parse_url( $current_url, PHP_URL_HOST );
					if ( ! empty( $sub_domain ) && false === stripos( $sub_domain, $current_domain ) ) {

						return $this->get_model()->get_new_login_url( $sub_domain );
					}
				}
			}
			/**
			 * Todo:
			 * add other condition ('my-sites' === $screen->id)
			 * create OTP key and link with the 'otp' arg inside
			 */
		}

		return $current_url;
	}

	/**
	 * Show the wp die screen for lockout, or redirect to defined URL.
	 */
	public function maybe_lock() {
		$forbidden_message = __( 'This feature is forbidden temporarily for security reason. Try login again.', 'wpdef' );

		if ( 'custom_url' === $this->get_model()->redirect_traffic && strlen( $this->get_model()->redirect_traffic_url ) ) {
			if ( 'url' === $this->get_model()->is_url_or_slug() ) {
				$redirect_url = wp_sanitize_redirect( $this->get_model()->redirect_traffic_url );
				$lp           = @parse_url( $redirect_url );

				// Give up if malformed URL.
				if ( false === $lp ) {
					wp_die( esc_html( $forbidden_message ) );
				}
				// If the URL is without scheme, e.g. example.com, then add 'http' protocol at the beginning of the URL.
				if ( ! isset( $lp['scheme'] ) && isset( $lp['path'] ) ) {
					$redirect_url = 'http://' . untrailingslashit( $redirect_url );
				}
				wp_redirect( $redirect_url );
			} else {
				wp_safe_redirect( home_url( $this->get_model()->redirect_traffic_url ) );
			}
			die;
		}

		if ( 'wp_page' === $this->get_model()->redirect_traffic ) {
			$id   = $this->get_model()->redirect_traffic_page_id;
			$post = get_post( $id );
			if ( is_object( $post ) ) {
				wp_safe_redirect( get_permalink( $post ) );
				exit;
			}
		}

		// Handle user profile email change request.
		$this->handle_email_change_request();

		wp_die( esc_html( $forbidden_message ) );
	}

	/**
	 * Safe way to get cached model.
	 *
	 * @return \WP_Defender\Model\Setting\Mask_Login
	 */
	private function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return new \WP_Defender\Model\Setting\Mask_Login();
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 * @throws \ReflectionException
	 */
	public function script_data( $data ) {
		$data['mask_login'] = $this->data_frontend();

		return $data;
	}

	/**
	 * Login redirect.
	 *
	 * @param string $url
	 * @param string $raw_url Raw url
	 * @param object $user User object
	 *
	 * @return string
	 */
	public function redirect_login( $url, $raw_url, $user ) {
		if ( home_url() === $url ) {
			$url = admin_url();
		}

		return apply_filters( 'defender_redirect_login', $url, $raw_url, $user );
	}

	/**
	 * @param null|int $blog_id
	 * @param string   $path
	 * @param null     $scheme
	 *
	 * @return string
	 */
	private function get_site_url( $blog_id = null, $path = '', $scheme = null ) {
		if ( empty( $blog_id ) || ! is_multisite() ) {
			$url = get_option( 'siteurl' );
		} else {
			switch_to_blog( $blog_id );
			$url = get_option( 'siteurl' );
			restore_current_blog();
		}

		$url = set_url_scheme( $url, $scheme );

		if ( $path && is_string( $path ) ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		if (
			is_plugin_active( 'wp-ultimo/wp-ultimo.php' )
			|| is_plugin_active_for_network( 'wp-ultimo/wp-ultimo.php' )
		) {
			return apply_filters( 'site_url', $url, $path, $scheme, $blog_id );
		} else {
			return $url;
		}
	}

	public function remove_settings() {}

	public function remove_data() {}

	/**
	 * @return array
	 */
	public function to_array() {
		$model                   = new \WP_Defender\Model\Setting\Mask_Login();
		list( $routes, $nonces ) = Route::export_routes( 'mask_login' );

		return array(
			'enabled'   => $model->enabled,
			'useable'   => $model->is_active(),
			'login_url' => $model->get_new_login_url(),
			'endpoints' => $routes,
			'nonces'    => $nonces,
		);
	}

	/**
	 * @return array
	 */
	function post_page_list() {
		$post_query  = new \WP_Query(
			array(
				'post_type'      => array( 'page', 'post' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$posts_array = $post_query->posts;

		return wp_list_pluck( $posts_array, 'post_title', 'ID' );
	}

	/**
	 * @return array
	 */
	function data_frontend() {
		// Don't use cache because wrong url is displayed for forbidden slugs.
		$model = new \WP_Defender\Model\Setting\Mask_Login();

		return array_merge(
			array(
				'model'         => $model->export(),
				'is_active'     => $model->is_active(),
				'new_login_url' => $model->get_new_login_url(),
				'notices'       => $this->compatibility_notices,
				'redirect_data' => $this->post_page_list(),
			),
			$this->dump_routes_and_nonces()
		);
	}

	public function import_data( $data ) {
		$model = $this->get_model();

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * @param $welcome_email
	 * @param $blog_id
	 * @param $user_id
	 * @param $password
	 * @param $title
	 * @param $meta
	 *
	 * @return string
	 */
	public function update_welcome_email_prosite_case( $welcome_email, $blog_id, $user_id, $password, $title, $meta ) {
		$url           = get_blogaddress_by_id( $blog_id );
		$welcome_email = str_replace(
			$url . 'wp-login.php',
			$this->get_model()->get_new_login_url( rtrim( $url, '/' ) ),
			$welcome_email
		);

		return $welcome_email;
	}

	/**
	 * @param string $logs_url
	 * @param string $email
	 *
	 * @return string
	 */
	public function update_report_logs_link( $logs_url, $email ) {
		$user = get_user_by( 'email', $email );
		if ( is_object( $user ) ) {
			$logs_url = $this->service->maybe_append_ticket_to_url( $logs_url );
		} else {
			$logs_url = add_query_arg( 'redirect_to', $logs_url, $this->get_model()->get_new_login_url() );
		}

		return $logs_url;
	}

	/**
	 * Change password URL for new user.
	 *
	 * @param string $wp_new_user_notification_email
	 * @param object $user
	 * @param string $blogname
	 *
	 * @return string
	 */
	public function change_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
		$wp_new_user_notification_email['message'] = str_replace(
			network_site_url( 'wp-login.php' ),
			$this->get_model()->get_new_login_url( $this->get_site_url() ),
			$wp_new_user_notification_email['message']
		);

		return $wp_new_user_notification_email;
	}

	/**
	 * Change password URL for existed user if the user login has a space, e.g. 'Test user'.
	 * Change via str_replace() without rawurlencode() doesn't work.
	 *
	 * @param string  $message
	 * @param string  $key
	 * @param string  $user_login
	 * @param WP_User $user_data
	 *
	 * @return string
	 */
	public function change_password_message( $message, $key, $user_login, $user_data ) {
		if ( false !== strpos( $user_login, ' ' ) ) {
			$message = str_replace(
				network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ),
				$this->get_model()->get_new_login_url( $this->get_site_url() )
				. "?action=rp&key=$key&login=" . rawurlencode( $user_login ),
				$message
			);
		}

		return $message;
	}

	/**
	 * @param string  $message
	 * @param string  $key
	 * @param string  $user_login
	 * @param WP_User $user_data
	 *
	 * @since 2.5.5
	 *
	 * @return string
	 */
	public function flywheel_change_password_message( $message, $key, $user_login, $user_data ) {
		$message = str_replace(
			network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ),
			$this->get_model()->get_new_login_url( $this->get_site_url() )
			. "?action=rp&key=$key&login=" . rawurlencode( $user_login ) . '&wd-ml-token=' . rawurlencode( $user_login ),
			$message
		);

		return $message;
	}


	/**
	 * Change redirect param of the link 'Lost your password?'.
	 *
	 * @param string $lostpassword_redirect
	 *
	 * @return string
	 */
	public function change_lostpassword_redirect( $lostpassword_redirect ) {
		return $this->get_model()->get_new_login_url( $this->get_site_url() ) . '?checkemail=confirm';
	}

	/**
	 * Handle user profile email change request.
	 */
	private function handle_email_change_request() {
		// If it is not for admin request.
		if ( ! is_admin() ) {
			return;
		}

		// If `IS_PROFILE_PAGE` constant is defined.
		if ( ! defined( 'IS_PROFILE_PAGE' ) ) {
			return;
		}

		// If request is not for profile page.
		if ( ! IS_PROFILE_PAGE ) {
			return;
		}

		// If query data is not set.
		if ( ! isset( $_GET['newuseremail'] ) ) {
			return;
		}

		global $wpdb;
		$hash     = sanitize_text_field( $_GET['newuseremail'] );
		$like     = '%' . $wpdb->esc_like( $hash ) . '%';
		$meta_key = $wpdb->get_var(
			$wpdb->prepare( "SELECT meta_key FROM {$wpdb->usermeta} WHERE meta_value LIKE %s", $like )
		);

		// Hash not found.
		if ( '_new_email' !== $meta_key ) {
			return;
		}

		// Everything good, now redirect user to login page.
		$current_url  = add_query_arg( $_GET, admin_url( 'profile.php' ) );
		$redirect_url = esc_url( wp_login_url( $current_url ) );
		wp_redirect( $redirect_url );
		die();
	}

	/**
	 * @return array
	 */
	public function export_strings() {

		return array(
			$this->get_model()->is_active() ? __( 'Active', 'wpdef' ) : __( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * @param array $config
	 * @param bool  $is_pro
	 *
	 * @return array
	 */
	public function config_strings( $config, $is_pro ) {

		return array(
			$config['enabled'] ? __( 'Active', 'wpdef' ) : __( 'Inactive', 'wpdef' ),
		);
	}

	/**
	 * Support for the password reset page on various hosting.
	 */
	public function handle_password_reset() {
		// Get the email link.
		if (
			isset( $_GET['action'], $_GET['key'], $_GET['login'], $_GET['wd-ml-token'] )
			&& 'rp' === $_GET['action']
			&& $_GET['login'] === $_GET['wd-ml-token']
		) {
			$key   = wp_unslash( $_REQUEST['key'] );
			$login = wp_unslash( $_REQUEST['login'] );
			$user  = check_password_reset_key( $key, $login );
			if ( ! is_wp_error( $user ) ) {
				$value = sprintf( '%s:%s', $login, $key );
				set_site_transient( 'wd-rp-' . COOKIEHASH, $value, 2 * MINUTE_IN_SECONDS );
				wp_safe_redirect( remove_query_arg( array( 'key', 'login', 'wd-ml-token' ) ) );
				exit;
			}
		}
		$value = get_site_transient( 'wd-rp-' . COOKIEHASH );
		// Process the data and display the result.
		if (
			isset( $_GET['action'] )
			&& in_array( $_GET['action'], array( 'rp', 'resetpass' ), true )
			&& isset( $value ) && 0 < strpos( $value, ':' )
		) {
			list( $login, $key ) = explode( ':', wp_unslash( $value ), 2 );
			$user                = check_password_reset_key( $key, $login );
			if ( 'resetpass' === $_GET['action'] ) {
				delete_site_transient( 'wd-rp-' . COOKIEHASH );
			}
			if ( ! is_wp_error( $user ) ) {
				$this->render_partial(
					'mask-login/reset',
					array(
						'user' => $user,
					)
				);
				exit;
			}
		}
	}

	/**
	 * Check if a path is allowed without login masking.
	 *
	 * @param string $path Path to check.
	 *
	 * @since 2.6.4
	 * @return bool
	 */
	private function is_allowed_path( $path ) {
		// Admin post requests to admin-post.php should be allowed.
		$allowed = 'wp-admin/admin-post.php' === $path && isset( $_REQUEST['action'] ); // phpcs:ignore

		/**
		 * Filter to allow whitelisting paths from login masking.
		 *
		 * @param bool   $allowed Is current path allowed?.
		 * @param string $path    Path to check.
		 *
		 * @since 2.6.4
		 */
		return apply_filters( 'wd_mask_login_is_allowed_path', $allowed, $path );
	}
}
