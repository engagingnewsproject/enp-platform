<?php if (!defined('ABSPATH')) exit;

/**
 * Class NF_MergeTags_Fields
 */
final class NF_MergeTags_Fields extends NF_Abstracts_MergeTags
{
    protected $id = 'fields';
    protected $form_id;

    public function __construct()
    {
        parent::__construct();
        $this->title = esc_html__('Fields', 'ninja-forms');
        $this->merge_tags = Ninja_Forms()->config('MergeTagsFields');

        if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $this->include_all_fields_merge_tags();
        }

        add_filter('ninja_forms_calc_setting', array($this, 'pre_parse_calc_settings'), 9);
        //add_filter( 'ninja_forms_calc_setting',  array( $this, 'calc_replace' ) );
    }

    public function __call($name, $arguments)
    {
        if (isset($arguments[0]['calc'])) {
            return $this->merge_tags[$name]['calc_value'];
        }
        if ($this->use_safe && isset($this->merge_tags[$name]['safe_value'])) {
            return $this->merge_tags[$name]['safe_value'];
        }
        return $this->merge_tags[$name]['field_value'];
    }

    /**
     * Helps process {all_fields_table} and {fields_table} 
     * 
     * This still requires to run add_field() for all fields in a submission before and after calling his function
     */
    public function include_all_fields_merge_tags() {
        $this->merge_tags = array_merge( $this->merge_tags, Ninja_Forms()->config( 'MergeTagsFieldsAJAX' ) );
    }

    public function all_fields()
    {
        if (is_rtl()) {
            $return = '<table style="direction: rtl;">';
        } else {
            $return = '<table>';
        }

        $hidden_field_types = array('html', 'submit', 'password', 'passwordconfirm');

        foreach ($this->get_fields_sorted() as $field) {

            if (!isset($field['type'])) continue;

            if (in_array($field['type'], array_values($hidden_field_types))) continue;

            $field['value'] = apply_filters('ninja_forms_merge_tag_value_' . $field['type'], $field['value'], $field);

            if (is_array($field['value'])) $field['value'] = implode(', ', $field['value']);

            $field = $this->maybe_sanitize($field);

            // Check if field is fieldset repeater, if not, go standard
            if ('repeater' !== $field['type']) {

                $return .= '<tr><td>' . apply_filters('ninja_forms_merge_label', $field['label'], $field, $this->form_id) . ':</td><td>' . $field['value'] . '</td></tr>';
            } else {
               // Handle fieldset repeater
               $return .= $this->generateFieldsetTableRows($field);
            }
        }
        $return .= '</table>';
        return $return;
    }






    public function all_fields_table()
    {
        if (is_rtl()) {
            $return = '<table style="direction: rtl;">';
        } else {
            $return = '<table>';
        }

        $hidden_field_types = array('submit', 'password', 'passwordconfirm');

        $list_fields_types = array('listcheckbox', 'listmultiselect', 'listradio', 'listselect');

        foreach ($this->get_fields_sorted() as $field) {
            if (!isset($field['type'])) continue;

            // Skip specific field types.
            if (in_array($field['type'], array_values($hidden_field_types))) continue;

            $field['value'] = apply_filters('ninja_forms_merge_tag_value_' . $field['type'], $field['value'], $field);

            // Check to see if the type is a list field and if it is...
            if (in_array($field['type'], array_values($list_fields_types))) {
                // If we have a comma separated value...
                if (strpos($field['value'], ',')) {
                    // ...build the value back into an array.
                    $field['value'] = explode(',', $field['value']);
                }
                // ...then set the value equal to the field label.
                $field['value'] = $this->get_list_labels($field);
            }

            if (is_array($field['value']) && $field['type'] !== "repeater") $field['value'] = implode(', ', $field['value']);

            $field = $this->maybe_sanitize($field);

            // Check if field is fieldset repeater, if not, go standard
            if ('repeater' !== $field['type']) {
                $return .= '<tr><td valign="top">' . apply_filters('ninja_forms_merge_label', $field['label'], $field, $this->form_id) . ':</td><td>' . $field['value'] . '</td></tr>';
            } else {
                // Handle fieldset repeater
                $return .= $this->generateFieldsetTableRows($field);
            }
        }

        $return .= '</table>';

        return $return;
    }


    public function fields_table()
    {
        if (is_rtl()) {
            $return = '<table style="direction: rtl;">';
        } else {
            $return = '<table>';
        }

        $hidden_field_types = array('html', 'submit', 'password', 'passwordconfirm', 'hidden');

        $list_fields_types = array('listcheckbox', 'listmultiselect', 'listradio', 'listselect');

        foreach ($this->get_fields_sorted() as $field) {

            if (!isset($field['type'])) continue;

            // Skip specific field types.
            if (in_array($field['type'], array_values($hidden_field_types))) continue;

            // TODO: Skip hidden fields, ie conditionally hidden.
            if (isset($field['visible']) && false === $field['visible']) continue;

            // Check to see if the type is a list field and if it is...
            if (in_array($field['type'], array_values($list_fields_types))) {
                // If we have a comma separated value...
                if (strpos($field['value'], ',')) {
                    // ...build the value back into an array.
                    $field['value'] = explode(',', $field['value']);
                }
                // ...then set the value equal to the field label.
                $field['value'] = $this->get_list_labels($field);
            }

            $field['value'] = apply_filters('ninja_forms_merge_tag_value_' . $field['type'], $field['value'], $field);

            // Skip fields without values.
            if (!$field['value']) continue;

            if (is_array($field['value']) && $field['type'] !== "repeater") $field['value'] = implode(', ', $field['value']);

            $field = $this->maybe_sanitize($field);

            // Check if field is fieldset repeater, if not, go standard
            if ('repeater' !== $field['type']) {
                $return .= '<tr><td valign="top">' . apply_filters('ninja_forms_merge_label', $field['label'], $field, $this->form_id) . ':</td><td>' . $field['value'] . '</td></tr>';
            } else {
                // Handle fieldset repeater
                $return .= $this->generateFieldsetTableRows($field);
            }
        }
        $return .= '</table>';
        return $return;
    }

    // TODO: Is this being used?
    public function all_field_plain()
    {
        $return = '';

        foreach ($this->get_fields_sorted() as $field) {

            $field['value'] = apply_filters('ninja_forms_merge_tag_value_' . $field['type'], $field['value'], $field);

            if (is_array($field['value'])) $field['value'] = implode(', ', $field['value']);

            $field = $this->maybe_sanitize($field);

            $return .= $field['label'] . ': ' . $field['value'] . "\r\n";
        }
        return $return;
    }

    public function add_field($field)
    {
        // set boolean check for isRepetater field type
        $isRepeater = isset($field['settings']['type'])
        && isset($field['key'])
        && 'repeater' === $field['settings']['type'] ? true : false;

        $hidden_field_types = apply_filters('nf_sub_hidden_field_types', array());

        if (
            in_array($field['type'], $hidden_field_types)
            && 'html' != $field['type'] // Specifically allow the HTML field in merge tags.
            && 'password' != $field['type'] // Specifically allow the Password field in merge tags for actions, ie User Management
        ) return;

        $field_id  = $field['id'];
        $callback  = 'field_' . $field_id;

        $list_fields_types = array('listcheckbox', 'listmultiselect', 'listradio', 'listselect');

        if (is_array($field['value']) && $field['type'] !== "repeater") $field['value'] = implode(',', $field['value']);

        $field['value'] = $this->stripShortcodesMaybeFieldset($field_id,$field['value']);

        $this->merge_tags['all_fields']['fields'][$field_id] = $field;

        //Set value of repeater fieldset or leave normal value
        $field_value = $isRepeater ? '<table>' . $this->generateFieldsetTableRows($field) . '</table>' : $field['value'];

        $value = apply_filters('ninja_forms_merge_tag_value_' . $field['type'], $field_value, $field);

        $safe = apply_filters(
            'ninja_forms_get_html_safe_fields',
            array('html')
        );
        $sanitize = (!in_array($field['type'], $safe));
        $this->add($callback, $field['id'], '{field:' . $field['id'] . '}', $value, false, $sanitize);

        if (isset($field['key'])) {
            $field_key =  $field['key'];
            //Set calc value of repeater fieldset or leave normal value
            $field_calc_value = $isRepeater ? $this->addRepeaterCalcValue( $field ) : $field['value'];

            $calc_value = apply_filters('ninja_forms_merge_tag_calc_value_' . $field['type'], $field_calc_value, $field);

            // Add Field Key Callback
            $callback = 'field_' . $field_key;
            $this->add($callback, $field_key, '{field:' . $field_key . '}', $value, $calc_value, $sanitize);

            // Add Field by Key for All Fields
            $this->merge_tags['all_fields_by_key']['fields'][$field_key] = $field;

            // Add Field Calc Callabck
            if ('' == $calc_value) $calc_value = '0';
            $callback = 'field_' . $field_key . '_calc';
            $this->add($callback, $field_key, '{field:' . $field_key . ':calc}', $calc_value, $calc_value, $sanitize);


            /*
             * Adds the ability to add :label to list field merge tags
             * this will cause the label to be displayed on the front end
             * instead of the value.
             *
             * @since 3.3.3
             */
            // Check to see if the type is a list field and if it is...
            if (in_array($field['type'], array_values($list_fields_types))) {
                // If we have a comma separated value...
                if (strpos($field['value'], ',')) {
                    // ...build the value back into an array.
                    $field['value'] = explode(',', $field['value']);
                }
                // ...then set the value equal to the field label.
                $field['value'] = $this->get_list_labels($field);

                // If we have multiple values in from the list field...
                if (is_array($field['value'])) {
                    // ...convert our values into an array.
                    $field['value'] = implode(', ', $field['value']);
                }

                // Set callback and add this merge tag.
                $callback = 'field_' . $field_key . '_label';
                $this->add($callback, $field_key, '{field:' . $field_key . ':label}', $field['value']);
            }
        }

        // Call repeter-specific call backs
        if($isRepeater){
            $this->addFieldsetRepeaterCallbacks($field,$sanitize);
        }
    }

     /**
     * Generate repeater fieldset calc value based on the number of fieldsets filled by the user
     *
     * @param array $field Array of field information
     * @return int of the number of fieldsets used
     */
    protected function addRepeaterCalcValue( $field )
    {
        $fieldsets = [];
        if( isset($field['value']) && is_array($field['value']) ){
            foreach( $field['value'] as $fieldset_field_index => $fieldset_field ){
                if( ($pos = strpos($fieldset_field['id'], "_")) !== false){
                    $fieldset_number = substr($fieldset_field['id'], $pos + 1);
                    if(!in_array($fieldset_number,$fieldsets)){
                        array_push($fieldsets, $fieldset_number); 
                    }
                } else {
                    $fieldsets = [0];
                }
            }
        }

        return count( $fieldsets );
       
    }

    /**
     * Generate fieldset-specific outputs
     *
     * @param array $field Array of field information
     * @param bool $sanitize
     * @return void
     */
    protected function addFieldsetRepeaterCallbacks( $field, $sanitize)
    {
        $field_key =  $field['key'];

        // Create merge tag for table output
        $tableBase = 'table';
        $tableCallback = 'field_' . $field_key.'_'.$tableBase;
        $tableTag = '{field:' . $field_key .':'.$tableBase. '}';
        $tableValue = '<table>' . $this->generateFieldsetTableRows($field) . '</table>';
        $this->add($tableCallback, $field_key, $tableTag, $tableValue, false, $sanitize);
    }

    /**
     * Generate merge tag output for fieldset repeater table values
     *
     * @param array $field Array of field information
     * @return void
     */
    protected function generateFieldsetTableRows($field)
    {
        $outgoingValue = '';

        $list_fields_types = array('listcheckbox', 'listmultiselect', 'listradio', 'listselect');
        
        // Handle fieldset repeater
        $array = Ninja_Forms()->fieldsetRepeater->extractSubmissions($field['id'], $field['value'], $field['settings']);

        // Iterate submission indexes (each repeated fieldset in the submission)
        foreach ($array as $submissionIndex => $fieldsetArray) {

            $outgoingValue .= '<tr><td><b>' . $field['label'] . ' #:' . $submissionIndex . '</b></td></tr>';

            // Iterate each field within a submission index
            foreach ($fieldsetArray as $fieldsetFieldId => $submissionValueArray) {

                // Check to see if the type is a list field and if it is...
                if (in_array($submissionValueArray['type'], array_values($list_fields_types))) {

                    // implode the value if is_array
                    if (is_array($submissionValueArray['value'])) {
                        $submissionValueArray['value'] = implode(', ', $submissionValueArray['value']);
                    }
                }
                $outgoingValue .= '<tr><td valign="top">' . apply_filters('ninja_forms_merge_label', $submissionValueArray['label'], $field, $this->form_id) . ':</td><td>' . $submissionValueArray['value'] . '</td></tr>';
            }
        }

        return $outgoingValue;
    }


    /**
     * Get List Labels
     * Accepts a field loops over options, compares field values and returns the labels.
     * @since 3.2.22
     *
     * @param $field array
     * @return array - label of the option.
     */
    public function get_list_labels($field)
    {
        // Build our array to store our labels.
        $labels = array();
        // Loop over our options...
        $field['options'] = apply_filters('ninja_forms_render_options', $field['options'], $field);
        $field['options'] = apply_filters('ninja_forms_render_options_' . $field['type'], $field['options'], $field);
        $field['options'] = apply_filters('ninja_forms_localize_list_labels', $field['options'], $field, $this->form_id);
        if (empty($field['options'])) {
            return $field['value'];
        }
        foreach ($field['options'] as $options) {
            // ...checks to see if our list has multiple values.
            if (is_array($field['value'])) {
                // Loop over our values...
                foreach ($field['value'] as $value) {
                    // ...See if our values match...
                    if ($options['value'] == $value) {
                        // if they do build an array of the labels.
                        $labels[] = $options['label'];
                    }
                }
                // Otherwise if we are dealing with a single value, then...
            } elseif ($field['value'] == $options['value']) {
                // ...Set the label.
                $labels = $options['label'];
            }
        }
        return $labels;
    }

    /**
     * Add a callback value to the merge tags array
     * 
     * Keyed on callback string, contains an array with tag, value, optional
     * calc_value, sanitize boolean
     * 
     * @param $callback
     * @param $id
     * @param $tag
     * @param $value
     * @param bool $calc_value
     * @param bool $sanitize
     */
    public function add($callback, $id, $tag, $value, $calc_value = false, $sanitize = true)
    {
        $this->merge_tags[$callback] = array(
            'id'          => $id,
            'tag'         => $tag,
            'callback'    => $callback,
            'field_value' => $value,
            'calc_value'  => ($calc_value === false) ? $value : $calc_value,
        );
       
        if ($sanitize) {
            // $id is field admin key
            $this->merge_tags[$callback]['safe_value'] = $this->stripTagsMaybeFieldset($id,$value);
        }
    }

    public function set_form_id($form_id)
    {
        $this->form_id = $form_id;
    }

    public function maybe_sanitize($field)
    {
        $safe = apply_filters(
            'ninja_forms_get_html_safe_fields',
            array('html')
        );
        if (!in_array($field['type'], $safe) && $this->use_safe) {
            $field['value'] = $this->stripTagsMaybeFieldset($field['id'],$field['value']);
        }
        return $field;
    }

    /**
     * Strip shortcodes in value
     *
     * @param int|string $id
     * @param mixed $incoming Incoming value
     * @return mixed 
     */
    protected function stripShortcodesMaybeFieldset( $id,$incoming)
    {
        $type = $this->determineFieldType($id);

        if('repeater'===$type) {
            $outgoing = $incoming;
            // Iterate each repeater value
            foreach($incoming as $fieldsetFieldId=>$fieldsetFieldSubmissionValue ){
                
                // ensure key 'value' is set
                if(is_array($fieldsetFieldSubmissionValue)&& isset($fieldsetFieldSubmissionValue['value'])){
                    
                    // If value is array (e.g. listcheckbox), then strip each
                    // individual value
                    if(is_array($fieldsetFieldSubmissionValue['value'])){
                        $outgoing[$fieldsetFieldId]['value']=array_map('strip_shortcodes',$fieldsetFieldSubmissionValue['value']);                    
                    }else{
                        // If value is not array, strip shortcode
                        $outgoing[$fieldsetFieldId]['value']=strip_shortcodes($fieldsetFieldSubmissionValue['value']);
                    }
                }else{
                    // fallback strip shortcodes (all repeater values should be
                    // array so this should not fire)
                    $outgoing[$fieldsetFieldId]['value']=strip_shortcodes($incoming[$fieldsetFieldId]['value']);
                }
            }
        }else{
            // Strip shortcodes for non-repeater values
            $outgoing = strip_shortcodes($incoming);
        }
       
        return $outgoing;
    }

    /**
     * Strip tags in value
     *
     * @param int|string $id
     * @param mixed $incoming
     * @return mixed 
     */
    protected function stripTagsMaybeFieldset( $id,$incoming)
    {
        $type = $this->determineFieldType($id);

        if('repeater'===$type) {
            // Iterate each repeater value
            $outgoing = $incoming;
            if(is_array($incoming)){ 
                foreach($incoming as $fieldsetFieldId=>$fieldsetFieldSubmissionValue ){
                    // ensure key 'value' is set
                    if(is_array($fieldsetFieldSubmissionValue)&& isset($fieldsetFieldSubmissionValue['value'])){
                        
                        // If value is array (e.g. listcheckbox), then strip each
                        // individual value
                        if(is_array($fieldsetFieldSubmissionValue['value'])){
                            // If value is not array, strip tag       
                            $outgoing[$fieldsetFieldId]['value']=array_map('strip_tags',$fieldsetFieldSubmissionValue['value']);                    
                        }else{
                            // If value is not array, strip shortcode

                            $outgoing[$fieldsetFieldId]['value']=strip_tags($fieldsetFieldSubmissionValue['value']);
                        }
                    }else{
                        // fallback strip tag (all repeater values should be
                        // array so this should not fire)                  
                        $outgoing[$fieldsetFieldId]['value']=strip_tags($incoming[$fieldsetFieldId]['value']);
                    }
                }
            }
        }else{
            // Strip tags for non-repeater values
            if(is_array($incoming)){
                $outgoing = array_map('strip_tags',$incoming);
            }else{

                $outgoing = strip_tags($incoming);
            }
        }
       
        return $outgoing;
    }

    /**
     * Determine the field type given a field id or key
     *
     * @param string $id
     * @return string
     */
    protected function determineFieldType($id)
    {
        $type = '';

        if($id === (int)  $id){

            $type = Ninja_Forms()->form()->field($id)->get()->get_setting('type');
        }else{
            foreach( $this->merge_tags['all_fields']['fields'] as $field){

                if(
                    isset($field['key'])
                    && $field['key'] == $id
                    && isset($field['type'])
                ){
                    $type = $field['type'];
                    break;
                }
            }

        }
       
        return $type;
    }

    private function get_fields_sorted()
    {
        $fields = $this->merge_tags['all_fields']['fields'];
        $sorted = array();

        // Filterable Sorting for Add-ons (ie Layout and Multi-Part ).
        if (has_filter('ninja_forms_get_fields_sorted')) {
            $fields_by_key = $this->merge_tags['all_fields_by_key']['fields'];
            $sorted = apply_filters('ninja_forms_get_fields_sorted', array(), $fields, $fields_by_key, $this->form_id);
        }

        // If our filter didn't actually give us an order...
        if( empty($sorted) ) {
            // Default Sorting by Field Order.
            uasort($fields, array($this, 'sort_fields'));
        } else {
            $fields = $sorted;
        }

        return $fields;
    }

    public static function sort_fields($a, $b)
    {
        if ($a['order'] == $b['order']) {
            return 0;
        }
        return ($a['order'] < $b['order']) ? -1 : 1;
    }

    public function calc_replace($subject)
    {
        if (is_array($subject)) {
            foreach ($subject as $i => $s) {
                $subject[$i] = $this->replace($s);
            }
            return $subject;
        }
        //print_r($subject);

        preg_match_all("/{(.*?)}/", $subject, $matches);

        if (empty($matches[0])) return $subject;

        foreach ($this->merge_tags as $merge_tag) {

            if (!in_array($merge_tag['tag'], $matches[0])) continue;

            if (!isset($merge_tag['callback'])) continue;
            //print_r($merge_tag);
            //echo( ' = ' );

            $replace = (is_callable(array($this, $merge_tag['callback']))) ? $this->{$merge_tag['callback']}(array('calc' => true)) : '0';
            //print_r($replace);
            //echo('  myspace  ');
            if ('' == $replace) $replace = '0';

            $subject = str_replace($merge_tag['tag'], $replace, $subject);
        }

        return $subject;
    }

    /*
     |--------------------------------------------------------------------------
     | Calculations
     |--------------------------------------------------------------------------
     | Force {field:...:calc} in this context of calculations.
     |      Example: {field:list} -> {field:list:calc}
     | When parsing the {field:...:calc} tag, if no calc value is found then the value will be used.
     | TODO: This makes explicit list field "values" inaccessible in calculations.
     */

    public function pre_parse_calc_settings($eq)
    {
        return preg_replace_callback(
            '/{field:([a-z0-9]|_|-)*}/',
            array($this, 'force_field_calc_tags'),
            $eq
        );
    }

    private function force_field_calc_tags($matches)
    {
        return str_replace('}', ':calc}', $matches[0]);
    }
} // END CLASS NF_MergeTags_Fields
