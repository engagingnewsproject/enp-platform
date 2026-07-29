<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_SelectList
 */
class NF_Fields_ListSelect extends NF_Abstracts_List
{
    protected $_name = 'listselect';

    protected $_type = 'listselect';

    protected $_nicename = 'Select';

    protected $_section = 'common';

    protected $_icon = 'chevron-down';

    protected $_templates = 'listselect';

    protected $_old_classname = 'list-select';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'Select', 'ninja-forms' );

        add_filter( 'ninja_forms_merge_tag_calc_value_' . $this->_type, array( $this, 'get_calc_value' ), 10, 2 );
    }

    /**
     * Validate submitted value is a configured option.
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

        $submitted = isset( $field['value'] ) ? (string) $field['value'] : '';
        if ( '' === $submitted ) {
            return $errors;
        }

        // Apply render options filters to get dynamically-provided options.
        // This matches the pattern used in includes/Display/Render.php.
        $options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
        $options = apply_filters( 'ninja_forms_render_options', $options, $field );
        $options = apply_filters( 'ninja_forms_render_options_' . $this->_type, $options, $field );

        $allowed = array();
        foreach ( $options as $option ) {
            if ( isset( $option['value'] ) ) {
                $allowed[] = (string) $option['value'];
            }
        }

        if ( ! in_array( $submitted, $allowed, true ) ) {
            $errors['slug'] = 'invalid-option';
            $errors['message'] = esc_html__( 'Invalid selection.', 'ninja-forms' );
        }

        return $errors;
    }

    /**
     * Get calculation value for merge tag.
     *
     * Returns the configured calc value for matched options,
     * or 0 if no match (fail closed for security).
     *
     * @param mixed $value Submitted field value.
     * @param array $field Field settings including options.
     * @return float Calculation value.
     */
    public function get_calc_value( $value, $field )
    {
        if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
            foreach ( $field['options'] as $option ) {
                if ( ! isset( $option['value'], $option['calc'] ) ) {
                    continue;
                }
                if ( (string) $value === (string) $option['value'] && is_numeric( trim( $option['calc'] ) ) ) {
                    return (float) $option['calc'];
                }
            }
        }
        return 0;
    }
}
