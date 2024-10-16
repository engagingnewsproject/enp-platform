<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Action_Custom
 */
final class NF_Actions_Custom extends NF_Abstracts_Action
{
    /**
     * @var string
     */
    protected $_name  = 'custom';

    /**
     * @var array
     */
    protected $_tags = array();

    /**
     * @var string
     */
    protected $_documentation_url = 'https://ninjaforms.com/docs/wp-hook/';

    /**
     * @var string
     */
    protected $_timing = 'normal';

    /**
     * @var int
     */
    protected $_priority = 10;

    /**
     * @var string
     */
    protected $_group = 'core';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'WP Hook', 'ninja-forms' );

        $settings = Ninja_Forms::config( 'ActionCustomSettings' );

        $this->_settings = array_merge( $this->_settings, $settings );
    }

    /*
    * PUBLIC METHODS
    */

    public function save( $action_settings )
    {

    }

    public function process( $action_settings, $form_id, $data )
    {
        if( isset( $action_settings[ 'tag' ] ) ) {
            ob_start(); // Use the Output Buffer to suppress output

            do_action($action_settings[ 'tag' ], $data);

            ob_end_clean();
        }

        return $data;
    }
}
