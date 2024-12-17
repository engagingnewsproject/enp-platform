<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class NF_Action_SuccessMessage
 */
final class NF_Actions_SuccessMessage extends SotAction implements InterfacesSotAction
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

        $this->_name  = 'successmessage';
        $this->_timing = 'late';
        $this->_priority = 10;
        $this->_documentation_url = 'https://ninjaforms.com/docs/success-message/';
        $this->_group = 'core';

        add_action('init', [$this, 'initHook']);

        add_action('nf_before_import_form', array($this, 'import_form_action_success_message'), 11);
    }

    public function initHook()
    {
        $this->_nicename = esc_html__('Success Message', 'ninja-forms');

        $settings = Ninja_Forms::config('ActionSuccessMessageSettings');

        $this->_settings = array_merge($this->_settings, $settings);
    }

    /*
    * PUBLIC METHODS
    */


    /** @inheritDoc */
    public function process(array $action_settings, int $form_id, array $data): array
    {
        if (isset($action_settings['success_msg'])) {

            if (! isset($data['actions']) || ! isset($data['actions']['success_message'])) {
                $data['actions']['success_message'] = '';
            }

            ob_start();
            do_shortcode($action_settings['success_msg']);
            $ob = ob_get_clean();

            if ($ob) {
                $data['debug']['console'][] = sprintf(esc_html__('Shortcodes should return and not echo, see: %s', 'ninja-forms'), 'https://codex.wordpress.org/Shortcode_API#Output');
                $data['actions']['success_message'] .= $action_settings['success_msg'];
            } else {
                $message = do_shortcode($action_settings['success_msg']);
                $data['actions']['success_message'] .= wpautop($message);
            }
        }

        return $data;
    }

    public function import_form_action_success_message($import)
    {
        if (! isset($import['actions'])) return $import;

        foreach ($import['actions'] as &$action) {

            if ('success_message' == $action['type']) {

                $action['type'] = 'successmessage';
            }
        }

        return $import;
    }
}
