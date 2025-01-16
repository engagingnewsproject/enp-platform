<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class NF_Action_Custom
 */
final class NF_Actions_Custom extends SotAction implements InterfacesSotAction
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

        $this->_name  = 'custom';
        $this->_priority = 10;
        $this->_timing = 'normal';
        $this->_documentation_url = 'https://ninjaforms.com/docs/wp-hook/';
        $this->_group = 'core';

        add_action('init', [$this, 'initHook']);
    }

    public function initHook()
    {
        $this->_nicename = esc_html__('WP Hook', 'ninja-forms');

        $settings = Ninja_Forms::config('ActionCustomSettings');

        $this->_settings = array_merge($this->_settings, $settings);
    }

    /*
    * PUBLIC METHODS
    */

    /** @inheritDoc */
    public function process(array $action_settings, int $form_id, array $data): array
    {
        if (isset($action_settings['tag'])) {
            ob_start(); // Use the Output Buffer to suppress output

            do_action($action_settings['tag'], $data);

            ob_end_clean();
        }

        return $data;
    }
}
