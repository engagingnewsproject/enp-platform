<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_ConstantContact_Fields_Optin
 */
class NF_ConstantContact_Fields_OptIn extends NF_Abstracts_FieldOptIn
{
    protected $_name = 'constant-contact-optin';

    protected $_section = 'common';

    protected $_type = 'constant-contact-optin';

    protected $_templates = 'checkbox';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Constant Contact OptIn', 'ninja-forms' );
    }
}
