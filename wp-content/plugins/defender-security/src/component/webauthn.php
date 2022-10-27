<?php

namespace WP_Defender\Component;

use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialSource;
use WP_Defender\Traits\Webauthn as Webauthn_Trait;

/**
 * Class Webauthn.
 *
 * @since 3.0.0
 */
class Webauthn implements PublicKeyCredentialSourceRepository {
	use Webauthn_Trait;

	/**
	 * Option key for storing user credentials.
	 *
	 * @type string
	 */
	public const CREDENTIAL_OPTION_KEY = 'user_credentials';

	/**
	 * Get user credentials.
	 *
	 * @return mixed|null
	 */
	public function getCredentials() {
		return $this->get_option( self::CREDENTIAL_OPTION_KEY );
	}

	/**
	 * Set user credentials.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function setCredentials( array $data ): bool {
		return $this->update_option( self::CREDENTIAL_OPTION_KEY, $data );
	}

	/**
	 * Get one credential by credential ID.
	 *
	 * @param string $publicKeyCredentialId
	 *
	 * @return PublicKeyCredentialSource|null
	 */
	public function findOneByCredentialId( string $publicKeyCredentialId ): ?PublicKeyCredentialSource {
		$data = $this->getCredentials();
		if ( isset( $data[ base64_encode( $publicKeyCredentialId ) ]['credential_source'] ) ) {
			return PublicKeyCredentialSource::createFromArray( $data[ base64_encode( $publicKeyCredentialId ) ]['credential_source'] );
		}
		return null;
	}

	/**
	 * Get all credentials of a user
	 *
	 * @param PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity
	 *
	 * @return array
	 */
	public function findAllForUserEntity( PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity ): array {
		return $this->findAllForUserEntityByType( $publicKeyCredentialUserEntity );
	}

	/**
	 * Get all credentials of a user by authenticator type.
	 *
	 * @param PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity
	 * @param null|string $type
	 *
	 * @return array
	 * @since 3.1.0
	 */
	public function findAllForUserEntityByType( PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity, $type = null ): array {
		$sources   = [];
		$user_data = $this->getCredentials();

		if ( is_array( $user_data ) ) {
			foreach ( $user_data as $data ) {
				if ( ! empty( $type ) && ! empty( $data['authenticator_type'] ) && $type !== $data['authenticator_type'] ) {
					continue;
				}

				if ( isset( $data['credential_source'] ) ) {
					$source = PublicKeyCredentialSource::createFromArray( $data['credential_source'] );
					if ( $source->getUserHandle() === $publicKeyCredentialUserEntity->getId() ) {
						$sources[] = $source;
					}
				}
			}
		}

		return $sources;
	}

	/**
	 * Store credential into database.
	 *
	 * @param PublicKeyCredentialSource $publicKeyCredentialSource
	 *
	 * @return void
	 */
	public function saveCredentialSource( PublicKeyCredentialSource $publicKeyCredentialSource ): void {
		$data = $this->getCredentials();
		$key  = base64_encode( $publicKeyCredentialSource->getPublicKeyCredentialId() );

		if ( ! isset( $data[ $key ] ) ) {
			$data[ $key ] = array(
				'label'              => ! empty( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '',
				'added'              => time(),
				'authenticator_type' => ! empty( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '',
				'user'               => $publicKeyCredentialSource->getUserHandle(),
				'credential_source'  => $publicKeyCredentialSource,
			);
		} else {
			$data[ $key ]['credential_source'] = $publicKeyCredentialSource;
		}

		$this->setCredentials( $data );
	}
}
