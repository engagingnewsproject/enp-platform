<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_FirstName
 */
class NF_Fields_LastName extends NF_Abstracts_UserInfo
{
    protected $_name = 'lastname';
    protected $_type = 'lastname';

    protected $_nicename = 'Last Name';

    protected $_section = 'userinfo';

    protected $_icon = 'user';

    protected $_templates = 'lastname';

    protected $_test_value = 'Doe';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'Last Name', 'ninja-forms' );

	    $this->_settings[ 'custom_name_attribute' ][ 'value' ] = 'lname';
	    $this->_settings[ 'personally_identifiable' ][ 'value' ] = '1';
    }
}
