<?php
/**
 * Handles WebAuthn functionalities providing methods to manage and verify user credentials.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_User;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use WP_Defender\Traits\Webauthn as Webauthn_Trait;
use Webauthn\PublicKeyCredentialSourceRepository;

/**
 * Handles WebAuthn functionalities providing methods to manage and verify user credentials.
 *
 * @since 3.0.0
 */
class Webauthn implements PublicKeyCredentialSourceRepository {

	use Webauthn_Trait;

	/**
	 * Option key for storing user credentials.
	 *
	 * @var string
	 */
	public const CREDENTIAL_OPTION_KEY = 'user_credentials';

	/**
	 * Meta key for storing credentials having userHandle mismatch.
	 *
	 * @var string
	 */
	public const USER_HANDLE_MISMATCH_KEY = 'user_handle_match_failed';

	/**
	 * Get user credentials.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return mixed
	 */
	public function getCredentials( int $user_id ) {
		return $this->get_user_meta( $user_id, self::CREDENTIAL_OPTION_KEY );
	}

	/**
	 * Set user credentials.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $data   The credentials data.
	 *
	 * @return bool
	 */
	public function setCredentials( int $user_id, array $data ): bool {
		return false !== $this->update_user_meta( $user_id, self::CREDENTIAL_OPTION_KEY, $data );
	}

	/**
	 * Get one credential by credential ID.
	 *
	 * @param string $public_key_credential_id The public key credential ID.
	 *
	 * @return PublicKeyCredentialSource|null
	 */
	public function findOneByCredentialId( string $public_key_credential_id ): ?PublicKeyCredentialSource {
		$username = defender_get_data_from_request( 'username', 'p' );
		if ( isset( $username ) ) {
			$user = get_user_by( 'login', $username );

			if ( is_object( $user ) ) {
				$user_id = $user->ID;
			} else {
				$user_id = 0;
			}
		} else {
			$user_id = get_current_user_id();
		}
		$data = $this->getCredentials( $user_id );
		if ( isset( $data[ base64_encode( $public_key_credential_id ) ]['credential_source'] ) ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			return PublicKeyCredentialSource::createFromArray( $data[ base64_encode( $public_key_credential_id ) ]['credential_source'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		return null;
	}

	/**
	 * Get all credentials of a user
	 *
	 * @param PublicKeyCredentialUserEntity $public_key_credential_user_entity The user entity.
	 *
	 * @return array
	 */
	public function findAllForUserEntity( PublicKeyCredentialUserEntity $public_key_credential_user_entity ): array {
		$credentials = array();
		$username    = $public_key_credential_user_entity->getName();
		$user        = get_user_by( 'login', $username );

		if ( is_object( $user ) ) {
			$credentials = $this->findAllForUserByType( $user->ID );
		}

		return $credentials;
	}

	/**
	 * Get all credentials of a user by authenticator type.
	 *
	 * @param int         $user_id The user ID.
	 * @param null|string $type   The type of authenticator.
	 *
	 * @return array
	 * @since 3.1.0
	 */
	public function findAllForUserByType( int $user_id, $type = null ): array {
		$sources   = array();
		$user_data = $this->getCredentials( $user_id );

		if ( is_array( $user_data ) ) {
			foreach ( $user_data as $data ) {
				if ( ! empty( $type ) && ! empty( $data['authenticator_type'] ) && $type !== $data['authenticator_type'] ) {
					continue;
				}

				if ( isset( $data['credential_source'] ) ) {
					$sources[] = PublicKeyCredentialSource::createFromArray( $data['credential_source'] );
				}
			}
		}

		return $sources;
	}

	/**
	 * Store credential into database.
	 *
	 * @param PublicKeyCredentialSource $public_key_credential_source The credential source to store.
	 *
	 * @return void
	 */
	public function saveCredentialSource( PublicKeyCredentialSource $public_key_credential_source ): void {
		$user_id = get_current_user_id();
		$data    = $this->getCredentials( $user_id );
		$key     = base64_encode( $public_key_credential_source->getPublicKeyCredentialId() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		if ( ! isset( $data[ $key ] ) ) {
			$data[ $key ] = array(
				'label'              => defender_get_data_from_request( 'name', 'p' ) ?? '',
				'added'              => time(),
				'authenticator_type' => defender_get_data_from_request( 'type', 'p' ) ?? '',
				'user'               => $public_key_credential_source->getUserHandle(),
				'credential_source'  => $public_key_credential_source,
			);
		} else {
			$data[ $key ]['credential_source'] = $public_key_credential_source;
		}

		$this->setCredentials( $user_id, $data );
	}

	/**
	 * Get userHandle mismatch list.
	 *
	 * @param  int $user_id  The user ID.
	 *
	 * @return array
	 * @since 3.4.0
	 */
	public function getUserHandleMatchFailed( int $user_id ): array {
		$meta_key = $this->option_prefix . self::USER_HANDLE_MISMATCH_KEY;
		$meta_val = get_user_meta( $user_id, $meta_key, true );

		return is_array( $meta_val ) ? $meta_val : array();
	}

	/**
	 * Set userHandle mismatch list.
	 *
	 * @param  int   $user_id  The user ID.
	 * @param  array $meta_val  The mismatches to set.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public function setUserHandleMatchFailed( int $user_id, array $meta_val ): void {
		$meta_key = $this->option_prefix . self::USER_HANDLE_MISMATCH_KEY;
		update_user_meta( $user_id, $meta_key, $meta_val );
	}

	/**
	 * Add authenticators to userHandle mismatch list.
	 *
	 * @param  WP_User $user  The user object.
	 * @param  array   $data  The data to add.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public function addUserHandleMatchFailed( $user, $data ): void {
		if ( ! empty( $user->ID ) && ! empty( $data['rawId'] ) ) {
			$meta_val                = $this->getUserHandleMatchFailed( $user->ID );
			$meta_val['show_notice'] = $meta_val['show_notice'] ?? true;

			if ( empty( $meta_val['authenticators'] ) || ! in_array( $data['rawId'], $meta_val['authenticators'], true ) ) {
				$meta_val['authenticators'][] = $data['rawId'];
			}

			$this->setUserHandleMatchFailed( $user->ID, $meta_val );
		}
	}

	/**
	 * Remove authenticator from userHandle mismatch list.
	 *
	 * @param  int    $user_id  The user ID.
	 * @param  string $auth_id  The authenticator ID to remove.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public function removeUserHandleMatchFailed( int $user_id, string $auth_id ): void {
		if ( ! empty( $auth_id ) ) {
			$meta_val = $this->getUserHandleMatchFailed( $user_id );

			if ( ! empty( $meta_val['authenticators'] ) && is_array( $meta_val['authenticators'] ) ) {
				$pos = array_search( $auth_id, $meta_val['authenticators'], true );

				if ( false !== $pos ) {
					array_splice( $meta_val['authenticators'], $pos, 1 );
					$this->setUserHandleMatchFailed( $user_id, $meta_val );
				}
			}
		}
	}

	/**
	 * Disable userHandle mismatch notice.
	 *
	 * @param  int $user_id  The user ID.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	public function disableUserHandleMatchFailedNotice( int $user_id ): void {
		$meta_val                = $this->getUserHandleMatchFailed( $user_id );
		$meta_val['show_notice'] = false;

		$this->setUserHandleMatchFailed( $user_id, $meta_val );
	}
}