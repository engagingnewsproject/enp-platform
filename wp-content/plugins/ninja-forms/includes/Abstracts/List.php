<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_List
 */
abstract class NF_Abstracts_List extends NF_Abstracts_Field
{
    protected $_name = '';

    protected $_section = 'common';

    protected $_type = 'list';

    protected $_test_value = FALSE;

    protected $_settings_all_fields = array(
        'key', 'label', 'label_pos', 'required', 'options', 'classes', 'admin_label', 'help', 'description'
    );

    public static $_base_template = 'list';

    public function __construct()
    {
        parent::__construct();

        add_filter( 'ninja_forms_custom_columns', array( $this, 'custom_columns' ), 10, 2 );

        add_filter( 'ninja_forms_render_options', array( $this, 'query_string_default' ), 10, 2 );
    }

    public function get_parent_type()
    {
        return 'list';
    }

    /**
     * Validate submitted value is a configured option.
     *
     * Provides server-side option validation for all list field types.
     * Handles both single-value and multi-value (array) submissions.
     *
     * @param array $field Field settings including submitted value.
     * @param array $data  Form data.
     * @return array Validation errors.
     */
    public function validate( $field, $data )
    {
        $errors = parent::validate( $field, $data );
        if ( ! empty( $errors ) ) {
            return $errors;
        }

        // Determine options source: image_options for listimage, options for others.
        $options_key = ( 'listimage' === $this->_type ) ? 'image_options' : 'options';
        $options = isset( $field[ $options_key ] ) && is_array( $field[ $options_key ] )
            ? $field[ $options_key ]
            : array();

        // Apply render options filters to get dynamically-provided options.
        $options = apply_filters( 'ninja_forms_render_options', $options, $field );
        $options = apply_filters( 'ninja_forms_render_options_' . $this->_type, $options, $field );

        // Build allowed values list.
        $allowed = array();
        foreach ( $options as $option ) {
            if ( isset( $option['value'] ) ) {
                $allowed[] = (string) $option['value'];
            }
        }

        // Normalize submitted value to array for uniform handling.
        $submitted = isset( $field['value'] ) ? $field['value'] : '';
        if ( ! is_array( $submitted ) ) {
            $submitted = array( (string) htmlspecialchars_decode( $submitted ) );
        } else {
            $submitted = array_map( function( $v ) {
                return (string) htmlspecialchars_decode( $v );
            }, $submitted );
        }

        // Empty submission is valid (required check handled by parent).
        if ( 1 === count( $submitted ) && '' === $submitted[0] ) {
            return $errors;
        }

        // Validate each submitted value exists in allowed options.
        foreach ( $submitted as $value ) {
            if ( '' === $value ) {
                continue;
            }
            if ( ! in_array( $value, $allowed, true ) ) {
                $errors['slug'] = 'invalid-option';
                $errors['message'] = esc_html__( 'Invalid selection.', 'ninja-forms' );
                return $errors;
            }
        }

        return $errors;
    }

    public function admin_form_element( $id, $value )
    {
        $form_id = get_post_meta( absint( $_GET[ 'post' ] ), '_form_id', true );

        $field = Ninja_Forms()->form( $form_id )->get_field( $id );

        $settings = $field->get_settings();
        $settings[ 'options' ] = apply_filters( 'ninja_forms_render_options', $settings[ 'options' ], $settings );
        $settings[ 'options' ] = apply_filters( 'ninja_forms_render_options_' . $field->get_setting( 'type' ), $settings[ 'options' ], $settings );

        $options = '<option>--</option>';
        if ( is_array( $settings[ 'options' ] ) ) {
            foreach( $settings[ 'options' ] as $option ){
                $selected = ( $value == $option[ 'value' ] ) ? "selected" : '';
                $options .= "<option value='" . esc_attr( $option[ 'value' ] ) . "' $selected>" . esc_html( $option[ 'label' ] ) . "</option>";
            }            
        }

        return "<select class='widefat' name='fields[" . esc_attr( $id ) . "]' id=''>$options</select>";
    }

    /*
     * Appropriate output for a column cell in submissions list.
     */
    public function custom_columns( $value, $field )
    {
        if( $this->_name != $field->get_setting( 'type' ) ) return $value;
        
        //Consider &amp; to be the same as the & values in database in a selectbox saved value:
        if( ! is_array( $value ) ) $value = array( htmlspecialchars_decode($value) );

        $settings = $field->get_settings();
        $options = $field->get_setting( 'options' );
        $options = apply_filters( 'ninja_forms_render_options', $options, $settings );
        $options = apply_filters( 'ninja_forms_render_options_' . $field->get_setting( 'type' ), $options, $settings );

        $output = '';
        if( ! empty( $options ) ) {
            foreach ($options as $option) {

                if ( ! in_array( $option[ 'value' ], $value ) ) continue;

                $output .= esc_html( $option[ 'label' ] ) . "<br />";
            }
        }

        return $output;
    }

    public function query_string_default( $options, $settings )
    {
        if( ! isset( $settings[ 'key' ] ) ) return $options;

        $field_key = $settings[ 'key' ];

        if( ! isset( $_GET[ $field_key ] ) ) return $options;

        foreach( $options as $key => $option ){

            if( ! isset( $option[ 'value' ] ) ) continue;

            if( $option[ 'value' ] != $_GET[ $field_key ] ) continue;

            $options[ $key ][ 'selected' ] = 1;
        }

        return $options;
    }
}
