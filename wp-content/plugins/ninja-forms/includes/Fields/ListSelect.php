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
