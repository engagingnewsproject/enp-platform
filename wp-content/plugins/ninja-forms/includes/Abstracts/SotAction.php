<?php 
namespace NinjaForms\Includes\Abstracts;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class 
 */
abstract class SotAction implements InterfacesSotAction
{

    /**
     * @var array
     */
    protected $_tags = array();

    /** @var int */
    public $timing;

    /** @var int */
    public $priority;

    /**
     * @var array
     */
    protected $_settings = array();

    /**
     * @var array
     */
    protected $_settings_all = array( 'label', 'active' );

    /**
     * @var array
     */
    protected $_settings_exclude = array();

    /**
     * @var array
     */
    protected $_settings_only = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init',[$this,'abstractInitHook'],5);
    }

    public function abstractInitHook(): void
    {
        $this->_settings_all = apply_filters( 'ninja_forms_actions_settings_all', $this->_settings_all );

        if( ! empty( $this->_settings_only ) ){

            $standardSettingsToLoad = $this->_settings_only;
        } else {

            $standardSettingsToLoad = array_diff( $this->_settings_all, $this->_settings_exclude );
        }

        $this->_settings =array_merge($this->_settings, $this->load_settings( $standardSettingsToLoad ));  
    }

    //-----------------------------------------------------
    // Public Methods
    //-----------------------------------------------------

    /**
     * Save
     */
    /** @inheritDoc */
    public function save( array $action_settings )
    {
        // This section intentionally left blank.
    }

    /**
     * Process
     */
    /** @inheritDoc */
    public abstract function process( array $action_id, int $form_id, array $data ):array;


    /**
     * Get Settings
     *
     * Returns the settings for an action.
     *
     * @return array|mixed
     */
    public function get_settings():array
    {
        return $this->_settings;
    }

    /**
     * Sort Actions
     *
     * A static method for sorting two actions by timing, then priority.
     *
     * @param $a
     * @param $b
     * @return int
     */
    public static function sort_actions( $a, $b )
    {
        if( ! isset( \Ninja_Forms()->actions[ $a->get_setting( 'type' ) ] ) ) return 1;
        if( ! isset( \Ninja_Forms()->actions[ $b->get_setting( 'type' ) ] ) ) return 1;

        $aTiming   = \Ninja_Forms()->actions[ $a->get_setting( 'type' ) ]->get_timing();
        $aPriority = \Ninja_Forms()->actions[ $a->get_setting( 'type' ) ]->get_priority();

        $bTiming   = \Ninja_Forms()->actions[ $b->get_setting( 'type' ) ]->get_timing();
        $bPriority = \Ninja_Forms()->actions[ $b->get_setting( 'type' ) ]->get_priority();

        // Compare Priority if Timing is the same
        if( $aTiming == $bTiming)
            return $aPriority > $bPriority ? 1 : -1;

        // Compare Timing
        return $aTiming < $bTiming ? 1 : -1;
    }

    protected function load_settings( $only_settings = array() )
    {
        $settings = array();

        // Loads a settings array from the ActionSettings configuration file.
        $all_settings = \Ninja_Forms::config( 'ActionSettings' );

        foreach( $only_settings as $setting ){

            if( isset( $all_settings[ $setting ]) ){

                $settings[ $setting ] = $all_settings[ $setting ];
            }
        }

        return $settings;
    }

} // END CLASS NF_Abstracts_Action
