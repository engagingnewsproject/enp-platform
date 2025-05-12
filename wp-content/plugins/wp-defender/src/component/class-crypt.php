<?php
/**
 * Methods for generating cryptographically secure pseudo-random bytes and integers, comparing strings securely,
 * and encrypting/decrypting data.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Error;
use Exception;
use SodiumException;
use RuntimeException;
use WP_Defender\Traits\IO;
use Calotes\Base\Component;

/**
 * Methods for generating cryptographically secure pseudo-random bytes and integers, comparing strings securely,
 *  and encrypting/decrypting data.
 *
 * @since 3.3.1
 */
class Crypt extends Component {

	use IO;

	/**
	 * Generates cryptographically secure pseudo-random bytes.
	 *
	 * @param  int $bytes  The number of bytes to generate.
	 *
	 * @return string
	 */
	public static function random_bytes( int $bytes ): string {
		// Try with random_bytes.
		if ( function_exists( 'random_bytes' ) ) {
			try {
				$rand = random_bytes( $bytes );
				if ( is_string( $rand ) && strlen( $rand ) === $bytes ) {
					return $rand;
				}
			} catch ( Exception $e ) {
				$_this = new self();
				$_this->log( $e->getMessage(), wd_internal_log() );
			}
		}
		// Try with openssl_random_pseudo_bytes.
		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$rand = openssl_random_pseudo_bytes( $bytes, $strong );
			if ( is_string( $rand ) && strlen( $rand ) === $bytes ) {
				return $rand;
			}
		}
		// Not safe. Use in extreme cases.
		$return = '';
		for ( $i = 0; $i < $bytes; $i++ ) {
			$return .= chr( wp_rand( 0, 255 ) );
		}

		return $return;
	}

	/**
	 * Generates cryptographically secure pseudo-random integers.
	 *
	 * @param  int $min  The minimum value of the generated integer (inclusive).
	 * @param  int $max  The maximum value of the generated integer (inclusive).
	 *
	 * @return int
	 * @throws RuntimeException On failure.
	 */
	public static function random_int( $min = 0, $max = 0x7FFFFFFF ): int {
		if ( function_exists( 'random_int' ) ) {
			try {
				return random_int( $min, $max );
			} catch ( Exception $e ) {
				$_this = new self();
				$_this->log( $e->getMessage(), wd_internal_log() );
			}
		}
		$diff  = $max - $min;
		$bytes = self::random_bytes( 4 );
		if ( 4 !== strlen( $bytes ) ) {
			throw new RuntimeException( 'Unable to get 4 bytes' );
		}
		$val = unpack( 'nint', $bytes );
		$val = $val['int'] & 0x7FFFFFFF;
		// Convert to [0,1].
		$fp = (float) $val / 2147483647.0;

		return (int) ( round( $fp * $diff ) + $min );
	}

	/**
	 * Compare two strings to avoid timing attacks.
	 *
	 * @param  string $expected  The expected string.
	 * @param  string $actual  The actual string to compare against expected.
	 *
	 * @return bool
	 */
	public static function compare_lines( $expected, $actual ): bool {
		if ( function_exists( 'hash_equals' ) ) {
			return hash_equals( $expected, $actual );
		}

		$len_expected = mb_strlen( $expected, '8bit' );
		$len_actual   = mb_strlen( $actual, '8bit' );
		$len          = min( $len_expected, $len_actual );

		$result = 0;
		for ( $i = 0; $i < $len; $i++ ) {
			$result |= ord( $expected[ $i ] ) ^ ord( $actual[ $i ] );
		}
		$result |= $len_expected ^ $len_actual;

		return 0 === $result;
	}

	/**
	 * Encrypts a given string using a specified key.
	 *
	 * @param  string $value  The plaintext value to encrypt.
	 * @param  string $key  The encryption key.
	 *
	 * @return string|WP_Error Returns the encrypted string or WP_Error on failure.
	 * @throws SodiumException Throws an exception if encryption fails.
	 */
	private static function encrypt( $value, $key ) {
		// This is not obfuscation. Just decode a base64-encoded string.
		$key = base64_decode( $key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== mb_strlen( $key, '8bit' ) ) {
			return new WP_Error(
				Error_Code::ENCRYPT_ERROR,
				esc_html__( 'The issue with Sodium library.', 'wpdef' )
			);
		}
		$nonce      = self::random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$ciphertext = sodium_crypto_secretbox( $value, $nonce, $key );
		// This is not obfuscation. Just encode the resulting string.
		return base64_encode( $nonce . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypts an encrypted string using a specified key.
	 *
	 * @param  string $encoded_value  The encrypted data to decrypt.
	 * @param  string $key  The decryption key.
	 *
	 * @return string|WP_Error Returns the decrypted string or WP_Error on failure.
	 * @throws SodiumException Throws an exception if decryption fails.
	 */
	private static function decrypt( $encoded_value, $key ) {
		if ( ! $encoded_value || '' === $key ) {
			return new WP_Error(
				Error_Code::DECRYPT_ERROR,
				esc_html__( 'Please re-setup 2FA TOTP method again.', 'wpdef' )
			);
		}
		// No obfuscation. Just decode base64-encoded $key and $encoded_value strings.
		$key        = base64_decode( $key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$decoded    = base64_decode( $encoded_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$nonce      = mb_substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit' );
		$ciphertext = mb_substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit' );

		$decrypted = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );
		if ( false === $decrypted ) {
			return new WP_Error(
				Error_Code::DECRYPT_ERROR,
				esc_html__( 'Please re-setup 2FA TOTP method again.', 'wpdef' )
			);
		}

		return $decrypted;
	}

	/**
	 * Get the path to a file with a random key. This is used for 2FA TOTP.
	 *
	 * @return string
	 */
	public static function get_path_to_key_file() {
		return wp_normalize_path( WP_CONTENT_DIR ) . DIRECTORY_SEPARATOR . 'wp-defender-secrets.php';
	}

	/**
	 * Decrypts data using a stored random key.
	 *
	 * @param  string $data  The encrypted data to decrypt.
	 *
	 * @return string|WP_Error Returns the decrypted data or WP_Error on failure.
	 * @throws SodiumException Throws an exception if decryption fails.
	 */
	public static function get_decrypted_data( $data ) {
		$key = self::get_random_key();
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		return self::decrypt( $data, $key );
	}

	/**
	 * Encrypts data using a stored random key.
	 *
	 * @param  string $data  The plaintext data to encrypt.
	 *
	 * @return string|WP_Error Returns the encrypted data or WP_Error on failure.
	 * @throws SodiumException Throws an exception if encryption fails.
	 */
	public static function get_encrypted_data( $data ) {
		$key = self::get_random_key();
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		return self::encrypt( $data, $key );
	}

	/**
	 * Retrieves a random cryptographic key from a file.
	 *
	 * @return string|WP_Error Returns the cryptographic key or WP_Error if the key file is not found or invalid.
	 * @throws SodiumException Throws an exception if key retrieval fails.
	 */
	private static function get_random_key() {
		$file = self::get_path_to_key_file();
		if ( ! file_exists( $file ) ) {
			return new WP_Error(
				Error_Code::IS_EMPTY,
				esc_html__( 'The Defender file with the random key does not exist.', 'wpdef' )
			);
		}

		if ( ! defined( 'WP_DEFENDER_TOTP_KEY' ) ) {
			require_once $file;
		}

		if ( '{{__REPLACE_CODE__}}' !== constant( 'WP_DEFENDER_TOTP_KEY' ) ) {
			return WP_DEFENDER_TOTP_KEY;
		} else {
			return new WP_Error(
				Error_Code::INVALID,
				esc_html__( 'The Defender file with the random key is incorrect.', 'wpdef' )
			);
		}
	}

	/**
	 * Generate a random key.
	 *
	 * @return string
	 * @throws Exception On failure.
	 */
	protected function generate_random_key(): string {
		// This is not obfuscation. Just encode the binary key into a base64 string.
		return base64_encode( sodium_crypto_secretbox_keygen() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Create a file with a random key.
	 *
	 * @return bool
	 * @throws Exception On failure.
	 */
	public function create_key_file(): bool {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$to = self::get_path_to_key_file();
		if ( ! file_exists( $to ) ) {
			// Move a template file to WP_CONTENT and replace the file content.
			$template_file = WP_DEFENDER_DIR . 'src' . DIRECTORY_SEPARATOR . 'component' . DIRECTORY_SEPARATOR
							. 'wp-defender-sample.php';
			if ( copy( $template_file, $to ) ) {
				$content = $wp_filesystem->get_contents( $to );
				if ( false !== strpos( $content, '{{__REPLACE_CODE__}}' ) ) {
					$new_content = str_replace( '{{__REPLACE_CODE__}}', $this->generate_random_key(), $content );

					return (bool) file_put_contents( $to, $new_content, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				}
			}

			// The file was not copied.
			return false;
		}

		// Everything is fine. The file exists.
		return true;
	}
}