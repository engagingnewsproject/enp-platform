<?php

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

class Recaptcha extends Setting {
	/**
	 * Option name
	 * @var string
	 */
	public $table = 'wd_recaptcha_settings';

	/**
	 * Main switch of this function
	 * @var bool
	 * @defender_property
	 */
	public $enabled = false;
	/**
	 * @var string
	 * @defender_property
	 * @rule required
	 * @rule in[v2_checkbox,v2_invisible,v3_recaptcha]
	 */
	public $active_type = 'v2_checkbox';
	/**
	 * @var array
	 * @defender_property
	 */
	public $data_v2_checkbox;
	/**
	 * @var array
	 * @defender_property
	 */
	public $data_v2_invisible;
	/**
	 * @var array
	 * @defender_property
	 */
	public $data_v3_recaptcha;
	/**
	 * @var string
	 * @defender_property
	 * @rule required
	 */
	public $language = '';
	/**
	 * @var string
	 * @defender_property
	 */
	public $message = '';
	/**
	 * @var array
	 * @defender_property
	 * @rule required
	 */
	public $locations = array();

	protected $rules = array(
		array( array( 'enabled' ), 'boolean' ),
		array( array( 'active_type' ), 'in', array( 'v2_checkbox', 'v2_invisible', 'v3_recaptcha' ) ),
	);

	protected function before_load() {
		$this->message           = __( 'reCAPTCHA verification failed. Please try again.', 'wpdef' );
		$this->language          = 'automatic';
		$this->data_v2_checkbox  = array(
			'key'    => '',
			'secret' => '',
			'size'   => 'normal',
			'style'  => 'light',
		);
		$this->data_v2_invisible = array(
			'key'    => '',
			'secret' => '',
		);
		$this->data_v3_recaptcha = array(
			'key'       => '',
			'secret'    => '',
			'threshold' => '0.5',
		);
	}

	/**
	 * @param string $active_type
	 * @return bool
	 */
	private function check_recaptcha_type( $active_type ) {
		if (
			'v2_checkbox' === $active_type
			&& ! empty( $this->data_v2_checkbox['key'] )
			&& ! empty( $this->data_v2_checkbox['secret'] )
		) {
			return true;
		} elseif (
			'v2_invisible' === $active_type
			&& ! empty( $this->data_v2_invisible['key'] )
			&& ! empty( $this->data_v2_invisible['secret'] )
		) {
			return true;
		} elseif (
			'v3_recaptcha' === $active_type
			&& ! empty( $this->data_v3_recaptcha['key'] ) && ! empty( $this->data_v3_recaptcha['secret'] )
		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function is_active() {

		return apply_filters(
			'wd_recaptcha_enable',
			$this->enabled
			&& '' !== $this->active_type
			&& '' !== $this->language
			//for each Recaptcha type
			&& $this->check_recaptcha_type( $this->active_type )
		);
	}

	/**
	 * @return bool
	 */
	public function enable_locations() {
		return ! empty( $this->locations );
	}

	/**
	 * Define labels for settings key.
	 *
	 * @param  string|null $key
	 *
	 * @return string|array|null
	 */
	public function labels( $key = null ) {
		$labels = array(
			'active_type' => __( 'Configure reCaptcha', 'wpdef' ),
			'language'    => __( 'Language', 'wpdef' ),
			'message'     => __( 'Error Message', 'wpdef' ),
			'locations'   => __( 'CAPTCHA Locations', 'wpdef' ),
		);

		if ( ! is_null( $key ) ) {
			return isset( $labels[ $key ] ) ? $labels[ $key ] : null;
		}

		return $labels;
	}
}
