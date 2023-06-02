<?php

use NinjaForms\ConstantContact\Handlers\Api2Subscribe;

/*
 * Plugin Name: Ninja Forms - Constant Contact
 * Plugin URI: https://ninjaforms.com/extensions/constant-contact/
 * Description: Sign users up for your Constant Contact newsletter when submitting Ninja Forms
 * Version: 3.1.1
 * Author: The WP Ninjas
 * Author URI: http://ninjaforms.com
 * Text Domain: ninja-forms-constant-contact
 *
 * Copyright 2016 The WP Ninjas.
 */

/**
 * Class NF_ConstantContact
 */
final class NF_ConstantContact
{
    const VERSION = '3.1.1';
    const SLUG    = 'constant-contact';
    const NAME    = 'Constant Contact';
    const AUTHOR  = 'The WP Ninjas';
    const PREFIX  = 'NF_ConstantContact';

    /**
     * @var NF_ConstantContact
     * @since 3.0
     */
    private static $instance;

    /**
     * Plugin Directory
     *
     * @since 3.0
     * @var string $dir
     */
    public static $dir = '';

    /**
     * Plugin URL
     *
     * @since 3.0
     * @var string $url
     */
    public static $url = '';

    /**
     * @var Constant Contact
     */
    private $_api;

    /**
     *
     * @var NF_ConstantContact_Admin_HandledResponse 
     */
    protected $handledResponse;

    /**
     * Response data array from CC subscribe request
     * @var array
     */
    protected $response = array();



    /**
     * Main Plugin Instance
     *
     * Insures that only one instance of a plugin class exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @since 3.0
     * @static
     * @static var array $instance
     * @return NF_ConstantContact Highlander Instance
     */
    public static function instance()
    {
        if (!isset(self::$instance) && !(self::$instance instanceof NF_ConstantContact)) {
            self::$instance = new NF_ConstantContact();

            self::$dir = plugin_dir_path(__FILE__);

            self::$url = plugin_dir_url(__FILE__);

            /*
                 * Register our autoloader
                 */
            spl_autoload_register(array(self::$instance, 'autoloader'));

            
            new NF_ConstantContact_Admin_Settings();
        }

        return self::$instance;
    }

    /**
     * @updated 3.0.4
     */
    public function __construct()
    {
        /*
             * Required for all Extensions.
             */
        add_action('admin_init', array($this, 'setup_license'));

        /*
             * Register any settings the instance needs.
             */
        add_action('admin_init', array($this, 'register_settings'));
        add_action('ninja_forms_loaded', array($this, 'setupAdmin'));

        /*
             * Register any notices we need to apply.
             */
        add_filter('ninja_forms_admin_notices', array($this, 'admin_notices'));

        /*
             * Optional. If your extension creates a new field interaction or display template...
             */
        add_filter('ninja_forms_register_fields', array($this, 'register_fields'));

        /*
             * Optional. If your extension processes or alters form submission data on a per form basis...
             */
        add_filter('ninja_forms_register_actions', array($this, 'register_actions'));

        add_filter('nf_react_table_extra_value_keys', array($this, 'addMetabox'));
    }

    /**
     * Add a metabox constructor to the react.js submissions page
     *
     * @param array $metaboxHandlers
     * @return array
     */
    public function addMetabox(array $metaboxHandlers): array
    {
        $metaboxHandlers['constant-contact'] = 'NF_ConstantContact_Admin_MetaboxOutput';
        return $metaboxHandlers;
    }

    /**
     * Setup Admin
     *
     * Setup admin classes for Ninja Forms and WordPress.
     * 
     * @codeCoverageIgnore Classes in here should be tested individually
     */
    public function setupAdmin()
    {
        $handledResponseObject = new NF_ConstantContact_Admin_HandledResponse();
        $extraValueKey = 'constant-contact';
        $metaboxLabel = 'Constant Contact Response';
        new NF_ConstantContact_Admin_OutputResponseDataMetabox($handledResponseObject, $extraValueKey, $metaboxLabel);
    }
    /**
     * Function to register any admin notices we need to show.
     * 
     * @param $notices (Array) The list of admin notices.
     * @return (Array) The updated list of admin notices.
     * 
     * @since 3.0.4
     */
    public function admin_notices($notices)
    {
        // If we've not already updated our dev key...
        if ('false' !== get_option('nf_const_ctc_dev_key_deprecated', 'false')) {
            // Register an admin notice.
            $notices['const_ctc_deprecated_key'] = array(
                'title' => __('Constant Contact Needs to Reconnect', 'ninja-forms-constant-contact'),
                'msg' => sprintf(__('%sWe have a new API version, and some features may not work correctly until you refresh your connection.%sThis will require you to reauthenticate with Constant Contact.%sRefresh my connection now%s', 'ninja-forms-constant-contact'), '<p>', '<br />', '</p><p><a href="https://oauth.ninjaforms.com/constant-contact/oauth.php?admin_url=' . urlencode(admin_url() . 'admin.php?page=nf-settings') . '&plugin_version=' . self::VERSION . '">', '</a></p>'),
                'int' => 0,
                'ignore_spam' => true,
                'dismiss' => 1
            );
        }
        return $notices;
    }

    /**
     * Optional. If your extension creates a new field interaction or display template...
     */
    public function register_fields($actions)
    {
        $actions['constant-contact-optin'] = new NF_ConstantContact_Fields_Optin(); // includes/Fields/ConstantContactExample.php

        return $actions;
    }

    /**
     * Function to register the settings of the instance.
     * 
     * @since 3.0.4
     */
    public function register_settings()
    {
        // If we do not have a reference to the deprecated key...
        if (!get_option('nf_const_ctc_dev_key_deprecated', false)) {
            /*
                 * Register the old key.
                 * 
                 * This was the active dev api key as of version 3.0.3.
                 * Now deprecated in favor of a new key, defined in the get_dev_api_key function.
                 */
            add_option('nf_const_ctc_dev_key_deprecated', 'd4tkm7yt9chm5bmfc32txtj6', '', 'no');
        }
    }

    /**
     * Optional. If your extension processes or alters form submission data on a per form basis...
     */
    public function register_actions($actions)
    {
        $actions['constant-contact'] = new NF_ConstantContact_Actions_ConstantContact(); // includes/Actions/ConstantContactExample.php

        return $actions;
    }

    /*
         * Optional methods for convenience.
         */

    public function autoloader($class_name)
    {
        if (class_exists($class_name)) return;

        if (false === strpos($class_name, self::PREFIX)) return;

        $class_name = str_replace(self::PREFIX, '', $class_name);
        $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
        $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';

        if (file_exists($classes_dir . $class_file)) {
            require_once $classes_dir . $class_file;
        }
    }

    /**
     * Template
     *
     * @param string $file_name
     * @param array $data
     */
    public static function template($file_name = '', array $data = array())
    {
        if (!$file_name) return;

        extract($data);

        include self::$dir . 'includes/Templates/' . $file_name;
    }

    /**
     * @return string containing dev api key
     * TODO: Talk with Kevin about how to make this scalable.
     * 
     * @updated 3.0.4
     */
    private function get_dev_api_key()
    {
        // Check for the deprecated api key.
        $use_deprecated = get_option('nf_const_ctc_dev_key_deprecated', 'false');
        // If we got something back...
        if ('false' !== $use_deprecated) {
            // Use it.
            $dev_api_key = $use_deprecated;
        } // Otherwise... (We didn't get anything back.)
        else {
            // Use our current api key.
            $dev_api_key = 'gfhe344qj8ymgxkrcxufa22q';
        }

        return $dev_api_key;
    }

    public function subscribe($member_data = array())
    {
        if (empty($member_data) 
        || !is_array($member_data)
        || !isset($member_data['email'])
        ) return false;

        $subscribe = new Api2Subscribe();

        $subscribe->setApiDevKey($this->get_dev_api_key());
        
        $subscribe->setAccessToken(Ninja_Forms()->get_setting('constant_contact_access_token'));

        $return = $subscribe->handle($member_data);
        
        $this->handledResponse = $subscribe->getHandledResponse();

        // Contact added successfully
        return $return;
    }

    /**
     * Return the response array
     * @return array
     */
    public function getResponse()
    {
        $this->response = $this->handledResponse->toArray();

        return $this->response;
    }

    public function get_lists()
    {
        if (!$this->api()) return array();

        $lists = array();

        if (is_wp_error($this->_api))
            return $lists;

        if (200 != $this->_api['response']['code'])
            return $lists;

        $body = wp_remote_retrieve_body($this->_api);

        $list_data = json_decode($body);

        if ($list_data) {
            foreach ($list_data as $list) {
                $lists[] = array(
                    'label'  => $list->name,
                    'value' => $list->id,
                    'fields' => array(
                        array(
                            'label' => __('First Name', 'ninja-forms-constant-contact'),
                            'value' => 'first_name',
                        ),
                        array(
                            'label' => __('Last Name', 'ninja-forms-constant-contact'),
                            'value' => 'last_name',
                        ),
                        array(
                            'label' => __('Email', 'ninja-forms-constant-contact'),
                            'value' => 'email',
                        ),
                    ),
                );
            }
        }
        return $lists;
    }

    /**
     * @return string `getLists` API call from Constant Contact
     * TODO: Add check for settings.
     */
    public function api()
    {
        if (!$this->_api) {

            $api_url  = 'https://api.constantcontact.com/v2/lists?api_key=' . $this->get_dev_api_key();

            $headers = array('Authorization' => 'Bearer ' . trim(Ninja_Forms()->get_setting('constant_contact_access_token')));

            $this->_api = wp_remote_get($api_url, array('headers' => $headers, 'sslverify' => false));

            return $this->_api;
        }
    }

    /**
     * Load Config File
     *
     * @param $file_name
     * @return array
     */
    public static function config($file_name)
    {
        return include self::$dir . 'includes/Config/' . $file_name . '.php';
    }

    /*
         * Required methods for all extension.
         */

    public function setup_license()
    {
        if (!class_exists('NF_Extension_Updater')) return;

        new NF_Extension_Updater(self::NAME, self::VERSION, self::AUTHOR, __FILE__, self::SLUG);
    }
}

/**
 * The main function responsible for returning The Highlander Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @codeCoverageIgnore
 * 
 * @since 3.0
 * @return {class} Highlander Instance
 */
function NF_ConstantContact()
{
    return NF_ConstantContact::instance();
}

$autoloadFile = dirname(__FILE__).'/vendor/autoload.php';

if(\file_exists($autoloadFile)){
    include_once $autoloadFile;}

NF_ConstantContact();
