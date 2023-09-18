<?php 

/**
 * Class NF_Database_Models_Submission
 */
class NF_Database_Models_Submission
{
    protected $_id = '';

    protected $_status = '';

    protected $_user_id = '';

    protected $_form_id = '';

    protected $_seq_num = '';

    protected $_sub_date = '';

    protected $_mod_date = '';

    protected $_field_values = array();

    protected $_extra_values = array();

    /**
     * Delimiter that uniquely identifies a field as type 'repeater'
     * 
     * Requests for a field can be made by either an (int) field id or a 
     * (string) field reference, which prior to fieldset repeaters had been
     * for the field key only.  For disambiguation, a fieldset repeater field
     * request for a specific field within the fieldset is in the form of: 
     * {fieldsetFieldId}{delimiter}{fieldIdOfFieldWithinFieldset}
     * 
     * @var string
     */
    protected $_fieldsetDelimiter='.';
    
    /**
     * Delimiter that uniquely identifies multiple fieldset repeater submissions
     * 
     * Fieldset Repeaters can have multiple values submitted on any given 
     *  submission.  Each repeated value for a field in the fieldset is
     * delimited in the submission data with an incremented index value
     * @var string
     */
    protected $_fieldsetRepetitionDelimiter='_';
    
    public function __construct( $id = '', $form_id = '' )
    {
        $this->_id = $id;
        $this->_form_id = $form_id;

        if( $this->_id ){
            $sub = $this->retrieveSub($this->_id);

            if ($sub) {
                $this->_status = $sub->post_status;
                $this->_user_id = $sub->post_author;
                $this->_sub_date = $sub->post_date;
                $this->_mod_date = $sub->post_modified;
            }
        }

        if( $this->_id && ! $this->_form_id ){
            $this->_form_id = $this->retrieveFormId($this->_id);
        }

        if( $this->_id && $this->_form_id ){
            $this->_seq_num = $this->retrieveSeqNum($this->_id);
        }
    }

    /**
     * Get post object
     * 
     * Uses WP functionality
     *
     * @param string $id
     * @return object
     */
    protected function retrieveSub($id)
    {
        $return = get_post( $id );

        return $return;
    }

    /**
     * Get the Form Id
     * 
     * Uses WP functionality
     *
     * @return int
     */
    protected function retrieveFormId( $id)
    {
        $return = $this->getPostMeta( $id, '_form_id', TRUE );
        return $return;
    }

    /**
     * Get the sequence number
     * 
     * Uses WP functionality
     *
     * @return int
     */
    protected function retrieveSeqNum($id)
    {
        $return = $this->getPostMeta( $id, '_seq_num', TRUE  );
        return $return;
    }

    /**
     * Get post meta value for given post Id and key
     *
     * @param int $id
     * @param string $key
     * @param bool $bool
     * @return mixed
     */
    protected function getPostMeta($id, $key, $bool = TRUE)
    {
        $return = get_post_meta( $id, $key, $bool );

        return $return;
    }

    /**
     * Get Submission ID
     *
     * @return int
     */
    public function get_id()
    {
        return intval( $this->_id );
    }

    public function get_status()
    {
        return $this->_status;
    }

    public function get_user()
    {
        return get_user_by( 'id', $this->_user_id );
    }

    public function get_form_id()
    {
        return intval( $this->_form_id );
    }

    public function get_form_title()
    {
        $form = Ninja_Forms()->form( $this->_form_id )->get();
        return $form->get_setting( 'title' );
    }

    public function get_seq_num()
    {
        return intval( $this->_seq_num );
    }

    public function get_sub_date( $format = 'm/d/Y' )
    {
        return date( $format, strtotime( $this->_sub_date ) );
    }

    public function get_mod_date( $format = 'm/d/Y' )
    {
        return date( $format, strtotime( $this->_mod_date ) );
    }

    /**
     * Get Field Value
     *
     * Returns a single submission value by field ID or field key.
     *
     * @param int|string $field_ref
     * @return string
     */
    public function get_field_value( $field_ref )
    {
        // Bypass existing method if fieldset repeater
        if(Ninja_Forms()->fieldsetRepeater->isRepeaterFieldByFieldReference($field_ref) ){
            
            $parsedField = Ninja_Forms()->fieldsetRepeater
                    ->parseFieldsetFieldReference($field_ref);
            
            $return = $this->get_field_value_for_fieldset_child($parsedField['fieldId'], $parsedField['fieldsetFieldId']);
            
            return $return;
        }
        
        $field_id = ( is_numeric( $field_ref ) ) ? $field_ref : $this->get_field_id_by_key( $field_ref );

        $field = '_field_' . $field_id;

        if( isset( $this->_field_values[ $field ] ) ) return $this->_field_values[ $field ];

        $this->_field_values[ $field ] = get_post_meta($this->_id, $field, TRUE);
        $this->_field_values[ $field_ref ] = get_post_meta($this->_id, $field, TRUE);

        return WPN_Helper::htmlspecialchars( $this->_field_values[ $field ] );
    }

    /**
     * Get field values of a single child field within a fieldset repeater field
     * 
     * get_field_value(), which calls this method, is expected to return a 
     *  string.  Fieldset Repeater child fields have a unique field reference,
     *  differentiated by their delimiter that ensures that the requesting
     *  external caller knows that it is requesting a fieldset repeater field.
     *  This this method returns a serialized string of values, honoring the
     *  get_field_value() method with the expectation that the external
     *  caller will unserialize this value.
     * 
     * @param int $fieldsetId
     * @param int $childFieldId
     */
    protected function get_field_value_for_fieldset_child($fieldsetId, $childFieldId) {

   
        if (!isset($this->_field_values[$fieldsetId])) {
            $this->_field_values[$fieldsetId] = get_post_meta($this->_id, '_field_' . $fieldsetId, true);
        }

        $valueCollection = [];

        if(!empty($this->_field_values[$fieldsetId] )){
            foreach ($this->_field_values[$fieldsetId] as $submissionKey => $value) {

                $explodedFieldset = explode($this->_fieldsetDelimiter, $submissionKey);

                if (!isset($explodedFieldset[1])) {
                    // data is corrupted as we cannot determine field id construct
                    break;
                }

                $explodedChildField = explode($this->_fieldsetRepetitionDelimiter, $explodedFieldset[1]);

                if (!isset($explodedChildField[1])) {
                    // data is corrupted as we cannote determine child field id construct
                    break;
                }

                $submissionChildFieldId = $explodedChildField[0];
                $submissionIndex = $explodedChildField[1];

                if ($submissionChildFieldId === $childFieldId) {

                    $valueCollection[$submissionIndex] = WPN_Helper::htmlspecialchars($value);
                }
            }
        }
        
        $return = serialize($valueCollection);

        return $return;
    }

    /**
     * Get Field Values
     *
     * @return array|mixed
     */
    public function get_field_values()
    {
        if( ! empty( $this->_field_values ) ) return $this->_field_values;

        $field_values = $this->getPostMeta( $this->_id, '' );

        foreach( $field_values as $field_id => $field_value ){
            $this->_field_values[ $field_id ] = implode( ', ', $field_value );

            if( 0 === strpos( $field_id, '_field_' ) ){
                $field_id = substr( $field_id, 7 );
            }

            if( ! is_numeric( $field_id ) ) continue;

            if($this->_form_id){

                $field = Ninja_Forms()->form($this->_form_id)->get_field( $field_id );
            }else{

                $field = Ninja_Forms()->form()->get_field( $field_id );
            }

            $key = $field->get_setting( 'key' );
            if( $key ) {
                $this->_field_values[ $key ] = implode(', ', $field_value);
            }
        }

        return $this->_field_values;
    }

    /**
     * Update Field Value
     *
     * @param $field_ref
     * @param $value
     * @return $this
     */
    public function update_field_value( $field_ref, $value )
    {
        $field_id = ( is_numeric( $field_ref ) ) ? $field_ref : $this->get_field_id_by_key( $field_ref );

        $this->_field_values[ $field_id ] = WPN_Helper::kses_post( $value );

        return $this;
    }

    /**
     * Update Field Values
     *
     * @param $data
     * @return $this
     */
    public function update_field_values( $data )
    {
        foreach( $data as $field_ref => $value )
        {
            $this->update_field_value( $field_ref, $value );
        }

        return $this;
    }

    public function get_extra_value( $key )
    {
        if( ! isset( $this->_extra_values[ $key ] ) ||  ! $this->_extra_values[ $key ] ){
            $id = ( $this->_id ) ? $this->_id : 0;
            $this->_extra_values[ $key ] = get_post_meta( $id, $key, TRUE );
        }

        return $this->_extra_values[ $key ];
    }

    public function get_extra_values( $keys )
    {
        $values = array();

        foreach( $keys as $key ) {
            $values[ $key ] = $this->get_extra_value( $key );
        }

        return $values;
    }

    public function update_extra_value( $key, $value )
    {
        if( property_exists( $this, $key ) ) return FALSE;

        return $this->_extra_values[ $key ] = $value;
    }

    public function update_extra_values( $values )
    {
        foreach( $values as $key => $value ){
            $this->update_extra_value( $key, $value );
        }
    }

    /**
     * Find Submissions
     *
     * @param $form_id
     * @param array $where
     * @return array
     */
    public function find( $form_id, array $where = array(), array $ids = array() )
    {

        $this->_form_id = $form_id;

        $args = array(
            'post_type' => 'nf_sub',
            'posts_per_page' => -1,
            'meta_query' => $this->format_meta_query( $where )
        );

        if ( ! empty ( $ids ) ) {
            $args[ 'post__in' ] = $ids;
        }

        $subs = get_posts( $args );

        $class = get_class( $this );

        $return = array();
        foreach( $subs as $sub ){
            $return[] = new $class( $sub->ID, $this->_form_id );
        }

        return $return;
    }

    /**
     * Delete Submission
     */
    public function delete()
    {
        if( ! $this->_id ) return;

        wp_delete_post( $this->_id );
    }

     /**
     * Trash Submission
     */
    public function trash()
    {
        if( ! $this->_id ) return;

        wp_trash_post( $this->_id );
    }

    /**
     * Save Submission
     *
     * @return $this|NF_Database_Models_Submission|void
     */
    public function save()
    {
        if( ! $this->_id ){

            $sub = array(
                'post_type' => 'nf_sub',
                'post_status' => 'publish'
            );

            $this->_id = wp_insert_post( $sub );

            // Log Error
            if( ! $this->_id ) return;
        }

        if( ! $this->_seq_num && $this->_form_id ){

            $this->_seq_num = NF_Database_Models_Form::get_next_sub_seq( $this->_form_id );
        }

        $this->_save_extra_values();

        return $this->_save_field_values();
    }

    public static function export( $form_id, array $sub_ids = array(), $return = FALSE )
    {
        $date_format = Ninja_Forms()->get_setting( 'date_format' );

        /*
         * Labels
         */

        $field_labels = array(
            '_seq_num' => '#',
            '_date_submitted' => esc_html__( 'Date Submitted', 'ninja-forms' )
        );

        $fields = Ninja_Forms()->form( $form_id )->get_fields();

        /*
         * If we are using an add-on that filters our field order, we don't want to call sort again.
         *
         * TODO: This is probably not the most effecient way to handle this. It should be re-thought.
         */
        if ( ! has_filter( 'ninja_forms_get_fields_sorted' ) ) {
            uasort( $fields, array( 'NF_Database_Models_Submission', 'sort_fields' ) );
        }

        $hidden_field_types = apply_filters( 'nf_sub_hidden_field_types', array() );

        /*
         * Submissions
         */

        $subs = Ninja_Forms()->form( $form_id )->get_subs( array(), FALSE, $sub_ids );

        foreach( $subs as $sub ){

            $value[ '_seq_num' ] = $sub->get_seq_num();
            $value[ '_date_submitted' ] = $sub->get_sub_date( $date_format );

            // boolean - does this submission use a repeater
            $hasRepeater = false;
            // How many repeater submissions does this submission have
            $submissionCount = 0;
            // Ids of fields in the repeater
            $fieldsetFieldIds=[];

            foreach ($fields as $field_id => $field) {
                        // Bypass existing method if fieldset repeater
                if('repeater'===$field->get_setting('type')){
                    $hasRepeater = true;
                    
                    $fieldsetSubmission=    $sub->get_field_value( $field_id );
                    $fieldsetSettings = $field->get_settings();
                    $fieldsetLabels = Ninja_Forms()->fieldsetRepeater
                            ->getFieldsetLabels($field_id, $fieldsetSettings, true);
                                    
                    foreach($fieldsetLabels as $fieldsetFieldId =>$fieldsetFieldLabel){
                        
                        $fieldsetFieldIds[]=$fieldsetFieldId;

                        $field_labels[$fieldsetFieldId]=WPN_Helper::maybe_escape_csv_column( $fieldsetFieldLabel );
                        
                        $fieldType = Ninja_Forms()->fieldsetRepeater->getFieldtype($fieldsetFieldId, $fieldsetSettings);
                        
                        $fieldsetFieldSubmissionCollection=Ninja_Forms()->fieldsetRepeater
                                ->extractSubmissionsByFieldsetField($fieldsetFieldId, $fieldsetSubmission);
                       
                       $submissionCount = count($fieldsetFieldSubmissionCollection);
                       
                            foreach ($fieldsetFieldSubmissionCollection as  &$fieldsetFieldSubmission) {
                                
                                if(is_array($fieldsetFieldSubmission['value'])){

                                    $fieldsetFieldSubmission['value']= implode(', ',$fieldsetFieldSubmission['value']);
                                }
                            }
                            

                        $value[$fieldsetFieldId]= array_column($fieldsetFieldSubmissionCollection,'value');
                    }
                                      
                }else{
                    if (!is_int($field_id)) continue;
                  if( in_array( $field->get_setting( 'type' ), $hidden_field_types ) ) continue;

                  if ( $field->get_setting( 'admin_label' ) ) {
                      $field_labels[ $field->get_id() ] = WPN_Helper::maybe_escape_csv_column( $field->get_setting( 'admin_label' ) );
                  } else {
                      $field_labels[ $field->get_id() ] = WPN_Helper::maybe_escape_csv_column( $field->get_setting( 'label' ) );
                  }

                  $field_value = maybe_unserialize( $sub->get_field_value( $field_id ) );

                  $field_value = apply_filters('nf_subs_export_pre_value', $field_value, $field_id);
                  $field_value = apply_filters('ninja_forms_subs_export_pre_value', $field_value, $field_id, $form_id);
                  $field_value = apply_filters( 'ninja_forms_subs_export_field_value_' . $field->get_setting( 'type' ), $field_value, $field );

                  if ( is_array($field_value ) ) {
                      $field_value = implode( ',', $field_value );
                  }

                  $value[ $field_id ] = $field_value;
                  
                }   
            }

            if(!$hasRepeater){
                $value_array[] = $value;
            }else{
                // The the submission has repeater fields, create an indexed array first
                $repeatingValueArray=[];
                $index = 0;

                do {
                    // iterate each column in the row 'value'
                    foreach($value as $fieldId=>$columnValue){
                        
                        // If the column in the row value is not a repeater
                        // fieldset field, simply copy it into a new row of the
                        // repeating value array
                        if(!in_array($fieldId,$fieldsetFieldIds)){
                            $repeatingValueArray[$index][]=$columnValue;
                        }else{

                            // If the column in the row value is a repeater
                            // fieldset field, copy the next submission index value
                            
                            
                            $repeatingValueArray[$index][]=$columnValue[$index];
                        }
                    }
                    // at the end of the row value columns, increment the index
                    // until all the submission index values are added
                    $index++;
                } while ($index < $submissionCount);

                // After iterating the row value once for each submission index,
                // add the repeatingValueArray to the value array

                $value_array[]=$repeatingValueArray;
            }

        }

        $value_array = WPN_Helper::stripslashes( $value_array );

        $csv_array[ 0 ][] = $field_labels;
        $csv_array[ 1 ][] = $value_array;
        
        // Get any extra data from our other plugins...
        $csv_array = apply_filters( 'nf_subs_csv_extra_values', $csv_array, $subs, $form_id );

        $today = date( $date_format, current_time( 'timestamp' ) );
        $filename = apply_filters( 'nf_subs_csv_filename', 'nf_subs_' . $today );
        $filename = $filename . ".csv";

        if( $return ){
            return WPN_Helper::str_putcsv( $csv_array,
                apply_filters( 'nf_sub_csv_delimiter', ',' ),
                apply_filters( 'nf_sub_csv_enclosure', '"' ),
                apply_filters( 'nf_sub_csv_terminator', "\n" )
            );
        }else{
            header( 'Content-type: application/csv');
            header( 'Content-Disposition: attachment; filename="'.$filename .'"' );
            header( 'Pragma: no-cache');
            header( 'Expires: 0' );
            echo apply_filters( 'nf_sub_csv_bom',"\xEF\xBB\xBF" ) ; // Byte Order Mark
            echo WPN_Helper::str_putcsv( $csv_array,
                apply_filters( 'nf_sub_csv_delimiter', ',' ),
                apply_filters( 'nf_sub_csv_enclosure', '"' ),
                apply_filters( 'nf_sub_csv_terminator', "\n" )
            );

            die();
        }
    }

    /*
     * PROTECTED METHODS
     */



    /**
     * Save Field Value
     *
     * @param $field_id
     * @param $value
     * @return $this
     */
    protected function _save_field_value( $field_id, $value )
    {
        update_post_meta( $this->_id, '_field_' . $field_id, $value );

        return $this;
    }

    /**
     * Save Field Values
     *
     * @return $this|void
     */
    protected function _save_field_values()
    {
        if( ! $this->_field_values ) return FALSE;

        foreach( $this->_field_values as $field_id => $value )
        {
            $this->_save_field_value( $field_id, $value );
        }

        update_post_meta( $this->_id, '_form_id', $this->_form_id );

        update_post_meta( $this->_id, '_seq_num', $this->_seq_num );

        return $this;
    }

    protected function _save_extra_values()
    {
        if( ! $this->_extra_values ) return FALSE;

        $maxCount = apply_filters('ninja_forms_max_extra_data_count',200,$this->_form_id);

        /*
         * if extra data has more than 200 elements, then stop.  Add-ons should
         * not be adding those many individual pieces of data; rather, they
         * should add data keyed on specific functional areas from their usage.
         *
         * Over the allowed limit, it is expected to be an attack.  Site
         * developers can use filter to raise limit either globally or per-form
         */
        if($maxCount<count($this->_extra_values)){
            return FALSE;
        }

        foreach( $this->_extra_values as $key => $value )
        {
            if( property_exists( $this, $key ) ) continue;

            update_post_meta( $this->_id, $key, $value );
        }
    }


    /*
     * UTILITIES
     */

    /**
     * Format Meta Query
     *
     * @param array $where
     * @return array
     */
    protected function format_meta_query( array $where = array() )
    {
        $return = array(
            array(
                'key' => '_form_id',
                'value' => $this->_form_id
            )
        );

        if( ! empty( $where ) ) {
            foreach ($where as $ref => $value) {

                $field_id = ( is_int( $ref ) ) ? $ref : $this->get_field_id_by_key( $ref );

                $return[] = ( is_array($value) ) ? $value : array('key' => "_field_$field_id", 'value' => $value);
            }
        }

        return $return;
    }

    /**
     * Get Field ID By Key
     *
     * @param $field_key
     * @return mixed
     */
    protected function get_field_id_by_key( $field_key )
    {
        global $wpdb;

        $field_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}nf3_fields WHERE `key` = %s AND `parent_id` = {$this->_form_id}",
            $field_key
        ));

        return $field_id;
    }

    public static function sort_fields( $a, $b )
    {
        if ( $a->get_setting( 'order' ) == $b->get_setting( 'order' ) ) {
            return 0;
        }
        return ( $a->get_setting( 'order' ) < $b->get_setting( 'order' ) ) ? -1 : 1;
    }


} // End NF_Database_Models_Submission
