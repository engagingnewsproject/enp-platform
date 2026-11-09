<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_MergeTags_Calcs
 */
class NF_MergeTags_Calcs extends NF_Abstracts_MergeTags
{
    protected $id = 'calcs';

    protected $_default_group = FALSE;

    /** @var array Separators for each calculation, separate from numeric values. */
    protected $calc_formats = array();

    /** @var bool Whether the current replacement is for a display action. */
    protected $format_action_values = false;

    public function __construct()
    {
        parent::__construct();
        $this->title = esc_html__( 'Calculations', 'ninja-forms' );
        add_filter( 'ninja_forms_calc_setting',  array( $this, 'replace' ) );
    }

    public function __call($name, $arguments)
    {
        $value = $this->merge_tags[ $name ][ 'calc_value' ];

        // Action context is scoped to email/success messages.
        // Arithmetic and integration consumers continue to receive canonical numbers.
        if ( $this->format_action_values && isset( $this->calc_formats[ $name ] ) ) {
            $format = $this->calc_formats[ $name ];
            $point = strpos( $value, '.' );
            $precision = ( false === $point ) ? 0 : strlen( $value ) - $point - 1;
            return number_format( (float) $value, $precision, $format['decimal'], $format['thousands'] );
        }

        return $value;
    }

    /**
     * Format display actions even when their settings omit builder-only objectType.
     *
     * @param mixed $subject Action settings.
     * @return string|array
     */
    public function action_replace( $subject )
    {
        $previous = $this->format_action_values;
        $this->format_action_values = is_array( $subject ) && isset( $subject['type'] )
            && in_array( $subject['type'], array( 'email', 'successmessage' ), true );
        try {
            return parent::action_replace( $subject );
        } finally {
            $this->format_action_values = $previous;
        }
    }

    public function set_merge_tags( $key, $value, $round = 2 , $dec = '.', $sep = ',')
    {
        $callback = ( is_numeric( $key ) ) ? 'calc_' . $key : $key;

        // Retain display separators without changing stored numeric values.
        $format = array( 'decimal' => $dec, 'thousands' => $sep );
        $this->calc_formats[ $callback ] = $format;
        $this->calc_formats[ $callback . '2' ] = $format;

        try {
            $locale = new stdClass();
            $locale->number_format = array(
                'thousands_sep' => $sep,
                'decimal_point' => $dec
            );
            $handler = new NF_Handlers_LocaleNumberFormatting($locale);
            $eq = $handler->locale_decode_equation($value);
            $calculated_value = Ninja_Forms()->eos()->solve( $eq );
        } catch( Exception $e ){
            $calculated_value = FALSE;
        }

        $this->merge_tags[ $callback ] = array(
            'id' => $key,
            'tag' => "{calc:$key}",
            'callback' => $callback,
            'calc_value' => number_format( $calculated_value, $round, '.', '' )
        );

        $callback .= '2';

        $this->merge_tags[ $callback ] = array(
            'id' => $key,
            'tag' => "{calc:$key:2}",
            'callback' => $callback,
            'calc_value' => number_format( $calculated_value, 2, '.', '' )
        );
    }
    
    public function get_calc_value( $key )
    {
        $return = null;

        if(isset($this->merge_tags[ $key ][ 'calc_value' ])){
            $return = $this->merge_tags[ $key ][ 'calc_value' ];
        }
        
        return $return; 
    }
    
    // @TODO: $round is no longer necessary in this context.
    public function get_formatted_calc_value( $key, $round = 2, $dec = '.', $sep = ',')
    {
        $locale = new stdClass();
        $locale->number_format = array(
            'thousands_sep' => $sep,
            'decimal_point' => $dec
        );
        $handler = new NF_Handlers_LocaleNumberFormatting($locale);
        return $handler->locale_encode_number( $this->merge_tags[ $key ][ 'calc_value' ] );
    }

} // END CLASS NF_MergeTags_Calcs
