<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Component\Response;
use WP_Error;
use Calotes\Base\Component;
use WP_Defender\Model\Setting\Mask_Login;

/**
 * Class Security_Key
 * @package WP_Defender\Component\Security_Tweaks
 */
class Security_Key extends Component {
	public $slug = 'security-key';
	public $default_days = '60 days';
	public $reminder_duration = null;
	public $reminder_date = null;
	public $last_modified = null;
	public $file = ABSPATH . 'wp-config.php';

	/**
	 * Check wheter the issue has been resolved or not
	 *
	 * @return bool
	 */
	public function check() {
		$this->get_options();

		if ( ! $this->last_modified ) {
			if ( file_exists( $this->file ) ) {
				$this->last_modified = filemtime( $this->file );
			} else {
				$this->last_modified = filemtime( ABSPATH . WPINC . '/general-template.php' );
			}
		}

		if ( $this->last_modified ) {
			$reminder_date = strtotime( '+' . $this->reminder_duration, $this->last_modified );

			return $reminder_date > time();
		}
	}

	/**
	 * Get options
	 *
	 * @return void
	 */
	private function get_options() {
		$options                 = get_site_option( 'defender_security_tweaks_' . $this->slug );
		$this->reminder_date     = ! empty( $options['reminder_date'] ) ? $options['reminder_date'] : null;
		$this->reminder_duration = ! empty( $options['reminder_duration'] ) ? $options['reminder_duration'] : $this->default_days;
		$this->last_modified     = ! empty( $options['last_modified'] ) ? $options['last_modified'] : null;
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error
	 *
	 * @return bool|WP_Error
	 */
	public function process() {
		$constants = $this->get_constants();
		$salts     = $this->get_salts();

		if ( is_wp_error( $salts ) ) {
			return $salts;
		}

		if ( ! is_writable( $this->file ) ) {
			return new WP_Error(
				'defender_file_not_writable',
				sprintf( __( 'The file %s is not writable', 'wpdef' ), $this->file )
			);
		}

		$contents = file_get_contents( $this->file );

		foreach ( $constants as $key => $const ) {
			$pattern     = "/^define\(\s*['|\"]{$const}['|\"],(.*)\)\s*;/m";
			$replacement = $salts[ $key ];
			$contents    = preg_replace_callback( $pattern, function () use ( $replacement ) {
				return $replacement;
			}, $contents );
		}

		$is_done = (bool) file_put_contents( $this->file, $contents, LOCK_EX );

		if ( $is_done ) {
			$values                  = get_site_option( 'defender_security_tweaks_' . $this->slug );
			$values['last_modified'] = time();
			update_site_option( 'defender_security_tweaks_' . $this->slug, $values );

			$url        = wp_login_url( network_admin_url( 'admin.php?page=wdf-hardener' ) );
			$mask_login = new Mask_Login();

			if ( $mask_login->is_active() ) {
				$url = $mask_login->get_new_login_url();
			}

			$interval = 3;
			return new Response(
				true,
				array(
					'message' => sprintf(
						__( 'All key salts have been regenerated. You will now need to <a href="%s"><strong>re-login</strong></a>.<br/>This will auto reload after <span class="hardener-timer">%s</span> seconds.',
							'wpdef' ),
						$url,
						$interval
					),
					'redirect' => $url,
					'interval' => $interval,
				)
			);
		}

		return $is_done;
	}

	/**
	 * This is for un-do stuff that has be done in @process
	 *
	 * @return bool|WP_Error
	 */
	public function revert() {
		return true;
	}

	/**
	 * Shild up method
	 *
	 * @return bool
	 */
	public function shield_up() {
		return true;
	}

	/**
	 * Get salts to be palced in wp-config.php
	 *
	 * @return string|WP_Error on false
	 */
	private function get_salts() {
		$response = wp_safe_remote_get( 'https://api.wordpress.org/secret-key/1.1/salt/' );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'defender_salts_not_found',
				__( 'Unable to generate salts. Please try again.', 'wpdf' ) );
		}

		return array_filter( explode( "\n", wp_remote_retrieve_body( $response ) ) );
	}

	/**
	 * Get how long the wp-config file is last updated
	 *
	 * @return string
	 */
	private function get_last_modified_days() {
		$timestamp    = filemtime( ABSPATH . '/' . WPINC . '/general-template.php' );
		$current_time = time();
		$days_ago     = '';

		if ( $this->last_modified ) {
			$days_ago = ( $current_time - $this->last_modified ) / DAY_IN_SECONDS;
		} elseif ( $timestamp ) {
			$days_ago = ( $current_time - $timestamp ) / DAY_IN_SECONDS;
		}

		$days_ago = $days_ago ? round( $days_ago ) : __( 'unknown', 'wpdef' );

		return $days_ago ? $days_ago : 1;
	}

	/**
	 * Get all the constants
	 *
	 * @return array
	 */
	private function get_constants() {
		return [
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		];
	}

	/**
	 * Return a summary data of this tweak
	 *
	 * @return array
	 */
	public function to_array() {
		$get_last_modified_days = $this->get_last_modified_days();

		if ( 'unknown' === $get_last_modified_days ) {
			$error_message = __( 'We can\'t tell how old your security keys are, perhaps it\'s time to update them?',
				'wpdef' );
		} else {
			$error_message = sprintf( __( 'Your current security keys are %s days old. Time to update them!', 'wpdef' ),
				$get_last_modified_days );
		}

		return [
			'slug'             => $this->slug,
			'title'            => __( 'Update old security keys', 'wpdef' ),
			'errorReason'      => $error_message,
			'successReason'    => sprintf( __( 'Your security keys are less than %s days old, nice work.', 'wpdef' ),
				$get_last_modified_days ),
			'misc'             => [
				'reminder' => $this->reminder_duration
			],
			'bulk_description' => __( 'Your current security keys are unknown days old. Time to update them! We will update the frequency to 60 days.',
				'wpdef' ),
			'bulk_title'       => __( 'Security Keys', 'wpdef' )
		];
	}
}
