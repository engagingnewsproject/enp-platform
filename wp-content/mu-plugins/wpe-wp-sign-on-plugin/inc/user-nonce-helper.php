<?php

namespace wpengine\sign_on_plugin;

require_once __DIR__ . '/security-checks.php';
\wpengine\sign_on_plugin\check_security();

class UserNonceHelper {

	const WPE_NONCE_KEY      = 'WPE_NONCE';
	const WP_USER_META_TABLE = 'wp_usermeta';
	const EXPIRATION_TIME    = 10;
	const HASHING_ALG        = 'sha256';

	public function __construct() {
	}

	public function generate_nonce() {
		$nonce      = $this->uuidv4( random_bytes( 16 ) );
		$expiration = time() + self::EXPIRATION_TIME;

		return array(
			'nonce'      => $nonce,
			'expiration' => $expiration,
		);
	}

	public function validate_nonce( $user_id, $nonce, $nonce_data_stored_array, $install_name ) {
		$current_time = time();

		if ( empty( $nonce_data_stored_array ) ) {
			$user_email = $this->get_user_email_from_id( $user_id );
			Logger::log( Logger::NONCE_META_DATA_VALIDATION_ERROR, "Empty nonce meta data structure stored for User ({$user_email}).", $user_email, PWP_NAME );
			return false;
		}

		foreach ( $nonce_data_stored_array as $item ) {
			if ( $this->validate_nonce_array_item( $user_id, $nonce, $item, $install_name, $current_time ) ) {
				return true;
			}
		}

		return false;
	}

	public function add_nonce( $user_id, $nonce, $expiration, $install_name ) {
		$nonce_data               = new \stdClass();
		$nonce_data->nonce        = $this->hash_nonce( $nonce );
		$nonce_data->expiration   = $expiration;
		$nonce_data->install_name = $install_name;

		return add_user_meta(
			$user_id,
			self::WPE_NONCE_KEY,
			$nonce_data
		);
	}

	public function get_nonce_data( $user_id ) {
		wp_cache_delete( $user_id, 'user_meta' );
		return get_user_meta( $user_id, self::WPE_NONCE_KEY );
	}

	public function delete_nonce( $user_id, $nonce_data ) {
		$result = delete_user_meta( $user_id, self::WPE_NONCE_KEY, $nonce_data );

		return $result;
	}

	private function hash_nonce( $nonce ) {
		return hash( self::HASHING_ALG, $nonce );
	}

	private function validate_nonce_array_item( $user_id, $nonce, $nonce_data_stored, $install_name, $current_time ) {
		if ( ! $this->validate_nonce_item_structure( $nonce_data_stored ) ) {
			$this->delete_nonce( $user_id, $nonce_data_stored );
			$user_email = $this->get_user_email_from_id( $user_id );
			// phpcs:ignore
			$object_contents = print_r( $nonce_data_stored, true );
			Logger::log( Logger::NONCE_META_DATA_VALIDATION_ERROR, "Invalid nonce meta data structure for User ({$user_email}).\r\nContents:\r\n{$object_contents}.", $user_email, PWP_NAME );
			return false;
		}
		if ( $this->has_nonce_expired( $nonce_data_stored->expiration, $current_time ) ) {
			$this->delete_nonce( $user_id, $nonce_data_stored );
			$user_email      = $this->get_user_email_from_id( $user_id );
			$time_difference = $current_time - $nonce_data_stored->expiration;
			Logger::log( Logger::NONCE_META_DATA_VALIDATION_ERROR, "Nonce {$nonce_data_stored->nonce} has expired by {$time_difference} seconds for User ({$user_email}).", $user_email, PWP_NAME );
			return false;
		}

		if ( ( $this->hash_nonce( $nonce ) !== $nonce_data_stored->nonce ) || ( $nonce_data_stored->install_name !== $install_name ) ) {
			$user_email = $this->get_user_email_from_id( $user_id );
			Logger::log(
				Logger::NONCE_META_DATA_VALIDATION_ERROR,
				"Nonce stored ({$nonce_data_stored->nonce}) is not equal to nonce in the request ({$nonce}) OR \
				the install name stored ({$nonce_data_stored->install_name}) is not equal to install name in the request ({$install_name}).",
				$user_email,
				PWP_NAME
			);
			return false;
		}

		$this->delete_nonce( $user_id, $nonce_data_stored );
		return true;
	}

	private function get_user_email_from_id( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		return $user->data->user_email ?? '';
	}

	private function validate_nonce_item_structure( $nonce_object ) {
		if ( property_exists( $nonce_object, 'nonce' ) &&
		property_exists( $nonce_object, 'expiration' ) &&
		property_exists( $nonce_object, 'install_name' ) ) {
			return true;
		}

		return false;
	}

	private function has_nonce_expired( $nonce_stored_expiration, $current_time ): bool {
		return (int) $nonce_stored_expiration < $current_time;
	}

	private function uuidv4( $data ) {
		assert( strlen( $data ) === 16 );

		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 ); // set version to 0100.
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 ); // set bits 6-7 to 10.

		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}
}
