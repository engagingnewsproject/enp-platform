<?php

use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class NF_Action_SuccessMessage
 */
final class NF_Actions_GoogleAnalytics extends SotAction implements InterfacesSotAction
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

        $this->_name  = 'googleanalytics';
        $this->_timing = 'late';
        $this->_priority = 25;
        $this->_documentation_url = 'https://ninjaforms.com/docs/google-analytics-4/?utm_source=Ninja+Forms+Plugin&utm_medium=Form+Builder&utm_campaign=Documentation&utm_content=GA4';
        $this->_group = 'core';

        add_action('init', [$this, 'initHook']);
    }

    public function initHook()
    {
        $this->_nicename = esc_html__('Google Analytics 4', 'ninja-forms');

        $settings = Ninja_Forms::config('ActionGoogleAnalyticsSettings');

        $this->_settings = array_merge($this->_settings, $settings);
    }

    /*
    * PUBLIC METHODS
    */
    
    /** @inheritDoc */
    public function process(array $action_settings, int $form_id, array $data): array
    {
        if (isset($data['settings']['is_preview']) && $data['settings']['is_preview']) {
            return $data;
        }

        $data['actions'][$action_settings['type']][] = [
            'method_type'   => $action_settings['method_type'],
            'event_name'    => $action_settings['event_name'],
            'label'         => $action_settings['label'],
            'id'            => $action_settings['id']
        ];

        return $data;
    }

}
