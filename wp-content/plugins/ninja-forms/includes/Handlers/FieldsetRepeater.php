<?php

/**
 * Handles data of a FieldsetRepeater
 * 
 * Fieldset repeater field data are stored as part of the single fieldset
 * repeater field.  This includes both settings and submission data.  Since these
 * data are not managed by NF standard data handling, this class manages it.
 * 
 * Requests for a field can be made by either an (int) field id or a 
 * (string) field reference, which prior to fieldset repeaters had been
 * for the field key only.  
 * 
 * Fieldset fields are stored as {fieldsetRepeaterFieldId}{fieldsetDelimiter}{fieldsetFieldId}{submissionIndexDelimiter}{submissionIndex}
 * 
 * FieldSettings are passed into this class so that this class is not dependent
 * on NF core
 */
class NF_Handlers_FieldsetRepeater
{

    /**
     * Delimiter separating fieldId from fieldsetFieldId
     * 
     * Fieldset fields are individual fields within a fieldset.
     * 
     * @var string
     */
    protected $fieldsetDelimiter = '.';

    /**
     * Delimiter that uniquely identifies multiple fieldset repeater submissions
     * 
     * Fieldset Repeaters can have multiple values submitted on any given 
     *  submission.  Each repeated value for a field in the fieldset is
     * delimited in the submission data with an incremented index value
     * @var string
     */
    protected $submissionIndexDelimiter = '_';

    /**
     * Returns labels for the fieldset's fields keyed on id of each fieldset field
     * 
     * @param string $fieldId ID of the Fieldset Repeater field
     * @param array $fieldSettings Provided by (obj)$field->get_settings()
     * @param bool $useAdminLabels
     * @return array
     */
    public function getFieldsetLabels($fieldId, $fieldSettings, $useAdminLabels = false)
    {

        // Default is fieldset's label
        if ($useAdminLabels && !empty($fieldSettings['admin_label'])) {
            $label = $fieldSettings['admin_label'];
        } else {
            $label = $fieldSettings['label'];
        }

        // If this isn't the expected 'repeater' type, 
        // or if fields definition isn't set, return default
        if (
            'repeater' !== $fieldSettings['type'] ||
            !isset($fieldSettings['fields']) ||
            !is_array($fieldSettings['fields'])
        ) {
            return array($fieldId => $label);
        }

        $labels = array();

        foreach ($fieldSettings['fields'] as $field) {
            $id = $field['id'];

            if ($useAdminLabels && '' !== $field['admin_label']) {
                $label = $field['admin_label'];
            } else {
                $label = $field['label'];
            }

            $labels[$id] = $label;
        }

        return $labels;
    }

    /**
     * Returns fieldsetField types keyed on fieldsetField ids
     * @param string $fieldId ID of the Fieldset Repeater field
     * @param array $fieldSettings Provided by (obj)$field->get_settings()
     * @return array
     */
    public function getFieldsetTypes($fieldId, $fieldSettings)
    {

        $fieldsetFieldTypes = [];

        // If this isn't the expected 'repeater' type, 
        // or if fields definition isn't set, return default
        if (
            'repeater' !== $fieldSettings['type'] ||
            !isset($fieldSettings['fields']) ||
            !is_array($fieldSettings['fields'])
        ) {
            return $fieldsetFieldTypes;
        }


        foreach ($fieldSettings['fields'] as $field) {
            $idArray = $this->parseFieldsetFieldReference($field['id']);
            $id = $fieldId . $this->fieldsetDelimiter . $idArray['fieldsetFieldId'];
            $type = $field['type'];


            $fieldsetFieldTypes[$id] = $type;
        }

        return $fieldsetFieldTypes;
    }

    /**
     * Given a field reference (ID or Key), return boolean for 'is repeater field'
     * 
     * Determines if the given field reference is a fieldset repeater construct.
     *  This is NOT the parent field; this is a request for a child field within
     *  the fieldset repeater.  The field settings and values for such a field
     *  are stored differently than a standard field, so we need to know how
     *  to make requests for its settings/data.
     * 
     * For disambiguation, a fieldset repeater field
     * request for a specific field within the fieldset is in the form of: 
     * {fieldsetFieldId}{fieldsetDelimiter}{submissionIndexDelimiter}
     * 
     * 
     * @param int|string $fieldReference ID or key for the field
     * @return bool
     */
    public function isRepeaterFieldByFieldReference($fieldReference)
    {

        $return = false;

        $exploded = explode($this->fieldsetDelimiter, $fieldReference);

        if (isset($exploded[1])) {
            $return = true;
        }

        return $return;
    }

    /**
     * Determine if data matches fieldset repeater construct
     *
     * When given only a submission value without any meta data, check the
     * construct of the value to asssert with some level of confidence that the
     * value is from a fieldset repeater.
     *
     * Logic: 
     *  - is submission empty? then NO, we don't assert is is fieldset repeater
     *    data
     *  - can the array key be parsed as a fieldset repeater key?  If not, then
     *    NO...
     *  - is each value an array with 'id' and 'value' keys, and the `id`
     *    matches the id of its parent?  If not, then NO...
     *
     * If  all the above conditions  are met for every entry in the submission,
     * we assert that the submission value is that of a fieldset repeater.
     * 
     *  
     * @param array $submission
     * @return boolean
     */
    public function isFieldsetData(array $submission)
    {
        $return = true;

        // If not array containing data, not fieldset repeater
        if (empty($submission)) {
            $return = false;
        }

        foreach($submission as $key=>$submissionValueArray){
            $submissionReference = $this->parseFieldsetFieldReference($key);

            if(-1===$submissionReference){
                $return = false;
            }

            if(!isset($submissionValueArray['id']) || $key!==$submissionValueArray['id'] || !isset($submissionValueArray['value'])){
                $return = false;
            }
        }

        return $return;
    }


    /**
     * Parse field id, fieldset id, and submission index
     *
     * Returns array of fieldId, fieldsetFieldId, submissionId
     * If failing, fieldsetFieldId = -1
     * 
     * @param string $reference
     * @return array
     */
    public function parseSubmissionReference( $reference)
    {   
        $fieldset= $this->parseFieldsetFieldReference($reference);
        $fieldId=$fieldset['fieldId'];
        $submissionIndex = $this->parseSubmissionIndex($fieldset['fieldsetFieldId']);
        $fieldsetFieldId=$submissionIndex['fieldsetFieldId'];
        $submissionId=$submissionIndex['submissionIndex'];

        $return = array(
            'fieldId' => $fieldId,
            'fieldsetFieldId' => $fieldsetFieldId,
            'submissionId'=>$submissionId
        );

        return $return;
    }
    /**
     * Given field reference, return field Id and fieldset field id
     * 
     * Fieldset field is a field within the fieldset repeater.  The child's field
     *  settings and its submission data are not stored individually in the
     *  field or submission tables, but rather as nested data inside the
     *  parent's keyed location.
     * 
     * Caller should ensure field is fieldset type before calling.
     * 
     * @param string $fieldReference
     *
     * @return array Keys: 'fieldId', 'fieldsetFieldId'
     */
    public function parseFieldsetFieldReference($fieldReference)
    {

        $return = array(
            'fieldId' => 0,
            'fieldsetFieldId' => 0
        );

        if ($this->isRepeaterFieldByFieldReference($fieldReference)) {

            $exploded = explode($this->fieldsetDelimiter, $fieldReference);

            $return = array(
                'fieldId' => $exploded[0],
                'fieldsetFieldId' => $exploded[1]
            );
        }


        return $return;
    }
    /**
     * Parses fieldsetFieldId and submissionIndex keys 
     *
     * Given string of expect fieldsetField and submissionIndex as a key under
     * which submission data is stored, returns the fieldsetFieldId and
     * submissionIndex id 
     * 
     * If cannot be parsed as expected, default values of -1 are returned to
     * notify of failure
     * 
     * @param string $submissionIndex
     * @return array
     */
    public function parseSubmissionIndex($submissionIndex)
    {

        $return = [];

        $exploded = explode($this->submissionIndexDelimiter, $submissionIndex);

        $fieldsetFieldId = $exploded[0];

        if (isset($exploded[1])) {
            $submissionIndex=$exploded[1];
        } else {
            $submissionIndex = 0; // if no index present, set as -1 for an un-repeated fieldset
        }

        $return = array(
            'fieldsetFieldId' => $fieldsetFieldId,
            'submissionIndex' => $submissionIndex
        );


        return $return;
    }
    /**
     * Returns field type of a field within a fieldset, given the field reference
     * 
     * Field reference is the id of the field WITHIN the fieldset.  The fieldset
     *  has a numerical field id under which all settings and submission values
     *  are stored for any field within the fieldset.  Access to that setting
     *  and submission data are not handled by the standard core functions and
     *  are done through this class.
     * 
     * @param string $fieldsetFieldId Fieldset Field reference
     * @param array $fieldSettings Field settings (from (obj)$field->get_settings())
     * @return string
     */
    public function getFieldtype($fieldsetFieldId, $fieldSettings)
    {

        $return = 'unknown';

        if (!isset($fieldSettings['fields'])) {
            return $return;
        }

        // Ids for fieldset fields
        $idLookup = array_column($fieldSettings['fields'], 'id');

        if (in_array($fieldsetFieldId, $idLookup)) {
            $indexLookup = array_flip($idLookup);

            $return = $fieldSettings['fields'][$indexLookup[$fieldsetFieldId]]['type'];
        }

        return $return;
    }

    /**
     * Extract all repeater submission values for a given fieldset field
     * 
     * Fieldset data is all stored within the main fieldset field.  To prevent
     *  every caller from having to know the internal structure of the stored
     *  data, this method enables callers to provide the requested Fieldset
     *  Field's reference id with the full submission data and receive in 
     *  return all the submitted values for that given field.
     * 
     * @param string $fieldsetFieldId Fieldset Field reference
     * @param array $fieldSubmissionValue Submission data for entire fieldset
     */
    public function extractSubmissionsByFieldsetField($fieldsetFieldId, $fieldSubmissionValue)
    {

        $return = array();

        foreach ($fieldSubmissionValue as $submissionId => $submissionValueArray) {
            $exploded = explode($this->submissionIndexDelimiter, $submissionId);

            if ($fieldsetFieldId === $exploded[0]) {
                $return[] = $submissionValueArray;
            }
        }

        return $return;
    }


    /**
     * Extract fieldset repeater submissions by submission index and fieldset
     * field
     *
     * Unknown values can be passed as empty string or arrays; the method will
     * fill in what it can and set default values for those it can't
     * 
     * @todo Refactor this method after unit testing is in place.  It is being
     * used to share a common structure for output but refactoring should wait
     * until unit testing can ensure the data structure of responses don't
     * change during refactor.
     *
     * @param string $fieldId
     * @param array $fieldSubmissionValue Submission data array for entire field
     * @param array $fieldSettings Field settings (from
     * (obj)$field->get_settings())
     * @return array Array of submission values
     *
     * {submissionIndex}=> {fieldsetFieldId}=>['value'=>{submitted value}
     *      'type'=> {field type}, 'label'=> {label}
     * ]
     */
    public function extractSubmissions($fieldId, $fieldSubmissionValue, $fieldSettings, $useAdminLabels = false)
    {
        $return = [];

        if(is_string($fieldSubmissionValue)){
            $fieldSubmissionValue = maybe_unserialize($fieldSubmissionValue);
        }

        if (!is_array($fieldSubmissionValue)) {
            return $return;
        }

        if(is_null($fieldSettings)){
            $fieldSettings = Ninja_Forms()->form()->get_field( $fieldId )->get_settings();
        }

        if(''!==$fieldId and []!== $fieldSettings){

            $fieldsetLabelLookup = $this->getFieldsetLabels($fieldId, $fieldSettings);
            $fieldsetTypeLookup = $this->getFieldsetTypes($fieldId,$fieldSettings);
        }else{
            
            $fieldsetLabelLookup = null;
            $fieldsetTypeLookup = null;
        }


        // $completeFieldsetID is in format {fieldsetRepeaterFieldId}{fieldsetDelimiter}{fieldsetFieldId}{submissionIndexDelimiter}{submissionIndex}
        foreach ($fieldSubmissionValue as $completeFieldsetId => $incomingValueArray) {

            //Extract value from upload field
            if(isset($incomingValueArray["files"])){
                $field_files_names = [];
                foreach($incomingValueArray["files"] as $file_data){
                    $field_files_names[] = '<a href="' .  $file_data["data"]["file_url"] . '" title="' . $file_data["data"]["upload_id"] . '">' . $file_data["name"] . '</a>';
                }
                $incomingValueArray['value'] = implode(" , ", $field_files_names);
            }
            // value is expected to be keyed inside incoming value array
            if (isset($incomingValueArray['value'])) {
                $value = $incomingValueArray['value'];
            } else {
                $value = $incomingValueArray;
            }

            // attempt parsing of fielsetField; if any fail, exit as data is corrupt
            $fieldsetWithSubmissionIndex = $this->parseFieldsetFieldReference($completeFieldsetId);

            if (0 == $fieldsetWithSubmissionIndex['fieldsetFieldId']) {

                return $return;
            }

            $parsedSubmissionIds = $this->parseSubmissionIndex($fieldsetWithSubmissionIndex['fieldsetFieldId']);

            if (-1 === $parsedSubmissionIds['submissionIndex']) {
                return $return;
            }

            $fieldsetFieldId = $parsedSubmissionIds['fieldsetFieldId'];


            $submissionIndex = $parsedSubmissionIds['submissionIndex'];

            $idKey = $fieldId . $this->fieldsetDelimiter . $fieldsetFieldId;

            if(is_null($fieldsetTypeLookup)){
                $fieldsetFieldType='';
            }else{
                $fieldsetFieldType = $fieldsetTypeLookup[$idKey];
            }
            
            if(is_null($fieldsetLabelLookup)){
                $fieldsetFieldLabel='';
            }else{
                $fieldsetFieldLabel = $fieldsetLabelLookup[$idKey];
            }

            $array = [];
            $array['value'] = $value;
            $array['type'] = $fieldsetFieldType;
            $array['label'] = $fieldsetFieldLabel;
            
            if(!empty( $incomingValueArray['files'] ) ){
                $array['files'] = $incomingValueArray['files'];
            }

            $return[$submissionIndex][$fieldsetFieldId] = $array;
        }

        return $return;
    }
}
