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
    }

    public function display_filter( $field, $form ) {
        if ( empty ( $field[ 'fields' ] ) ) return $field;

        foreach( $field[ 'fields' ] as &$settings ) {

            $field_type = $settings[ 'type' ];

            if( ! is_string( $field_type ) ) continue;

            if( ! isset( Ninja_Forms()->fields[ $field_type ] ) ) {
                $unknown_field = NF_Fields_Unknown::create( $field );
                $field = array(
                    'settings' => $unknown_field->get_settings(),
                    'id' => $unknown_field->get_id()
                );
                $field_type = $settings[ 'type' ];
            }

            $field = apply_filters('ninja_forms_localize_fields', $field);
            $field = apply_filters('ninja_forms_localize_field_' . $field_type, $field);

            $field_class = Ninja_Forms()->fields[$field_type];

            if (NF_Display_Render::$use_test_values) {
                $field[ 'value' ] = $field_class->get_test_value();
            }

             // Hide the label on invisible reCAPTCHA fields
             if ( 'recaptcha' === $field[ 'type' ] && 'invisible' === $field[ 'size' ] ) {
                $field[ 'settings' ][ 'label_pos' ] = 'hidden';
            }

            // Copy field ID into the field settings array for use in localized data.
            $field[ 'settings' ] = [];
            $field[ 'settings' ][ 'id' ] = $field[ 'id' ];

            /*
             * TODO: For backwards compatibility, run the original action, get contents from the output buffer, and return the contents through the filter. Also display a PHP Notice for a deprecate filter.
             */

            $display_before = apply_filters( 'ninja_forms_display_before_field_type_' . $field[ 'type' ], '' );
            $display_before = apply_filters( 'ninja_forms_display_before_field_key_' . $field[ 'key' ], $display_before );
            $field[ 'beforeField' ] = $display_before;

            $display_after = apply_filters( 'ninja_forms_display_after_field_type_' . $field[ 'type' ], '' );
            $display_after = apply_filters( 'ninja_forms_display_after_field_key_' . $field[ 'key' ], $display_after );
            $field[ 'afterField' ] = $display_after;

            $templates = $field_class->get_templates();

            if (!array($templates)) {
                $templates = array($templates);
            }

            foreach ($templates as $template) {
                NF_Display_Render::load_template('fields-' . $template);
            }

            $settings['value'] = '';
            foreach ($settings as $key => $setting) {
                if (is_numeric($setting) && 'custom_mask' != $key )
                    $settings[$key] =
                    floatval($setting);
            }

            if( ! isset( $settings[ 'label_pos' ] ) || "default" === $settings[ 'label_pos' ] ){  
                $settings[ 'label_pos' ] =  is_object($form) ? $form->get_setting( 'default_label_pos' ) : $settings[ 'label_pos' ];
            }

            $settings[ 'parentType' ] = $field_class->get_parent_type();

            if( 'list' == $settings[ 'parentType' ] && isset( $settings[ 'options' ] ) && is_array( $settings[ 'options' ] ) ){
                $settings[ 'options' ] = apply_filters( 'ninja_forms_render_options', $settings[ 'options' ], $settings );
                $settings[ 'options' ] = apply_filters( 'ninja_forms_render_options_' . $field_type, $settings[ 'options' ], $settings );
            }

            $default_value = ( isset( $settings[ 'default' ] ) ) ? $settings[ 'default' ] : null;
            $default_value = apply_filters('ninja_forms_render_default_value', $default_value, $field_type, $settings);
            if ( $default_value ) {

                $default_value = preg_replace( '/{[^}]}/', '', $default_value );

                if ($default_value) {
                    $settings['value'] = $default_value;

                    if( ! is_array( $default_value ) ) {
                        ob_start();
                        do_shortcode( $settings['value'] );
                        $ob = ob_get_clean();

                        if( ! $ob ) {
                            $settings['value'] = do_shortcode( $settings['value'] );
                        }
                    }
                }
            }

            $settings['element_templates'] = $templates;
            $settings['old_classname'] = $field_class->get_old_classname();
            $settings['wrap_template'] = $field_class->get_wrap_template();

            $settings = apply_filters( 'ninja_forms_localize_field_settings_' . $field_type, $settings, $form );
        }

        return $field;
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
