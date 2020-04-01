<?php
/**
 * Plugin Name: WP Engine Seamless Login Plugin
 * Plugin URI:  https://www.wpengine.com
 * Description: WP Engine Seamless Login Plugin
 * Version:     1.5.3
 * Author:      WP Engine
 *
 * @package wpengine\sign_on_plugin
 */

namespace wpengine\sign_on_plugin;

require_once __DIR__ . '/wpe-wp-sign-on-plugin/inc/security-checks.php';
\wpengine\sign_on_plugin\check_security();

require_once __DIR__ . '/wpe-wp-sign-on-plugin/inc/user-nonce-helper.php';
require_once __DIR__ . '/wpe-wp-sign-on-plugin/inc/logger.php';
require_once __DIR__ . '/wpe-wp-sign-on-plugin/inc/sign-on-user-provider.php';
require_once __DIR__ . '/wpe-wp-sign-on-plugin/inc/custom-exceptions.php';

use WP_Error;
use wpengine\sign_on_plugin\UserNonceHelper;
use wpengine\sign_on_plugin\Logger;
use wpengine\sign_on_plugin\SignOnUserProvider;

const BASE_URL = 'wpe_sign_on_plugin/v1';

WPESignOnPlugin::initialize();

class WPESignOnPlugin {
	const REDIRECT_URL_ON_ERROR   = '/wp-login.php';
	const WP_CLI_COMMAND_NAME     = 'wpe-sso';
	const WP_CLI_EMAIL_ARG        = 'user-email';
	const WP_CLI_INSTALL_ARG      = 'install-name';
	const WP_CLI_FIRST_NAME_ARG   = 'first-name';
	const WP_CLI_LAST_NAME_ARG    = 'last-name';
	const WP_CLI_USER_ROLE_ARG    = 'user-role';
	const REDIRECT_URL_ON_SUCCESS = '/wp-admin/';

	public static $instance;

	private $login_route  = '/index.php';
	private $login_params = array( 'rest_route' => '/' . BASE_URL . '/login' );
	private $sign_on_user_provider;
	private $user_nonce_helper;

	public function __construct( $sign_on_user_provider, $user_nonce_helper ) {
		$this->sign_on_user_provider = $sign_on_user_provider;
		$this->user_nonce_helper     = $user_nonce_helper;
	}

	public static function initialize( $sign_on_user_provider = null, $user_nonce_helper = null ) {
		$sign_on_user_provider = $sign_on_user_provider ?? new SignOnUserProvider();
		$user_nonce_helper     = $user_nonce_helper ?? new UserNonceHelper();
		self::$instance        = new self( $sign_on_user_provider, $user_nonce_helper );

		// <domain_name>/index.php?rest_route=/<BASE_URL>/<endpoint>
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					BASE_URL,
					'/login',
					array(
						'methods'             => 'GET',
						'callback'            => array( self::$instance, 'login' ),
						'permission_callback' => array( self::$instance, 'permission_check' ),
					)
				);
				register_rest_route(
					BASE_URL,
					'/is_user_logged_in',
					array(
						'methods'             => 'GET',
						'callback'            => array( self::$instance, 'is_user_logged_in' ),
						'permission_callback' => array( self::$instance, 'permission_check' ),
					)
				);
			}
		);

		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( self::WP_CLI_COMMAND_NAME, self::$instance );
		}

	}

	public function permission_check( $request ) {
		$referer = $request->get_header( 'referer' );
		if ( null !== $referer && strpos( $referer, 'wp-json' ) !== false ) {
			Logger::log( Logger::WP_JSON_REFERER_ERROR, 'Request coming from wp-json' );
			return new WP_Error( 'bad_request', __( 'Bad request' ), array( 'status' => 400 ) );
		}
		return true;
	}

	public function login( $request ) {
		$time_start = round( microtime( true ) * 1000 );

		try {
			if ( is_multisite() ) {
				throw new MultisiteEnabledException();
			}

			if ( ! is_ssl() && force_ssl_admin() ) {
				return $this->generate_https_redirect( $request->get_query_params() );
			}

			list( $nonce, $user_email, $install_name ) = $this->get_params_from_login_request( $request );

			if ( ! $this->validate_non_empty_string( $user_email ) ) {
				throw new \Exception( " User email ({$user_email}) is blank " );
			}

			if ( ! $this->validate_install_name( $install_name ) ) {
				throw new InvalidInstallNameException( 'Expected: ' . PWP_NAME . ' ; Received: ' . $install_name );
			}

			$user       = $this->sign_on_user_provider->get_wp_user( $user_email );
			$nonce_data = $this->user_nonce_helper->get_nonce_data( $user->ID );

			if ( empty( $nonce_data ) ) {
				throw new NonceMetaDataValidationException( "Empty nonce data retrieved for User ({$user_email}) during login." );
			}

			$is_valid = $this->user_nonce_helper->validate_nonce( $user->ID, $nonce, $nonce_data, $install_name );
			if ( $is_valid ) {
				$this->sign_on_user_provider->login_user( $user, $time_start );
				$redirect_url = self::REDIRECT_URL_ON_SUCCESS;
			}
		} catch ( InvalidInstallNameException $e ) {
			Logger::log( Logger::INSTALL_NAME_ERROR, $e->getMessage(), $user_email, PWP_NAME );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
		} catch ( NonceMetaDataValidationException $e ) {
			Logger::log( Logger::NONCE_META_DATA_VALIDATION_ERROR, $e->getMessage(), $user_email, PWP_NAME );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
		} catch ( MultisiteEnabledException $e ) {
			Logger::log( Logger::MULTISITE_ENABLED_ERROR, $e->getMessage(), null, PWP_NAME );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
		} catch ( \Exception $e ) {
			Logger::log( Logger::GENERAL_EXCEPTION_ERROR, $e->getMessage(), isset( $user_email ) ? $user_email : null, PWP_NAME );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
		}

		$response = new \WP_REST_Response( null, 307, array( 'Location' => $redirect_url ?? self::REDIRECT_URL_ON_ERROR ) );

		return $response;
	}

	public function is_user_logged_in( $request ) {
		list( $install_id, $install_name, $user_email, $referer ) = $this->get_params_from_is_logged_in_request( $request );

		try {
			if ( is_multisite() ) {
				throw new MultisiteEnabledException();
			}

			if ( ! is_ssl() && force_ssl_admin() ) {
				return $this->generate_https_redirect( $request->get_query_params() );
			}

			if ( ! $this->validate_install_name( $install_name ) ) {
				throw new InvalidInstallNameException( 'Expected: ' . PWP_NAME . ' ; Received: ' . $install_name );
			}

			if ( $this->sign_on_user_provider->user_email_matches_current_user( $user_email ) ) {
				Logger::log( Logger::USER_LOGGED_IN, 'User ' . $user_email . ' already logged in.', $user_email, PWP_NAME );
				$redirect_url = self::REDIRECT_URL_ON_SUCCESS;
			} else {
				if ( null === $referer ) {
					throw new NoRefererException( 'No referer provided for user logged in check' );
				}
				Logger::log( Logger::USER_NOT_LOGGED_IN, 'User ' . $user_email . ' not logged in. Beginning flow.', $user_email, PWP_NAME );
				$redirect_url = $referer . '?' . http_build_query( $this->create_is_logged_in_response_params( $install_id ) );
			}
		} catch ( InvalidInstallNameException $e ) {
			Logger::log( Logger::INSTALL_NAME_ERROR, $e->getMessage(), $user_email, PWP_NAME );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
		} catch ( NoRefererException $e ) {
			Logger::log( Logger::NO_REFERER_ERROR, $e->getMessage(), $user_email, PWP_NAME );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
		} catch ( MultisiteEnabledException $e ) {
			Logger::log( Logger::MULTISITE_ENABLED_ERROR, $e->getMessage() );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
		} catch ( \Exception $e ) {
			Logger::log( Logger::GENERAL_EXCEPTION_ERROR, $e->getMessage(), isset( $user_email ) ? $user_email : null, PWP_NAME );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
		}

		$response = new \WP_REST_Response( null, 307, array( 'Location' => $redirect_url ?? self::REDIRECT_URL_ON_ERROR ) );

		return $response;
	}

	public function __invoke( $args, $assoc_args ) {
		echo wp_json_encode( $this->wpe_sso( $assoc_args ) );
	}

	private function validate_install_name( $install_name ) {
		if ( PWP_NAME === $install_name ) {
			return true;
		}
		return false;
	}

	private function get_params_from_login_request( $request ) {

		$nonce        = $request->get_param( 'nonce' );
		$user_email   = $request->get_param( 'user_email' );
		$install_name = $request->get_param( 'install_name' );

		return array( $nonce, $user_email, $install_name );
	}

	private function get_params_from_is_logged_in_request( $request ) {
		$install_id   = $request->get_param( 'install_id' );
		$install_name = $request->get_param( 'install_name' );
		$user_email   = $request->get_param( 'user_email' );
		$referer      = $request->get_param( 'redirect_url' );

		return array( $install_id, $install_name, $user_email, $referer );
	}

	private function wpe_sso( $assoc_args ) {
		try {
			if ( is_multisite() ) {
				throw new MultisiteEnabledException();
			}

			$user_email   = $assoc_args[ self::WP_CLI_EMAIL_ARG ];
			$install_name = $assoc_args[ self::WP_CLI_INSTALL_ARG ];
			$first_name   = $assoc_args[ self::WP_CLI_FIRST_NAME_ARG ];
			$last_name    = $assoc_args[ self::WP_CLI_LAST_NAME_ARG ];
			$role         = $assoc_args[ self::WP_CLI_USER_ROLE_ARG ];

			$this->validate_cli_command_params( $user_email, $install_name, $first_name, $last_name, $role );

			$user = $this->sign_on_user_provider->get_or_create_wp_user( $user_email, $first_name, $last_name, $role );

			$nonce_array = $this->user_nonce_helper->generate_nonce( $user->ID );
			$nonce       = $nonce_array['nonce'];
			$expiration  = $nonce_array['expiration'];

			$successfully_added = $this->user_nonce_helper->add_nonce( $user->ID, $nonce, $expiration, $install_name );

			if ( ! $successfully_added ) {
				throw new UserMetaAdditionException( "Nonce ({$nonce}) was not added successfully to users ({$user_email}) meta data" );
			}

			$redirect_url = $this->login_route;
			$query_params = $this->login_params;

		} catch ( UserCreationException $e ) {
			$this->sign_on_user_provider->rollback_user_creation( $user_email );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
			$error        = Logger::USER_CREATE_ERROR . ": {$e->getMessage()}";
		} catch ( UserMetaAdditionException $e ) {
			$this->sign_on_user_provider->rollback_user_creation( $user_email );
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
			$error        = Logger::USER_CREATE_ERROR . ": {$e->getMessage()}";
		} catch ( InvalidInstallNameException $e ) {
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
			$error        = Logger::INSTALL_NAME_ERROR . ": {$e->getMessage()}";
		} catch ( ImpersonatedUserException $e ) {
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
			$error        = Logger::IMPERSONATED_USER_ERROR . ": {$e->getMessage()}";
		} catch ( MultisiteEnabledException $e ) {
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
			$error        = Logger::MULTISITE_ENABLED_ERROR . ": {$e->getMessage()}";
		} catch ( \Exception $e ) {
			$redirect_url = self::REDIRECT_URL_ON_ERROR;
			$error        = Logger::GENERAL_EXCEPTION_ERROR . ": {$e->getMessage()}";
		}

		$data = array(
			'nonce'        => $nonce ?? '',
			'user_email'   => $user->data->user_email ?? '',
			'redirect_url' => $redirect_url,
			'query_params' => $query_params ?? new \stdClass(),
		);

		if ( isset( $error ) ) {
			$this->add_error_field_to_cli_command_return( $data, $error );
		}

		return $data;
	}

	private function add_error_field_to_cli_command_return( &$array, $error ) {
		$array['error_message'] = $error;
	}

	private function validate_cli_command_params( $user_email, $install_name, $first_name, $last_name, $role ) {
		if ( ! $this->validate_non_empty_string( $user_email ) ) {
			throw new \Exception( "User email ({$user_email}) is blank" );
		}

		if ( ! $this->validate_install_name( $install_name ) ) {
			throw new InvalidInstallNameException( 'Expected: ' . PWP_NAME . ' ; Received: ' . $install_name );
		}

		if ( ! $this->validate_non_empty_string( $first_name ) ) {
			throw new \Exception( 'Validation of CLI command parameters failed as first name was blank.' );
		}

		if ( ! $this->validate_non_empty_string( $last_name ) ) {
			throw new \Exception( 'Validation of CLI command parameters failed as last name was blank.' );
		}

		if ( ! $this->sign_on_user_provider->validate_role( $role ) ) {
			throw new \Exception( "Validation of user role ({$role}) failed as it is not a known WordPress role " );
		}
	}

	private function validate_non_empty_string( $string ) {
		return is_string( $string ) && ! empty( trim( $string ) );
	}

	private function generate_https_redirect( $query_params ) {
		$query_string = http_build_query( $query_params );
		$redirect_url = get_site_url( null, $this->login_route, 'https' );
		$response     = new \WP_REST_Response( null, 307, array( 'Location' => $redirect_url . '?' . $query_string ) );
		return $response;
	}

	private function create_is_logged_in_response_params( $install_id ) {
		$params = array(
			'install_id' => $install_id,
			'initiate'   => true,
		);
		return $params;
	}
}
