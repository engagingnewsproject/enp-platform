<?php
/**
 * Handles Web auth related functionality.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use Error;
use Exception;
use Webauthn\Server;
use WP_Defender\Controller;
use WP_Defender\Behavior\WPMUDEV;
use Nyholm\Psr7\Factory\Psr17Factory;
use Webauthn\PublicKeyCredentialRpEntity;
use Nyholm\Psr7Server\ServerRequestCreator;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialCreationOptions;
use WP_Defender\Traits\Webauthn as Webauthn_Trait;
use WP_Defender\Component\Two_Fa as Two_Fa_Component;
use WP_Defender\Component\Webauthn as Webauthn_Component;
use WP_Defender\Controller\Two_Factor as Two_Fa_Controller;
use WP_Defender\Component\Two_Factor\Providers\Webauthn as Webauthn_Provider;

/**
 * Handles Web auth related functionality.
 *
 * @since 3.0.0
 */
class Webauthn extends Controller {

	use Webauthn_Trait;

	/**
	 * Service for handling logic.
	 *
	 * @var Webauthn_Component
	 */
	protected $service;

	public const ALLOWED_AUTH_TYPES = array( 'platform', 'cross-platform' );

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->service = wd_di()->get( Webauthn_Component::class );

		if ( $this->service->check_webauthn_requirements() ) {
			// Register a new authenticator.
			add_action( 'wp_ajax_defender_webauthn_create_challenge', array( $this, 'create_challenge' ) );
			add_action( 'wp_ajax_defender_webauthn_verify_challenge', array( $this, 'verify_challenge' ) );
			// Remove authenticator.
			add_action( 'wp_ajax_defender_webauthn_remove_authenticator', array( $this, 'remove_authenticator' ) );
			// Rename authenticator.
			add_action( 'wp_ajax_defender_webauthn_rename_authenticator', array( $this, 'rename_authenticator' ) );
			// Verify user's device.
			add_action( 'wp_ajax_defender_webauthn_get_option', array( $this, 'get_credential_request_option' ) );
			add_action(
				'wp_ajax_nopriv_defender_webauthn_get_option',
				array(
					$this,
					'get_credential_request_option',
				)
			);
			add_action( 'wp_ajax_defender_webauthn_verify_response', array( $this, 'verify_response' ) );
			add_action( 'wp_ajax_nopriv_defender_webauthn_verify_response', array( $this, 'verify_response' ) );
			// Handling requests in the frontend.
			if ( wd_di()->get( Two_Fa_Controller::class )->woo_integration_enabled() ) {
				add_action( 'wp_ajax_nopriv_defender_webauthn_create_challenge', array( $this, 'create_challenge' ) );
				add_action( 'wp_ajax_nopriv_defender_webauthn_verify_challenge', array( $this, 'verify_challenge' ) );
				add_action(
					'wp_ajax_nopriv_defender_webauthn_remove_authenticator',
					array(
						$this,
						'remove_authenticator',
					)
				);
				add_action(
					'wp_ajax_nopriv_defender_webauthn_rename_authenticator',
					array(
						$this,
						'rename_authenticator',
					)
				);
			}

			// Disable userHandle mismatch notice.
			add_action(
				'wp_ajax_defender_webauthn_disable_user_handle_match_failed_notice',
				array( $this, 'disable_user_handle_match_failed_notice' )
			);
		}
	}

	/**
	 * Get authenticator records for current user.
	 *
	 * @return array
	 */
	public function get_current_user_authenticators(): array {
		$arr              = array();
		$user_id          = get_current_user_id();
		$user_credentials = $this->service->getCredentials( $user_id );
		if ( ! empty( $user_credentials ) && is_array( $user_credentials ) ) {
			foreach ( $user_credentials as $key => $value ) {
				$arr[] = array(
					'key'       => $this->base64url_encode( $key ),
					'label'     => $value['label'],
					'added'     => wp_date( 'Y-m-d', $value['added'] ),
					'auth_type' => $value['authenticator_type'],
				);
			}
		}

		return $arr;
	}

	/**
	 * Get user entity.
	 *
	 * @param  int $user_id  User ID.
	 *
	 * @return false|PublicKeyCredentialUserEntity
	 */
	public function get_user_entity( int $user_id ) {
		if ( $user_id <= 0 ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! is_object( $user ) ) {
			return false;
		}

		$user_hash   = $this->get_user_hash( $user->user_login );
		$user_avatar = get_avatar_url( $user->user_email, array( 'scheme' => 'https' ) );

		return new PublicKeyCredentialUserEntity(
			$user->user_login,
			$user_hash,
			$user->display_name,
			$user_avatar
		);
	}

	/**
	 * Create challenge.
	 *
	 * @return void
	 * @throws Exception If something goes wrong.
	 */
	public function create_challenge(): void {
		try {
			if ( ! $this->verify_nonce( 'wpdef_webauthn' ) ) {
				throw new Exception( esc_html__( 'Bad nonce.', 'wpdef' ) );
			}

			$type = defender_get_data_from_request( 'type', 'g' );
			if ( empty( $type ) ) {
				throw new Exception( esc_html__( 'Missing field(s).', 'wpdef' ) );
			}

			$user_id     = get_current_user_id();
			$user_entity = $this->get_user_entity( $user_id );
			if ( false === $user_entity ) {
				throw new Exception( esc_html__( 'User does not exist.', 'wpdef' ) );
			}

			// Get the list of all authenticators associated to a user.
			$credential_sources = $this->service->findAllForUserEntity( $user_entity );

			// Convert the Credential Sources into Public Key Credential Descriptors.
			$exclude_credentials = array_map(
				function ( $credential ) {
					return $credential->getPublicKeyCredentialDescriptor();
				},
				$credential_sources
			);

			if ( 'platform' === $type ) {
				$authenticator_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM;
			} elseif ( 'cross-platform' === $type ) {
				$authenticator_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM;
			} else {
				$authenticator_type = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE;
			}

			// Create authenticator selection.
			$resident_key                     = false;
			$user_verification                = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;
			$authenticator_selection_criteria = new AuthenticatorSelectionCriteria(
				$authenticator_type,
				$resident_key,
				$user_verification
			);

			$rp_entity = new PublicKeyCredentialRpEntity(
				$this->get_site_name(),
				$this->get_site_domain()
			);
			$server    = new Server(
				$rp_entity,
				$this->service,
				null
			);

			$public_key_credential_creation_options = $server->generatePublicKeyCredentialCreationOptions(
				$user_entity,
				PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
				$exclude_credentials,
				$authenticator_selection_criteria
			);

			$client_id = time() . defender_generate_random_string( 24 );

			// Set transition for later use.
			$encoded = wp_json_encode( $public_key_credential_creation_options );
			$this->set_trans_val( 'pub_key_cco', base64_encode( $encoded ), $client_id ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			// Send Challenge.
			$public_key_credential_creation_options             = json_decode( $encoded, true );
			$public_key_credential_creation_options['clientID'] = $client_id;
			wp_send_json_success( $public_key_credential_creation_options );
		} catch ( Error $error ) {
			wp_send_json_error( $error->getMessage() );
		} catch ( Exception $exception ) {
			wp_send_json_error( $exception->getMessage() );
		}
	}

	/**
	 * Verify challenge.
	 *
	 * @return void
	 * @throws Exception If something goes wrong.
	 */
	public function verify_challenge(): void {
		$client_id = null;
		try {
			if ( ! $this->verify_nonce( 'wpdef_webauthn', 'post' ) ) {
				throw new Exception( esc_html__( 'Bad nonce.', 'wpdef' ) );
			}

			$posted_data = defender_get_data_from_request( null, 'p' );
			if ( empty( $posted_data['data'] ) || empty( $posted_data['client_id'] ) ) {
				throw new Exception( esc_html__( 'Missing field(s).', 'wpdef' ) );
			}

			$psr17_factory = new Psr17Factory();
			$creator       = new ServerRequestCreator(
				$psr17_factory,
				$psr17_factory,
				$psr17_factory,
				$psr17_factory
			);

			$server_request = $creator->fromGlobals();

			$rp_entity = new PublicKeyCredentialRpEntity(
				$this->get_site_name(),
				$this->get_site_domain()
			);

			$server = new Server(
				$rp_entity,
				$this->service,
				null
			);

			$response_data = base64_decode( sanitize_text_field( $posted_data['data'] ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$client_id     = sanitize_text_field( $posted_data['client_id'] );
			$pub_key_cco   = $this->get_trans_val( 'pub_key_cco', $client_id );
			$pub_key_cco   = PublicKeyCredentialCreationOptions::createFromString( base64_decode( $pub_key_cco ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			$public_key_credential_source = $server->loadAndCheckAttestationResponse(
				$response_data,
				$pub_key_cco,
				$server_request
			);

			$this->service->saveCredentialSource( $public_key_credential_source );

			// Delete transient.
			$this->delete_trans( 'pub_key_cco', $client_id );

			$response = array();
			$username = $pub_key_cco->getUser()->getName();
			$user     = get_user_by( 'login', $username );

			$cred_data = array();
			if ( is_object( $user ) ) {
				$cred_data = $this->service->getCredentials( $user->ID );
			}

			$cred_id = base64_encode( $public_key_credential_source->getPublicKeyCredentialId() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			if ( isset( $cred_data[ $cred_id ] ) ) {
				$response = array(
					'key'       => $this->base64url_encode( $cred_id ),
					'label'     => ucfirst( $cred_data[ $cred_id ]['label'] ),
					'added'     => wp_date( 'Y-m-d', $cred_data[ $cred_id ]['added'] ),
					'auth_type' => $cred_data[ $cred_id ]['authenticator_type'],
				);
			}
			wp_send_json_success( $response );
		} catch ( Error $error ) {
			$this->delete_trans( 'pub_key_cco', $client_id );
			wp_send_json_error( $error->getMessage() );
		} catch ( Exception $exception ) {
			$this->delete_trans( 'pub_key_cco', $client_id );
			wp_send_json_error( $exception->getMessage() );
		}
	}

	/**
	 * Remove authenticator.
	 *
	 * @return void
	 * @throws Exception If something goes wrong.
	 */
	public function remove_authenticator(): void {
		try {
			if ( ! $this->verify_nonce( 'wpdef_webauthn', 'post' ) ) {
				throw new Exception( esc_html__( 'Bad nonce.', 'wpdef' ) );
			}

			$cred_id = defender_get_data_from_request( 'key', 'p' );
			if ( empty( $cred_id ) ) {
				throw new Exception( esc_html__( 'Missing field(s).', 'wpdef' ) );
			}

			$cred_id                 = $this->base64url_decode( $cred_id );
			$user_id                 = get_current_user_id();
			$option_user_credentials = $this->service->getCredentials( $user_id );

			if ( isset( $option_user_credentials[ $cred_id ] ) ) {
				unset( $option_user_credentials[ $cred_id ] );
				$this->service->setCredentials( $user_id, $option_user_credentials );
				$this->service->removeUserHandleMatchFailed( $user_id, $cred_id );

				if ( 0 === count( $option_user_credentials ) ) {
					$enabled_providers = get_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, true );
					if ( empty( $enabled_providers ) ) {
						$enabled_providers = array();
					}
					$key = array_search( Webauthn_Provider::$slug, $enabled_providers, true );
					if ( false !== $key ) {
						unset( $enabled_providers[ $key ] );
						update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, $enabled_providers );
					}
				}

				wp_send_json_success( esc_html__( 'Authenticator removed.', 'wpdef' ) );
			}

			wp_send_json_error( esc_html__( 'Key did not match any registered authenticator.', 'wpdef' ) );
		} catch ( Error $error ) {
			wp_send_json_error( $error->getMessage() );
		} catch ( Exception $exception ) {
			wp_send_json_error( $exception->getMessage() );
		}
	}

	/**
	 * Rename authenticator.
	 *
	 * @return void
	 * @throws Exception If something goes wrong.
	 */
	public function rename_authenticator(): void {
		try {
			if ( ! $this->verify_nonce( 'wpdef_webauthn', 'post' ) ) {
				throw new Exception( esc_html__( 'Bad nonce.', 'wpdef' ) );
			}

			$posted_data = defender_get_data_from_request( null, 'p' );
			if ( empty( $posted_data['key'] ) || empty( $posted_data['label'] ) ) {
				throw new Exception( esc_html__( 'Missing field(s).', 'wpdef' ) );
			}

			$cred_id                 = sanitize_text_field( $posted_data['key'] );
			$cred_id                 = $this->base64url_decode( $cred_id );
			$new_label               = sanitize_text_field( $posted_data['label'] );
			$user_id                 = get_current_user_id();
			$option_user_credentials = $this->service->getCredentials( $user_id );

			if ( isset( $option_user_credentials[ $cred_id ] ) ) {
				$option_user_credentials[ $cred_id ]['label'] = $new_label;
				$this->service->setCredentials( $user_id, $option_user_credentials );

				wp_send_json_success( esc_html__( 'Authenticator identifier renamed.', 'wpdef' ) );
			}

			wp_send_json_error( esc_html__( 'Key did not match any registered authenticator.', 'wpdef' ) );
		} catch ( Error $error ) {
			wp_send_json_error( $error->getMessage() );
		} catch ( Exception $exception ) {
			wp_send_json_error( $exception->getMessage() );
		}
	}

	/**
	 * Get credential request option.
	 *
	 * @return void
	 * @throws Exception If something goes wrong.
	 */
	public function get_credential_request_option(): void {
		try {
			if ( ! $this->verify_nonce( 'wpdef_webauthn', 'post' ) ) {
				throw new Exception( esc_html__( 'Bad nonce.', 'wpdef' ) );
			}

			$posted_data = defender_get_data_from_request( null, 'p' );
			if ( empty( $posted_data['username'] ) ) {
				throw new Exception( esc_html__( 'Missing field(s).', 'wpdef' ) );
			}

			$rp_entity = new PublicKeyCredentialRpEntity(
				$this->get_site_name(),
				$this->get_site_domain()
			);

			$server = new Server(
				$rp_entity,
				$this->service,
				null
			);

			$user_entity = false;
			$username    = sanitize_text_field( $posted_data['username'] );
			$user        = get_user_by( 'login', $username );
			if ( is_object( $user ) ) {
				$user_entity = $this->get_user_entity( $user->ID );
			}

			if ( false === $user_entity ) {
				throw new Exception( esc_html__( 'User does not exist.', 'wpdef' ) );
			}

			$auth_type = ! empty( $posted_data['type'] ) ? sanitize_text_field( $posted_data['type'] ) : null;
			if ( in_array( $auth_type, self::ALLOWED_AUTH_TYPES, true ) ) {
				$credential_sources = $this->service->findAllForUserByType( $user->ID, $auth_type );
			} else {
				$credential_sources = $this->service->findAllForUserEntity( $user_entity );
			}

			if ( ! is_array( $credential_sources ) || 0 === count( $credential_sources ) ) {
				throw new Exception( esc_html__( 'Please register a device first to authenticate it.', 'wpdef' ), 100 );
			}

			// Convert the Credential Sources into Public Key Credential Descriptors for excluding.
			$allowed_credentials = array_map(
				function ( $credential ) {
					return $credential->getPublicKeyCredentialDescriptor();
				},
				$credential_sources
			);
			$user_verification   = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED;

			$public_key_credential_request_options = $server->generatePublicKeyCredentialRequestOptions(
				$user_verification,
				$allowed_credentials
			);

			// set transition for later use.
			$client_id = time() . defender_generate_random_string( 24 );
			$this->set_trans_val( 'pub_key_cro', base64_encode( wp_json_encode( $public_key_credential_request_options ) ), $client_id ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			$public_key_credential_request_options             = json_decode(
				wp_json_encode( $public_key_credential_request_options ),
				true
			);
			$public_key_credential_request_options['clientID'] = $client_id;
			wp_send_json_success( $public_key_credential_request_options );
		} catch ( Error $error ) {
			wp_send_json_error( $error->getMessage() );
		} catch ( Exception $exception ) {
			wp_send_json_error(
				array(
					'message' => $exception->getMessage(),
					'code'    => $exception->getCode(),
				)
			);
		}
	}

	/**
	 * Verify response.
	 *
	 * @param  bool $will_return  Either return array or echo json.
	 *
	 * @return array
	 * @throws Exception If something goes wrong.
	 */
	public function verify_response( bool $will_return = false ) {
		$client_id = null;
		try {
			if ( ! $this->verify_nonce( 'wpdef_webauthn', 'post' ) ) {
				throw new Exception( esc_html__( 'Bad nonce.', 'wpdef' ) );
			}

			$posted_data = defender_get_data_from_request( null, 'p' );
			if ( empty( $posted_data['data'] ) || empty( $posted_data['username'] ) || empty( $posted_data['client_id'] ) ) {
				throw new Exception( esc_html__( 'Missing field(s).', 'wpdef' ) );
			}

			$user_entity   = false;
			$response_data = base64_decode( $posted_data['data'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$username      = sanitize_text_field( $posted_data['username'] );
			$client_id     = sanitize_text_field( $posted_data['client_id'] );
			$pub_key_cro   = PublicKeyCredentialRequestOptions::createFromString( base64_decode( $this->get_trans_val( 'pub_key_cro', $client_id ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$user          = get_user_by( 'login', $username );

			if ( is_object( $user ) ) {
				$user_entity = $this->get_user_entity( $user->ID );
			}

			if ( false === $user_entity ) {
				throw new Exception( esc_html__( 'User does not exist.', 'wpdef' ) );
			}

			$psr17_factory = new Psr17Factory();
			$creator       = new ServerRequestCreator(
				$psr17_factory,
				$psr17_factory,
				$psr17_factory,
				$psr17_factory
			);

			$server_request = $creator->fromGlobals();

			$rp_entity = new PublicKeyCredentialRpEntity(
				$this->get_site_name(),
				$this->get_site_domain()
			);

			$server = new Server(
				$rp_entity,
				$this->service,
				null
			);

			$server->loadAndCheckAssertionResponse(
				$response_data,
				$pub_key_cro,
				$user_entity,
				$server_request
			);

			$this->delete_trans( 'pub_key_cro', $client_id );

			$data = esc_html__( 'Authenticator verified successfully.', 'wpdef' );

			return defender_maybe_echo_json( $data, true, $will_return );
		} catch ( Error $error ) {
			$this->delete_trans( 'pub_key_cro', $client_id );

			return defender_maybe_echo_json( $error->getMessage(), false, $will_return );
		} catch ( Exception $exception ) {
			$data = array( 'message' => $exception->getMessage() );

			if ( $exception->getMessage() === 'Invalid user handle' ) {
				$user_credentials = $this->service->getCredentials( $user->ID );
				$decoded_response = json_decode( $response_data, true );

				$data['label'] = $user_credentials[ $decoded_response['rawId'] ]['label'];
				$data['key']   = $this->base64url_encode( $decoded_response['rawId'] );

				$this->service->addUserHandleMatchFailed( $user, $decoded_response );
			}

			$this->delete_trans( 'pub_key_cro', $client_id );

			return defender_maybe_echo_json( $data, false, $will_return );
		}
	}

	/**
	 * Get translation.
	 *
	 * @return array
	 */
	public function get_translations(): array {
		$translations = array(
			'registration_start'                 => esc_html__(
				'Registering a new authenticator is in process.',
				'wpdef'
			),
			'authenticator_reg_success'          => esc_html__(
				'Registered new authenticator.',
				'wpdef'
			),
			'authenticator_reg_failed'           => esc_html__(
				'ERROR: Something went wrong.',
				'wpdef'
			),
			'multiple_reg_attempt'               => esc_html__(
				'Registration failed! The authenticator you are trying to register is already registered with your account.',
				'wpdef'
			),
			'authentication_start'               => esc_html__( 'Authenticating', 'wpdef' ),
			'authenticator_verification_success' => esc_html__(
				'Authenticated device successfully.',
				'wpdef'
			),
			'authenticator_verification_failed'  => esc_html__(
				'Authentication verification failed! Please make sure that biometric functionality is configured on your phone.',
				'wpdef'
			),
			'authenticator_verification_failed_user_handle_mismatch' => sprintf(
				/* translators: %s is the name of the authenticator. */
				esc_html__( 'Due to some Biometric Authentication security improvements, the %s device will not work. Please delete this device and re-register it.', 'wpdef' ),
				'<strong>%s</strong>'
			),
			'remove_auth'                        => esc_html__(
				'Are you sure you want to remove authenticator?',
				'wpdef'
			),
			'login_failed'                       => esc_html__(
				'ERROR: Verification failed.',
				'wpdef'
			),
			'login_user_handle_match_failed'     => esc_html__(
				'ERROR: Due to some Biometric Authentication security improvements, some of your registered devices might not work. Please ask your website administrator to reset 2FA for your account. Then navigate to your profile page to check which devices need re-registration.',
				'wpdef'
			),
			'client_webauthn_notice'             => esc_html__(
				'WebAuth is not supported by your web browser. Please install an updated version, or try another browser.',
				'wpdef'
			),
			'auth_user_handle_mismatch_notice'   => esc_html__(
				'This device might not work. Please delete the device and re-register it.',
				'wpdef'
			),
			'user_handle_mismatch_main_notice'   => esc_html__(
				'Due to some biometric authentication security improvements, some of your registered devices might not work. Please click the Authenticate Device button to check which devices you need to delete and re-registered.',
				'wpdef'
			),
		);

		if ( ( new WPMUDEV() )->show_support_links() ) {
			$translations['authenticator_verification_failed'] .= defender_support_ticket_text();
		}

		return $translations;
	}

	/**
	 * Disable userHandle mismatch notice.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public function disable_user_handle_match_failed_notice(): void {
		$this->service->disableUserHandleMatchFailedNotice( get_current_user_id() );
		wp_send_json_success( esc_html__( 'Notice disabled!', 'wpdef' ) );
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data(): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s;", "%{$this->option_prefix}%" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		return array();
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings(): void {
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		return array();
	}
}