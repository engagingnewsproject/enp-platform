<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_FirstName
 */
class NF_Fields_FirstName extends NF_Abstracts_UserInfo
{
    protected $_name = 'firstname';
    protected $_type = 'firstname';

    protected $_nicename = 'First Name';

    protected $_section = 'userinfo';

    protected $_icon = 'user';

    protected $_templates = 'firstname';

    protected $_test_value = 'John';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'First Name', 'ninja-forms' );

	    $this->_settings[ 'custom_name_attribute' ][ 'value' ] = 'fname';
	    $this->_settings[ 'personally_identifiable' ][ 'value' ] = '1';
    }
}
