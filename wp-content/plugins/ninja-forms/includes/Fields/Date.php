<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Date
 */
class NF_Fields_Date extends NF_Fields_Textbox
{
    protected $_name = 'date';

    protected $_nicename = 'Date';

    protected $_section = 'common';

    protected $_icon = 'calendar';

    protected $_type = 'date';

    protected $_templates = 'date';

    protected $_test_value = '12/12/2022';

    protected $_settings = array( 'date_mode', 'date_default', 'date_format', 'year_range', 'time_settings' );

    protected $_settings_exclude = array( 'default', 'input_limit_set', 'disable_input' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'Date/Time', 'ninja-forms' );

        add_filter( 'ninja_forms_localize_field_date', [ $this,'localizeField'], 10, 2);
        add_filter( 'ninja_forms_localize_field_date_preview', [ $this,'localizeField'], 10, 2);
        add_filter( 'ninja_forms_custom_columns', [ $this, 'custom_columns' ], 10, 2 );
        add_filter( 'ninja_forms_subs_export_field_value_' . $this->_name, array( $this, 'filter_csv_value' ), 10, 2 );
        add_filter( 'ninja_forms_merge_tag_value_' . $this->_name, array( $this, 'filter_merge_tag_value' ), 10, 2 );
    }

    public function process( $field, $data )
    {
        return $data;
    }

    private function get_format( $format )
    {
        $lookup = array(
            'MM/DD/YYYY' => esc_html__( 'm/d/Y', 'ninja-forms' ),
            'MM-DD-YYYY' => esc_html__( 'm-d-Y', 'ninja-forms' ),
            'MM.DD.YYYY' => esc_html__( 'm.d.Y', 'ninja-forms' ),
            'DD/MM/YYYY' => esc_html__( 'd/m/Y', 'ninja-forms' ),
            'DD-MM-YYYY' => esc_html__( 'd-m-Y', 'ninja-forms' ),
            'DD.MM.YYYY' => esc_html__( 'd.m.Y', 'ninja-forms' ),
            'YYYY-MM-DD' => esc_html__( 'Y-m-d', 'ninja-forms' ),
            'YYYY/MM/DD' => esc_html__( 'Y/m/d', 'ninja-forms' ),
            'YYYY.MM.DD' => esc_html__( 'Y.m.d', 'ninja-forms' ),
            'dddd, MMMM D YYYY' => esc_html__( 'l, F d Y', 'ninja-forms' ),
        );

        return ( isset( $lookup[ $format ] ) ) ? $lookup[ $format ] : $format;
    }

    /**
     * 
     * 
     * @since  3.0
     * @param  array  $field  Array of field settings
     * @return void
     */
    public function localizeField( $field )
    {
        /**
         * Time-specific settings below.
         * If this is a "date_only" field, we can bail early.
         */
        if ( isset ( $field[ 'settings' ][ 'date_mode' ] ) && 'date_only' == $field[ 'settings' ][ 'date_mode' ] ) {
            return $field;
        }

        /**
         * Hours Select Options
         */
        $hours_options = $this->get_hours_options( false, $field );

        $field[ 'settings' ][ 'hours_options' ] = $hours_options;

        /**
         * Minutes Select Options
         */
        $minutes_options = $this->get_minutes_options( false, $field );

        $field[ 'settings' ][ 'minutes_options' ] = $minutes_options;

        return $field;
    }

    public function custom_columns( $field_value, $field )
    {
        if( $this->_name != $field->get_setting( 'type' ) ) return $field_value;
        return $this->stringify_value( $field_value, $field );
    }

    public function filter_csv_value( $field_value, $field ) {
        $field_value = $this->stringify_value( $field_value, $field );
        return parent::filter_csv_value( $field_value, $field );
    }

    public function admin_form_element( $id, $value )
    {
        $form_id = get_post_meta( absint( $_GET[ 'post' ] ), '_form_id', true );

        $field = Ninja_Forms()->form( $form_id )->get_field( $id );

        // If the value is an array, output an appropriate edit element.
        if ( ! is_array( $value ) ) return '<input class="widefat" name="fields[' . $id . '][date]" value="' . $value . '" type="text">';

        $edit_values = '';

        // Get our date and time, the combine them into a string.
        $date = isset ( $value[ 'date' ] ) ? $value[ 'date' ] : '';
        $hour = isset ( $value[ 'hour' ] ) ? $value[ 'hour' ] : '';
        $minute = isset ( $value[ 'minute' ] ) ? $value[ 'minute' ] : '';
        $ampm = isset ( $value[ 'ampm' ] ) ? $value[ 'ampm' ] : '';
        $time = '';

        $hours_options = $this->get_hours_options( $hour, $field );
        $minutes_options = $this->get_minutes_options( $minute, $field );        

        if ( ! empty ( $date ) ) {
            $edit_values = '<input class="" name="fields[' . $id . '][date]" value="' . $date . '" type="text">';
        }

        if ( ! empty ( $hour ) && ! empty ( $minute ) ) {
            $edit_values .= '<select class="" name="fields[' . $id . '][hour]" id="">' . $hours_options . '</select>';
            $edit_values .= ':<select class="" name="fields[' . $id . '][minute]" id="">' . $minutes_options . '</select>';
        
            // Display an edit for am/pm if necessary
            if ( 1 != $field->get_setting( 'hours_24' ) ) {
                $selected_am = ( 'am' == $ampm ) ? 'selected="selected"' : '';
                $selected_pm = ( 'pm' == $ampm ) ? 'selected="selected"' : '';
                $edit_values .= ' <select class="" name="fields[' . $id . '][ampm]" id="">
                    <option value="am" ' . $selected_am . '>AM</option>
                    <option value="pm" ' . $selected_pm . '>PM</option>
                </select>';
            }
        }

        return $edit_values;
    }

    private function stringify_value( $field_value, $field )
    {
        $hours_24 = false;
        $date_mode = '';

        if ( is_object( $field ) ) {
            $hours_24 = 1 == $field->get_setting( 'hours_24' );
            $date_mode = $field->get_setting( 'date_mode' );
        } elseif ( is_array( $field ) && isset ( $field[ 'settings' ] ) ) {
            $hours_24 = isset ( $field[ 'settings' ][ 'hours_24' ] ) && 1 == $field[ 'settings' ][ 'hours_24' ];
            $date_mode = isset ( $field[ 'settings' ][ 'date_mode' ] ) ? $field[ 'settings' ][ 'date_mode' ] : '';
        }

        return self::combine_value_parts( $field_value, $hours_24, $date_mode );
    }

    /**
     * Combine a stored date value into the single string we display.
     *
     * A date field's submission is stored as separate date/hour/minute/ampm
     * parts. Every surface that shows one - the submissions columns, CSV
     * export, merge tags - needs the same combined string. Exposed statically
     * so a date field nested inside a repeater, whose parts are stored under
     * the repeater rather than as a field of its own, renders identically to
     * one outside a repeater. See issue #7440.
     *
     * @param mixed $field_value Stored value: the parts array, or a plain string.
     * @param bool $hours_24 Whether the field is configured for 24 hour time.
     * @param string $date_mode 'date_only', 'date_and_time' or 'time_only'.
     * @return string
     */
    public static function combine_value_parts( $field_value, $hours_24 = false, $date_mode = '' )
    {
        if ( ! is_array( $field_value ) ) {
            return $field_value;
        }

        $date = isset ( $field_value[ 'date' ] ) ? trim( (string) $field_value[ 'date' ] ) : '';
        $hour = isset ( $field_value[ 'hour' ] ) ? trim( (string) $field_value[ 'hour' ] ) : '';
        $minute = isset ( $field_value[ 'minute' ] ) ? trim( (string) $field_value[ 'minute' ] ) : '';
        $ampm = isset ( $field_value[ 'ampm' ] ) ? trim( (string) $field_value[ 'ampm' ] ) : '';
        $time = '';

        /*
         * '00' is a legitimate hour and a legitimate minute. Testing for an
         * empty string rather than using empty() keeps midnight and on-the-hour
         * times, which were previously discarded along with their date.
         */
        if ( '' !== $hour && '' !== $minute ) {
            $time = $hour . ':' . $minute;

            // Display am/pm if necessary
            if ( ! $hours_24 && '' !== $ampm ) {
                $time .= ' ' . $ampm;
            }
        }

        /*
         * A time-only field inside a repeater stores the assembled time under
         * the 'date' key as well as in the parts. Emit the time once.
         */
        if ( 'time_only' === $date_mode ) {
            return '' !== $time ? $time : $date;
        }

        if ( '' === $date ) {
            return $time;
        }

        return '' === $time ? $date : $date . ' ' . $time;
    }

    private function get_hours_options( $hour, $field )
    {
        $hours_24 = 1;

        if ( is_object( $field ) ) {
            $hours_24 = $field->get_setting( 'hours_24' );
        } elseif ( is_array( $field ) && isset ( $field[ 'settings' ][ 'hours_24' ] ) ) {
            $hours_24 = $field[ 'settings' ][ 'hours_24' ];
        }

        // Defaults
        $hours = 12;
        $first_hour = 1;
        $hours_options = '<option value="12">12</option>';

        if ( 1 == $hours_24 ) {
            $hours = 24;
            $first_hour = 0;
            $hours_options = '';
        }

        for ( $i = $first_hour; $i < $hours; $i++ ) {
            $output = $i;
            if ( $i < 10 ) {
                $output = '0' . $i;
            }

            $selected = '';

            if ( $hour == $output ) {
                $selected = 'selected="selected"';
            }

            $hours_options .= '<option value="' . $output . '" ' . $selected . '>' . $output . '</option>';
        }

        return $hours_options;
    }

    private function get_minutes_options( $minute, $field )
    {
        if ( is_object( $field ) ) {
            $minute_increment = $field->get_setting( 'minute_increment' );
        } elseif ( is_array( $field ) && isset ( $field[ 'settings' ][ 'minute_increment' ] ) ) {
            $minute_increment = $field[ 'settings' ][ 'minute_increment' ];
        }

        if ( empty( $minute_increment ) ) {
            $minute_increment = 1;
        }

        /**
         * Minutes Select Options
         */
        $minutes_options = '';

        $i = 0;
        while ( $i < 60 ) {
            $output = $i;
            if ( $i < 10 ) {
                $output = '0' . $i;
            }

            $selected = '';

            if ( $minute == $output ) {
                $selected = 'selected="selected"';
            }

            $minutes_options .= '<option value="' . $output . '" ' . $selected . '>' . $output . '</option>';
            $i += $minute_increment;
        }

        return $minutes_options;
    }

    /**
     * Filter Merge Tag Value
     * This is what provides the merge tag with the fields value.
     * @since 3.0
     *
     * @param $value Field value
     * @param $field field model
     * @return string|void
     */
    public function filter_merge_tag_value( $value, $field )
    {
        if ( ! isset( $field[ 'settings' ][ 'date_mode' ] ) ||
            'date_only' == $field[ 'settings' ][ 'date_mode' ] ) {
            return $value;
        }

        /**
         * Explode our value at each ','.
         * It'll be in a format like 01/05/2021,hour,minute,ampm.
         */
        
        $exploded_value = explode( ',', $value );

        /*
         * A time-only value arrives already assembled, as '02:15 pm', with no
         * commas to split on. Default the parts rather than reading positions
         * that are not there. See #7440.
         */
        $date = isset ( $exploded_value[0] ) ? $exploded_value[0] : '';
        $hour = isset ( $exploded_value[1] ) ? $exploded_value[1] : '';
        $minute = isset ( $exploded_value[2] ) ? $exploded_value[2] : '';

        $time = $hour . ':' . $minute;

        if ( isset ( $exploded_value[3] ) ) {
            $time .= ' ' . $exploded_value[3];
        }

        if ( 'time_only' == $field[ 'settings' ][ 'date_mode' ] ) {
            if($time === ':' && !strpos($value, ',')) {
                return $value;
            } else {
                return $time;
            } 
        }

        return $date . ' ' . $time;
    }
}