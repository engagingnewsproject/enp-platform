<?php
/**
 * API module.
 * Handles all functions that are doing or processing remote calls.
 *
 * @since   4.0.0
 * @package WPMUDEV_Dashboard
 */

/**
 * The main API class.
 */
class WPMUDEV_Dashboard_Api {

	/**
	 * Expiry time of the token used for Single SignOn, in seconds.
	 * If the token returned from the DEV site is older than that, the user won't be logged in.
	 */
	const SSO_TOKEN_EXPIRY_TIME = 30.0;

	/**
	 * The WPMUDEV API server.
	 *
	 * @var string (URL)
	 */
	protected $server_root = 'https://wpmudev.com/';

	/**
	 * Path to the REST API on the server.
	 *
	 * @var string (URL)
	 */
	protected $rest_api = 'api/dashboard/v2/';

	/**
	 * Path to the Analytics REST API on the server.
	 *
	 * @var string (URL)
	 */
	protected $rest_api_analytics = 'api/analytics/v1/';

	/**
	 * Path to the Analytics REST API on the server.
	 *
	 * @var string (URL)
	 */
	protected $rest_api_translation = 'api/translations/v1/';

	/**
	 * Path to the hosting site endpoints.
	 *
	 * @var string
	 */
	protected $rest_api_hub = 'api/hub/v1/';

	/**
	 * The complete WPMUDEV REST API endpoint. Defined in constructor.
	 *
	 * @var string (URL)
	 */
	protected $server_url = '';

	/**
	 * Stores the API key used for authentication.
	 *
	 * @var string
	 */
	protected $api_key = '';

	/**
	 * Stores the site_id from the API.
	 *
	 * @var int
	 */
	protected $api_site_id = '';

	/**
	 * Holds the last API error that occured (if any)
	 *
	 * @var string
	 */
	public $api_error = '';

	/**
	 * Set up the API module.
	 *
	 * @since 4.0.0
	 * @internal
	 */
	public function __construct() {
		if ( WPMUDEV_CUSTOM_API_SERVER ) {
			$this->server_root = trailingslashit( WPMUDEV_CUSTOM_API_SERVER );
		}
		$this->server_url = $this->server_root . $this->rest_api;

		if ( defined( 'WPMUDEV_APIKEY' ) && WPMUDEV_APIKEY ) {
			$this->api_key = WPMUDEV_APIKEY;
		} else {
			// If 'clear_key' is present in URL then do not load the key from DB.
			$this->api_key = get_site_option( 'wpmudev_apikey' );
		}

		// Schedule automatic data update on the main site of the network.
		if ( is_main_site() ) {
			if ( ! wp_next_scheduled( 'wpmudev_scheduled_jobs' ) ) {
				wp_schedule_event( time(), 'twicedaily', 'wpmudev_scheduled_jobs' );
			}

			// Run action on wpmudev admin actions.
			add_action( 'wpmudev_dashboard_admin_request', array( $this, 'run_admin_cron' ) );

			add_action( 'wpmudev_scheduled_jobs', array( $this, 'cron_hub_sync' ) );
			add_action( 'wpmudev_scheduled_jobs', array( $this, 'refresh_projects_data' ) );
			add_action( 'wpmudev_scheduled_jobs', array( $this, 'maybe_update_translations' ) );

		} elseif ( wp_next_scheduled( 'wpmudev_scheduled_jobs' ) ) {
			// In case the cron job was already installed in a sub-site...
			wp_clear_scheduled_hook( 'wpmudev_scheduled_jobs' );
		}

		/**
		 * Run custom initialization code for the API module.
		 *
		 * @since  4.0.0
		 * @var  WPMUDEV_Dashboard_Api The dashboards API module.
		 */
		do_action( 'wpmudev_dashboard_api_init', $this );
	}


	/*
	 * *********************************************************************** *
	 * *     PUBLIC INTERFACE FOR OTHER MODULES
	 * *********************************************************************** *
	 */


	/**
	 * Returns true if the API key is defined.
	 *
	 * @since  4.0.0
	 * @return bool
	 */
	public function has_key() {
		return ! empty( $this->api_key );
	}

	/**
	 * Returns the API key.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_key() {
		return $this->api_key;
	}

	/**
	 * Updates the API key in the database.
	 *
	 * @since 4.0.0
	 *
	 * @param string $key The new API key to store.
	 */
	public function set_key( $key ) {
		$this->api_key = $key;
		update_site_option( 'wpmudev_apikey', $key );
	}

	/**
	 * Returns the Hub Site ID.
	 *
	 * We just need this get method for this because
	 * it comes with membershipdata which is handled
	 * set/cleared on hubsync.
	 *
	 * @since  4.7.4
	 *
	 * @return int
	 */
	public function get_site_id() {
		// Do this here since we don't need it in construct.
		if ( ! $this->api_site_id ) {
			// Careful while using this.
			// Manually changing site ID could break your site and hub connection.
			// This is only for advance usage.
			if ( defined( 'WPMUDEV_SITE_ID' ) && WPMUDEV_SITE_ID ) {
				$this->api_site_id = WPMUDEV_SITE_ID;
			} else {
				$membership = $this->get_membership_data();
				if ( ! empty( $membership ) && isset( $membership['hub_site_id'] ) ) {
					$this->api_site_id = $membership['hub_site_id'];
				}
			}
		}

		return $this->api_site_id;
	}

	/**
	 * Returns the canonical site_url that should be used for the site in the hub.
	 *
	 * Define WPMUDEV_HUB_SITE_URL to override or make static the url it should show as
	 *  in the hub. Defaults to network_site_url() which may be dynamically filtered
	 *  by some plugins and hosting providers.
	 *
	 * @since  4.6.0
	 *
	 * @return string
	 */
	public function network_site_url() {
		return defined( 'WPMUDEV_HUB_SITE_URL' ) ? WPMUDEV_HUB_SITE_URL : network_site_url();
	}

	/**
	 * Returns the canonical home_url that should be used for the site in the hub.
	 *
	 * Define WPMUDEV_HUB_HOME_URL to override or make static the url it should show as
	 *  in the hub. Defaults to WPMUDEV_HUB_SITE_URL if set, or network_home_url() which may be dynamically filtered
	 *  by some plugins and hosting providers.
	 *
	 * @since  4.6.0
	 *
	 * @return string
	 */
	public function network_home_url() {
		if ( defined( 'WPMUDEV_HUB_HOME_URL' ) ) {
			return WPMUDEV_HUB_HOME_URL;
		} elseif ( defined( 'WPMUDEV_HUB_SITE_URL' ) ) {
			return WPMUDEV_HUB_SITE_URL;
		} else {
			return network_home_url();
		}
	}

	/**
	 * Returns the canonical home_url that should be used for the site in the hub.
	 *
	 * Define WPMUDEV_HUB_ADMIN_URL to override or make static the url it should show as
	 *  in the hub. Defaults to deriving from WPMUDEV_HUB_SITE_URL if set, or network_admin_url() which may be dynamically filtered
	 *  by some plugins and hosting providers.
	 *
	 * @since  4.6.0
	 *
	 * @return string
	 */
	public function network_admin_url() {
		if ( defined( 'WPMUDEV_HUB_ADMIN_URL' ) ) {
			return WPMUDEV_HUB_ADMIN_URL;
		} elseif ( defined( 'WPMUDEV_HUB_SITE_URL' ) ) {
			return is_multisite() ? trailingslashit( WPMUDEV_HUB_SITE_URL ) . 'wp-admin/network/' : trailingslashit( WPMUDEV_HUB_SITE_URL ) . 'wp-admin/';
		} else {
			return network_admin_url();
		}
	}

	/**
	 * Returns a URL we use to validate connection to server. This is not an
	 * API endpoint and does not return any defined information. Only the
	 * HTTP-Status of the GET/POST response is validated.
	 *
	 * @since  4.0.0
	 * @return string
	 */
	public function get_test_url() {
		return $this->rest_url( 'test' );
	}

	/**
	 * Returns the full URL to the specified REST API endpoint.
	 *
	 * This is a function instead of making the property $server_url public so
	 * we have better control and overview of the requested pages:
	 * It's easy to add a filter or add extra URL params to all URLs this way.
	 *
	 * @since  4.0.0
	 *
	 * @param  string $endpoint The endpoint to call on the server.
	 *
	 * @return string The full URL to the requested endpoint.
	 */
	public function rest_url( $endpoint ) {
		if ( preg_match( '!^https?://!', $endpoint ) ) {
			$url = $endpoint;
		} else {
			$url = $this->server_url . $endpoint;
		}

		return $url;
	}

	/**
	 * Returns the full URL to the specified REST API endpoint and includes
	 * the API key as last element in URL.
	 *
	 * Uses the function `rest_url()` to build the URL.
	 *
	 * @since  4.0.0
	 *
	 * @param  string $endpoint The endpoint to call on the server.
	 *
	 * @return string The full URL to the requested endpoint.
	 */
	public function rest_url_auth( $endpoint ) {
		$api_key = $this->get_key();
		if ( false === strpos( $endpoint, '/' . $api_key ) ) {
			$endpoint .= '/' . $api_key;
		}

		$membership_data = $this->get_membership_data();
		if ( isset( $membership_data['hub_site_id'] ) ) {
			$endpoint .= '?site_id=';
			$endpoint .= $membership_data['hub_site_id'];
		}

		return $this->rest_url( $endpoint );
	}

	/**
	 * Checks if the specified URL is on our remote server.
	 *
	 * @since  4.0.0
	 *
	 * @param  string $url The full URL to evaluate.
	 *
	 * @return bool True if the URL is on our remote server.
	 */
	public function is_server_url( $url ) {
		return false !== strpos( $url, $this->server_url );
	}

	/**
	 * Process admin side actions if it's from cron.
	 *
	 * @since  4.11.6
	 * @access public
	 *
	 * @param array $data Request data.
	 *
	 * @return void
	 */
	public function run_admin_cron( $data ) {
		if (
			isset( $data['action'], $data['from'] ) &&
			'cron' === $data['from'] &&
			'hub_sync' === $data['action']
		) {
			// Run hub sync.
			$this->hub_sync();
		}
	}

	/**
	 * Run cron hub sync using admin HTTP request.
	 *
	 * @since  4.11.6
	 * @access public
	 *
	 * @return void
	 */
	public function cron_hub_sync() {
		WPMUDEV_Dashboard::$utils->send_admin_request(
			array(
				'from'   => 'cron',
				'action' => 'hub_sync',
			)
		);
	}

	/**
	 * Makes an API call and returns the results.
	 *
	 * The remote_path can be either relative to the server_url or it can be
	 * an absolute URL to any server.
	 *
	 * If remote_path is a relative path then the API-Key is automatically
	 * added the URL.
	 *
	 * @since  4.0.0
	 *
	 * @param  string $remote_path The API function to call.
	 * @param  array  $data        Optional. GET or POST data to send.
	 * @param  string $method      Optional. GET or POST.
	 * @param  array  $options     Optional. Array of request options.
	 *
	 * @return array Results of the wp_remote_get/post call.
	 */
	public function call( $remote_path, $data = false, $method = 'GET', $options = array() ) {
		$link = $this->rest_url( $remote_path );

		$options = wp_parse_args(
			$options,
			array(
				'timeout'    => 15,
				'sslverify'  => WPMUDEV_API_SSLVERIFY,
				'user-agent' => 'WPMUDEV Dashboard Client/' . WPMUDEV_Dashboard::$version . ' (+' . network_site_url() . ')',
			)
		);

		// Solve the annoying WordPress warning: "gzinflate(): data error".
		if ( WPMUDEV_API_UNCOMPRESSED ) {
			$options['decompress'] = false;
		}

		if ( WPMUDEV_API_AUTHORIZATION ) {
			if ( ! isset( $options['headers'] ) ) {
				$options['headers'] = array();
			}
			$options['headers']['Authorization'] = WPMUDEV_API_AUTHORIZATION;
		}

		if ( 'GET' === $method ) {
			if ( ! empty( $data ) ) {
				$link = add_query_arg( $data, $link );
			}
			$response = wp_remote_get( $link, $options );
		} elseif ( 'POST' === $method ) {
			$options['body'] = $data;
			$response        = wp_remote_post( $link, $options );
		} elseif ( 'DELETE' === $method ) {
			$options['method'] = 'DELETE';
			$response          = wp_remote_request( $link, $options );
		}

		// Add the request-URL to the response data.
		if ( $response && is_array( $response ) ) {
			$response['request_url'] = $link;
		}

		if ( WPMUDEV_API_DEBUG ) {
			$log = '[WPMUDEV API call] %s | %s: %s (%s)';
			if ( WPMUDEV_API_DEBUG_ALL ) {
				$log .= "\nRequest options: %s\nResponse: %s";
			}

			// strip down big vars unless WPMUDEV_API_DEBUG_CRAZY is defined
			$resp_body = wp_remote_retrieve_body( $response );
			if ( ! defined( 'WPMUDEV_API_DEBUG_CRAZY' ) ) {
				$req_body = isset( $options['body'] ) ? $options['body'] : '';
				if ( isset( $req_body['projects'] ) ) {
					/**
					 * this contains 2 keys: plugins and themes
					 *
					 * @see self::get_repo_updates_infos()
					 */
					$repo_updates = json_decode( $req_body['repo_updates'], true );
					$req_body['projects']     = count( (array) json_decode( $req_body['projects'] ) ) . ' PROJECTS';
					// TODO: subject for code/implementation improvement
					$req_body['repo_updates'] = array(
						'plugins' => ( isset( $repo_updates['plugins'] ) && $repo_updates['plugins'] ) ? count( $repo_updates['plugins'] ) : 0,
						'themes'  => ( isset( $repo_updates['themes'] ) && $repo_updates['themes'] ) ? count( $repo_updates['themes'] ) : 0,
					);
					$packages                 = (object) json_decode( $req_body['packages'] );
					$packages->plugins        = count( (array) $packages->plugins ) . ' PLUGINS';
					$packages->themes         = count( (array) $packages->themes ) . ' THEMES';
					$req_body['packages']     = wp_json_encode( $packages );
				}
				$options['body'] = $req_body;

				$resp_body = json_decode( wp_remote_retrieve_body( $response ) );
				if ( is_object( $resp_body ) ) {
					if ( isset( $resp_body->projects ) ) {
						$resp_body->projects = '[...]';
					}
					if ( isset( $resp_body->plugin_tags ) ) {
						$resp_body->plugin_tags = '[...]';
					}
				}
				$resp_body = wp_json_encode( $resp_body );
			}

			if ( $response && is_array( $response ) ) {
				$debug_data  = sprintf( "%s %s\n", wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) );
				$debug_data .= var_export( wp_remote_retrieve_headers( $response ), true ) . PHP_EOL; // WPCS: var_export() ok.
				$debug_data .= $resp_body;
			} else {
				$debug_data = '';
			}

			$msg = sprintf(
				$log,
				WPMUDEV_Dashboard::$version,
				$method,
				$link,
				wp_remote_retrieve_response_code( $response ),
				wp_json_encode( $options ),
				$debug_data
			);
			error_log( $msg );
		}

		return $response;
	}

	/**
	 * Makes an API call and includes the API key in the REST URL and returns
	 * the results.
	 *
	 * Uses `call()` to get the results.
	 *
	 * @since  4.0.0
	 *
	 * @param  string $remote_path The API function to call.
	 * @param  array  $data        Optional. GET or POST data to send.
	 * @param  string $method      Optional. GET or POST.
	 * @param  array  $options     Optional. List of Request options.
	 *
	 * @return array Results of the wp_remote_get/post call.
	 */
	public function call_auth( $remote_path, $data = false, $method = 'GET', $options = array() ) {
		if ( 'GET' == $method ) {
			$remote_path = $this->rest_url_auth( $remote_path );
		} elseif ( 'POST' == $method ) {
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$key_data            = array();
			$key_data['api_key'] = $this->get_key();

			// make sure api key is first
			$data = array_merge( $key_data, $data );
		}

		$response = $this->call( $remote_path, $data, $method, $options );

		return $response;
	}

	/**
	 * In WP Engine hosting only requests from logged in users with auth cookies are given filesystem
	 *  write access. So we need to send those to Hub to allow for remote updates, backups, etc. Encrypted
	 *  for extra safety. Workaround inspired by ManageWP.
	 *
	 * @return array $cookies
	 */
	public function get_encrypted_cookies() {

		$crypt_file = WPMUDEV_Dashboard::$site->plugin_path . 'lib/PHPSecLib/Crypt/RSA.php';

		// we only need to run this in WP Engine environment.
		if ( ! defined( 'WPE_APIKEY' ) || ! is_readable( $crypt_file ) ) {
			return array();
		}

		// Make sure the constants are set.
		$this->define_cookie_constants();

		// figure out the first admin.
		if ( is_multisite() ) {
			$supers = get_super_admins();
			$user   = get_user_by( 'login', $supers[0] );
		} else {
			$admins = get_users(
				array(
					'role'   => 'administrator',
					'number' => 1,
				)
			);
			$user   = $admins[0];
		}
		$user_id = $user->ID;

		$cookies = array();
		$secure  = is_ssl();
		$secure  = apply_filters( 'secure_auth_cookie', $secure, $user_id );

		if ( $secure ) {
			$auth_cookie_name = SECURE_AUTH_COOKIE;
			$scheme           = 'secure_auth';
		} else {
			$auth_cookie_name = AUTH_COOKIE;
			$scheme           = 'auth';
		}

		$expiration = time() + ( DAY_IN_SECONDS * 14 ); // we expire sites from the hub after 14 days, so long enough for these cookies

		$cookies[ $auth_cookie_name ] = wp_generate_auth_cookie( $user_id, $expiration, $scheme );
		$cookies[ LOGGED_IN_COOKIE ]  = wp_generate_auth_cookie( $user_id, $expiration, 'logged_in' );
		$cookies['wpe-auth']          = md5( 'wpe_auth_salty_dog|' . WPE_APIKEY ); // this is WP Engine's proprietary auth cookie

		if ( empty( $cookies ) ) {
			return $cookies;
		}

		if ( ! class_exists( 'Crypt_RSA', false ) ) {
			require_once WPMUDEV_Dashboard::$site->plugin_path . 'lib/PHPSecLib/Crypt/RSA.php';
		}

		$rsa = new Crypt_RSA();
		$rsa->setEncryptionMode( CRYPT_RSA_SIGNATURE_PKCS1 );
		$rsa->loadKey( file_get_contents( WPMUDEV_Dashboard::$site->plugin_path . 'keys/dashboard.pub' ), CRYPT_RSA_PUBLIC_FORMAT_PKCS1 ); // public key

		foreach ( $cookies as &$cookieValue ) {
			$cookieValue = base64_encode( $rsa->encrypt( $cookieValue ) );
		}

		return $cookies;
	}

	/**
	 * Defines cookie-related WordPress constants if required.
	 *
	 * In WP Engine, sometimes there is a delay so we try to access
	 * the constants before it's defined.
	 *
	 * @see https://incsub.atlassian.net/browse/WDD-140
	 *
	 * @since 4.11.1
	 */
	private function define_cookie_constants() {
		// Include required file.
		if ( ! function_exists( 'wp_cookie_constants' ) ) {
			include_once ABSPATH . 'wp-includes/default-constants.php';
		}

		// Make sure the constants are defined by WP.
		wp_cookie_constants();
	}

	/**
	 * The proper way to get details about the current projects on DEV.
	 *
	 * @since  1.0.0
	 * @return array {
	 *         Details about current projects on DEV.
	 *
	 * @type string $downloads         [disabled|enabled]
	 * @type array  $free_notice       Array with 'key' and 'msg'
	 * @type array  $full_notice       Array with 'key' and 'msg'
	 * @type array  $single_notice     Array with 'key' and 'msg'
	 * @type int    $latest_release    A Project-ID
	 * @type array  $latest_plugins    Array of latest 5 project-IDs
	 * @type array  $latest_themes     Array of latest 5 project-IDs
	 * @type array  $plugin_tags       List of all plugin tags with list of tagged projects
	 * @type array  $theme_tags        List of all theme tags with list of tagged projects
	 * @type array  $projects          Complete list of all available projects (plugins and themes)
	 * @type string $text_admin_notice HTML text for display
	 * @type string $text_page_head    HTML text for display
	 * }
	 */
	public function get_projects_data() {
		$expire = time() - ( HOUR_IN_SECONDS * 12 );
		$flag   = WPMUDEV_Dashboard::$settings->get( 'refresh_remote', 'flags' );

		if ( $flag ) {
			WPMUDEV_Dashboard::$settings->set( 'updates_data', false );
			$res      = false;
			$last_run = 0;
		} else {
			$res      = WPMUDEV_Dashboard::$settings->get( 'updates_data' );
			$last_run = intval( WPMUDEV_Dashboard::$settings->get( 'last_run_updates', 'general' ) );
		}

		if ( $flag || ! is_array( $res ) || ! $last_run || $expire > $last_run ) {
			// This condition prevents race condition in case of network error
			// or problems on API side.
			if ( $last_run < time() ) {
				$res = $this->refresh_projects_data();
			}
		}

		// Basic sanitation, to avoid incompatible return values.
		if ( ! is_array( $res ) ) {
			$res = array();
		}
		$res = wp_parse_args(
			$res,
			array(
				'latest_release' => 0,
				'latest_plugins' => array(),
				'latest_themes'  => array(),
				'plugin_tags'    => array(),
				'theme_tags'     => array(),
				'projects'       => array(),
			)
		);

		return apply_filters( 'wpmudev_dashboard_get_projects_data', $res );
	}

	/**
	 * The proper way to get details about the current membership
	 *
	 * @since  4.4.1
	 * @return array {
	 *         Details about current membership.
	 *
	 * @type string $membership            [free|single|unit|full]
	 * @type string $membership_full_level [gold|bronze|silver]
	 * }
	 */
	public function get_membership_data() {
		$res = WPMUDEV_Dashboard::$settings->get( 'membership_data' );
		// Basic sanitation, to avoid incompatible return values.
		if ( ! is_array( $res ) ) {
			$res = array();
		}
		$res = wp_parse_args(
			$res,
			array(
				'membership' => '',
			)
		);

		return apply_filters( 'wpmudev_dashboard_get_membership_data', $res );
	}

	/**
	 * Get the current membership type/status (deprecated).
	 *
	 * Possible return values:
	 * 'free'    - expired/not signed up yet.
	 * 'single'  - Single membership (i.e. only 1 project is licensed)
	 * 'unit'    - One or more projects licensed
	 * 'full'    - Full membership, no restrictions.
	 * 'free_hub'  - Free hub membership.
	 *
	 * @since  4.0.0
	 * @deprecated 4.11.9 Use WPMUDEV_Dashboard::$api->get_membership_status()
	 *
	 * @param mixed $legacy_param - Not to be used! Remains as part of public facing
	 *                              API, but does not affect anything.
	 *
	 * @return string The membership type.
	 */
	public function get_membership_type( &$legacy_param = null ) {
		// Get the current membership status.
		$type = $this->get_membership_status();

		// Available membership types.
		$types = array( 'full', 'unit', 'single' );

		if ( ! in_array( $type, $types, true ) ) {
			/**
			 * For backward compatibility.
			 * We previously considered expired, paused etc. types as free.
			 * But now we have a separate `free` type. But for backward compat
			 * we need to use different name for `free` type.
			 */
			$type = 'free' === $type ? 'free_hub' : 'free';
		}

		return $type;
	}

	/**
	 * Get current membership type/status.
	 *
	 * Possible return values:
	 * 'free'    - Free hub membership.
	 * 'single'  - Single membership (i.e. only 1 project is licensed)
	 * 'unit'    - One or more projects licensed
	 * 'full'    - Full membership, no restrictions.
	 * 'paused'  - Membership access is paused.
	 * 'expired' - Expired membership.
	 * ''        - (empty string) If user is not logged in or with an unknown type.
	 *
	 * @since 4.11.9
	 *
	 * @return string The membership type.
	 */
	public function get_membership_status() {
		$data = $this->get_membership_data();

		// Available membership types.
		$types = array(
			'full',
			'unit',
			'free',
			'paused',
			'expired',
		);

		// Default type is empty.
		$type = '';

		// All possible string values.
		if ( is_string( $data['membership'] ) && in_array( $data['membership'], $types, true ) ) {
			$type = $data['membership'];
		} elseif (
			is_numeric( $data['membership'] ) ||
			( is_bool( $data['membership'] ) && isset( $data['membership_full_level'] ) && is_numeric( $data['membership_full_level'] ) )
		) {
			$type = 'single';
		}

		return $type;
	}

	/**
	 * Returns a numeric id or array of numeric ids or projects available on plan.
	 * Numeric id is returned only if "single" plan is active, for backwards compatibility.
	 *
	 * This method does not account for membership_excluded_projects!!! Use:
	 * WPMUDEV_Dashboard::$api->get_excluded_projects()
	 * to exclude projects where needed.
	 *
	 * @since  4.9.0
	 *
	 * @return mixed Numeric id or available project for "single" plan or array or
	 *               numeric ids for "unit" plans.
	 */
	public function get_membership_projects() {
		$data = $this->get_membership_data();

		if ( 'full' === $data['membership'] ) {
			return array();
		}
		// For free and unit memberships.
		if ( in_array( $data['membership'], array( 'free', 'unit' ), true ) ) {
			$projects = is_array( $data['membership_projects'] ) ? $data['membership_projects'] : array();
			foreach ( $projects as $i => $p ) {
				$projects[ $i ] = intval( $p );
			}
			return $projects;
		}
		if ( is_numeric( $data['membership'] ) ) {
			return intval( $data['membership'] );
		}
		if ( is_bool( $data['membership'] ) && is_numeric( $data['membership_full_level'] ) ) {
			return intval( $data['membership_full_level'] );
		}

		return array();
	}

	/**
	 * Get projects that are strictly forbidden to be installed or updated for
	 * current membership level.
	 *
	 * @return array[int] List of excluded project ids as numeric values.
	 */
	public function get_excluded_projects() {
		$key      = 'membership_excluded_projects';
		$defaults = array( $key => array() );
		$data     = wp_parse_args( $this->get_membership_data(), $defaults );

		$projects = array();
		if ( false === empty( $data[ $key ] ) ) {
			foreach ( $data[ $key ] as $pid ) {
				$projects[] = intval( $pid );
			}
		}

		return $projects;
	}

	/**
	 * Checks if feature is allowed for membership plan by feature string.
	 *
	 * @param string $feature Feature string.
	 * @return boolean is allowed.
	 */
	private function is_feature_allowed( $feature ) {
		$data     = $this->get_membership_data();
		$features = isset( $data['membership_access'] ) ? $data['membership_access'] : array();

		// The membership_access can be boolean true for full accesss, or array with allowed features strings.
		if ( true === $features ) {
			return true;
		}

		if ( false === is_array( $features ) ) {
			return false;
		}

		return in_array( $feature, $features, true );
	}

	/**
	 * Checks if feature is allowed for membership plan by feature string.
	 *
	 * This is here for other plugins to check feature availability.
	 *
	 * @param string $feature Feature string.
	 *
	 * @since 4.11.9
	 *
	 * @return boolean is allowed.
	 */
	public function has_access( $feature ) {
		return $this->is_feature_allowed( $feature );
	}

	/**
	 * Checks if whitelabel is allowed by membership plan.
	 *
	 * @return boolean is allowed.
	 */
	public function is_whitelabel_allowed() {
		return $this->is_feature_allowed( 'whitelabel-dashboard' );
	}

	/**
	 * Checks if analytics is allowed by membership plan.
	 *
	 * @since 4.11
	 *
	 * @return boolean is allowed.
	 */
	public function is_analytics_allowed() {
		return $this->is_feature_allowed( 'whitelabel-basic-analytics' );
	}

	/**
	 * Checks if support forum is allowed by membership plan.
	 *
	 * @since 4.11.8
	 *
	 * @return boolean is allowed.
	 */
	public function is_support_allowed() {
		return $this->is_feature_allowed( 'support-forums' );
	}

	/**
	 * Checks if tickets are hidden on UI.
	 *
	 * @since 4.11.4
	 *
	 * @return bool is hidden.
	 */
	public function is_tickets_hidden() {
		// Get membership data.
		$data = $this->get_membership_data();
		// Check tickets visibility.
		$hidden = isset( $data['is_tickets_hidden'] ) && (bool) $data['is_tickets_hidden'];

		/**
		 * Filter hook to change tickets visibility.
		 *
		 * @param bool $visible Is hidden.
		 *
		 * @since 4.11.4
		 */
		return apply_filters( 'wpmudev_dashboard_is_tickets_hidden', $hidden );
	}

	/**
	 * Returns the details of a single project from the API.
	 *
	 * @since  4.0.0
	 *
	 * @param  int $project_id The project to return.
	 *
	 * @return array Project details.
	 */
	public function get_project_data( $project_id ) {
		static $all_projects = null;
		$item                = false;

		if ( null === $all_projects ) {
			$data = $this->get_projects_data();
			if ( isset( $data['projects'] ) ) {
				$all_projects = $data['projects'];
			}
		}

		if ( $all_projects && isset( $all_projects[ $project_id ] ) ) {
			$item = wp_parse_args(
				$all_projects[ $project_id ],
				array(
					'id'                => 0,
					'paid'              => 'paid',
					'type'              => 'plugin',
					'name'              => '',
					'released'          => 0,
					'updated'           => 0,
					'downloads'         => 0,
					'popularity'        => 0,
					'short_description' => '',
					'features'          => array(),
					'active'            => true,
					'version'           => '1.0.0',
					'autoupdate'        => 1,
					'requires'          => 'wp',
					'requires_min_php'  => '5.6',
					'compatible'        => '',
					'url'               => '',
					'thumbnail'         => '',
					'video'             => false,
					'wp_config_url'     => '',
					'ms_config_url'     => '',
					'package'           => 0,
					'screenshots'       => array(),
					'free_version_slug' => '',
				)
			);
		} else {
			if ( WPMUDEV_API_DEBUG && defined( 'WPMUDEV_API_DEBUG_CRAZY' ) ) {
				error_log(
					sprintf(
						'[WPMUDEV API Warning] No remote data found for project %s',
						$project_id
					)
				);
			}
		}

		return $item;
	}

	/**
	 * Get available teams for the authenticated user.
	 *
	 * @param string $key User API key.
	 *
	 * @since  4.11.10
	 *
	 * @return array|bool
	 */
	public function get_user_teams( $key ) {
		// Sets up special auth header.
		$options['headers']                  = array();
		$options['headers']['Authorization'] = $key;

		// Send API request.
		$response = WPMUDEV_Dashboard::$api->call(
			'site-authenticate-teams',
			false,
			'GET',
			$options
		);

		// Error.
		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$this->parse_api_error( $response );

			return false;
		} else {
			// Get team list.
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $data['data'] ) ) {
				return $data['data'];
			}
		}

		return array();
	}

	/**
	 * Returns a list of all plugins and themes installed the WordPress site
	 * WPMU DEV projects are not included here.
	 *
	 * @since  4.3.0
	 * @return array Array that contains 2 sub-arrays: 'plugins' and 'themes'.
	 */
	public function get_repo_packages() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$packages = array(
			'plugins' => array(),
			'themes'  => array(),
		);

		$plugins = get_plugins();
		$themes  = wp_get_themes();

		// First remove WPMUDEV plugins from the WP update data (for slug conflicts like
		$local_projects = WPMUDEV_Dashboard::$site->get_cached_projects();
		foreach ( $local_projects as $id => $update ) {
			if ( isset( $plugins[ $update['filename'] ] ) ) {
				unset( $plugins[ $update['filename'] ] );
			}

			$theme_slug = dirname( $update['filename'] );
			if ( isset( $themes[ $theme_slug ] ) ) {
				unset( $themes[ $theme_slug ] );
			}
		}

		// Extract and collect details we need.
		foreach ( $plugins as $slug => $data ) {
			// Only network active plugin should be considered as active.
			$active = is_multisite() ? is_plugin_active_for_network( $slug ) : is_plugin_active( $slug );

			$packages['plugins'][ $slug ] = array(
				'name'       => $data['Name'],
				'version'    => $data['Version'],
				'plugin_url' => $data['PluginURI'],
				'author'     => $data['Author'],
				'author_url' => $data['AuthorURI'],
				'network'    => $data['Network'],
				'active'     => $active,
			);
		}

		foreach ( $themes as $slug => $theme ) {
			if ( is_multisite() ) {
				$active = $theme->is_allowed() || get_stylesheet() == $slug; // network enabled or on main site
			} else {
				// If the theme is available on main site it's "active".
				$active = get_stylesheet() == $slug;
			}

			$parent                      = $theme->parent() ? $theme->get_template() : false;
			$packages['themes'][ $slug ] = array(
				'name'       => $theme->display( 'Name', false ),
				'version'    => $theme->display( 'Version', false ),
				'author'     => $theme->display( 'Author', false ),
				'author_url' => $theme->display( 'AuthorURI', false ),
				'screenshot' => $theme->get_screenshot(),
				'parent'     => $parent,
				'active'     => $active,
			);
		}

		return $packages;
	}

	/**
	 * Returns a list of all plugins and themes on the WordPress site that have
	 * an pending update. WPMU DEV projects are not included here.
	 *
	 * @since  4.1.0
	 * @return array Array that contains 2 sub-arrays: 'plugins' and 'themes'.
	 */
	public function get_repo_updates_infos() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$core_updates = array(
			'plugins' => array(),
			'themes'  => array(),
		);

		// Remove our custom filters, so we get the original updates list.
		remove_filter(
			'site_transient_update_plugins',
			array( WPMUDEV_Dashboard::$site, 'filter_plugin_update_count' )
		);
		remove_filter(
			'site_transient_update_themes',
			array( WPMUDEV_Dashboard::$site, 'filter_theme_update_count' )
		);

		// Get the available updates list.
		$plugin_data = get_site_transient( 'update_plugins' );
		$theme_data  = get_site_transient( 'update_themes' );

		// Restore our filters to include WPMU DEV projects in the updates list.
		add_filter(
			'site_transient_update_plugins',
			array( WPMUDEV_Dashboard::$site, 'filter_plugin_update_count' )
		);
		add_filter(
			'site_transient_update_themes',
			array( WPMUDEV_Dashboard::$site, 'filter_theme_update_count' )
		);

		// First remove WPMUDEV plugins from the WP update data (for slug conflicts like
		$local_projects = WPMUDEV_Dashboard::$site->get_cached_projects();
		foreach ( $local_projects as $id => $update ) {
			if ( isset( $plugin_data->response[ $update['filename'] ] ) ) {
				unset( $plugin_data->response[ $update['filename'] ] );
			}
			if ( isset( $plugin_data->no_update[ $update['filename'] ] ) ) {
				unset( $plugin_data->no_update[ $update['filename'] ] );
			}

			$theme_slug = dirname( $update['filename'] );
			if ( isset( $theme_data->response[ $theme_slug ] ) ) {
				unset( $theme_data->response[ $theme_slug ] );
			}
			if ( isset( $theme_data->no_update[ $theme_slug ] ) ) {
				unset( $theme_data->no_update[ $theme_slug ] );
			}
		}

		// Extract and collect details we need.
		if ( isset( $plugin_data->response ) && is_array( $plugin_data->response ) ) {
			foreach ( $plugin_data->response as $slug => $infos ) {
				$item                             = get_plugin_data( WP_PLUGIN_DIR . '/' . $slug );
				$core_updates['plugins'][ $slug ] = array(
					'name'        => $item['Name'],
					'version'     => $item['Version'],
					'new_version' => $infos->new_version,
					'upgradable'  => ! empty( $infos->package ),
				);
			}
		}

		if ( isset( $theme_data->response ) && is_array( $theme_data->response ) ) {
			foreach ( $theme_data->response as $slug => $infos ) {
				$item                            = wp_get_theme( $slug );
				$core_updates['themes'][ $slug ] = array(
					'name'        => $item->Name,
					'version'     => $item->Version,
					'new_version' => $infos['new_version'],
					'upgradable'  => ! empty( $infos['package'] ),
				);
			}
		}

		return $core_updates;
	}

	/**
	 * The proper way to get the array of profile data from cache/Api.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_profile() {
		$expire = time() - ( MINUTE_IN_SECONDS * 10 );
		$flag   = WPMUDEV_Dashboard::$settings->get( 'refresh_profile', 'flags' );

		if ( $flag ) {
			WPMUDEV_Dashboard::$settings->set( 'profile_data', false );
			$res      = false;
			$last_run = 0;
		} else {
			$res      = WPMUDEV_Dashboard::$settings->get( 'profile_data' );
			$last_run = intval( WPMUDEV_Dashboard::$settings->get( 'last_run_profile', 'general' ) );
		}

		if ( $flag || ! $res || ! $last_run || $expire > $last_run ) {
			// This condition prevents race condition in case of network error
			// or problems on API side.
			if ( $last_run < time() ) {
				$res = $this->refresh_profile();
			}
		}

		// Basic sanitation, to avoid incompatible return values.
		if ( ! is_array( $res ) ) {
			$res = array();
		}
		if ( empty( $res['profile'] ) || ! is_array( $res['profile'] ) ) {
			$res['profile'] = array();
		}
		if ( empty( $res['points'] ) || ! is_array( $res['points'] ) ) {
			$res['points'] = array();
		}
		if ( empty( $res['forum'] ) ) {
			$res['forum'] = array();
		}
		if ( empty( $res['forum']['support_threads'] ) ) {
			$res['forum']['support_threads'] = array();
		}

		$res['profile'] = wp_parse_args(
			$res['profile'],
			array(
				'avatar'       => '',
				'member_since' => time(),
				'name'         => '[name]',
				'title'        => '[title]',
				'user_name'    => '[username]',
			)
		);
		$res['points']  = wp_parse_args(
			$res['points'],
			array(
				'hero_points' => 0,
				'history'     => array(),
				'rank'        => 0,
				'rep_points'  => 0,
			)
		);

		return $res;
	}

	/**
	 * The proper way to get a projects changelog from cache/Api.
	 * The changelog is stored in transients with expire date of 7 days.
	 *
	 * @since  4.0.0
	 *
	 * @param  int    $pid          The Project ID.
	 * @param  string $last_version Optional. The last version that must appear
	 *                              in the changelog; used to refresh cached changelog data before
	 *                              the cache expires.
	 *
	 * @return array
	 */
	public function get_changelog( $pid, $last_version = false ) {
		$res = WPMUDEV_Dashboard::$settings->get_transient( 'changelog_' . $pid );

		if ( $last_version && is_array( $res ) && ! empty( $res[0] ) ) {
			$retry_stamp = time() - MINUTE_IN_SECONDS;

			if ( empty( $res['timestamp'] ) ) {
				$res = false;
			} elseif ( ! empty( $res['timestamp'] ) && $res['timestamp'] <= $retry_stamp ) {
				// Check if version in cache is less then the latest version.
				if ( version_compare( $res[0]['version'], $last_version, 'lt' ) ) {
					$res = false; // Cache is outdated and needs to be refreshed.
				}
			}
		}

		if ( empty( $res ) || ! is_array( $res ) ) {
			$res = $this->refresh_changelog( $pid );
		}

		// Basic sanitation, to avoid incompatible return values.
		if ( ! is_array( $res ) ) {
			$res = array();
		}

		return $res;
	}


	/*
	 * *********************************************************************** *
	 * *     FETCH AND REFRESH DATA FROM API
	 * *********************************************************************** *
	 */


	/**
	 * Generates the stats data about the site and installed products
	 *
	 * @param  bool       $encoded        Whether to json encode the fields that are arrays
	 * @param  bool|array $local_projects Optional array of local projects, pass if you have it to save time
	 *
	 * @return array
	 */
	public function build_api_data( $encoded = false, $local_projects = false ) {
		global $wp_version;

		if ( ! is_array( $local_projects ) ) {
			$local_projects = WPMUDEV_Dashboard::$site->get_cached_projects();
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$projects   = array();
		$theme      = wp_get_theme();
		$ms_allowed = $theme->get_allowed();
		foreach ( $local_projects as $pid => $item ) {
			if ( 'theme' == $item['type'] ) {
				$slug = dirname( $item['filename'] );
				if ( is_multisite() ) {
					$active = ! empty( $ms_allowed[ $slug ] ) || ( $theme->stylesheet == $slug || $theme->template == $slug ); // network enabled or on main site
				} else {
					// If the theme is available on main site it's "active".
					$active = ( $theme->stylesheet == $slug || $theme->template == $slug );
				}
			} else {
				// On multisite, only consider network active plugins as active.
				$active = is_multisite() ? is_plugin_active_for_network( $item['filename'] ) : is_plugin_active( $item['filename'] );
			}

			$extra = '';

			/**
			 * Collect extra data from individual plugins.
			 *
			 * @since  4.0.0
			 * @api    wpmudev_api_project_extra_data-$pid
			 *
			 * @param  string $extra Default extra data is an empty string.
			 */
			$extra = apply_filters( "wpmudev_api_project_extra_data-$pid", $extra );
			$extra = apply_filters( 'wpmudev_api_project_extra_data', $extra, $pid );

			$projects[ $pid ] = array(
				'version' => $item['version'],
				'active'  => $active ? true : false,
				'extra'   => $extra,
			);
		}

		/**
		 * Allows modification of the plugin data that is sent to the server.
		 *
		 * @since  4.0.0
		 * @api    wpmudev_api_project_data
		 *
		 * @param  array $projects The whole array of project details.
		 */
		$projects = apply_filters( 'wpmudev_api_project_data', $projects );

		// Get WP/BP version string to help with support.
		if ( is_multisite() ) {
			$wp_ver     = "WordPress Multisite $wp_version";
			$blog_count = get_blog_count();
		} else {
			$wp_ver     = "WordPress $wp_version";
			$blog_count = 1;
		}
		if ( defined( 'BP_VERSION' ) ) {
			$wp_ver .= ', BuddyPress ' . BP_VERSION;
		}

		// Prepare site info.
		$site_info = WPMUDEV_Dashboard::$utils->get_site_info();

		// Get a list of pending WP updates of non-WPMUDEV themes/plugins.
		$repo_updates = $this->get_repo_updates_infos();

		$packages = $this->get_repo_packages();

		// get auth cookies if in WP Engine
		$auth_cookies = $this->get_encrypted_cookies();

		$call_version = WPMUDEV_Dashboard::$version;

		$data = array(
			'call_version' => $call_version,
			'domain'       => $this->network_site_url(),
			'blog_count'   => $blog_count,
			'wp_version'   => $wp_ver,
			'projects'     => $projects,
			'admin_url'    => $this->network_admin_url(),
			'home_url'     => $this->network_home_url(),
			'sso_status'   => WPMUDEV_Dashboard::$settings->get( 'enabled', 'sso' ),
			'repo_updates' => $repo_updates,
			'packages'     => $packages,
			'auth_cookies' => $auth_cookies,
			'site_info'    => $site_info,
		);

		// Report the hosting site_id if in WPMUDEV Hosting environment.
		if ( WPMUDEV_Dashboard::$api->is_wpmu_dev_hosting() ) {
			$data['hosting_site_id'] = defined( 'WPMUDEV_HOSTING_SITE_ID' ) ? WPMUDEV_HOSTING_SITE_ID : gethostname();
		}

		if ( $encoded ) {
			$data['projects']     = json_encode( $data['projects'] );
			$data['repo_updates'] = json_encode( $data['repo_updates'] );
			$data['packages']     = json_encode( $data['packages'] );
			$data['auth_cookies'] = json_encode( $data['auth_cookies'] );
			$data['site_info']    = json_encode( $data['site_info'] );
		}

		return $data;
	}

	/**
	 * Checks if site is hosted on WPMU Dev hosting.
	 *
	 * @since 4.9.0
	 * @since 4.11.15 Added extra checks.
	 *
	 * @return bool Is site hosted on WPMU Dev, true if it is.
	 */
	public function is_wpmu_dev_hosting() {
		return defined( 'WPMUDEV_HOSTING_SITE_ID' ) || isset( $_SERVER['WPMUDEV_HOSTED'] );
	}

	/**
	 * Checks if site is hosted on WPMU Dev hosting with standalone hosting plan.
	 *
	 * @since 4.11.15 Added extra checks.
	 *
	 * @return bool
	 */
	public function is_standalone_hosting_plan() {
		// Get membership data.
		$data = $this->get_membership_data();
		// For standalone hosting there should be active products.
		if ( isset( $data['membership_active_products'] ) && is_array( $data['membership_active_products'] ) ) {
			foreach ( $data['membership_active_products'] as $product ) {
				// If hosting plan found return early.
				if ( strpos( $product, 'hosting-' ) === 0 ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks if current site is a third party site with standalone hosting plan.
	 *
	 * @since 4.11.15
	 *
	 * @return bool
	 */
	public function is_hosted_third_party() {
		return $this->is_standalone_hosting_plan() && ! $this->is_wpmu_dev_hosting();
	}

	/**
	 * Contacts the API to sync the latest data from this site.
	 *
	 * Returns the membership status if things are working out.
	 * In case the API call fails the function returns boolean false and does
	 * not update the update
	 *
	 * @since    1.0.0
	 * @internal Function only is public because it's an action handler.
	 *
	 * @param  bool|array $local_projects Optional array of local projects.
	 * @param  bool       $force          Optional forces a sync
	 *
	 * @return array|bool
	 */
	public function hub_sync( $local_projects = false, $force = false ) {
		$res = false;

		/*
		Note: This endpoint does not require an API key.
		 */

		if ( defined( 'WP_INSTALLING' ) ) {
			return false;
		}

		// Clear the "Force data update" flag to avoid infinite loop.
		WPMUDEV_Dashboard::$settings->set( 'refresh_remote', false, 'flags' );

		$stats_data = $hash_data = $this->build_api_data( true, $local_projects );

		unset( $hash_data['auth_cookies'] );
		$data_hash = md5( json_encode( $hash_data ) ); // get a hash of the data to see if it changed (minus auth cookies)
		unset( $hash_data );

		$last_run = (array) WPMUDEV_Dashboard::$settings->get( 'last_run_sync', 'general', array() );


		if ( ! $force && ! empty( $last_run ) ) {
			// this is the main check to prevent pinging unless the data is changed or 6 hrs have passed
			if ( ( $last_run['hash'] ?? '' ) == $data_hash && ( $last_run['time'] ?? 0 ) > ( time() - ( HOUR_IN_SECONDS * 6 ) ) ) {
				if ( WPMUDEV_API_DEBUG ) {
					error_log( '[WPMUDEV API] Skipped sync due to unchanged local data.' );
				}

				return $this->get_membership_data();
			} elseif ( ( $last_run['fails'] ?? 0 ) ) { // check for exponential backoff
				$backoff = min( pow( 5, ( $last_run['fails'] ?? 0 ) ), HOUR_IN_SECONDS ); // 5, 25, 125, 625, 3125, 3600 max
				if ( $last_run['time'] > ( time() - $backoff ) ) {
					if ( WPMUDEV_API_DEBUG ) {
						error_log( '[WPMUDEV API] Skipped sync due to API error exponential backoff.' );
					}

					return $this->get_membership_data();
				}
			}
		}

		$stats_data['sync_version'] = WPMUDEV_Dashboard::$version;

		$response = WPMUDEV_Dashboard::$api->call_auth(
			'hub-sync',
			$stats_data,
			'POST'
		);

		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) ) {
				if ( isset( $data['membership'] ) && empty( $data['membership'] ) && ! defined( 'WPMUDEV_APIKEY' ) && WPMUDEV_Dashboard::$api->has_key() ) {
					if ( WPMUDEV_API_DEBUG ) {
						error_log( '[WPMUDEV API Warning] Invalid API key, logging out.' );
					}
					WPMUDEV_Dashboard::$api->set_key( '' );
				}

				WPMUDEV_Dashboard::$settings->set( 'membership_data', $data );
				WPMUDEV_Dashboard::$settings->set(
					'last_run_sync',
					array(
						'time'  => time(),
						'hash'  => $data_hash,
						'fails' => 0,
					),
					'general'
				);

				// Sync analytics state
				$prev_analytics_data = [
					'site_id'    => (int) WPMUDEV_Dashboard::$settings->get( 'site_id', 'analytics' ),
					'tracker'    => (string) WPMUDEV_Dashboard::$settings->get( 'tracker', 'analytics' ),
					'enabled'    => wp_validate_boolean( WPMUDEV_Dashboard::$settings->get( 'enabled', 'analytics' ) ),
					'script_url' => (string) WPMUDEV_Dashboard::$settings->get( 'script_url', 'analytics' ),
				];

				$new_analytics_data = [
					'site_id'    => (int) ( $data['analytics_site_id'] ?? $prev_analytics_data['site_id'] ),
					'tracker'    => (string) ( $data['analytics_tracker'] ?? $prev_analytics_data['tracker'] ),
					'enabled'    => wp_validate_boolean( $data['analytics_enabled'] ?? $prev_analytics_data['enabled'] ),
					'script_url' => (string) ( $data['analytics_script_url'] ?? $prev_analytics_data['script_url'] ),
				];

				$analytics_updated = false;
				foreach ( $new_analytics_data as $key => $value ) {
					WPMUDEV_Dashboard::$settings->set( $key, $value, 'analytics' );
					if ( ! $analytics_updated && ( $prev_analytics_data[ $key ] ?? null ) !== $value ) {
						$analytics_updated = true;
					}
				}

				if ( $analytics_updated ) {
					$this->maybe_clear_hosting_static_cache();
				}

				$res = $data;
			} else {
				$this->parse_api_error( 'Error unserializing remote response.' );
			}
		} else {
			$this->parse_api_error( $response );

			// Check specifically for whether the user has exceeded the sites they can add to the Hub, due to being on the single site plan.
			$body = is_array( $response )
				? wp_remote_retrieve_body( $response )
				: false;

			$data = array();
			if ( is_array( $response ) && ! empty( $body ) ) {
				$data = json_decode( $body, true );
			}

			if ( isset( $data['code'] ) ) {
				if ( 'limit_exceeded_no_hosting_sites' === $data['code'] ) {
					$res['limit_exceeded_no_hosting_sites'] = true;
				}
				$res['limit_data'] = $data['data'];
			}

			/*
			 * For network errors, perform exponential backoff
			 */
			$last_run['time']  = time();
			$last_run['fails'] = $last_run['fails'] + 1;
			WPMUDEV_Dashboard::$settings->set( 'last_run_sync', $last_run, 'general' );
		}

		return $res;
	}

	/**
	 * Contacts the API to get the latest API updates data.
	 *
	 * Returns the available update details if things are working out.
	 * In case the API call fails the function returns boolean false and does
	 * not update the update
	 *
	 * @since    4.4.1
	 * @internal Function only is public because it's an action handler.
	 *
	 * @return array|bool
	 */
	public function refresh_projects_data() {
		$res = false;

		/*
		Note: This endpoint does not require an API key.
		 */

		if ( defined( 'WP_INSTALLING' ) ) {
			return false;
		}

		// Clear the "Force data update" flag to avoid infinite loop.
		WPMUDEV_Dashboard::$settings->set( 'refresh_remote', false, 'flags' );

		// we don't want/need to add apikey to this as we pass no data, and want CDN to cache it as a whole
		$response = WPMUDEV_Dashboard::$api->call(
			'projects',
			false,
			'GET'
		);

		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) ) {

				// Default order to display plugins is the order in the array.
				if ( isset( $data['projects'] ) ) {
					$pos = 1;
					foreach ( $data['projects'] as $id => $project ) {
						$data['projects'][ $id ]['_order'] = $pos;
						$pos                              += 1;
					}
				}

				// Remove projects that are not accessible for current member.
				$data = $this->strip_unavailable_projects( $data );

				WPMUDEV_Dashboard::$settings->set( 'updates_data', $data );
				WPMUDEV_Dashboard::$settings->set( 'last_run_updates', time(), 'general' );
				$this->calculate_upgrades();
				$this->calculate_translation_upgrades( true );
				$this->enqueue_notices( $data );

				$res = $data;
			} else {
				$this->parse_api_error( 'Error unserializing remote response.' );
			}
		} else {
			$this->parse_api_error( $response );

			/*
			 * For network errors, set last run to 1 hour in future so it
			 * doesn't retry every single pageload (in case of server
			 * connection issues)
			 */
			WPMUDEV_Dashboard::$settings->set(
				'last_run_updates',
				time() + HOUR_IN_SECONDS,
				'general'
			);
		}

		return $res;
	}

	/**
	 * Refresh the user profile in the local cache and return it.
	 *
	 * If there is any error while loading the current profile from the API
	 * the function will return boolean false and not update the cache.
	 *
	 * @since  1.0.0
	 * @return array|bool
	 */
	public function refresh_profile() {
		$res = false;

		/*
		Note: We need a VALID API KEY to access this endpoint.
		 */

		if ( defined( 'WP_INSTALLING' ) ) {
			return false;
		}
		if ( ! $this->has_key() ) {
			return false;
		}

		WPMUDEV_Dashboard::$settings->set( 'refresh_profile', false, 'flags' );

		$response = WPMUDEV_Dashboard::$api->call_auth(
			'user-info',
			false,
			'GET'
		);

		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) ) {
				// 3.1.2 - 2012-06-26 PaulM Convert image urls for ssl admin
				if ( is_ssl() && isset( $data['profile']['gravatar'] ) ) {
					$data['profile']['gravatar'] = str_replace(
						'http://',
						'https://',
						$data['profile']['gravatar']
					);
				}

				WPMUDEV_Dashboard::$settings->set( 'profile_data', $data );
				WPMUDEV_Dashboard::$settings->set( 'last_run_profile', time(), 'general' );

				if ( ! empty( $data['profile']['user_name'] ) ) {
					// The only place we use this, is the login form.
					WPMUDEV_Dashboard::$settings->set(
						'auth_user',
						$data['profile']['user_name'],
						'general'
					);
				}

				$res = $data;
			} else {
				$this->parse_api_error( 'Error unserializing remote response.' );
			}
		} else {
			$this->parse_api_error( $response );
		}

		/*
		 * For network errors, set last run to 1 hour in future so it
		 * doesn't retry every single pageload (in case of server
		 * connection issues)
		 */
		WPMUDEV_Dashboard::$settings->set(
			'last_run_profile',
			time() + HOUR_IN_SECONDS,
			'general'
		);

		return $res;
	}

	/**
	 * Refresh a single projects changelog in the local cache and return it.
	 *
	 * If there is any error while loading the changelog from the API the
	 * function will return boolean false and not update the cache.
	 *
	 * The changlog is cached in a transient for 7 days.
	 *
	 * @since  4.0.0
	 *
	 * @param  int $pid Refresh changelog of this project-ID.
	 *
	 * @return array|bool
	 */
	public function refresh_changelog( $pid ) {
		$res = false;

		/*
		Note: This endpoint does not require an API key.
		 */

		if ( defined( 'WP_INSTALLING' ) ) {
			return false;
		}

		$response = WPMUDEV_Dashboard::$api->call(
			'changelog/' . $pid,
			false,
			'GET'
		);

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) ) {
				$data['timestamp'] = time();
				WPMUDEV_Dashboard::$settings->set_transient(
					'changelog_' . $pid,
					$data,
					WEEK_IN_SECONDS
				);
				$res = $data;
			} else {
				$this->parse_api_error( 'Error unserializing remote response' );
			}
		} else {
			$this->parse_api_error( $response );
		}

		return $res;
	}

	/*
	 * *********************************************************************** *
	 * *     TRANSLATION UPDATE FUNCTIONS
	 * *********************************************************************** *
	 */

	/**
	 * Get translation details from the API.
	 * The API usually returns specific data of all projects
	 * so this is parsed and sorted here.
	 *
	 * @since  4.8.0
	 *
	 * @param  string $locale Locale to search translations for.
	 * @param  bool   $force Forcing will update the data and ignore cache.
	 */
	public function get_project_locale_translations( $locale, $force = false ) {
		$res = false;

		/*
		Note: This endpoint requires an API key.
		 */

		if ( defined( 'WP_INSTALLING' ) ) {
			return false;
		}

		// if no locale is present return.
		if ( ! $locale ) {
			return false;
		}

		// return from cache if possible. Get locale baset cache.
		$cached = WPMUDEV_Dashboard::$settings->get_transient( 'translations_all_' . $locale );
		// Return from cache.
		if ( false !== $cached && ! $force ) {
			return $cached;
		}

		// Get last check time.
		$last_checked = WPMUDEV_Dashboard::$settings->get( 'last_run_translation', 'general', 0 );
		// Already checked in within last 12 hours. Skip API call.
		if ( false !== $cached && ! empty( $last_checked ) && $last_checked > ( time() - DAY_IN_SECONDS ) ) {
			return $cached;
		}

		// Do not continue if no API is set.
		if ( ! $this->has_key() ) {
			return false;
		}

		// set api base.
		$api_base = $this->server_root . $this->rest_api_translation;

		// sets up special auth header.
		$options['headers']                  = array();
		$options['headers']['Authorization'] = $this->get_key();

		$response = WPMUDEV_Dashboard::$api->call(
			$api_base . 'sets/' . $locale . '/projects',
			false,
			'GET',
			$options
		);

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) ) {
				$res = $data;
			} else {
				$this->parse_api_error( 'Error unserializing remote response' );
			}
		} else {
			$this->parse_api_error( $response );
		}

		if ( is_array( $res ) ) {
			$res = $this->sort_translation_projects( $res );
		}
		$data['timestamp'] = time();
		WPMUDEV_Dashboard::$settings->set_transient( 'translations_all_' . $locale, $res, WEEK_IN_SECONDS );
		// Set last checked time.
		WPMUDEV_Dashboard::$settings->set( 'last_run_translation', time(), 'general' );

		return $res;
	}

	/**
	 * Parses the Translation API response data and sort active premium projects
	 *
	 * @since  4.8.0
	 *
	 * @param  array $translations Response data from Translation API call to parse.
	 */
	public function sort_translation_projects( $translations ) {
		$data                = WPMUDEV_Dashboard::$api->get_projects_data();
		$projects            = wp_list_pluck( $data['projects'], 'id' );
		$project_translation = array();

		foreach ( $translations as $key => $project ) {
			if ( is_wp_error( $project ) ) {
				continue;
			}
			if ( in_array( $project['dev_project_id'], $projects, true ) ) {
				$project_translation[] = $project;
			}
		}
		return $project_translation;
	}

	/**
	 * Calculate if the translation files need update.
	 *
	 * @since  4.8.0
	 */
	public function calculate_translation_upgrades( $force = false ) {
		$available_translation = wp_get_installed_translations( 'plugins' );
		$projects              = array();
		$translation_needed    = array();
		$locale                = WPMUDEV_Dashboard::$settings->get( 'translation_locale', 'general' );
		$auto_update           = WPMUDEV_Dashboard::$settings->get( 'enable_auto_translation', 'flags' );
		$update_available      = WPMUDEV_Dashboard::$settings->get( 'translation_updates_available' );
		$translations          = $this->get_project_locale_translations( $locale, $force );

		// set api base.
		$api_base = $this->server_root . $this->rest_api_translation;

		// cache
		if ( ! $force && ! empty( $update_available ) ) {
			return $update_available;
		}

		if ( $translations ) {
			// sort installed plugins
			foreach ( $translations as $key => $value ) {
				$project = WPMUDEV_Dashboard::$site->get_project_info( $value['dev_project_id'] );
				if ( ! empty( $project->is_installed ) ) {
					// Handle Snapshot translation slug.
					// https://incsub.atlassian.net/browse/WDD-187
					$value['translation_slug'] = 3760011 === (int) $value['dev_project_id'] ? 'snapshot' : $value['slug'];
					$value['version']          = $project->version_installed;
					$value['name']             = $project->name;
					$projects[]                = $value;
				}
			}
		}

		// check if translation is not installed and if is installed check if is available.
		foreach ( $projects as $key => $updates ) {

			if (
				! array_key_exists( $updates['translation_slug'], $available_translation ) ||
				! array_key_exists( $locale, $available_translation[ $updates['translation_slug'] ] ) ||
					(
						array_key_exists( $updates['translation_slug'], $available_translation ) &&
						array_key_exists( $locale, $available_translation[ $updates['translation_slug'] ] ) &&
						strtotime( $available_translation[ $updates['translation_slug'] ][ $locale ]['PO-Revision-Date'] ) < strtotime( $updates['sets'][0]['last_modified_utc'] )
					)
				) {

				// package url
				$package = $this->rest_url_auth( $updates['sets'][0]['download_url'] );
				$package = add_query_arg(
					array(
						'format' => 'pomo_zip',
					),
					$package
				);

				$translation_needed[] = array(
					'type'       => 'plugin',
					'slug'       => $updates['translation_slug'],
					'language'   => $locale,
					'version'    => $updates['version'],
					'updated'    => $updates['sets'][0]['last_modified_utc'],
					'package'    => $package,
					'autoupdate' => (bool) $auto_update,
					'name'       => $updates['name'],
				);
			}
		}

		WPMUDEV_Dashboard::$settings->set( 'translation_updates_available', $translation_needed );
		return $translation_needed;
	}

	/**
	 * Auto update the translation files.
	 *
	 * @since  4.8.0
	 */
	public function maybe_update_translations() {
		if ( WPMUDEV_Dashboard::$settings->get( 'enable_auto_translation', 'flags' ) ) {
			// upgrade all the translations
			WPMUDEV_Dashboard::$upgrader->upgrade_translation();
			return true;
		}
		return false;
	}
	/**
	 * Parses the API response data and enqueues the correct message for the
	 * current member.
	 *
	 * @since  4.0.0
	 *
	 * @param  array $api_response Response data from API call to parse.
	 */
	public function enqueue_notices( $api_response ) {
		if ( ! $this->has_key() ) {
			return false;
		}
		if ( ! is_array( $api_response ) ) {
			return false;
		}
		if ( empty( $api_response['membership'] ) ) {
			return false;
		}
		$membership_type = $this->get_membership_status();

		$field = false;

		if ( 'full' == $membership_type ) {
			$field = 'full_notice';
		} elseif ( 'single' == $membership_type ) {
			$field = 'single_notice';
		} elseif ( in_array( $membership_type, array( 'expired', 'paused', 'free' ), true ) ) {
			$field = 'free_notice';
		}

		if ( $field && isset( $api_response[ $field ] ) ) {
			$notice = $api_response[ $field ];

			if ( is_array( $notice ) && ! empty( $notice['time'] ) ) {
				WPMUDEV_Dashboard::$notice->enqueue(
					$notice['time'],
					$notice['msg']
				);

				return true;
			}
		}
	}

	/**
	 * Compares the list of local plugins/themes against Api data to determine
	 * available updates. Save the details to wdp_un_updates_available site
	 * option for later use.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $local_projects List of local projects from the transient.
	 * @param  int   $force_update   Optional. A single project ID that is marked
	 *                               for update, regardless of the version-check.
	 *
	 * @return array
	 */
	public function calculate_upgrades( $local_projects = false, $force_update = 0 ) {
		$updates = array();

		if ( ! is_array( $local_projects ) ) {
			$local_projects = WPMUDEV_Dashboard::$site->get_cached_projects();
		}

		// Check for updates.
		foreach ( $local_projects as $pid => $dummy ) {
			// Skip if the project is not installed on current site.
			$item = WPMUDEV_Dashboard::$site->get_project_info( $pid );
			if ( ! $item || empty( $item->name ) ) {
				continue;
			}
			if ( ! $item->is_installed ) {
				continue;
			}

			if ( $pid != $force_update ) {
				if ( ! $item->has_update ) {
					continue;
				}

				/**
				 * Allows excluding certain projects from update notifications.
				 *
				 * Basically just check the ID and return true if you want to
				 * silence updates.
				 *
				 * Filter result is only used if the remote-project `autoupdate`
				 * attribute does not have value 2.
				 *
				 * @since  1.0.0
				 * @api    wpmudev_project_ignore_updates
				 *
				 * @param  bool $flag Defaults to false, return true to silence.
				 * @param  int  $pid  The WDP ID of the plugin/theme
				 */
				$silence = apply_filters(
					'wpmudev_project_ignore_updates',
					false,
					$pid
				);

				// Handle WP auto-upgrades.
				if ( $silence ) {
					continue;
				}
			}

			// Fallback image is main thumbnail.
			$icon = $item->url->thumbnail;
			if ( ! empty( $item->url->icon ) ) {
				// Use icon if available.
				$icon = $item->url->icon;
			} elseif ( ! empty( $item->url->thumbnail_square ) ) {
				// If icon not available, check if we can use square thumb.
				$icon = $item->url->thumbnail_square;
			}

			// Add to array.
			$updates[ $pid ] = array(
				'url'              => $item->url->website,
				'type'             => $item->type,
				'instructions_url' => $item->url->instructions,
				'name'             => $item->name,
				'filename'         => $item->filename,
				'thumbnail'        => $icon,
				'version'          => $item->version_installed,
				'new_version'      => $item->version_latest,
				'changelog'        => $item->changelog,
				'autoupdate'       => $item->can_autoupdate ? 1 : 0,
			);
		}

		// Record results.
		WPMUDEV_Dashboard::$settings->set( 'updates_available', $updates );

		return $updates;
	}

	/**
	 * Remove projects from the data array that are not available for the
	 * current users membership-plan.
	 *
	 * This means:
	 * - FULL members will NOT see any LITE projects.
	 *
	 * @since  4.0.0
	 *
	 * @param  array $data Response from the API.
	 *
	 * @return array Modified response from the API.
	 */
	protected function strip_unavailable_projects( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		if ( empty( $data['projects'] ) ) {
			return $data;
		}

		$my_level = $this->get_membership_status();

		foreach ( $data['projects'] as $id => $project ) {
			if ( 'full' == $my_level ) {
				// Remove lite from the projects list.
				if ( 'lite' == $project['paid'] ) {
					unset( $data['projects'][ $id ] );
				}
			}
		}

		return $data;
	}

	/**
	 * Uses usermeta cache to store gravatar validity flag,
	 * in order to tighten up outgoing requests.
	 *
	 * @since  1.0.0
	 * @return bool True if the user has a gravatar.
	 */
	public function current_user_has_dev_gravatar() {
		$res = (int) WPMUDEV_Dashboard::$site->get_usermeta( '_wdp_un_has_gravatar' );

		// If user has a confirmed gravatar we're good already.
		if ( $res ) {
			return true;
		}

		$profile = $this->get_profile();

		// Check if the user has a valid gravatar.
		$gravatar = $profile['profile']['gravatar'];
		$res      = true;
		$link     = false;

		// Extract clean gravatar URL.
		if ( preg_match_all( '/src=[\'"](https?:\/\/.+\.gravatar.com\/avatar\/.+?\b)/', $gravatar, $parts ) ) {
			$link = isset( $parts[1][0] ) ? $parts[1][0] : false;
		} else {
			$res = false;
		}

		// Check if the gravatar URL is valid.
		if ( $res && $link ) {
			// Construct a special, 404-fallback URL format
			// @see https://en.gravatar.com/site/implement/images/ .
			$link    .= '?d=404';
			$options  = array(
				'sslverify' => true,
				'timeout'   => 5,
			);
			$response = WPMUDEV_Dashboard::$api->call(
				$link,
				false,
				'GET',
				$options
			);

			if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
				$res = false;
			}
		} else {
			$res = false;
		}

		// Only remember the result if the user has a valid gravatar.
		if ( $res ) {
			WPMUDEV_Dashboard::$site->set_usermeta( '_wdp_un_has_gravatar', 1 );
		}

		return $res;
	}


	/*
	 * *********************************************************************** *
	 * *     REMOTE ACCESS FUNCTIONS
	 * *********************************************************************** *
	 */

	/**
	 * Returns details about the remote access permission.
	 *
	 * If no param is specified the function will return a list of all access
	 * details. If a valid param is specified, the function will return a single
	 * string/value of the detail, or false if the detail-name is invalid.
	 *
	 * Details:
	 *   enabled (bool)
	 *   granted (int/timestamp)
	 *   expires (int/timestamp)
	 *   user (int/user-ID)
	 *
	 * @since  4.0.0
	 *
	 * @param  string $detail Optional. Specify the requested detail.
	 *
	 * @return object|scalar The requested detail or all details.
	 */
	public function remote_access_details( $detail = null ) {
		static $Remote_Details = null;

		if ( null === $Remote_Details ) {
			$Remote_Details = array();

			$Remote_Details['enabled'] = false;
			$Remote_Details['expires'] = 0;
			$Remote_Details['granted'] = 0;
			$Remote_Details['user']    = 0;

			$option_val = WPMUDEV_Dashboard::$settings->get( 'remote_access' );

			if ( ! WPMUDEV_DISABLE_REMOTE_ACCESS ) {
				$access = true;
				if ( ! $option_val ) {
					$access = false;
				} elseif ( ! is_array( $option_val ) ) {
					$access = false;
				}

				if ( $access ) {
					if ( isset( $option_val['expire'] ) ) {
						$Remote_Details['expires'] = (int) $option_val['expire'];
					}
					if ( isset( $option_val['granted'] ) ) {
						$Remote_Details['granted'] = (int) $option_val['granted'];
					}
					if ( isset( $option_val['userid'] ) ) {
						$Remote_Details['user'] = (int) $option_val['userid'];
					}
				}

				if ( $Remote_Details['expires'] <= time() ) {
					$access = false;
				}

				$Remote_Details['enabled'] = $access;
			}
		}

		// Reset access details for security if remote access is disabled.
		if ( WPMUDEV_DISABLE_REMOTE_ACCESS || ! $Remote_Details['enabled'] ) {
			$Remote_Details['enabled'] = false;
			$Remote_Details['expires'] = 0;
			$Remote_Details['granted'] = 0;
			$Remote_Details['user']    = 0;
		}

		if ( empty( $detail ) ) {
			return (object) $Remote_Details;
		} elseif ( isset( $Remote_Details[ $detail ] ) ) {
			return $Remote_Details[ $detail ];
		} else {
			return false;
		}
	}

	/**
	 * Enable WPMUDEV staff remote access login.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $action Optional. Can either be 'start' or 'extend'.
	 *                        start .. Will grant access for 5 days from now.
	 *                        extend .. Will grant access for additional 3 days to the current
	 *                        expiration date. This option only works if support access is
	 *                        granted already.
	 */
	public function enable_remote_access( $action = 'start' ) {
		global $current_user;

		if ( ! current_user_can( 'edit_users' ) ) {
			return false;
		}

		if ( WPMUDEV_DISABLE_REMOTE_ACCESS ) {
			return false;
		}

		$details   = $this->remote_access_details();
		$time_base = time();
		$span      = '+5 Days'; // By default grant 5 days from now.

		if ( $details->enabled && $details->expires > $time_base && 'extend' == $action ) {
			// When extending add 3 days to previous expire date.
			$time_base = $details->expires;
			$span      = '+3 Days';
		}

		// We will always create a new access key, even if we only extend!
		$access_key = wp_generate_password( 64, true );
		$expiration = strtotime( $span, $time_base );

		$response = WPMUDEV_Dashboard::$api->call_auth(
			'grant-access',
			array(
				'domain'      => $this->network_site_url(),
				'auth_key'    => $access_key,
				'auth_expire' => $expiration,
				'auth_url'    => admin_url( 'admin-ajax.php?action=wdpunauth' ),
			),
			'POST'
		);

		if ( 200 != wp_remote_retrieve_response_code( $response ) || 'true' != wp_remote_retrieve_body( $response ) ) {
			$this->parse_api_error( $response );

			return false;
		}

		// Save the access details.
		$access = array(
			'userid'  => $current_user->ID,
			'key'     => $access_key,
			'expire'  => $expiration,
			'granted' => time(),
		);
		WPMUDEV_Dashboard::$settings->set( 'remote_access', $access );

		return true;
	}

	/**
	 * Removes access ability for support staff.
	 *
	 * @since 1.0.0
	 */
	public function revoke_remote_access() {
		// Do this whether or not we can update the API.
		WPMUDEV_Dashboard::$settings->set( 'remote_access', '' );

		$response = $this->call_auth(
			'revoke-access',
			array(
				'domain' => $this->network_site_url(),
			),
			'POST'
		);

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			$this->parse_api_error( $response );

			return false;
		}

		return true;
	}

	/**
	 * Listener for WPMU DEV staff remote access login.
	 *
	 * @since    1.0.0
	 * @internal Ajax handler
	 */
	public function authenticate_remote_access() {
		if ( WPMUDEV_DISABLE_REMOTE_ACCESS ) {
			wp_die( 'Error: Remote access disabled in wp-config' );
		}

		$access = WPMUDEV_Dashboard::$settings->get( 'remote_access' );

		// @codingStandardsIgnoreStart: We have own validation, not using nonce!
		$_REQUEST = $_POST;
		// @codingStandardsIgnoreEnd

		$error = false;
		if ( ! $access ) {
			$error = 'no token';
		} elseif ( ! is_array( $access ) ) {
			$error = 'no token';
		} elseif ( empty( $_REQUEST['wdpunkey'] ) ) {
			$error = 'invalid';
		} elseif ( ! hash_equals( $_REQUEST['wdpunkey'], $access['key'] ) ) { // timing attack safe key comparison.
			$error = 'invalid';
		} elseif ( (int) $access['expire'] <= current_time( 'timestamp' ) ) {
			$error = 'expired';
		}

		if ( ! $error ) {
			/* Authentication was successful, log in our support user. */

			// Force 1 hour cookie timeout.
			add_filter( 'auth_cookie_expiration', array( $this, 'auth_cookie_expiration' ) );

			/**
			 * Filter access user_id to be used on remote_access.
			 *
			 * @param int   $usser_id User ID to be used on remote_access.
			 * @param array $access   Remote access details.
			 *
			 * @since 4.11.29
			 */
			$access['userid'] = apply_filters( 'wpmudev_remote_access_set_current_user_id', $access['userid'], $access );

			wp_clear_auth_cookie();
			wp_set_auth_cookie( $access['userid'], false );
			wp_set_current_user( $access['userid'] );

			/**
			 * Do action after successful remote access login..
			 *
			 * @param int   $usser_id User ID being used on remote_access..
			 * @param array $access   Remote access details..
			 *
			 * @since 4.11.29
			 */
			do_action( 'wpmudev_remote_access_set_current_user', $access['userid'], $access );

			$secure_cookie = 'https' === wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );
			setcookie( 'wpmudev_is_staff', '1', time() + 3600, COOKIEPATH, COOKIE_DOMAIN, $secure_cookie, true );

			// Record login info.
			$access['logins'][ time() ] = array(
				'name'  => $_REQUEST['staff'],
				'image' => $_REQUEST['gravatar_hash'],
			);

			WPMUDEV_Dashboard::$settings->set( 'remote_access', $access );

			// Send to dashboard.
			$url = WPMUDEV_Dashboard::$ui->page_urls->support_url . '#access';
			wp_redirect( $url );
			exit;
		} else {
			// There was an error. Display the error message.
			switch ( $error ) {
				case 'no token':
					wp_die( 'The admin did not enable remote access. Please ask the user to grant access.' );

				case 'expired':
					wp_die( 'This access token has expired. Please ask the user to renew it.' );

				case 'invalid':
				default:
					wp_die( 'This is an invalid access token. Please ask the user to grant access.' );
			}
		}
	}

	/**
	 * Listener for SSO through the Hub - 1st step.
	 * This step will check if the Dashboard user is logged in and the SSO is enabled.
	 * If so, it will redirect to the auth endpoint in the Hub to try the first hmac verification.
	 *
	 * @param string $redirect        Where to redirect after a successful SSO.
	 * @param string $nonce           Nonce coming from the DEV site, to later check if user is logged in.
	 * @param string $jwttoken        JWT Token coming from the DEV site, to later check if user is logged in.
	 * @param string $dev_user_apikey User API Key coming from the DEV site, to later check if user is logged in.
	 * @param string $hubteam         Arbitrary team ID, to later check if user has valid access to specified Hub Team.
	 *
	 * @since    4.7.3
	 * @internal Ajax handler
	 */
	public function authenticate_sso_access_step1( $redirect, $nonce, $jwttoken = '', $dev_user_apikey = '', $hubteam = '' ) {
		// If user is already logged in, let's bypass the whole auth process.
		if ( is_user_logged_in() ) {
			$redirect = urldecode( $redirect );
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( WPMUDEV_DISABLE_SSO ) {
			wp_die( 'Error: Single Signon is disabled in wp-config' );
		}

		$access = WPMUDEV_Dashboard::$settings->get( 'enabled', 'sso' );
		$user   = $this->refresh_profile();

		/**
		 * Checking if user is logged in.
		 * This could have been checked by
		 * just checking if api key is present
		 * but an extra layer has been added here
		 * to check if the api key can actually
		 * fetch proper data
		 */
		$logged = ! empty( $user ) && $this->has_key();

		$error = false;
		if ( ! $access ) {
			$error = 'sso_disabled';
		} elseif ( ! $logged ) {
			$error = 'no_logged_in_dashboard_user';
		}

		if ( ! $error ) {
			/* SSO is enabled and Dashboard user is logged in. */

			$token = uniqid() . '-' . microtime( true );
			WPMUDEV_Dashboard::$settings->set( 'active_token', $token, 'sso' );

			// Create state session cookie.
			$api_key = $this->get_key();

			$pre_sso_state = uniqid( '', true );
			$secure_cookie = 'https' === wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );

			setcookie( 'wdp-pre-sso-state', $pre_sso_state, time() + 3600, COOKIEPATH, COOKIE_DOMAIN, $secure_cookie, true );

			$hashed_pre_sso_state = hash_hmac( 'sha256', $pre_sso_state, $api_key );
			// Build hmac for OAuth.
			$domain  = $this->network_site_url();
			$profile = $this->get_profile();

			$outgoing_hmac = hash_hmac( 'sha256', $token . $hashed_pre_sso_state . $redirect . $domain, $api_key );

			$auth_endpoint = $this->rest_url( 'sso-hub' );
			$auth_params   = array(
				'domain'        => $domain,
				'hmac'          => $outgoing_hmac,
				'token'         => $token,
				'pre_sso_state' => $hashed_pre_sso_state,
				'redirect'      => $redirect,
				'_hubteam'      => $hubteam,
			);

			// Use user id if available.
			if ( isset( $profile['profile']['id'] ) ) {
				$auth_params['user_id'] = (int) $profile['profile']['id'];
			} else {
				// Fallback to email in case we are still on old cache.
				$auth_params['email'] = rawurlencode( $profile['profile']['user_name'] );
			}

			if ( $jwttoken ) {
				$auth_params['_jwttoken'] = $jwttoken;
			} elseif ( $dev_user_apikey ) {
				$auth_params['_apikey'] = $dev_user_apikey;
			} else {
				// always fallback to nonce as default auth
				$auth_params['_wpnonce'] = $nonce;
			}

			$auth_endpoint = add_query_arg( $auth_params, $auth_endpoint );

			wp_redirect( $auth_endpoint );
			exit;

		} else {
			// There was an error. Display the error message.
			switch ( $error ) {
				case 'sso_disabled':
					$redirect_upon_failure = add_query_arg(
						array(
							'wdp_sso_fail' => 'sso_disabled',
						),
						wp_login_url( urldecode( $redirect ) )
					);

					wp_redirect( $redirect_upon_failure );
					exit;

				case 'no_logged_in_dashboard_user':
					$redirect_upon_failure = add_query_arg(
						array(
							'wdp_sso_fail' => 'no_logged_in_dashboard_user',
						),
						wp_login_url( urldecode( $redirect ) )
					);

					wp_redirect( $redirect_upon_failure );
					exit;
				default:
					$redirect_upon_failure = add_query_arg(
						array(
							'wdp_sso_fail' => 'unkown_reasons',
						),
						wp_login_url( urldecode( $redirect ) )
					);

					wp_redirect( $redirect_upon_failure );
					exit;
			}
		}
	}

	/**
	 * Listener for SSO through the Hub - 2nd step.
	 * This step will verify the hmac coming from the Hub.
	 * If the verification works, it should log in the user and redirect him.
	 *
	 * @param array $sso_access_data {
	 *                               incoming_hmac: string, The hmac coming from the Hub.
	 *                               token: string, The one-time passcode to prevent replay attacks.
	 *                               pre_sso_state: string, The state value that has been saved in a session cookie, in the previous step.
	 *                               redirect: string, The URL that the user needs to be redirected to.
	 *                               dev_user_id: int, The WPMU DEV User ID coming from the Hub.
	 *                               dev_user_email: string, The WPMU DEV User email address coming from the Hub.
	 *                               } An array of SSO Access data
	 *
	 * @since    4.7.3
	 * @since    4.11.29 Simplify parameter format into an array.
	 * @internal Ajax handler
	 */
	public function authenticate_sso_access_step2( $sso_access_data ) {
		if ( WPMUDEV_DISABLE_SSO ) {
			wp_die( 'Error: Single Signon is disabled in wp-config' );
		}

		$incoming_hmac = $sso_access_data['incoming_hmac'] ?? '';
		$token         = $sso_access_data['token'] ?? '';
		$pre_sso_state = $sso_access_data['pre_sso_state'] ?? '';
		$redirect      = $sso_access_data['redirect'] ?? '';

		$api_key        = $this->get_key();
		$verifying_hmac = hash_hmac( 'sha256', $token . $pre_sso_state . $redirect, $api_key );
		$redirect       = urldecode( $redirect );

		$userid = WPMUDEV_Dashboard::$settings->get( 'userid', 'sso' );
		$user   = $this->refresh_profile();

		$is_valid = hash_equals( $incoming_hmac, $verifying_hmac );

		if ( $is_valid && ! empty( $user ) ) {

			list( $req_id, $token_timestamp ) = explode( '-', $token );
			$token_timestamp_float            = floatval( $token_timestamp );

			// Check if the token has expired.
			$current_time = microtime( true );
			if ( number_format( floatval( $current_time ) - $token_timestamp_float, 2 ) > self::SSO_TOKEN_EXPIRY_TIME ) {
				wp_die( 'The SSO token has expired.' );
			}

			// Check if the session cookie of the state value exists in the user's browser.
			if ( isset( $_COOKIE['wdp-pre-sso-state'] ) ) {
				// Check that the state value is the same with what was passed through the endpoint.
				$hmac_state_value = hash_hmac( 'sha256', $_COOKIE['wdp-pre-sso-state'], $api_key );

				if ( hash_equals( $hmac_state_value, $pre_sso_state ) ) {

					// Check if the token has been used in the past, to prevent replay attacks.
					$previous_sso_token = WPMUDEV_Dashboard::$settings->get( 'previous_token', 'sso', 0 );
					if ( $token_timestamp_float > $previous_sso_token ) {
						WPMUDEV_Dashboard::$settings->set( 'previous_token', $token_timestamp_float, 'sso' );
					} else {
						wp_die( 'The SSO token has been used in the past.' );
					}

					// Finally, check if the passed token is the same that was saved in the first place.
					$active_sso_token = WPMUDEV_Dashboard::$settings->get( 'active_token', 'sso' );
					if ( $token !== $active_sso_token ) {
						wp_die( 'The SSO token could not be verified.' );
					} else {
						WPMUDEV_Dashboard::$settings->set( 'active_token', uniqid(), 'sso' );
					}

					/**
					 * Filter access user_id to be used on SSO.
					 *
					 * @param int   $userid          User ID to be used on SSO.
					 * @param array $sso_access_data SSO access details.
					 *
					 * @since 4.11.29
					 */
					$userid = apply_filters( 'wpmudev_sso_set_current_user_id', $userid, $sso_access_data );

					// If everything checks out, log in the user.
					wp_clear_auth_cookie();
					wp_set_auth_cookie( $userid, false );
					wp_set_current_user( $userid );

					/**
					 * Do action after successful SSO login.
					 *
					 * @param int    $usser_id User ID being used on remote_access..
					 * @param string $redirect Where to redirect after a successful SSO.
					 *
					 * @since 4.11.29
					 */
					do_action( 'wpmudev_sso_set_current_user', $userid, $sso_access_data );

					wp_safe_redirect( $redirect );
					exit;
				} else {
					if ( defined( 'WPMUDEV_API_DEBUG' ) && WPMUDEV_API_DEBUG ) {
						error_log(
							sprintf(
								'WPMU DEV Dashboard Error: SSO failed. Expected: %s / Recieved: %s',
								$hmac_state_value,
								$pre_sso_state
							)
						);
					}
					wp_die( 'Passed state value does not match with the session cookie.' );
				}
			} else {
				wp_die( 'Session cookie of the state value does not exist.' );
			}
		} else {
			wp_die( 'Key mismatch.' );
		}
	}

	/**
	 * Clear WPMUDEV hosting static cache if possible.
	 *
	 * Using Internal `wpmudev_hosting_purge_static_cache` Hosting function
	 *
	 * @return void
	 */
	public function maybe_clear_hosting_static_cache() {
		// Only if WPMUDEV hosting.
		if ( ! $this->is_wpmu_dev_hosting() ) {
			return;
		}

		if ( function_exists( 'wpmudev_hosting_purge_static_cache' ) ) {
			wpmudev_hosting_purge_static_cache();
		}
	}

	/**
	 * Enable and configure analytics to collect data for the site.
	 *
	 * @since  4.6
	 *
	 * @return bool
	 */
	public function analytics_enable() {
		$api_base = $this->server_root . $this->rest_api_analytics;

		// sets up special auth header.
		$options['headers']                  = array();
		$options['headers']['Authorization'] = $this->get_key();

		$response = WPMUDEV_Dashboard::$api->call(
			$api_base . 'enable',
			array(
				'domain'       => $this->network_site_url(),
				'sync_version' => WPMUDEV_Dashboard::$version,
			),
			'POST',
			$options
		);
		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $data['site_id'], $data['tracker'] ) ) {
				WPMUDEV_Dashboard::$settings->set( 'site_id', $data['site_id'], 'analytics' );
				WPMUDEV_Dashboard::$settings->set( 'tracker', $data['tracker'], 'analytics' );
				if ( isset( $data['script_url'] ) ) {
					WPMUDEV_Dashboard::$settings->set( 'script_url', $data['script_url'], 'analytics' );
				}

				// Clear WPMUDEV static cache.
				$this->maybe_clear_hosting_static_cache();

				return true;
			}
		} else {
			$this->parse_api_error( $response );
		}

		return false;
	}

	/**
	 * Enable and configure analytics to collect data for the site.
	 *
	 * @since  4.6
	 *
	 * @return bool
	 */
	public function analytics_disable() {
		$api_base = $this->server_root . $this->rest_api_analytics;

		// sets up special auth header.
		$options['headers']                  = array();
		$options['headers']['Authorization'] = $this->get_key();

		$response = WPMUDEV_Dashboard::$api->call(
			$api_base . 'disable',
			array( 'domain' => $this->network_site_url() ),
			'POST',
			$options
		);

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			// Clear WPMUDEV static cache.
			$this->maybe_clear_hosting_static_cache();

			return true;
		} else {
			$this->parse_api_error( $response );
		}

		return false;
	}

	/**
	 * Get overall analytics data for the given site.
	 *  Cached in a transient for 24hrs.
	 *
	 * @since  4.6
	 *
	 * @param int $days_ago How many days in the past to look back.
	 * @param int $subsite  If filtering to a subsite pass the blog_id of it.
	 *
	 * @return mixed
	 */
	public function analytics_stats_overall( $days_ago = 7, $subsite = 0 ) {
		$site_id = WPMUDEV_Dashboard::$settings->get( 'site_id', 'analytics' );
		$tracker = WPMUDEV_Dashboard::$settings->get( 'tracker', 'analytics' );

		// Analytics site id is needed.
		if ( empty( $site_id ) ) {
			return false;
		}

		// figure out what widget view we want.
		if ( is_multisite() ) {
			if ( $subsite ) {
				$type = 'subsite';
			} else {
				$type = 'network';
			}
		} else {
			$type = 'normal';
		}

		$api_base    = $this->server_root . $this->rest_api_analytics;
		$remote_path = add_query_arg( 'days_ago', $days_ago, "{$api_base}site/{$site_id}/overall/{$type}" );
		if ( $subsite ) {
			$remote_path = add_query_arg( 'subsite', $subsite, $remote_path );
		}

		// Add hub site ID.
		$remote_path = add_query_arg( 'domain', $this->network_site_url(), $remote_path );

		// version to update the logic completely ( typically on plugin update )
		// include tracker url in cache key, as id can be collided between tracker
		$transient_key = 'analytics_data_v1_' . md5( $remote_path . $tracker );
		// Get from transient.
		$cached = WPMUDEV_Dashboard::$settings->get_transient( $transient_key );

		// return from cache if possible. We don't use *_site_transient() to avoid unnecessary autoloading. ( we use it behind the scene though ?)
		if ( false !== $cached ) {
			$cached = $this->_analytics_overall_filter_metrics( $cached );

			// Temporary fix to make data format in autocomplete format.
			if ( ! empty( $cached['autocomplete'][0]['value'] ) && is_array( $cached['autocomplete'][0]['value'] ) ) {
				foreach ( $cached['autocomplete'] as $index => $item ) {
					$cached['autocomplete'][ $index ] = array(
						'label'  => $item['label'],
						'value'  => $item['label'],
						'filter' => $item['value']['filter'],
						'type'   => $item['value']['type'],
					);
				}
			}

			return $cached;
		}

		// sets up special auth header.
		$options['headers']                  = array();
		$options['headers']['Authorization'] = $this->get_key();

		$response = WPMUDEV_Dashboard::$api->call(
			$remote_path,
			false,
			'GET',
			$options
		);

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
		} else {
			$this->parse_api_error( $response );
			return false;
		}

		// parse the data into a format best for our needs
		$final_data                 = array();
		$final_data['autocomplete'] = array();
		$comparison_data            = isset( $data['comparision_overall'] ) ? $data['comparision_overall'] : array();
		// overall data for charts and totals.
		if ( isset( $data['overall'] ) ) {

			// available fields are a bit different when filtered to subsite
			$to_process = array(
				'bounce_rate'      => array(
					'orig_key' => 'bounce_rate',
					'label'    => __( 'Bounce Rate', 'wpmudev' ),
					'callback' => '_analytics_format_pcnt',
				),
				'exit_rate'        => array(
					'orig_key' => 'exit_rate',
					'label'    => __( 'Exit Rate', 'wpmudev' ),
					'callback' => '_analytics_format_pcnt',
				),
				'visit_time'       => array(
					'orig_key' => 'avg_time_on_site',
					'label'    => __( 'Visit Time', 'wpmudev' ),
					'callback' => '_analytics_format_time',
				),
				'visits'           => array(
					'orig_key' => 'nb_visits',
					'label'    => __( 'Entrances', 'wpmudev' ),
					'callback' => '_analytics_format_num',
				),
				'unique_visits'    => array(
					'orig_key' => 'nb_uniq_visitors',
					'label'    => __( 'Unique Visits', 'wpmudev' ),
					'callback' => '_analytics_format_num',
				),
				'pageviews'        => array(
					'orig_key' => 'nb_pageviews',
					'label'    => __( 'Page Views', 'wpmudev' ),
					'callback' => '_analytics_format_num',
				),
				'unique_pageviews' => array(
					'orig_key' => 'nb_uniq_pageviews',
					'label'    => __( 'Unique Page Views', 'wpmudev' ),
					'callback' => '_analytics_format_num',
				),
			);
			if ( $subsite ) {
				unset( $to_process['visits'] );
				unset( $to_process['unique_visits'] );
				unset( $to_process['visit_time'] );
				$to_process['pageviews']        = array(
					'orig_key' => 'nb_hits',
					'label'    => __( 'Pageviews', 'wpmudev' ),
					'callback' => '_analytics_format_num',
				);
				$to_process['unique_pageviews'] = array(
					'orig_key' => 'nb_visits',
					'label'    => __( 'Unique Pageviews', 'wpmudev' ),
					'callback' => '_analytics_format_num',
				);
				$to_process['page_time']        = array(
					'orig_key' => 'avg_time_on_page',
					'label'    => __( 'Page Time', 'wpmudev' ),
					'callback' => '_analytics_format_time',
				);
			}

			foreach ( $data['overall'] as $date => $day ) {
				if ( isset( $day[0] ) ) {
					$day = $day[0];
				}

				// this helps data appear on correct day in x axis.
				$timestamp = date( 'c', strtotime( '+1 day', strtotime( $date ) ) );
				foreach ( $to_process as $key => $process ) {
					$y_value = isset( $day[ $process['orig_key'] ] ) ? $day[ $process['orig_key'] ] : null;
					$final_data['overall']['chart'][ $key ]['label']  = $process['label'];
					$final_data['overall']['chart'][ $key ]['data'][] = array(
						't' => $timestamp,
						'y' => $y_value,
					);
				}
			}

			foreach ( $comparison_data as $date => $day ) {
				if ( isset( $day[0] ) ) {
					$day = $day[0];
				}
				// this helps data appear on correct day in x axis.
				$timestamp = date( 'c', strtotime( '+1 day', strtotime( $date ) ) );
				foreach ( $to_process as $key => $process ) {
					$y_value                          = isset( $day[ $process['orig_key'] ] ) ? $day[ $process['orig_key'] ] : null;
					$comparing_data[ $key ]['label']  = $process['label'];
					$comparing_data[ $key ]['data'][] = array(
						't' => $timestamp,
						'y' => $y_value,
					);

				}
			}

			// for totals, we only wants if any of the days ( keys ) has page views
			// note: 1 visits can have multiple page views
			$data_count_with_page_views = 0;
			if ( isset( $final_data['overall']['chart']['pageviews']['data'] ) ) {
				foreach ( $final_data['overall']['chart']['pageviews']['data'] as $key => $value ) {
					if ( isset( $value['y'] ) && $value['y'] > 0 ) {
						++$data_count_with_page_views;
					}
				}
			}
			$data_compare_count_with_page_views = 0;
			if ( isset( $comparing_data['pageviews']['data'] ) ) {
				foreach ( $comparing_data['pageviews']['data'] as $key => $value ) {
					if ( isset( $value['y'] ) && $value['y'] > 0 ) {
						++$data_compare_count_with_page_views;
					}
				}
			}

			foreach ( $to_process as $key => $process ) {
				if ( isset( $final_data['overall']['chart'][ $key ] ) ) {
					$totals        = 0;
					$compare_total = 0;
					$compare_avg   = false;
					$compare_data  = array();
					if ( isset( $comparing_data[ $key ] ) ) {
						$compare_data = wp_list_pluck( $comparing_data[ $key ]['data'], 'y' );
					}

					$list = wp_list_pluck( $final_data['overall']['chart'][ $key ]['data'], 'y' );

					// for number we want total, others mean
					if ( '_analytics_format_num' === $process['callback'] ) {
						$totals        = array_sum( $list );
						$compare_total = array_sum( $compare_data );
					} else {
						$avg         = 0;
						$compare_avg = 0;
						if ( $data_count_with_page_views > 0 ) {
							$avg = array_sum( $list ) / $data_count_with_page_views;
						}
						if ( $data_compare_count_with_page_views > 0 ) {
							$compare_avg = array_sum( $compare_data ) / $data_compare_count_with_page_views;
						}
						$totals        = $avg;
						$compare_total = $compare_avg;
					}

					if ( count( $list ) ) {

						// assume 1 if no data found.
						$start = $compare_total > 0 ? abs( $compare_total ) : 0;

						if ( 0 === $start && 0 === abs( $totals ) ) {
							$end = 0;
						} else {
							$end = $totals > 0 ? abs( $totals ) : 1;
						}

						// if no data found the current data is the increment.
						if ( $start <= 0 && $end <= 0 ) {
							$change = 0;
						} elseif ( $start <= 0 ) {
							$change = round( $end, 1 );
						} else {
							$change = round( ( ( $end - $start ) / $start * 100 ), 1 );
						}
					} else {
						$change = 0;
					}

					$final_data['overall']['totals'][ $key ] = array(
						'change'    => number_format_i18n( abs( $change ) ) . '%',
						'direction' => ( $change == 0 ) ? 'none' : ( $change > 0 ? 'up' : 'down' ),
						'value'     => call_user_func( array( $this, $process['callback'] ), $totals ),
					);

				}
			}
		}

		$to_process = array(
			'pageviews'        => array(
				'orig_key' => 'nb_hits',
				'callback' => '_analytics_format_num',
			),
			'unique_pageviews' => array(
				'orig_key' => 'nb_visits',
				'callback' => '_analytics_format_num',
			),
			'bounce_rate'      => array(
				'orig_key' => 'bounce_rate',
				'callback' => '_analytics_format_pcnt',
			),
			'visits'           => array(
				'orig_key' => 'entry_nb_visits',
				'callback' => '_analytics_format_num',
			),
			'exit_rate'        => array(
				'orig_key' => 'exit_rate',
				'callback' => '_analytics_format_pcnt',
			),
			'gen_time'         => array(
				'orig_key' => 'avg_page_load_time',
				'callback' => '_analytics_format_time',
			),
			'page_time'        => array(
				'orig_key' => 'avg_time_on_page',
				'callback' => '_analytics_format_time',
			),
		);

		// top pages & posts list.
		if ( isset( $data['pages'] ) ) {
			foreach ( $data['pages'] as $page ) {

				$new_page = array(
					'filter' => isset( $page['url'] ) ? $page['url'] : '',
					'name'   => isset( $page['label'] ) ? trim( $page['label'] ) : '',
				);

				// get desired categories.
				foreach ( $to_process as $key => $process ) {
					if ( isset( $page[ $process['orig_key'] ] ) ) {
						$new_page[ $key ] = array(
							'value' => call_user_func( array( $this, $process['callback'] ), $page[ $process['orig_key'] ] ),
							'sort'  => $page[ $process['orig_key'] ],
						);
					}
				}
				$final_data['pages'][]        = $new_page;
				$final_data['autocomplete'][] = array(
					'label'  => sprintf( __( 'Page: %s', 'wpmudev' ), $new_page['name'] ),
					'value'  => sprintf( __( 'Page: %s', 'wpmudev' ), $new_page['name'] ),
					'type'   => 'page',
					'filter' => $new_page['filter'],
				);
			}
		}

		// sites list.
		if ( isset( $data['sites'] ) && is_multisite() ) {
			$blog_ids = array();
			foreach ( $data['sites'] as $site ) {

				$new_site = array(
					'filter' => urlencode( $site['label'] ),
				);

				// try to get the blog domain from blog_id
				$blog_id = trim( $site['label'] );
				if ( $blog_id && is_numeric( $blog_id ) && absint( $blog_id ) && ! in_array( absint( $blog_id ), $blog_ids, true ) ) {
					$blog = get_blog_details( absint( $blog_id ), true );
					if ( $blog ) {
						$blog_ids[]       = absint( $blog_id ); // save to make sure we only see each blog once (first with most data) in case of tracking bugs.
						$new_site['name'] = untrailingslashit( $blog->domain . $blog->path ) . ' - ' . $blog->blogname;
					} else {
						continue;
					}
				} else {
					continue;
				}

				// get desired categories.
				foreach ( $to_process as $key => $process ) {
					if ( isset( $site[ $process['orig_key'] ] ) ) {
						$new_site[ $key ] = array(
							'value' => call_user_func( array( $this, $process['callback'] ), $site[ $process['orig_key'] ] ),
							'sort'  => $site[ $process['orig_key'] ],
						);
					}
				}
				$final_data['sites'][]        = $new_site;
				$final_data['autocomplete'][] = array(
					'label'  => sprintf( __( 'Site: %s', 'wpmudev' ), $new_site['name'] ),
					'value'  => sprintf( __( 'Site: %s', 'wpmudev' ), $new_site['name'] ),
					'type'   => 'subsite',
					'filter' => $new_site['filter'],
				);
			}
		}

		// authors list.
		if ( isset( $data['authors'] ) ) {
			// page_time key is different for custom dimension.
			$to_process['page_time'] = array(
				'orig_key' => 'avg_time_on_dimension',
				'callback' => '_analytics_format_time',
			);

			foreach ( $data['authors'] as $author ) {

				// attempt to decode author json object.
				$author_object = json_decode( trim( $author['label'] ) );
				if ( ! isset( $author_object->ID ) ) {
					continue;
				}

				$new_author = array();
				$user       = get_userdata( $author_object->ID );
				if ( $user ) {
					$new_author['name']     = $user->display_name;
					$new_author['gravatar'] = get_avatar_url( $author_object->ID, array( 'size' => 25 ) );
				} else {
					$new_author['name']     = $author_object->name;
					$new_author['gravatar'] = get_avatar_url( $author_object->avatar, array( 'size' => 25 ) );
				}

				$new_author['filter'] = urlencode( $author['label'] );

				// get desired categories.
				foreach ( $to_process as $key => $process ) {
					if ( isset( $author[ $process['orig_key'] ] ) ) {
						$new_author[ $key ] = array(
							'value' => call_user_func( array( $this, $process['callback'] ), $author[ $process['orig_key'] ] ),
							'sort'  => $author[ $process['orig_key'] ],
						);
					}
				}
				$final_data['authors'][]      = $new_author;
				$final_data['autocomplete'][] = array(
					'label'  => sprintf( __( 'Author: %s', 'wpmudev' ), $new_author['name'] ),
					'value'  => sprintf( __( 'Author: %s', 'wpmudev' ), $new_author['name'] ),
					'type'   => 'author',
					'filter' => $new_author['filter'],
				);
			}
		}

		// Cache for later.
		WPMUDEV_Dashboard::$settings->set_transient( $transient_key, $final_data, DAY_IN_SECONDS );

		return $this->_analytics_overall_filter_metrics( $final_data );
	}

	/**
	 * Get analytics data for a specific dimension query for the given site.
	 *  Not cached due to the vast number of possible args.
	 *
	 * @since  4.6
	 *
	 * @param int    $days_ago How many days in the past to look back
	 * @param string $type     Can be page|author|subsite.
	 * @param string $filter   Page, author, or blog_id to filter to.
	 *
	 * @return mixed
	 */
	public function analytics_stats_single( $days_ago, $type, $filter ) {
		$site_id  = WPMUDEV_Dashboard::$settings->get( 'site_id', 'analytics' );
		$metrics  = WPMUDEV_Dashboard::$site->get_metrics_on_analytics();
		$days_ago = isset( $days_ago ) ? $days_ago : 7;

		$api_base    = $this->server_root . $this->rest_api_analytics;
		$remote_path = add_query_arg(
			array(
				'filter'   => $filter,
				'days_ago' => $days_ago,
				'domain'   => $this->network_site_url(),
			),
			"{$api_base}site/{$site_id}/{$type}"
		);

		// sets up special auth header.
		$options['headers']                  = array();
		$options['headers']['Authorization'] = $this->get_key();

		$response = WPMUDEV_Dashboard::$api->call(
			$remote_path,
			false,
			'GET',
			$options
		);

		if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
		} else {
			$this->parse_api_error( $response );
			return false;
		}

		// parse the data into a format best for our needs
		$final_data      = array();
		$comparison_data = isset( $data['comparisions'] ) ? $data['comparisions'] : array();

		// available fields are a bit different when filtered to subsite
		$to_process = array(
			'bounce_rate'      => array(
				'orig_key' => 'bounce_rate',
				'label'    => __( 'Bounce Rate', 'wpmudev' ),
				'callback' => '_analytics_format_pcnt',
			),
			'exit_rate'        => array(
				'orig_key' => 'exit_rate',
				'label'    => __( 'Exit Rate', 'wpmudev' ),
				'callback' => '_analytics_format_pcnt',
			),
			'visits'        => array(
				'orig_key' => 'entry_nb_visits',
				'label'    => __( 'Entrances', 'wpmudev' ),
				'callback' => '_analytics_format_num',
			),
			'page_time'        => array(
				'orig_key' => 'avg_time_on_page',
				'label'    => __( 'Page Time', 'wpmudev' ),
				'callback' => '_analytics_format_time',
			),
			'pageviews'        => array(
				'orig_key' => 'nb_hits',
				'label'    => __( 'Pageviews', 'wpmudev' ),
				'callback' => '_analytics_format_num',
			),
			'unique_pageviews' => array(
				'orig_key' => 'nb_visits',
				'label'    => __( 'Unique Pageviews', 'wpmudev' ),
				'callback' => '_analytics_format_num',
			),
		);

		// limit metrics
		if ( ! in_array( 'pageviews', $metrics, true ) ) {
			unset( $to_process['pageviews'] );
		}
		if ( ! in_array( 'unique_pageviews', $metrics, true ) ) {
			unset( $to_process['unique_pageviews'] );
		}
		if ( ! in_array( 'page_time', $metrics, true ) ) {
			unset( $to_process['page_time'] );
		}
		if ( ! in_array( 'bounce_rate', $metrics, true ) ) {
			unset( $to_process['bounce_rate'] );
		}
		if ( ! in_array( 'exit_rate', $metrics, true ) ) {
			unset( $to_process['exit_rate'] );
		}
		if ( ! in_array( 'visits', $metrics, true ) ) {
			unset( $to_process['visits'] );
		}

		// key is different for authors
		if ( 'author' === $type ) {
			if ( in_array( 'page_time', $metrics, true ) ) {
				$to_process['page_time']['orig_key'] = 'avg_time_on_dimension';
			}
		}

		foreach ( $data as $date => $day ) {
			// Do not convert non-date values.
			if ( 'comparisions' === $date ) {
				continue;
			}

			if ( isset( $day[0] ) ) {
				$day = $day[0];
			}

			// this helps data appear on correct day in x axis.
			$timestamp = date( 'c', strtotime( '+1 day', strtotime( $date ) ) );
			foreach ( $to_process as $key => $process ) {
				$y_value                               = isset( $day[ $process['orig_key'] ] ) ? $day[ $process['orig_key'] ] : null;
				$final_data['chart'][ $key ]['label']  = $process['label'];
				$final_data['chart'][ $key ]['data'][] = array(
					't' => $timestamp,
					'y' => $y_value,
				);
			}
		}

		foreach ( $comparison_data as $date => $day ) {
			// Do not convert non-date values.
			if ( 'comparisions' === $date ) {
				continue;
			}

			if ( isset( $day[0] ) ) {
				$day = $day[0];
			}
			// this helps data appear on correct day in x axis.
			$timestamp = date( 'c', strtotime( '+1 day', strtotime( $date ) ) );
			foreach ( $to_process as $key => $process ) {
				$y_value                                   = isset( $day[ $process['orig_key'] ] ) ? $day[ $process['orig_key'] ] : null;
				$comparing_data['chart'][ $key ]['label']  = $process['label'];
				$comparing_data['chart'][ $key ]['data'][] = array(
					't' => $timestamp,
					'y' => $y_value,
				);
			}
		}
		foreach ( $to_process as $key => $process ) {

			if ( isset( $final_data['chart'][ $key ] ) ) {
				$list          = array_filter( wp_list_pluck( $final_data['chart'][ $key ]['data'], 'y' ) );
				$compare_data  = array();
				$totals        = 0;
				$compare_total = 0;
				$compare_avg   = false;

				if ( isset( $comparing_data['chart'][ $key ] ) ) {
					$compare_data = wp_list_pluck( $comparing_data['chart'][ $key ]['data'], 'y' );
				}

				// for number we want total, others mean
				if ( '_analytics_format_num' === $process['callback'] ) {
					$totals        = array_sum( $list );
					$compare_total = array_sum( $compare_data );
				} else {
					if ( count( $list ) ) {
						$avg = array_sum( $list ) / count( $list );
						if ( ! empty( $compare_data ) && count( $compare_data ) ) {
							$compare_avg = array_sum( $compare_data ) / count( $compare_data );
						}
					} else {
						$avg         = false;
						$compare_avg = false;
					}
					$totals        = $avg;
					$compare_total = $compare_avg;
				}

				if ( count( $list ) ) {

					// assume 1 if no data found.
					$start = $compare_total > 0 ? abs( $compare_total ) : 0;

					if ( 0 === $start && 0 === abs( $totals ) ) {
						$end = 0;
					} else {
						$end = $totals > 0 ? abs( $totals ) : 1;
					}

					// if no data found the current data is the increment.
					if ( $start <= 0 && $end <= 0 ) {
						$change = 0;
					} elseif ( $start <= 0 ) {
						$change = round( $end, 1 );
					} else {
						$change = round( ( ( $end - $start ) / $start * 100 ), 1 );
					}
				} else {
					$change = 0;
				}

				$final_data['totals'][ $key ] = array(
					'change'    => number_format_i18n( abs( $change ) ) . '%',
					'direction' => ( $change == 0 ) ? 'none' : ( $change > 0 ? 'up' : 'down' ),
					'value'     => call_user_func( array( $this, $process['callback'] ), $totals ),
				);

			}
		}

		return $final_data;
	}

	/*
	 * *********************************************************************** *
	 * *     INTERNAL ACTION HANDLERS
	 * *********************************************************************** *
	 */

	/**
	 * Callback to format percentage for analytics widget.
	 *
	 * @since  4.6
	 *
	 * @param  int|float $decimal Number to format.
	 *
	 * @return string
	 */
	public function _analytics_format_pcnt( $decimal ) {
		if ( false === $decimal ) {
			return '-';
		}
		return round( $decimal * 100, 2 ) . '%';
	}

	/**
	 * Callback to format time for analytics widget.
	 *
	 * @since  4.6
	 *
	 * @param  int|float $seconds Seconds to format.
	 *
	 * @return string
	 */
	public function _analytics_format_time( $seconds ) {
		if ( false === $seconds ) {
			return '-';
		}
		if ( $seconds >= 60 ) {
			$mins = round( ( $seconds / 60 ), 2 );
			return sprintf( _n( '%s min', '%s mins', $seconds, 'wpmudev' ), $mins );
		} elseif ( $seconds >= 1 ) {
			$seconds = round( $seconds, 2 );
			return sprintf( _n( '%s sec', '%s secs', $seconds, 'wpmudev' ), $seconds );
		} else {
			$milliseconds = round( $seconds * 1000 );
			return sprintf( __( '%s ms', 'wpmudev' ), $milliseconds );
		}
	}

	/**
	 * Callback to format number for analytics widget.
	 *
	 * @since  4.6
	 *
	 * @param  int|float $number Number to format.
	 *
	 * @return string
	 */
	public function _analytics_format_num( $number ) {
		return number_format_i18n( round( $number ) );
	}

	/**
	 * Filter overall analytics data
	 *
	 * @since 4.7
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function _analytics_overall_filter_metrics( $data ) {
		$metrics = WPMUDEV_Dashboard::$site->get_metrics_on_analytics();
		// filter metrics
		if ( isset( $data['overall'] ) && is_array( $data['overall'] ) ) {
			if ( isset( $data['overall']['chart'] ) && is_array( $data['overall']['chart'] ) ) {
				// limit metrics
				if ( ! in_array( 'pageviews', $metrics, true ) ) {
					unset( $data['overall']['chart']['pageviews'] );
				}
				if ( ! in_array( 'unique_pageviews', $metrics, true ) ) {
					unset( $data['overall']['chart']['unique_pageviews'] );
				}
				if ( ! in_array( 'page_time', $metrics, true ) ) {
					unset( $data['overall']['chart']['page_time'] );
					unset( $data['overall']['chart']['visit_time'] );
				}
				if ( ! in_array( 'bounce_rate', $metrics, true ) ) {
					unset( $data['overall']['chart']['bounce_rate'] );
				}
				if ( ! in_array( 'exit_rate', $metrics, true ) ) {
					unset( $data['overall']['chart']['exit_rate'] );
				}
				if ( ! in_array( 'visits', $metrics, true ) ) {
					unset( $data['overall']['chart']['visits'] );
				}
			}
			if ( isset( $data['overall']['totals'] ) && is_array( $data['overall']['totals'] ) ) {
				// limit metrics
				if ( ! in_array( 'pageviews', $metrics, true ) ) {
					unset( $data['overall']['totals']['pageviews'] );
				}
				if ( ! in_array( 'unique_pageviews', $metrics, true ) ) {
					unset( $data['overall']['totals']['unique_pageviews'] );
				}
				if ( ! in_array( 'page_time', $metrics, true ) ) {
					unset( $data['overall']['totals']['page_time'] );
					unset( $data['overall']['totals']['visit_time'] );
				}
				if ( ! in_array( 'bounce_rate', $metrics, true ) ) {
					unset( $data['overall']['totals']['bounce_rate'] );
				}
				if ( ! in_array( 'exit_rate', $metrics, true ) ) {
					unset( $data['overall']['totals']['exit_rate'] );
				}
				if ( ! in_array( 'visits', $metrics, true ) ) {
					unset( $data['overall']['totals']['visits'] );
				}
			}
		}

		if ( isset( $data['pages'] ) && is_array( $data['pages'] ) ) {
			foreach ( $data['pages'] as $key => $page ) {
				// limit metrics
				if ( ! in_array( 'pageviews', $metrics, true ) ) {
					unset( $data['pages'][ $key ]['pageviews'] );
				}
				if ( ! in_array( 'unique_pageviews', $metrics, true ) ) {
					unset( $data['pages'][ $key ]['unique_pageviews'] );
				}
				if ( ! in_array( 'page_time', $metrics, true ) ) {
					unset( $data['pages'][ $key ]['page_time'] );
					unset( $data['pages'][ $key ]['visit_time'] );
				}
				if ( ! in_array( 'bounce_rate', $metrics, true ) ) {
					unset( $data['pages'][ $key ]['bounce_rate'] );
				}
				if ( ! in_array( 'exit_rate', $metrics, true ) ) {
					unset( $data['pages'][ $key ]['exit_rate'] );
				}
				if ( ! in_array( 'visits', $metrics, true ) ) {
					unset( $data['pages'][ $key ]['visits'] );
				}
			}
		}

		if ( isset( $data['sites'] ) && is_array( $data['sites'] ) ) {
			foreach ( $data['sites'] as $key => $site ) {
				// limit metrics
				if ( ! in_array( 'pageviews', $metrics, true ) ) {
					unset( $data['sites'][ $key ]['pageviews'] );
				}
				if ( ! in_array( 'unique_pageviews', $metrics, true ) ) {
					unset( $data['sites'][ $key ]['unique_pageviews'] );
				}
				if ( ! in_array( 'page_time', $metrics, true ) ) {
					unset( $data['sites'][ $key ]['page_time'] );
					unset( $data['sites'][ $key ]['visit_time'] );
				}
				if ( ! in_array( 'bounce_rate', $metrics, true ) ) {
					unset( $data['sites'][ $key ]['bounce_rate'] );
				}
				if ( ! in_array( 'exit_rate', $metrics, true ) ) {
					unset( $data['sites'][ $key ]['exit_rate'] );
				}
				if ( ! in_array( 'visits', $metrics, true ) ) {
					unset( $data['sites'][ $key ]['visits'] );
				}
			}
		}

		if ( isset( $data['authors'] ) && is_array( $data['authors'] ) ) {
			foreach ( $data['authors'] as $key => $author ) {
				// limit metrics
				if ( ! in_array( 'pageviews', $metrics, true ) ) {
					unset( $data['authors'][ $key ]['pageviews'] );
				}
				if ( ! in_array( 'unique_pageviews', $metrics, true ) ) {
					unset( $data['authors'][ $key ]['unique_pageviews'] );
				}
				if ( ! in_array( 'page_time', $metrics, true ) ) {
					unset( $data['authors'][ $key ]['page_time'] );
					unset( $data['authors'][ $key ]['visit_time'] );
				}
				if ( ! in_array( 'bounce_rate', $metrics, true ) ) {
					unset( $data['authors'][ $key ]['bounce_rate'] );
				}
				if ( ! in_array( 'exit_rate', $metrics, true ) ) {
					unset( $data['authors'][ $key ]['exit_rate'] );
				}
				if ( ! in_array( 'visits', $metrics, true ) ) {
					unset( $data['authors'][ $key ]['visits'] );
				}
			}
		}

		return $data;
	}

	/**
	 * Used to filter auth cookie expiration.
	 *
	 * @since  4.5
	 *
	 * @param  int $timeout Time in seconds.
	 *
	 * @return int $timeout
	 */
	public function auth_cookie_expiration( $timeout ) {
		return HOUR_IN_SECONDS;
	}

	/**
	 * Parses an HTTP response object (or other value) to determine an error
	 * reason. The error reason is added to the PHP error log.
	 *
	 * @since  4.0.0
	 *
	 * @param mixed $response String, WP_Error object, HTTP response array.
	 */
	protected function parse_api_error( $response ) {
		$error_code = wp_remote_retrieve_response_code( $response );
		if ( ! $error_code ) {
			$error_code = 500;
		}
		$this->api_error = '';

		$body = is_array( $response )
			? wp_remote_retrieve_body( $response )
			: false;

		if ( is_scalar( $response ) ) {
			$this->api_error = $response;
		} elseif ( is_wp_error( $response ) ) {
			$this->api_error = $response->get_error_message();
		} elseif ( is_array( $response ) && ! empty( $body ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) && ! empty( $data['message'] ) ) {
				$this->api_error = $data['message'];
			}
		}

		$url = '(unknown URL)';
		if ( is_array( $response ) && isset( $response['request_url'] ) ) {
			$url = $response['request_url'];
		}

		if ( empty( $this->api_error ) ) {
			$this->api_error = sprintf(
				'HTTP Error: %s "%s"',
				$error_code,
				wp_remote_retrieve_response_message( $response )
			);
		}

		// Collect back-trace information for the logfile.
		$caller_dump = '';
		if ( defined( 'WPMUDEV_API_DEBUG' ) && WPMUDEV_API_DEBUG ) {
			$trace     = debug_backtrace();
			$caller    = array();
			$last_line = '';
			foreach ( $trace as $level => $item ) {
				if ( ! isset( $item['class'] ) ) {
					$item['class'] = '';
				}
				if ( ! isset( $item['type'] ) ) {
					$item['type'] = '';
				}
				if ( ! isset( $item['function'] ) ) {
					$item['function'] = '<function>';
				}
				if ( ! isset( $item['line'] ) ) {
					$item['line'] = '?';
				}

				if ( $level > 0 ) {
					$caller[] = $item['class'] .
					            $item['type'] .
					            $item['function'] .
					            ':' . $last_line;
				}
				$last_line = $item['line'];
			}
			$caller_dump = "\n\t# " . implode( "\n\t# ", $caller );

			if ( is_array( $response ) && isset( $response['request_url'] ) ) {
				$caller_dump = "\n\tURL: " . $response['request_url'] . $caller_dump;
			}

			// Log the error to PHP error log.
			error_log(
				sprintf(
					'[WPMUDEV API Error] %s | %s (%s [%s]) %s',
					WPMUDEV_Dashboard::$version,
					$this->api_error,
					$url,
					$error_code,
					$caller_dump
				),
				0
			);
		}

		// If error was "invalid API key" then log out the user. (we don't call logout here to avoid infinite loop)
		if ( 401 == $error_code && ! defined( 'WPMUDEV_APIKEY' ) && ! defined( 'WPMUDEV_OVERRIDE_LOGOUT' ) ) {
			WPMUDEV_Dashboard::$api->set_key( '' );
		}
	}
}

/**
 * Returns the correct network_site_url to use for API calls to the hub.
 *
 * For use by other WPMU DEV plugins.
 *
 * @since  4.6.0
 *
 * @return string
 */
function wpmudev_api_url() {
	return WPMUDEV_Dashboard::$api->network_site_url();
}