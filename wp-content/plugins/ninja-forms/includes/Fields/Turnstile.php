<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;}

/**
 * Class NF_Fields_Turnstile
 */
class NF_Fields_Turnstile extends NF_Abstracts_Field {

	protected $_name = 'turnstile';

	protected $_type = 'turnstile';

	protected $_section = 'misc';

	protected $_icon = 'shield';

	protected $_templates = 'turnstile';

	protected $_test_value = '';

	protected $_settings = array( 'label', 'classes' );

	public function __construct() {
		parent::__construct();

		$this->_nicename = esc_html__( 'Cloudflare Turnstile', 'ninja-forms' );

		$this->_settings['label_visibility'] = array(
			'name'    => 'label_visibility',
			'type'    => 'select',
			'label'   => esc_html__( 'Label Visibility', 'ninja-forms' ),
			'options' => array(
				array(
					'label' => esc_html__( 'Visible', 'ninja-forms' ),
					'value' => 'visible',
				),
				array(
					'label' => esc_html__( 'Hidden', 'ninja-forms' ),
					'value' => 'invisible',
				),
			),
			'width'   => 'one-half',
			'group'   => 'primary',
			'value'   => 'invisible',
			'help'    => esc_html__( 'Choose whether to show or hide the field label for the Turnstile widget.', 'ninja-forms' ),
		);

		$this->_settings['size'] = array(
			'name'    => 'size',
			'type'    => 'select',
			'label'   => esc_html__( 'Size', 'ninja-forms' ),
			'options' => array(
				array(
					'label' => esc_html__( 'Normal', 'ninja-forms' ),
					'value' => 'normal',
				),
				array(
					'label' => esc_html__( 'Compact', 'ninja-forms' ),
					'value' => 'compact',
				),
			),
			'width'   => 'one-half',
			'group'   => 'display',
			'value'   => 'normal',
			'help'    => esc_html__( 'Select the size of the Turnstile widget.', 'ninja-forms' ),
		);

		$this->_settings['theme'] = array(
			'name'    => 'theme',
			'type'    => 'select',
			'label'   => esc_html__( 'Theme', 'ninja-forms' ),
			'options' => array(
				array(
					'label' => esc_html__( 'Light', 'ninja-forms' ),
					'value' => 'light',
				),
				array(
					'label' => esc_html__( 'Dark', 'ninja-forms' ),
					'value' => 'dark',
				),
				array(
					'label' => esc_html__( 'Auto', 'ninja-forms' ),
					'value' => 'auto',
				),
			),
			'width'   => 'one-half',
			'group'   => 'display',
			'value'   => 'auto',
			'help'    => esc_html__( 'Select the theme for the Turnstile widget.', 'ninja-forms' ),
		);

		add_filter( 'nf_sub_hidden_field_types', array( $this, 'hide_field_type' ) );
	}

	public function localize_settings( $settings, $form ) {
		$settings['site_key'] = Ninja_Forms()->get_setting( 'turnstile_site_key' );
		$settings['theme']    = ( ! empty( $settings['theme'] ) ) ? $settings['theme'] : Ninja_Forms()->get_setting( 'turnstile_theme' );
		$settings['size']     = ( ! empty( $settings['size'] ) ) ? $settings['size'] : Ninja_Forms()->get_setting( 'turnstile_size' );
		return $settings;
	}

	public function validate( $field, $data ) {
		if ( empty( $field['value'] ) ) {
			return esc_html__( 'Turnstile verification failed. Please try again.', 'ninja-forms' );
		}

		$secret_key = Ninja_Forms()->get_setting( 'turnstile_secret_key' );
		if ( empty( $secret_key ) ) {
			return esc_html__( 'Turnstile secret key is not configured.', 'ninja-forms' );
		}

		$url      = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		$response = wp_remote_post(
			esc_url_raw( $url ),
			array(
				'body' => array(
					'secret'   => $secret_key,
					'response' => sanitize_text_field( $field['value'] ),
					'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return esc_html__( 'Unable to verify Turnstile. Please try again.', 'ninja-forms' );
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( empty( $result['success'] ) ) {
			$error_codes = $result['error-codes'] ?? array();

			if ( in_array( 'missing-input-secret', $error_codes ) || in_array( 'invalid-input-secret', $error_codes ) ) {
				return esc_html__( 'Please make sure you have entered your Site & Secret keys correctly.', 'ninja-forms' );
			} else {
				return esc_html__( 'Turnstile verification failed. Please try again.', 'ninja-forms' );
			}
		}

		return false; // No error
	}

	function hide_field_type( $field_types ) {
		$field_types[] = $this->_name;
		return $field_types;
	}
}
