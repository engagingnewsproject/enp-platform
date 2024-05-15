<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Repeater
 */
class NF_Fields_Repeater extends NF_Abstracts_Field
{
    protected $_name = 'repeater';

    protected $_section = 'layout';

    protected $_icon = 'clone';

    protected $_aliases = array( 'repeater' );

    protected $_type = 'repeater';

    protected $_templates = 'repeater';
    
    protected $_wrap_template = 'wrap-no-label';

    protected $_settings_only = array( 'label', 'classes', 'description', 'help_text' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'Repeatable Fieldset', 'ninja-forms' );

        add_filter( 'ninja_forms_localize_field_settings_repeater', array( $this, 'display_filter' ), 10, 2 );

        add_filter( 'ninja_forms_custom_columns', array( $this, 'custom_columns' ), 10, 2 );

        $this->_settings[ 'button_text'] = array(
            'name'      => 'button_text',
            'type'      => 'textbox',
            'label'     => esc_html__( 'Button text', 'ninja-forms' ),
            'width'     => 'one-half',
            'group'     => 'primary',
            'value'     => esc_html__("Add Fieldset"),
            'help'      => esc_html__( 'Text used for the button that adds a fieldset.', 'ninja-forms' ),
        );
    }

    public function display_filter( $fieldset, $form ) {
        if ( empty ( $fieldset[ 'fields' ] ) ) return $fieldset;

        foreach( $fieldset[ 'fields' ] as $index => &$field ) {

            $field_type = $field[ 'type' ];

            if( ! is_string( $field_type ) ) continue;

            if( ! isset( Ninja_Forms()->fields[ $field_type ] ) ) {
                $unknown_field = NF_Fields_Unknown::create( $field );
                $field = array(
                    'settings' => $unknown_field->get_settings(),
                    'id' => $unknown_field->get_id()
                );
                $field_type = $field[ 'type' ];
            }

            // Convert each field into the format that the filter expects before running localization.
            $temp_field = [
                'id' => $fieldset['id'],
                'settings' => $field
            ];
            $temp_field = apply_filters('ninja_forms_localize_fields', $temp_field);
            $temp_field = apply_filters('ninja_forms_localize_field_' . $field_type, $temp_field);
            $field = $temp_field['settings'];

            $field_class = Ninja_Forms()->fields[$field_type];

            if (NF_Display_Render::$use_test_values) {
                $field[ 'value' ] = $field_class->get_test_value();
            }

            // Disallow recaptcha fields in repeater.
            if( 'recaptcha' === $field_type ) {
                unset($fieldset['fields'][$index]);
                continue 1;
            }

            /*
             * TODO: For backwards compatibility, run the original action, get contents from the output buffer, and return the contents through the filter. Also display a PHP Notice for a deprecate filter.
             */

            $field[ 'beforeField' ] = $this->before_field( $field );

            $field[ 'afterField' ] = $this->after_field( $field );

            $templates = $field_class->get_templates();

            if (!array($templates)) {
                $templates = array($templates);
            }

            foreach ($templates as $template) {
                NF_Display_Render::load_template('fields-' . $template);
            }

            $field['value'] = '';
            foreach ($field as $key => $setting) {
                if (is_numeric($setting) && 'custom_mask' != $key  && 'id' != $key)
                    $field[$key] =
                    floatval($setting);
            }

            if( ! isset( $field[ 'label_pos' ] ) || "default" === $field[ 'label_pos' ] ){  
                $field[ 'label_pos' ] =  is_object($form) ? $form->get_setting( 'default_label_pos' ) : $field[ 'label_pos' ];
            }

            $field[ 'parentType' ] = $field_class->get_parent_type();

            if( 'list' == $field[ 'parentType' ] && isset( $field[ 'options' ] ) && is_array( $field[ 'options' ] ) ){
                $field[ 'options' ] = apply_filters( 'ninja_forms_render_options', $field[ 'options' ], $field );
                $field[ 'options' ] = apply_filters( 'ninja_forms_render_options_' . $field_type, $field[ 'options' ], $field );
            }

            $default_value = ( isset( $field[ 'default' ] ) ) ? $field[ 'default' ] : null;
            $default_value = apply_filters('ninja_forms_render_default_value', $default_value, $field_type, $field);
            if ( $default_value ) {

                $default_value = preg_replace( '/{[^}]}/', '', $default_value );

                if ($default_value) {
                    $field['value'] = $default_value;

                    if( ! is_array( $default_value ) ) {
                        ob_start();
                        do_shortcode( $field['value'] );
                        $ob = ob_get_clean();

                        if( ! $ob ) {
                            $field['value'] = do_shortcode( $field['value'] );
                        }
                    }
                }
            }

            $field['element_templates'] = $templates;
            $field['old_classname'] = $field_class->get_old_classname();
            $field['wrap_template'] = $field_class->get_wrap_template();

            $field = apply_filters( 'ninja_forms_localize_field_settings_' . $field_type, $field, $form );
        }

        $fieldset[ 'beforeField' ] = $this->before_field( $fieldset );

        $fieldset[ 'afterField' ] = $this->after_field( $fieldset );

        return $fieldset;
    }

    public function admin_form_element( $id, $value )
    {
        $fieldSettings = Ninja_Forms()->form()->field($id)->get_settings();
        $extractedSubmissionData = Ninja_Forms()->fieldsetRepeater->extractSubmissions($id,$value,$fieldSettings);
        
        $return ='';

        foreach($extractedSubmissionData as $index=> $indexedSubmission){
            $return .= '<br /><span style="font-weight:bold;">Repeated Fieldset #'.$index.'</span><br />';
            foreach($indexedSubmission as $submissionValueArray){
                $fieldsetFieldSubmissionValue = $submissionValueArray['value'];

                if(is_array($fieldsetFieldSubmissionValue)){
                    $fieldsetFieldSubmissionValue=implode(', ',$fieldsetFieldSubmissionValue);
                }
                $return.='<span>'.$submissionValueArray['label'].' </span><input class="widefat" name="fields[' . absint( $id ) . ']" disabled = "disabled" value="' . esc_attr( $fieldsetFieldSubmissionValue ) . '" type="text" />';
            }

        }
        return $return;
        
    }

    /**
     * Apply before field filters.
     * @param Array $field
     * @return Array
     */
    private function before_field( $field )
    {
        $response = apply_filters( 'ninja_forms_display_before_field_type_' . $field[ 'type' ], '' );
        $response = apply_filters( 'ninja_forms_display_before_field_key_' . $field[ 'key' ], $response );
        return $response;
    }

    /**
     * Apply after field filters.
     * @param Array $field
     * @return Array
     */
    private function after_field( $field )
    {
        $response = apply_filters( 'ninja_forms_display_after_field_type_' . $field[ 'type' ], '' );
        $response = apply_filters( 'ninja_forms_display_after_field_key_' . $field[ 'key' ], $response );
        return $response;
    }


    /**
     * Custom Columns
     * Creates what is displayed in the columns on the submissions page.
     * @since 3.4.34
     *  nf_subs_export_pre_value
     * @param $value checkbox value
     * @param $field field model.
     * @return $value string|void
     */
    public function custom_columns( $value, $field )
     {
        // If the field type is equal to Repeater...
        if( 'repeater' == $field->get_setting( 'type' ) ) {
            // Get Child Fields
            $fields = $field->get_setting( 'fields' );
            foreach($fields as $child_field ){
                // If the field type is equal to checkbox...
                if($child_field['type'] === "checkbox"){
                    //Get set readable values
                    $checked = !empty($child_field['checked_value']) ? $child_field['checked_value'] : esc_html__( 'Checked', 'ninja-forms');
                    $unchecked = !empty($child_field['unchecked_value']) ? $child_field['unchecked_value'] : esc_html__( 'Unchecked', 'ninja-forms');
                    // Replace occurences
                    $value = str_replace("1", $checked, $value);
                    $value = str_replace("0", $unchecked, $value);
                }
            }
        }
            
        return $value;
    }

}
