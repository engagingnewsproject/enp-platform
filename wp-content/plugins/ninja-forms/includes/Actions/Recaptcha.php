<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH') ) {
	exit;
}

/**
 * Class NF_Actions_Recaptcha
 */
final class NF_Actions_Recaptcha extends SotAction implements InterfacesSotAction
{
	use SotGetActionProperties;

	/**
	 * @var array
	 */
	protected $_tags = array('spam', 'filtering', 'recaptcha');

	/**
	 * @var string
	 */
	protected $site_key;

	/**
	 * @var string
	 */
	protected $site_secret;

	/**
	 * @var int
	 */
	protected $form_id;

	/**
	 * @var array
	 */
	protected $forms_with_action;

	/**
	 * @var array
	 */
	protected $_settings_exclude = array('conditions');

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_name = 'recaptcha';
		$this->_timing = 'normal';
		$this->_priority = '10';
		$this->_group = 'core';

		add_action('init', [$this, 'initHook']);

		add_filter('ninja_forms_action_type_settings', array($this, 'maybe_remove_action'));

		add_action('nf_get_form_id', array($this, 'set_form_id'), 15, 1);

		add_filter('ninja_forms_display_fields', array($this, 'maybe_inject_field'), 10, 2);
		add_filter('ninja_forms_form_fields', array($this, 'maybe_remove_v2_field'));
		add_filter('ninja_forms_field_show_in_builder', array($this, 'maybe_remove_v2_field_from_builder'), 10, 2);
		add_action('ninja_forms_output_templates', array($this, 'maybe_output_field_template'));
		add_filter('nf_display_enqueue_scripts', array($this, 'enqueue_script'));
	}

	public function initHook()
	{

		$this->_nicename = esc_html__('reCAPTCHA v3', 'ninja-forms');
		$settings        = Ninja_Forms::config('ActionRecaptchaV3Settings');
		$this->_settings = array_merge($this->_settings, $settings);

		$this->site_key    = Ninja_Forms()->get_setting('recaptcha_site_key_3');
		$this->site_secret = Ninja_Forms()->get_setting('recaptcha_secret_key_3');
	}

	/**
	 * Setter method for the form_id and callback for the nf_get_form_id action.
	 * @since 3.2.2
	 *
	 * @param string $form_id The ID of the current form.
	 * @return void
	 */
	public function set_form_id($form_id)
	{
		$this->form_id = $form_id;
	}

	public function get_form_id()
	{
		if ($this->form_id) {
			return $this->form_id;
		}

		$this->form_id = filter_input(INPUT_GET, 'form_id', FILTER_VALIDATE_INT);

		return $this->form_id;
	}

	/**
	 * Remove the action registration if Akismet functions not available.
	 *
	 * @param array $action_type_settings
	 *
	 * @return array
	 */
	public function maybe_remove_action($action_type_settings)
	{
		if (! $this->is_recaptcha_configured()) {
			unset($action_type_settings[$this->_name]);
		}

		return $action_type_settings;
	}

	/**
	 * @return bool
	 */
	protected function is_action_enabled_for_form()
	{
		$form_id = $this->get_form_id();

		if (isset($this->forms_with_action[$form_id])) {
			return $this->forms_with_action[$form_id];
		}

		$actions = Ninja_Forms()->form($form_id)->get_actions();

		$enabled = false;
		foreach ($actions as $action) {
			if ($this->_name == $action->get_settings('type') && 1 == $action->get_setting('active')) {
				$enabled = true;
				break;
			}
		}

		$this->forms_with_action[$form_id] = $enabled;

		return $enabled;
	}

	/**
	 * Is the reCAPTCHA configured correctly
	 *
	 * @return bool
	 */
	protected function is_recaptcha_configured()
	{
		if (empty($this->site_key) || empty($this->site_secret)) {
			return false;
		}

		return true;
	}

	/**
	 * Is the reCAPTCHA action enabled
	 *
	 * @return bool
	 */
	protected function is_action_configured()
	{
		if (! $this->is_recaptcha_configured()) {
			return false;
		}

		if (! $this->is_action_enabled_for_form()) {
			return false;
		}

		return true;
	}

	public function maybe_output_field_template()
	{
		if (! $this->is_action_configured()) {
			return;
		}

		$file_path = Ninja_Forms::$dir . 'includes/Templates/';

		echo file_get_contents($file_path . "fields-recaptcha-v3.html");
	}

	protected function get_field_id_hash($form_id)
	{
		return substr(base_convert(md5($form_id), 16, 10), -5);
	}

	/**
	 * Remove v2 reCAPTCHA fields if still configured, when using the v3 Action
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function maybe_remove_v2_field($fields)
	{
		if (! $this->is_action_configured()) {
			return $fields;
		}

		foreach ($fields as $key => $field) {
			if ('recaptcha' === $field->get_setting('type')) {
				// Remove v2 reCAPTCHA fields if still configured
				unset($fields[$key]);
			}
		}

		return $fields;
	}

	/**
	 * Don't show the v2 reCAPTCHA field in the builder when using the v3 Action
	 *
	 * @param bool               $show
	 * @param NF_Abstracts_Field $field
	 *
	 * @return bool
	 */
	public function maybe_remove_v2_field_from_builder($show, $field)
	{
		if (! $this->is_action_configured()) {
			return $show;
		}

		if ('recaptcha' !== $field->get_type()) {
			return $show;
		}

		$saved_fields = Ninja_Forms()->form($this->get_form_id())->get_fields(array('saved' => 1), true);

		foreach ($saved_fields as $key => $field) {
			if ('recaptcha' === $field->get_setting('type')) {
				// recaptcha v2 field exists on form, don't hide it as it will break the JS
				return $show;
			}
		}

		// Hide the recaptcha v2 field
		return false;
	}

	/**
	 * @param array $fields
	 * @param int $form_id
	 *
	 * @return array
	 */
	public function maybe_inject_field($fields, $form_id)
	{
		if (! $this->is_action_configured()) {
			return $fields;
		}

		$field_id = $this->get_field_id_hash($form_id);

		$field = array(
			'objectType'        => 'Field',
			'objectDomain'      => 'fields',
			'editActive'        => false,
			'order'             => number_format(count($fields) + 1, 1),
			'type'              => 'recaptcha_v3',
			'label'             => 'Hidden',
			'key'               => 'recaptcha_v3',
			'default'           => '',
			'admin_label'       => '',
			'drawerDisabled'    => false,
			'id'                => $field_id,
			'beforeField'       => '',
			'afterField'        => '',
			'value'             => '',
			'label_pos'         => 'above',
			'parentType'        => 'hidden',
			'element_templates' => array(
				'recaptcha-v3',
				'hidden',
				'input',
			),
			'old_classname'     => '',
			'wrap_template'     => 'wrap-no-label',
			'site_key'          => $this->site_key,
		);

		$fields[] = $field;

		return $fields;
	}

	public function enqueue_script()
	{
		if (! $this->is_action_configured()) {
			return;
		}

		$recaptcha_lang = Ninja_Forms()->get_setting('recaptcha_lang', 'en');

		if ($this->maybe_enqueue_recaptcha_js()) {
			wp_enqueue_script('nf-google-recaptcha', 'https://www.google.com/recaptcha/api.js?hl=' . $recaptcha_lang . '&render=' . $this->site_key, array('jquery'), '3.0', true);
		}
	}

	/**
	 * Check to not load the Google reCAPTCHA JS if other plugins are doing it
	 * 
	 * @return bool
	 */
	protected function maybe_enqueue_recaptcha_js()
	{
		if (false !== apply_filters('ninja_forms_pre_enqueue_recaptcha_v3_js', false)) {
			// Allow other plugins to tell Ninja Forms not to load the Google JS script, if they are doing that
			return false;
		}

		$scripts = wp_scripts();

		foreach ($scripts->registered as $script) {
			if (false !== strpos($script->src, 'google.com/recaptcha/api.js')) {
				return false;
			}
		}

		return true;
	}

	protected function get_form_data()
	{
		if (empty($_POST['formData'])) {
			return false;
		}

		$form_data = json_decode($_POST['formData'], true);

		// php5.2 fallback
		if (! $form_data) {
			$form_data = json_decode(stripslashes($_POST['formData']), true);
		}

		return $form_data ? $form_data : false;
	}

	protected function get_recaptcha_response()
	{
		$form_data = $this->get_form_data();

		if (! $form_data || ! isset($form_data['id'])) {
			return false;
		}

		$field_id = $this->get_field_id_hash($form_data['id']);

		if (! isset($form_data['fields']) || ! isset($form_data['fields'][$field_id])) {
			return false;
		}

		return $form_data['fields'][$field_id]['value'];
	}

	/**
	 * Process the action
	 *
	 * @param array $action_settings
	 * @param int   $form_id
	 * @param array $data
	 *
	 * @return array
	 */
	public function process(array $action_settings, int $form_id, array $data): array
	{
		if (! $this->is_recaptcha_configured()) {
			return $data;
		}

		$recaptcha_response = $this->get_recaptcha_response();

		if (! $recaptcha_response) {
			$data['errors']['form']['recaptcha'] = esc_html__('Recaptcha validation failed. Please try again later', 'ninja-forms');

			return $data;
		}

		if ($this->is_submission_human($recaptcha_response, $action_settings['score'])) {
			return $data;
		}

		$data['errors']['form']['recaptcha'] = esc_html__('Recaptcha validation failed. Please try again later', 'ninja-forms');

		return $data;
	}

	protected function is_submission_human($token, $score_threshold)
	{
		$endpoint = 'https://www.google.com/recaptcha/api/siteverify';

		$request = array(
			'body' => array(
				'secret'   => $this->site_secret,
				'response' => esc_html($token),
			),
		);

		$response = wp_remote_post(esc_url_raw($endpoint), $request);

		if (200 != wp_remote_retrieve_response_code($response)) {
			if (WP_DEBUG) {
				error_log(print_r($response, true));
			}

			return false;
		}

		$response_body = wp_remote_retrieve_body($response);
		$response_body = json_decode($response_body, true);

		$score = isset($response_body['score']) ? $response_body['score'] : 0;

		$threshold = apply_filters('ninja_forms_action_recaptcha_score_threshold', $score_threshold);
		$is_human  = $threshold < $score;

		$is_human = apply_filters('ninja_forms_action_recaptcha__verify_response', $is_human, $response_body);

		return $is_human;
	}
}
