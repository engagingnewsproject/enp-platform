<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_UserInfo
 *
 * Shared base for the First Name, Last Name and Email fields.
 *
 * These fields once offered a "User Meta (if logged in)" default type, driven by
 * a `default_type` sub-setting on Default Value and a `filter_default_value`
 * callback per field. The `default_type` sub-setting was removed from the field
 * settings configuration before 3.0 shipped, leaving the callbacks unreachable
 * from the builder but still hooked. Every caller of
 * `ninja_forms_render_default_value` passes the field type as a string, so a
 * field whose stored meta still carried `default_type = user-meta` fataled the
 * request. The dead path is gone; Default Value is a plain text box, and the
 * `{wp:user_first_name}` family of merge tags is the supported way to pre-fill
 * a field from the logged-in user. See issue #8233.
 */
abstract class NF_Abstracts_UserInfo extends NF_Fields_Textbox
{
    protected $_name = 'input';

    protected $_section = 'userinfo';

    protected $_type = 'textbox';

    public static $_base_template = 'textbox';

    public function __construct()
    {
        parent::__construct();

        $this->_settings = $this->load_settings(
            array( 'key',
	            'label',
	            'label_pos',
	            'required',
	            'default',
	            'placeholder',
	            'classes',
	            'admin_label',
	            'help',
	            'description',
	            'custom_name_attribute',
	            'personally_identifiable',
	            'disable_browser_autocomplete' )
        );
    }
}
