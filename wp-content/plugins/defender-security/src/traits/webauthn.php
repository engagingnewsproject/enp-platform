<?php

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
		if (
			( ! empty( $_SERVER['HTTPS'] ) && ( 'on' === strtolower( $_SERVER['HTTPS'] ) || '1' === $_SERVER['HTTPS'] ) ) ||
			( ! empty( $_SERVER['REQUEST_SCHEME'] ) && 'https' === $_SERVER['REQUEST_SCHEME'] ) ||
			( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ||
			( ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && 'on' === $_SERVER['HTTP_X_FORWARDED_SSL'] )
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
	 * Set transient.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @param string $client_id
	 * @param int    $exp
	 *
	 * @return bool
	 */
	public function set_trans_val( string $name, $value, string $client_id, int $exp = 90 ): bool {
		$trans_name = $this->option_prefix . $name . '_' . $client_id;
		$trans_val = serialize( $value );

		return set_transient( $trans_name, $trans_val, $exp );
	}

	/**
	 * Get transient.
	 *
	 * @param string $name
	 * @param string $client_id
	 *
	 * @return mixed
	 */
	public function get_trans_val( string $name, string $client_id ) {
		$trans_name = $this->option_prefix . $name . '_' . $client_id;
		$trans_val = get_transient( $trans_name );

		return false !== $trans_val ? unserialize( $trans_val ) : false;
	}

	/**
	 * Delete transient.
	 *
	 * @param string $name
	 * @param string $client_id
	 *
	 * @return bool
	 */
	public function delete_trans( string $name, string $client_id ): bool {
		$trans_name = $this->option_prefix . $name . '_' . $client_id;

		return delete_transient( $trans_name );
	}

	/**
	 * Update option.
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function update_option( string $name, $value ): bool {
		return update_option( $this->option_prefix . $name, json_encode( $value ) );
	}

	/**
	 * Get option.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function get_option( string $name ) {
		$value = get_option( $this->option_prefix . $name );
		if ( null !== $value ) {
			try {
				return json_decode( $value, true );
			} catch ( Throwable $exception ) {
				return [];
			}
		}
		return [];
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
		$site_url = get_bloginfo( 'url' );
		$site_domain = preg_replace( '#^http(s)?:\/\/#', '', $site_url );
		$site_domain = preg_replace( '#^www\.#', '', $site_domain );
		$site_domain = explode( '/', $site_domain );

		return $site_domain[0] ?? $site_domain;
	}

	/**
	 * Get user hash.
	 *
	 * @param string $username
	 * @param string $display_name
	 *
	 * @return string
	 */
	public function get_user_hash( string $username, string $display_name ): string {
		return hash( 'sha256', $username . '-' . $display_name . '-' . AUTH_SALT );
	}

	/**
	 * URL safe base64 encoding.
	 *
	 * @param string $data
	 *
	 * @return string
	 */
	public function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64 decoding from URL safe base64 encoding.
	 *
	 * @param string $data
	 *
	 * @return string|bool
	 */
	public function base64url_decode( string $data ) {
		// No need to add '=' at the end.
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}
}
