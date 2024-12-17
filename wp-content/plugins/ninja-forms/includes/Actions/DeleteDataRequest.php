<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class NF_Actions_DataRemoval
 */
final class NF_Actions_DeleteDataRequest extends SotAction implements InterfacesSotAction
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

		$this->_name  = 'deletedatarequest';
		$this->_priority = 10;
		$this->_documentation_url = 'https://ninjaforms.com/docs/delete-data-request-action/';
		$this->_timing = 'late';
		$this->_group = 'core';

		add_action('init', [$this, 'initHook']);
	}

	public function initHook()
	{
		$this->_nicename = esc_html__('Delete Data Request', 'ninja-forms');

		$settings = Ninja_Forms::config('ActionDeleteDataRequestSettings');
		$this->_settings = array_merge($this->_settings, $settings);
	}

	/*
	* PUBLIC METHODS
	*/

	/**
	 * Creates a Erase Personal Data request for the user with the email
	 * provided
	 *
	 * @param $action_settings
	 * @param $form_id
	 * @param $data
	 *
	 * @return array
	 */
	public function process(array $action_settings, int $form_id, array $data): array
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
			'remove_personal_data'
		);

		/**
		 * Basically ignore if we get a user error as it will be one of two
		 * things.
		 *
		 * 1) The email in question is already in the erase data request queue
		 * 2) The email does not belong to an actual user.
		 */
		if (! $request_id instanceof WP_Error) {
			// send the request if it's not an error.

			// to anonymize or not to anonymize, that is the question
			add_post_meta(
				$request_id,
				'nf_anonymize_data',
				$action_settings['anonymize']
			);

			wp_send_user_request($request_id);
		}

		return $data;
	}
}
