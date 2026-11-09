<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_Field
 */
abstract class NF_Abstracts_Field extends NF_Abstracts_Element
{
    /**
    * @var string
    */
    protected $_name  = '';

    /**
     * @var string
     */
    protected $_nicename = '';

    /**
    * @var string
    */
    protected $_section = '';

    /**
    * @var string
    */
    protected $_icon = 'square-o';

    /**
     * @var array
     */
    protected $_aliases = array();

    /**
     * @var array
     */
    protected $_settings = array();

    /**
     * @var array
     */
    protected $_settings_all_fields = array();

    /**
     * @var array
     */
    protected $_settings_exclude = array();

    /**
     * @var array
     */
    protected $_settings_only = array();

    /**
     * @var array
     */
    protected $_use_merge_tags = array( 'user', 'post', 'system', 'fields' );

    /**
     * @var array
     */
    protected $_use_merge_tags_include = array();

    /**
     * @var array
     */
    protected $_use_merge_tags_exclude = array();

    /**
     * @var string
     */
    protected $_test_value = 'test';

    /**
     * @var string
     */
    protected $_attr = '';

    /**
     * @var string
     */
    protected $_type = '';

    /**
     * @var string
     */
    protected $_parent_type = '';

    /**
     * @var string
     */
    public static $_base_template = 'input';

    /**
     * @var array
     */
    protected $_templates = array();

    /**
     * @var string
     */
    protected $_wrap_template = 'wrap';

    /**
     * @var array
     */
    protected $_old_classname = '';

	/**
	 * @var bool
	 */
    protected $_show_in_builder = true;

    //-----------------------------------------------------
    // Public Methods
    //-----------------------------------------------------

    /**
     * Constructor
     */
    public function __construct()
    {
        if( ! empty( $this->_settings_only ) ){

            $this->_settings = array_merge( $this->_settings, $this->_settings_only );
        } else {

            $this->_settings = array_merge( $this->_settings_all_fields, $this->_settings );
            $this->_settings = array_diff( $this->_settings, $this->_settings_exclude );
        }

        $this->_settings = $this->load_settings( $this->_settings );

        $this->_test_value = apply_filters( 'ninja_forms_field_' . $this->_name . '_test_value', $this->_test_value );

        add_filter( 'ninja_forms_localize_field_settings_' . $this->_type, array( $this, 'localize_settings' ), 10, 2 );
    }

    /**
     * Validate
     *
     * @param $field
     * @param $data
     * @return array $errors
     */
    public function validate( $field, $data )
    {
        $errors = array();
        // Required check.

        if( is_array( $field[ 'value' ] ) && "repeater" !== $field[ 'type' ] ){
            $field[ 'value' ] = implode( '', $field[ 'value' ] );
        }

        if( isset( $field['required'] ) && 1 == intval( $field['required'] ) ) {
            $val = trim( $field['value'] );
            if( empty( $val ) && '0' !== $val ){
                $errors['slug'] = 'required-error';
                $errors['message'] = $this->get_required_field_message( $data );
            }
        }
        return $errors;
    }

    /**
     * Required Field Message
     *
     * Resolves the message shown when a required field is submitted empty.
     *
     * The browser-side check renders the form's `validateRequiredField`
     * setting, so this server-side check resolves the same setting instead of
     * carrying wording of its own. One required-field condition therefore
     * produces one message, whichever check detects it. See issue #8182.
     *
     * The wording is read from the saved form, never from the submitted
     * payload, so a submitted `settings` array cannot dictate the message.
     *
     * The default below must stay identical to the `validateRequiredField`
     * entry in includes/Config/i18nFrontEnd.php: they are deliberately one
     * translatable string, and splitting them is the defect this fixes.
     *
     * @param array $data The submitted form data.
     * @return string
     */
    protected function get_required_field_message( $data )
    {
        $message = esc_html__( 'This is a required field.', 'ninja-forms' );

        if( ! is_array( $data ) ) return $message;

        $form_id = 0;
        if( isset( $data[ 'id' ] ) ){
            $form_id = $data[ 'id' ];
        } elseif( isset( $data[ 'form_id' ] ) ){
            $form_id = $data[ 'form_id' ];
        }

        // Render instances arrive as `<form id>_<instance id>`.
        if( is_string( $form_id ) && FALSE !== strpos( $form_id, '_' ) ){
            $form_id = current( explode( '_', $form_id ) );
        }

        $form_id = intval( $form_id );
        if( ! $form_id ) return $message;

        $form = Ninja_Forms()->form( $form_id )->get();
        if( ! $form ) return $message;

        $custom = $form->get_setting( 'validateRequiredField' );
        if( is_string( $custom ) && '' !== trim( $custom ) ){
            $message = esc_html( $custom );
        }

        return $message;
    }

    public function process( $field, $data )
    {
        return $data;
    }

    /**
     * Admin Form Element
     *
     * Returns the output for editing fields in a submission.
     *
     * @param $id
     * @param $value
     * @return string
     */
    public function admin_form_element( $id, $value )
    {
        return '<input class="widefat" name="fields[' . absint( $id ) . ']" value="' . esc_attr( $value ) . '" type="text" />';
    }

    public function get_name()
    {
        return $this->_name;
    }

    public function get_nicename()
    {
        return $this->_nicename;
    }

    public function get_section()
    {
        return $this->_section;
    }

    public function get_icon()
    {
        return $this->_icon;
    }

    public function get_aliases()
    {
        return $this->_aliases;
    }

    public function get_type()
    {
        return $this->_type;
    }

    public function get_parent_type()
    {
        if( $this->_parent_type ){
            return $this->_parent_type;
        }

        // If a type is not set, return 'textbox'
        return ( get_parent_class(self::class) && isset ( parent::$_type ) ) ? parent::$_type : 'textbox';
    }

    public function get_settings()
    {
        return $this->_settings;
    }

    public function use_merge_tags()
    {
        $use_merge_tags = array_merge( $this->_use_merge_tags, $this->_use_merge_tags_include );
        $use_merge_tags = array_diff( $use_merge_tags, $this->_use_merge_tags_exclude );

        return $use_merge_tags;
    }

    public function get_test_value()
    {
        return $this->_test_value;
    }

    public function get_templates()
    {
        $templates = (array) $this->_templates;

        // Create a reflection for examining the parent
        $reflection = new ReflectionClass( $this );
        $parent_class = $reflection->getParentClass();

        if ( $parent_class->isAbstract() ) {

            $parent_class_name = $parent_class->getName();
            $parent_templates = call_user_func( array( $parent_class_name, 'get_base_template' ) ); // Parent Class' Static Property
            return array_merge( $templates, (array) $parent_templates );
        }

        $parent_class_name = strtolower( str_replace('NF_Fields_', '', $parent_class->getName() ) );

        if( ! isset( Ninja_Forms()->fields[ $parent_class_name ] ) ) return $templates;

        $parent = Ninja_Forms()->fields[ $parent_class_name ];
        return array_merge($templates, $parent->get_templates());

    }

    public function get_wrap_template()
    {
        return $this->_wrap_template;
    }

    public function get_old_classname()
    {
        return $this->_old_classname;
    }

    public function show_in_builder() {
	    return $this->_show_in_builder;
    }

    protected function load_settings( $only_settings = array() )
    {
        $settings = array();

        // Loads a settings array from the FieldSettings configuration file.
        $all_settings = Ninja_Forms::config( 'FieldSettings' );

        foreach( $only_settings as $setting ){

            if( isset( $all_settings[ $setting ]) ){

                $settings[ $setting ] = $all_settings[ $setting ];
            }
        }

        return $settings = apply_filters( 'ninja_forms_field_load_settings', $settings, $this->_name, $this->get_parent_type() );
    }

    public static function get_base_template()
    {
        return self::$_base_template;
    }

    public static function sort_by_order( $a, $b )
    {
        return $a->get_setting( 'order' ) - $b->get_setting( 'order' );
    }

    public function localize_settings( $settings, $form_id ) {
        return $settings;
    }

}
