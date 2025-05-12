<?php
/**
 * Responsible for handling ALTCHA.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use AltchaOrg\Altcha\ChallengeOptions;
use AltchaOrg\Altcha\Altcha;

/**
 * Provides methods for creating and verifying Altcha's challenge.
 */
class Altcha_Handler {

	/**
	 * The log file name.
	 */
	public const LOG_FILE_NAME = 'altcha.log';

	/**
	 * The HMAC key used for generating and verifying challenges.
	 *
	 * @var string
	 */
	private $hmac_key;

	/**
	 * The option name for the HMAC key.
	 */
	public const HMAC_KEY_OPTION_NAME = 'wpdef_hmac_key';

	/**
	 * Constructor for Altcha_Handler class.
	 */
	public function __construct() {
		$this->hmac_key = $this->get_hmac_key();
	}

	/**
	 * Generate or retrieve the HMAC key from the WordPress options.
	 *
	 * @return string The HMAC key.
	 */
	private function get_hmac_key() {
		$hmac_key = get_option( self::HMAC_KEY_OPTION_NAME );

		if ( ! $hmac_key ) {
			// Generate a random key if it doesn't exist.
			$hmac_key = wp_generate_password( 64, true, true );

			update_option( self::HMAC_KEY_OPTION_NAME, $hmac_key );
		}

		return $hmac_key;
	}

	/**
	 * Create a new ALTCHA challenge.
	 *
	 * @param int $max_number Maximum random number for the challenge.
	 *
	 * @return array Challenge data.
	 */
	public function create_challenge( $max_number = 100000 ) {
		$options = new ChallengeOptions(
			array(
				'hmacKey'   => $this->hmac_key,
				'maxNumber' => $max_number,
			)
		);

		return Altcha::createChallenge( $options );
	}

	/**
	 * Verify the given solution against the challenge.
	 *
	 * @param array $payload     Payload containing the solution details.
	 * @param bool  $strict_mode Enable or disable strict verification.
	 *
	 * @return bool Verification result.
	 */
	public function verify_solution( array $payload, $strict_mode = true ) {
		return Altcha::verifySolution( $payload, $this->hmac_key, $strict_mode );
	}
}