<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class NF_Action_Redirect
 */
final class NF_Actions_Redirect extends SotAction implements InterfacesSotAction
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

        $this->_name  = 'redirect';
        $this->_timing = 'late';
        $this->_priority = 20;
        $this->_documentation_url = 'https://ninjaforms.com/docs/redirect-action/';
        $this->_group = 'core';

        add_action('init', [$this, 'initHook']);
    }

    public function initHook()
    {

        $this->_nicename = esc_html__('Redirect', 'ninja-forms');

        $settings = Ninja_Forms::config('ActionRedirectSettings');

        $this->_settings = array_merge($this->_settings, $settings);
    }
    /*
    * PUBLIC METHODS
    */


    /** @inheritDoc */
    public function process(array $action_settings, int $form_id, array $data): array
    {
        $data['actions']['redirect'] = $action_settings['redirect_url'];

        return $data;
    }
}
