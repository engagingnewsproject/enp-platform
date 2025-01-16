<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class NF_Action_Save
 */
class NF_Actions_Save extends SotAction implements InterfacesSotAction
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

        $this->_name  = 'save';
        $this->_timing = 'late';
        $this->_priority = '-1';
        $this->_documentation_url = 'https://ninjaforms.com/docs/record-submission-action/';
        $this->_group = 'core';

        add_action('init', [$this, 'initHook']);
    }

    public function initHook()
    {
        $this->_nicename = esc_html__('Record Submission', 'ninja-forms');

        $settings = Ninja_Forms::config('ActionSaveSettings');

        $this->_settings = array_merge($this->_settings, $settings);
    }

    /*
    * PUBLIC METHODS
    */

    /** @inheritDoc */
    public function save(array $action_settings)
    {
        if (! isset($_POST['form'])) return;
        // Get the form data from the Post variable and send it off for processing.
        $form = json_decode(stripslashes($_POST['form']));
        $this->submission_expiration_processing($action_settings, $form->id);
    }

    /**
     * Submission Expiration Processing
     * Decides if the submission expiration data should be added to the
     * submission expiration option or not.
     *
     * @param $action_settings - array.
     * @param $form_id - ( int ) The ID of the Form.
     *
     * @return void
     */
    public function submission_expiration_processing($action_settings, $form_id)
    {
        /*
         * Comma separated value of the form id and action setting.
         * Example: 5,90
         */
        $expiration_value = $form_id . ',' . $action_settings['subs_expire_time'];

        // Get our expiration option.
        $option = $this->getOption('nf_sub_expiration', array());

        // Check if form is already listed in the option and remove it if it is
        $expiration_option = $this->clean_form_option($expiration_value, $option);

        // If our expiration setting is turned on, add current cron interval to the form entry in the option.
        if (1 == $action_settings['set_subs_to_expire']) {
            $expiration_option[] = $expiration_value;
        }

        // Update our option.
        $this->updateOption('nf_sub_expiration', $expiration_option);
    }

    /**
     * Retrieve a stored option
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getOption(string $key, $default)
    {
        $return = get_option($key, $default);

        return $return;
    }

    /**
     * Update a stored value in option table
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function updateOption(string $key, $value): void
    {
        update_option($key, $value);
    }
    /**
     * Compare Expiration Option
     * Accepts $expiration_data and checks to see if the values already exist in the array.
     * This allows to resave the option with new cron interval if it is set and just remove the form from the option if it is not set
     * @since 3.6.35
     *
     * @param string $expiration_value - key/value pair
     *      $expiration_value[ 'form_id' ]      = form_id(int)
     *      $expiration_value[ 'expire_time' ]  = subs_expire_time(int)
     * @param array $expiration_option - list of key/value pairs of the expiration options.
     *
     * @return array $expiration_option without current saved form 
     */
    public function clean_form_option($expiration_value, $expiration_option)
    {
        /*
         * Breaks a part our options.
         *      $value[ 0 ] - ( int ) Form ID
         *      $value[ 1 ] - ( int ) Expiration time in days
         */
        $values = explode(',', $expiration_value);

        // Find the position of the value we are tyring to update.
        //This checks if this form is already in the expiration options, removes the form from the option's array and adds it again with the new expiration time
        foreach ($expiration_option as $index => $form_option) {
            $form_option = explode(',', $form_option);
            if ($form_option[0] == $values[0]) {
                unset($expiration_option[$index]);
            }
        }

        return $expiration_option;
    }

    /** @inheritDoc */
    public function process(array $action_settings, int $form_id, array $data): array
    {

        if (isset($data['settings']['is_preview']) && $data['settings']['is_preview']) {
            return $data;
        }

        if (! apply_filters('ninja_forms_save_submission', true, $form_id)) return $data;

        $sub = Ninja_Forms()->form($form_id)->sub()->get();

        $hidden_field_types = apply_filters('nf_sub_hidden_field_types', array());

        // For each field on the form...
        foreach ($data['fields'] as $field) {

            // If this is a "hidden" field type.
            if (in_array($field['type'], array_values($hidden_field_types))) {
                // Do not save it.
                $data['actions']['save']['hidden'][] = $field['type'];
                continue;
            }

            $field['value'] = apply_filters('nf_save_sub_user_value', $field['value'], $field['id']);

            $save_all_none = $action_settings['fields-save-toggle'];
            $save_field = true;

            // If we were told to save all fields...
            if ('save_all' == $save_all_none) {
                $save_field = true;
                // For each exception to that rule...
                foreach ($action_settings['exception_fields'] as $exception_field) {
                    // Remove it from the list.
                    if ($field['key'] == $exception_field['field']) {
                        $save_field = false;
                        break;
                    }
                }
            } // Otherwise... (We were told to save no fields.)
            else if ('save_none' == $save_all_none) {
                $save_field = false;
                // For each exception to that rule...
                foreach (
                    $action_settings['exception_fields'] as
                    $exception_field
                ) {
                    // Add it to the list.
                    if ($field['key'] == $exception_field['field']) {
                        $save_field = true;
                        break;
                    }
                }
            }

            // If we're supposed to save this field...
            if ($save_field) {
                // Do so.
                $sub->update_field_value($field['id'], $field['value']);
            } // Otherwise...
            else {
                // If this field is not a list...
                // AND If this field is not a checkbox...
                // AND If this field is not a product...
                // AND If this field is not a termslist...
                if (
                    false == strpos($field['type'], 'list') &&
                    false == strpos($field['type'], 'checkbox') &&
                    'products' !== $field['type'] &&
                    'terms' !== $field['type']
                ) {
                    // Anonymize it.
                    $sub->update_field_value($field['id'], '(redacted)');
                }
            }
        }

        // If we have extra data...
        if (isset($data['extra'])) {

            $data['extra'] = $this->validateExtraData($data['extra'], $form_id);

            // Save that.
            $sub->update_extra_values($data['extra']);
        }

        do_action('nf_before_save_sub', $sub->get_id());

        $sub->save();

        do_action('nf_save_sub', $sub->get_id());
        do_action('nf_create_sub', $sub->get_id());
        do_action('ninja_forms_save_sub', $sub->get_id());

        $data['actions']['save']['sub_id'] = $sub->get_id();

        return $data;
    }

    /**
     * Ensure extra data is valid
     * 
     * 1. Ensure that extra data is array
     * 2. Check that count of extra data is within allowed limit
     * 3. If count exceeds limit, consolidate data into single value
     *
     * The purpose of 'extraDataOverflowOnSave' is to attempt to store the data submitted in the case that the data truly is valid, but an add-on is storing too many values as individually keyed.  It has the added benefit of providing insight on the nature of an attack should that be the case instead of an errant add-on.
     * 
     * @param array $dataExtra
     * @param int $form_id
     * @return array
     */
    protected function validateExtraData($dataExtra, $form_id): array
    {
        return $dataExtra;
        $return = [];

        if (!is_array($dataExtra)) {
            return $return;
        }

        $maxCount = apply_filters('ninja_forms_max_extra_data_count', 200, $form_id);

        if ($maxCount < count($dataExtra)) {

            $return['extraDataOverflowOnSave'] = json_encode($dataExtra);
        }

        return $return;
    }
}
