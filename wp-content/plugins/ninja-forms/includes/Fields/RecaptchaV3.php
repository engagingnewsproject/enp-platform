<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_RecaptchaV3
 *
 * This is a dynamically injected field based on if a form has the reCAPTCHA action.
 * It doesn't appear in the builder.
 */
class NF_Fields_RecaptchaV3 extends NF_Abstracts_Field
{
    protected $_name = 'recaptcha_v3';

    protected $_type = 'recaptcha_v3';

    protected $_templates = 'recaptcha-v3';

    protected $_show_in_builder = false;
}
