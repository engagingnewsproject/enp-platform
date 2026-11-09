<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Terms
 */
class NF_Fields_Terms extends NF_Fields_ListCheckbox
{
    protected $_name = 'terms';
    protected $_type = 'terms';

    protected $_nicename = 'Terms List';

    protected $_section = '';

    protected $_icon = 'tags';

    protected $_templates = array( 'terms', 'listcheckbox' );

    protected $_settings = array( 'taxonomy', 'add_new_terms' );

    protected $_settings_exclude = array();

    protected $_excluded_taxonomies = array(
        'post_format'
    );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__( 'Terms List', 'ninja-forms' );

        // If we are on the ninja-forms page...
        // OR we're looking at nf_sub post types...
        // OR we're editing a single post...
        if ( ( ! empty( $_GET[ 'page' ] ) && 'ninja-forms' == $_GET[ 'page' ] ) ||
           ( ! empty( $_GET[ 'post_type' ] ) && 'nf_sub' == $_GET[ 'post_type' ] ) ||
           isset( $_GET[ 'post' ] ) && 'nf_sub' === get_post_type( $_GET[ 'post' ] ) ) {
            // Initiate the termslist.
            add_action( 'admin_init', array( $this, 'init_settings' ) );
        }

        add_filter( 'ninja_forms_display_field', array( $this, 'active_taxonomy_field_check' ) );
        add_filter( 'ninja_forms_localize_field_' . $this->_type, array( $this, 'add_term_options' ) );
        add_filter( 'ninja_forms_localize_field_' . $this->_type . '_preview', array( $this, 'add_term_options' ) );

        add_filter( 'ninja_forms_merge_tag_value_' . $this->_type, array( $this, 'merge_tag_value' ), 10, 2 );

        // The available terms are the field's options for every server-side
        // consumer: submission validation, the single-submission screen, and
        // the submissions list column. Without this they all see no options.
        add_filter( 'ninja_forms_render_options_' . $this->_type, array( $this, 'render_term_options' ), 10, 2 );

        // The submissions screens build their columns from the field's stored
        // options, which a Terms List field does not have. Supply the available
        // terms so a submission reads as the term name. #8135
        add_filter( 'ninja_forms_submission_field_options_' . $this->_type, array( $this, 'render_term_options' ), 10, 2 );

        // A CSV export carries values rather than options, so the stored term
        // IDs are mapped to names on the way out. #8135
        add_filter( 'ninja_forms_subs_export_field_value_' . $this->_type, array( $this, 'display_value' ), 10, 2 );

        $this->_settings[ 'options' ][ 'group' ] = '';
    }

    public function process( $field, $data )
    {
        return $data;
    }

    /**
     * Validate the submitted terms.
     *
     * A Terms List field never carries an `options` setting: its options are
     * built from the taxonomy and the per-term switches when the field is
     * localized for display. The shared list validation added for issue #8147
     * reads options through the `ninja_forms_render_options` filters, which this
     * field does not use, so it would reject every submitted term. Build the
     * offered options here before deferring to it.
     *
     * When the site owner has switched Add New Terms on, the visitor may submit
     * a term that was never offered - that is the purpose of the setting - so
     * only the shared required check applies to that field.
     *
     * @see https://github.com/Saturday-Drive/ninja-forms/issues/8131
     * @see https://github.com/Saturday-Drive/ninja-forms/issues/8147
     *
     * @param array $field Field settings including submitted value.
     * @param array $data  Form data.
     * @return array Validation errors.
     */
    public function validate( $field, $data )
    {
        if( ! empty( $field[ 'add_new_terms' ] ) ) {
            return NF_Abstracts_Field::validate( $field, $data );
        }

        $field[ 'options' ] = $this->get_offered_options( $field );

        return parent::validate( $field, $data );
    }

    /**
     * Build the options this field offers, as the display layer builds them.
     *
     * @param array $field Field settings.
     * @return array Options, each with a `value` and `label`.
     */
    protected function get_offered_options( $field )
    {
        $localized = $this->add_term_options( array( 'settings' => $field ) );

        return isset( $localized[ 'settings' ][ 'options' ] ) && is_array( $localized[ 'settings' ][ 'options' ] )
            ? $localized[ 'settings' ][ 'options' ]
            : array();
    }

    public function init_settings()
    {
        $term_settings = array();
        $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
        foreach( $taxonomies as $name => $taxonomy ){

            $tax_term_settings = array();

            if( in_array( $name, $this->_excluded_taxonomies ) ) continue;

            $this->_settings[ 'taxonomy' ][ 'options' ][] = array(
                'label' => $taxonomy->labels->name,
                'value' => $name
            );

            $terms = get_terms( $name, array( 'hide_empty' => false ) );

            foreach( $terms as $term ){

                // Check the slug instead of term_id to ensure we ONLY remove 'uncategorized'.
                if( 'uncategorized' == $term->slug ) continue;

                $tax_term_settings[] =  array(
                    'name' => 'taxonomy_term_' . $term->term_id,
                    'type' => 'toggle',
                    'label' => $term->name . ' (' . $term->count .')',
                    'width' => 'one-third',
                    'deps' => array(
                        'taxonomy' => $name
                    ),
                );
            }

            if( empty( $tax_term_settings ) ){
                $tax_term_settings[] =  array(
                    'name' => $name . '_no_terms',
                    'type' => 'html',
                    'width' => 'full',
                    'value' => sprintf( esc_html__( 'No available terms for this taxonomy. %sAdd a term%s', 'ninja-forms' ), '<a href="' . admin_url( "edit-tags.php?taxonomy=$name" ) . '">', '</a>' ),
                    'deps' => array(
                        'taxonomy' => $name
                    )
                );
            }

            $term_settings = array_merge( $term_settings, $tax_term_settings );

        }

        $term_settings[] =  array(
            'name' => '_no_taxonomy',
            'type' => 'html',
            'width' => 'full',
            'value' => esc_html__( 'No taxonomy selected.', 'ninja-forms' ),
            'deps' => array(
                'taxonomy' => ''
            )
        );

        $this->_settings[ 'taxonomy_terms' ] = array(
            'name' => 'taxonomy_terms',
            'type' => 'fieldset',
            'label' => esc_html__( 'Available Terms', 'ninja-forms' ),
            'width' => 'full',
            'group' => 'primary',
            'settings' => $term_settings
        );
    }

    public function active_taxonomy_field_check( $field )
    {
        if( $this->_type != $field->get_setting( 'type' ) ) return $field;

        $taxonomy = $field->get_setting( 'taxonomy' );

        if( ! taxonomy_exists( $taxonomy ) ) return FALSE;

        return $field;
    }

    public function add_term_options( $field )
    {
        $settings = ( is_object( $field ) ) ? $field->get_settings() : $field[ 'settings' ];

        $settings[ 'options' ] = $this->build_term_options( $settings );

        if( is_object( $field ) ) {
            $field->update_settings( $settings );
        } else {
            $field[ 'settings' ] = $settings;
        }

        return $field;
    }

    /**
     * Render the field on the single-submission screen.
     *
     * Rendered here rather than on the parent so that a stored answer matching
     * no available term stays on screen and survives an administrator pressing
     * Update. #8135
     *
     * @since 3.14.10
     *
     * @param int $id Field ID.
     * @param mixed $value Stored submission value.
     * @return string
     */
    public function admin_form_element( $id, $value )
    {
        $form_id = get_post_meta( absint( $_GET[ 'post' ] ), '_form_id', true );

        $field = Ninja_Forms()->form( $form_id )->get_field( $id );

        $settings = $field->get_settings();

        $options = $field->get_setting( 'options' );
        $options = apply_filters( 'ninja_forms_render_options', $options, $settings );
        $options = apply_filters( 'ninja_forms_render_options_' . $this->_type, $options, $settings );

        return $this->render_admin_checkbox_list( $id, $value, $options );
    }

    /**
     * Render the available terms as a checkbox list with the stored answer
     * selected.
     *
     * @since 3.14.10
     *
     * @param int $id Field ID.
     * @param mixed $value Stored submission value.
     * @param array $options Available term options.
     * @return string
     */
    public function render_admin_checkbox_list( $id, $value, $options )
    {
        $selected = $this->normalize_stored_value( $value );

        $list = '';
        $shown = array();

        if( is_array( $options ) ){
            foreach( $options as $option ){

                if( ! isset( $option[ 'value' ] ) ) continue;

                $option_value = (string) $option[ 'value' ];

                $checked = in_array( $option_value, $selected, true );

                if( $checked ) $shown[] = $option_value;

                $label = isset( $option[ 'label' ] ) ? $option[ 'label' ] : $option_value;

                $list .= $this->render_admin_checkbox( $id, $option_value, $label, $checked );
            }
        }

        // An answer matching no available term is still the visitor's answer:
        // show it, keep it selected, and let it post back unchanged. #8135
        foreach( array_diff( $selected, $shown ) as $unresolved ){

            if( '' === $unresolved ) continue;

            $list .= $this->render_admin_checkbox( $id, $unresolved, $unresolved, true );
        }

        return "<input type='hidden' name='fields[" . esc_attr( $id ) . "]' value='0' ><ul>$list</ul>";
    }

    /**
     * Render one checkbox for the single-submission screen.
     *
     * @param int $id Field ID.
     * @param string $value Option value.
     * @param string $label Option label.
     * @param bool $checked Whether the option is selected.
     * @return string
     */
    protected function render_admin_checkbox( $id, $value, $label, $checked )
    {
        $checked_attribute = ( $checked ) ? ' checked' : '';

        return "<li><label><input type='checkbox' value='" . esc_attr( $value )
            . "' name='fields[" . esc_attr( $id ) . "][]'" . $checked_attribute . "> "
            . esc_html( $label ) . "</label></li>";
    }

    /**
     * Normalize a stored submission value to an array of strings.
     *
     * @param mixed $value Stored submission value.
     * @return array
     */
    protected function normalize_stored_value( $value )
    {
        if( is_array( $value ) ) return array_map( 'strval', $value );

        if( ! is_string( $value ) || '' === $value ) return array();

        return array_map( 'strval', explode( ',', $value ) );
    }

    /**
     * Provide the available terms as render options.
     *
     * The terms a form owner made available on the field are its options, but
     * they are stored as `taxonomy_term_<id>` toggles rather than in `options`.
     * Publishing them here is what lets submission validation accept a chosen
     * term and lets the single-submission screen render a control.
     *
     * @since 3.14.10
     *
     * @param array $options Options provided so far.
     * @param array|object $settings Field settings, or the field itself.
     * @return array
     */
    public function render_term_options( $options, $settings )
    {
        if( is_object( $settings ) && is_callable( array( $settings, 'get_settings' ) ) ){
            $settings = $settings->get_settings();
        }

        if( ! is_array( $settings ) ) return $options;

        $term_options = $this->build_term_options( $settings );

        return ( empty( $term_options ) ) ? $options : $term_options;
    }

    /**
     * Display a stored submission value as term names.
     *
     * A submission stores term IDs. A value that resolves to no term is
     * returned untouched: terms typed in through Add New Terms are stored as
     * the text itself, and a term deleted after submission no longer resolves.
     * Both must stay visible rather than disappear. #8135
     *
     * @since 3.14.10
     *
     * @param mixed $value Stored submission value.
     * @param array|object $field Field settings, or the field itself.
     * @return mixed
     */
    public function display_value( $value, $field )
    {
        $taxonomy = $this->get_field_taxonomy( $field );

        if( is_array( $value ) ){
            return array_map( function( $stored ) use ( $taxonomy ) {
                return $this->term_name_or_stored_value( $stored, $taxonomy );
            }, $value );
        }

        if( ! is_string( $value ) || '' === $value ) return $value;

        $names = array();

        foreach( explode( ',', $value ) as $stored ){
            $names[] = $this->term_name_or_stored_value( $stored, $taxonomy );
        }

        return implode( ',', $names );
    }

    /**
     * Build option definitions for the terms made available on the field.
     *
     * @param array $settings Field settings.
     * @return array
     */
    protected function build_term_options( $settings )
    {
        $options = array();

        if( ! isset( $settings[ 'taxonomy' ] ) || ! $settings[ 'taxonomy' ] ) return $options;

        $terms = get_terms( $settings[ 'taxonomy' ], array( 'hide_empty' => false ) );

        if( is_wp_error( $terms ) || ! is_array( $terms ) ) return $options;

        foreach( $terms as $term ){

            if( ! isset( $settings[ 'taxonomy_term_' . $term->term_id ] ) ) continue;
            if( ! $settings[ 'taxonomy_term_' . $term->term_id ] ) continue;

            $options[] = array(
                'label' => $term->name,
                // A stored submission value is a string, and the screens match
                // an answer to its option by value.
                'value' => (string) $term->term_id,
                'calc' => '',
                'selected' => 0,
                'order' => 0
            );
        }

        return $options;
    }

    /**
     * Resolve a stored value to its term name, or return it as it stands.
     *
     * @param mixed $stored Stored value.
     * @param string $taxonomy Taxonomy the field is mapped to.
     * @return mixed
     */
    protected function term_name_or_stored_value( $stored, $taxonomy )
    {
        if( ! is_numeric( trim( (string) $stored ) ) ) return $stored;

        $term = get_term_by( 'id', trim( (string) $stored ), $taxonomy );

        if( ! $term || is_wp_error( $term ) ) return $stored;

        return $term->name;
    }

    /**
     * Read the taxonomy from a field given as settings or as a field object.
     *
     * @param array|object $field Field settings, or the field itself.
     * @return string
     */
    protected function get_field_taxonomy( $field )
    {
        if( is_array( $field ) ){
            return isset( $field[ 'taxonomy' ] ) ? $field[ 'taxonomy' ] : '';
        }

        if( is_object( $field ) && is_callable( array( $field, 'get_setting' ) ) ){
            return $field->get_setting( 'taxonomy' );
        }

        return '';
    }

    public function merge_tag_value( $value, $field )
    {
        $terms = explode( ',', $value );
        if( ! is_array( $terms ) ) {
            $term = get_term_by( 'id', $value, $field[ 'taxonomy' ] );
            if( $term ) {
                return $term->name;
            } else {
                return $value;
            }
        }

        $term_names = array();
        foreach( $terms as $term_id ){
            $term = get_term_by( 'id', $term_id, $field[ 'taxonomy' ] );
            $term_names[] = ( $term ) ? $term->name : $term_id; // If the term is `false`, fallback to the term_id.
        }

        return implode( ',', $term_names );
    }

    public function get_parent_type()
    {
        return 'listcheckbox';
    }
}
