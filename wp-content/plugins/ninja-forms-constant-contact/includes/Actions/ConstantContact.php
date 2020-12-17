<?php if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'NF_Abstracts_ActionNewsletter' ) ) return;

/**
 * Class NF_ConstantContact_Actions_ConstantContact
 */
final class NF_ConstantContact_Actions_ConstantContact extends NF_Abstracts_ActionNewsletter
{
    /**
     * @var string
     */
    protected $_name  = 'constant-contact';

    /**
     * @var array
     */
    protected $_tags = array();

    /**
     * @var string
     */
    protected $_timing = 'normal';

    /**
     * @var int
     */
    protected $_priority = '10';

    /**
     * Constructor
     */
    public function __construct()
{
    parent::__construct();

    $this->_nicename = __( 'Constant Contact', 'ninja-forms-constant-contact' );

    unset( $this->_settings[ 'constant-conatactnewsletter_list_groups' ] );

}

    /*
    * PUBLIC METHODS
    */

    public function save( $action_settings )
    {

    }

    public function process( $action_settings, $form_id, $data )
    {
        if( ! $this->is_opt_in( $data ) ) return $data;

        $member_data = array(
            'email' => $action_settings[ 'email' ],
            "fields" => array(
                "first_name" => $action_settings[ 'first_name' ],
                "last_name" => $action_settings[ 'last_name' ],
            ),
            'lists' => $action_settings[ 'newsletter_list' ],
        );

        $response = NF_ConstantContact()->subscribe( $member_data );
        
        $data[ 'actions' ][ 'constant-contact' ][ 'response' ] = $response;
        $data[ 'actions' ][ 'constant-contact' ][ 'member_data' ] = $member_data;
        
        $dataHandler = new NF_ConstantContact_Admin_HandleProcessData($data, $this->_name);
                
        $responseData = NF_ConstantContact()->getResponse();

        $dataHandler->appendResponseData($responseData);
        return $dataHandler->toArray();
    }

    protected function is_opt_in( $data )
    {
        $opt_in = TRUE;
        foreach( $data[ 'fields' ]as $field ){

            if( 'constant-contact-optin' != $field[ 'type' ] ) continue;

            if( ! $field[ 'value' ] ) $opt_in = FALSE;
        }
        return $opt_in;
    }

    public function get_lists()
    {
        return NF_ConstantContact()->get_lists();
    }
}
