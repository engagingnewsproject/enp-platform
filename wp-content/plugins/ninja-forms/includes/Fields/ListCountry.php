<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_CountryList
 */
class NF_Fields_ListCountry extends NF_Abstracts_List
{
    protected $_name = 'listcountry';

    protected $_type = 'listcountry';

    protected $_nicename = 'Country';

    protected $_section = 'userinfo';

    protected $_templates = array( 'listcountry', 'listselect' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'Country', 'ninja-forms' );

        $this->_settings[ 'options' ][ 'group' ] = '';
//        $this->_settings[ 'options' ][ 'value' ] = $this->get_options();

        $this->_settings[ 'default' ] = array(
            'name' => 'default',
            'type' => 'select',
            'label' => esc_html__( 'Default Value', 'ninja-forms' ),
            'options' => $this->get_default_value_options(),
            'width' => 'one-half',
            'group' => 'primary',
            'value' => 'US',
        );

        add_filter( 'ninja_forms_custom_columns',                          array( $this, 'custom_columns'   ), 10, 2 );
        add_filter( 'ninja_forms_render_options_' . $this->_type,          array( $this, 'filter_options'   ), 10, 2 );
        add_filter( 'ninja_forms_subs_export_field_value_' . $this->_name, array( $this, 'filter_csv_value' ), 10, 2 );
    }

    public function custom_columns( $value, $field )
    {
        if( $this->_name != $field->get_setting( 'type' ) ) return $value;

        $countries = self::country_options();

        return isset( $countries[ $value ] ) ? $countries[ $value ] : $value;
    }

    public function filter_options( $options, $settings )
    {
        $default_value = ( isset( $settings[ 'default' ] ) ) ? $settings[ 'default' ] : '';

        $options = $this->get_options(); // Overwrite the default list options.
        foreach( $options as $key => $option ){
            if( $default_value != $option[ 'value' ] ) continue;
            $options[ $key ][ 'selected' ] = 1;
        }

		usort( $options, array($this,'sort_options_by_label') );

		return $options;
	}

	/**
	 * The countries this field offers, as code => translated name.
	 *
	 * Reads the code-keyed list, where no two countries can merge however
	 * their names translate, and then carries across whatever the older
	 * name-keyed filter did, so an add-on that adds, removes or renames a
	 * country through `ninja_forms_config_country_list` still gets its way.
	 * That filter's own shape can collide under translation, so it is used
	 * only to find the difference it made, never as the list itself.
	 *
	 * @return array Country code => translated country name.
	 */
	private static function country_options()
	{
		$by_code = Ninja_Forms()->config( 'CountryListByCode' );

		// What the name-keyed shape looks like before anyone filters it.
		$baseline = array();
		foreach ( $by_code as $code => $name ) {
			$baseline[ $name ] = $code;
		}

		$filtered = Ninja_Forms()->config( 'CountryList' );

		if ( $filtered === $baseline ) {
			return $by_code;
		}

		// Both arrays lose the same entries to any name collision, so the
		// difference between them is exactly what the filter changed.
		$baseline_by_code = array_flip( $baseline );
		$filtered_by_code = array_flip( $filtered );

		$options = array();

		foreach ( $by_code as $code => $name ) {
			if ( isset( $baseline_by_code[ $code ] ) && ! isset( $filtered_by_code[ $code ] ) ) {
				continue; // The filter removed this country.
			}

			$options[ $code ] = isset( $filtered_by_code[ $code ] )
				? $filtered_by_code[ $code ] // The filter may have renamed it.
				: $name;
		}

		foreach ( $filtered_by_code as $code => $name ) {
			if ( ! isset( $by_code[ $code ] ) ) {
				$options[ $code ] = $name; // The filter added this country.
			}
		}

		return $options;
	}

	/**
	 * Order the country names as a reader expects.
	 *
	 * The labels are translated, so a byte comparison files every accented
	 * name after the whole plain alphabet: Åland Islands below Zimbabwe in
	 * English, États-Unis and Égypte below Zimbabwe in French. Folding the
	 * accents away first puts each name under the letter it reads as. The
	 * original comparison breaks ties so the order stays deterministic when
	 * two names fold to the same text.
	 *
	 * @param array $option_a One option, carrying a 'label'.
	 * @param array $option_b The option to compare it with.
	 * @return int
	 */
	private function sort_options_by_label( $option_a, $option_b )
	{
		$folded = strcasecmp(
			remove_accents( $option_a['label'] ),
			remove_accents( $option_b['label'] )
		);

		if ( 0 !== $folded ) {
			return $folded;
		}

		return strcasecmp( $option_a['label'], $option_b['label'] );
	}

    public function filter_options_preview( $field_settings )
    {
        $field_settings[ 'settings' ][ 'options' ] = $this->get_options();

        foreach( $field_settings[ 'settings' ][ 'options' ] as $key => $option ){
            if( $field_settings[ 'settings' ][ 'default' ] != $option[ 'value' ] ) continue;
            $field_settings[ 'settings' ][ 'options' ][ $key ][ 'selected' ] = 1;
        }

        return $field_settings;
    }

    public function admin_form_element( $id, $value )
    {
        $field = Ninja_Forms()->form()->get_field( $id );

        $options = $this->get_options();
        $options = apply_filters( 'ninja_forms_render_options', $options, $field->get_settings() );
        $options = apply_filters( 'ninja_forms_render_options_' . $this->_type, $options, $field->get_settings() );

        ob_start();
        echo "<select name='fields[$id]'>";
        foreach( $options as $option ){
            $selected = ( $option['value'] == $value ) ? ' selected' : '';
            echo "<option value='" . esc_attr( $option['value'] ) . "'" . $selected . ">" . esc_html( $option['label'] ) . "</option>";
        }
        echo "</select>";
        return ob_get_clean();
    }

    private function get_default_value_options()
    {
        $options = array();
        // Option to have no default country
        $options[] = array(
            'label' => '- ' . esc_html__( 'Select Country', 'ninja-forms' ) . ' -',
	        'value' => ''
        );
        foreach( self::country_options() as $value => $label ){
            $options[] = array(
                'label'  => $label,
                'value' => $value,
            );
        }

        return $options;
    }

    private function get_options()
    {
        $order = 0;
        $options = array();
        // option to have no default country selected
	    $options[] = array(
		    'label' => '- ' . esc_html__( 'Select Country', 'ninja-forms' ) . ' -',
		    'value' => '',
		    'calc' => '',
		    'selected' => 0,
		    'order' => $order,
	    );
	    $order++;
        foreach( self::country_options() as $value => $label ){
            $options[] = array(
                'label'  => $label,
                'value' => $value,
                'calc' => '',
                'selected' => 0,
                'order' => $order
            );

            $order++;
        }

        return $options;
    }

    public function filter_csv_value( $field_value, $field )
    {
        $countries = self::country_options();
        if( isset( $countries[ $field_value ] ) ) $field_value = $countries[ $field_value ];
        return $field_value;
    }
}
