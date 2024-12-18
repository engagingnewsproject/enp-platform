<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class NF_Actions_ExportPersonalData
 */
final class NF_Actions_ExportDataRequest extends SotAction implements InterfacesSotAction
{
	use SotGetActionProperties;

	/**
	 * @var array
	 */
	protected $_tags = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_name  = 'exportdatarequest';
		$this->_timing = 'late';
		$this->_priority = 10;
		$this->_documentation_url = 'https://ninjaforms.com/docs/export-data-request-action/';
		$this->_group = 'core';

		add_action('init', [$this, 'initHook']);
	}

	public function initHook()
	{
		$this->_nicename = esc_html__('Export Data Request', 'ninja-forms');

		$settings = Ninja_Forms::config('ActionExportDataRequestSettings');
		$this->_settings = array_merge($this->_settings, $settings);
	}

	/*
	* PUBLIC METHODS
	*/


	/**
	 * Creates a Export Personal Data request for the user with the email
	 * provided
	 *
	 * @param $action_settings
	 * @param $form_id
	 * @param $data
	 *
	 * @return array
	 */
	public function process(array  $action_settings, int $form_id, array $data): array
	{
		$data = array();

		if (isset($data['settings']['is_preview']) && $data['settings']['is_preview']) {
			return $data;
		}

		// get the email setting
		$email = $action_settings['email'];

		// create request for user
		$request_id = wp_create_user_request(
			$email,
			'export_personal_data'
		);

		/**
		 * Basically ignore if we get a user error as it will be one of two
		 * things.
		 *
		 * 1) The email in question is already in the erase data request queue
		 * 2) The email does not belong to an actual user.
		 */
		if (! $request_id instanceof WP_Error) {
			wp_send_user_request($request_id);
		}

		return $data;
	}
}
