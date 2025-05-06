<?php
/**
 * Helper functions for webauthn related tasks.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

use Throwable;

trait Webauthn {

	/**
	 * Prefix used for option.
	 *
	 * @var string
	 */
	public $option_prefix = 'wpdef_webauthn_';

	/**
	 * Check if SSL is used.
	 *
	 * @return bool
	 */
	public function is_ssl(): bool {
		$server_data = defender_get_data_from_request( null, 's' );
		if (
			( ! empty( $server_data['HTTPS'] ) && ( 'on' === strtolower( $server_data['HTTPS'] ) || '1' === $server_data['HTTPS'] ) ) ||
			( ! empty( $server_data['REQUEST_SCHEME'] ) && 'https' === $server_data['REQUEST_SCHEME'] ) ||
			( ! empty( $server_data['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $server_data['HTTP_X_FORWARDED_PROTO'] ) ||
			( ! empty( $server_data['HTTP_X_FORWARDED_SSL'] ) && 'on' === $server_data['HTTP_X_FORWARDED_SSL'] )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check if 'gmp' extension is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled_gmp(): bool {
		return extension_loaded( 'gmp' );
	}

	/**
	 * Check if 'mbstring' extension is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled_mbstring(): bool {
		return extension_loaded( 'mbstring' );
	}

	/**
	 * Check if 'sodium' extension is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled_sodium(): bool {
		return extension_loaded( 'sodium' );
	}

	/**
	 * Check if server requirements are met.
	 *
	 * @return bool
	 */
	public function check_webauthn_requirements(): bool {
		return $this->is_ssl() &&
				$this->is_enabled_gmp() &&
				$this->is_enabled_mbstring() &&
				$this->is_enabled_sodium();
	}

	/**
	 * Sets the value of a transient with a specific name, client ID, and expiration time.
	 *
	 * @param  string $name  The name of the transient.
	 * @param  mixed  $value  The value to be serialized and stored in the transient.
	 * @param  string $client_id  The client ID associated with the transient.
	 * @param  int    $exp  The expiration time in seconds. Default is 90.
	 *
	 * @return bool Returns true if the transient was successfully set, false otherwise.
	 */
	public function set_trans_val( string $name, $value, string $client_id, int $exp = 90 ): bool {
		$trans_name = $this->option_prefix . $name . '_' . $client_id;
		$trans_val  = wp_json_encode( $value );

		return set_transient( $trans_name, $trans_val, $exp );
	}

	/**
	 * Retrieves the value of a transient with the specified name and client ID.
	 *
	 * @param  string $name  The name of the transient.
	 * @param  string $client_id  The client ID associated with the transient.
	 *
	 * @return mixed|false The deserialized value of the transient, or false if the transient does not exist.
	 */
	public function get_trans_val( string $name, string $client_id ) {
		$trans_name = $this->option_prefix . $name . '_' . $client_id;
		$trans_val  = get_transient( $trans_name );

		return false !== $trans_val ? json_decode( $trans_val ) : false;
	}

	/**
	 * Delete transient.
	 *
	 * @param  string $name  The name of the transient.
	 * @param  string $client_id  The client ID associated with the transient.
	 *
	 * @return bool
	 */
	public function delete_trans( string $name, string $client_id ): bool {
		$trans_name = $this->option_prefix . $name . '_' . $client_id;

		return delete_transient( $trans_name );
	}

	/**
	 * Update user meta.
	 *
	 * @param  int    $user_id  User ID.
	 * @param  string $name  Metadata key.
	 * @param  mixed  $value  Metadata value. Must be serializable if non-scalar.
	 *
	 * @return int|bool
	 */
	public function update_user_meta( int $user_id, string $name, $value ) {
		return update_user_meta( $user_id, $this->option_prefix . $name, addslashes( wp_json_encode( $value ) ) );
	}

	/**
	 * Get user meta.
	 *
	 * @param  int    $user_id  User ID.
	 * @param  string $name  Metadata key.
	 *
	 * @return mixed
	 */
	public function get_user_meta( int $user_id, string $name ) {
		$value = get_user_meta( $user_id, $this->option_prefix . $name, true );

		if ( null !== $value ) {
			try {
				return json_decode( $value, true );
			} catch ( Throwable $exception ) {
				return array();
			}
		}

		return array();
	}

	/**
	 * Get website name.
	 *
	 * @return string
	 */
	public function get_site_name(): string {
		return get_bloginfo( 'name' );
	}

	/**
	 * Get website domain.
	 *
	 * @return string
	 */
	public function get_site_domain(): string {
		$site_url    = get_bloginfo( 'url' );
		$site_domain = preg_replace( '#^http(s)?:\/\/#', '', $site_url );
		$site_domain = preg_replace( '#^www\.#', '', $site_domain );
		$site_domain = explode( '/', $site_domain );

		return $site_domain[0] ?? $site_domain;
	}

	/**
	 * Get the hash of a user's username using SHA-256 algorithm.
	 *
	 * @param  string $username  The username of the user.
	 *
	 * @return string The hashed username.
	 */
	public function get_user_hash( string $username ): string {
		return hash( 'sha256', $username );
	}

	/**
	 * URL safe base64 encoding.
	 *
	 * @param  string $data  The data to encode.
	 *
	 * @return string
	 */
	public function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Base64 decoding from URL safe base64 encoding.
	 *
	 * @param  string $data  The data to decode.
	 *
	 * @return string|bool
	 */
	public function base64url_decode( string $data ) {
		// No need to add '=' at the end.
		return base64_decode( strtr( $data, '-_', '+/' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}
}