<?php

namespace NinjaForms\Includes\Handlers;

use NinjaForms\Includes\Entities\SubmissionField;
use NinjaForms\Includes\Entities\SingleSubmission;

use NinjaForms\Includes\Handlers\SubmissionAggregate;
use NinjaForms\Includes\Handlers\Field;

/**
 * Adapts SubmissionAggregate to provide data required by CSV Export
 *
 * CSV Export requires specific structuring of submission data, too specialized
 * to be included in the SubmissionAggregate.  This class structures submission
 * data as needed for CSV export
 */
class SubmissionAggregateCsvExportAdapter
{

    /**
     * Field labels, keyed on field key
     *
     * @var array
     */
    protected $labels;

    /**
     * Admin field labels, keyed on field key
     *
     * @var array
     */
    protected $adminLabels;

    /**
     * Indexed array of field types to be omitted in output
     * @var array
     */
    protected $hiddenFieldTypes;

    /**
     * Array of field types keyed on field key
     * @var array
     */
    protected $fieldTypes = [];

    /**
     * Array of field Ids keyed on field key
     * @var array
     */
    protected $fieldIds = [];

    /**
     * Array of column values keyed on field key
     *
     * Property is reset and reused for each request; column count must equal
     * that of $labels, $adminLabels, $fieldtypes, and $fieldIds
     *
     * @var array
     */
    protected $columnValues = [];

    /**
     * @var SubmissionAggregate
     */
    public $submissionAggregate;

    /**
     * Construct with SubmissionAggregate
     *
     * Lookup array properties are not populated until the first request for
     * either fieldTypes, label/adminLable, or fieldIds.
     *
     * @param SubmissionAggregate $submissionAggregate
     */
    public function __construct(SubmissionAggregate $submissionAggregate)
    {
        $this->submissionAggregate = $submissionAggregate;
    }


    /**
     * Return array of field labels keyed on field keys
     * 
     * If hiddenFieldTypes array is set, labels filtered to hide those types
     * 
     * @param bool $useAdminLabels Optionally use admin_labels
     * @return array
     */
    public function getLabels(?bool $useAdminLabels = false): array
    {
        if (!isset($this->labels)) {
            $this->constructFieldLookups();
        }

        if ($useAdminLabels) {
            $return  = $this->adminLabels;
        } else {
            $return  = $this->labels;
        }

        return $return;
    }

    /**
     * Return array of field types keyed on field keys
     * 
     * @return array
     */
    public function getFieldTypes(): array
    {
        if (!isset($this->fieldTypes)) {
            $this->constructFieldLookups();
        }

        return $this->fieldTypes;
    }

    /**
     * Return array of field Ids keyed on field keys
     */
    public function getFieldIds(): array
    {
        if (!isset($this->fieldIds)) {
            $this->constructFieldLookups();
        }

        return $this->fieldIds;
    }

    /**
     * Return array of submission Ids in the collection
     * 
     * Generated at time of request to ensure it is up to date after last
     *  query / construction
     * @return array
     */
    public function getSubmissionIds(): array
    {
        $idArray = [];

        /** @var SingleSubmission $singleSubmission */
        foreach ($this->submissionAggregate->getAggregatedSubmissions() as $singleSubmission) {
            $idArray[] = $singleSubmission->getSubmissionRecordId();
        }

        return $idArray;
    }

    /**
     * Get column values for a given submission aggregated key
     *
     * @param string $aggregatedKey
     * @return array
     */
    public function getColumnValuesByAggregatedKey(string $aggregatedKey): array
    {
        $this->columnValues = [];
        $singleSubmission = $this->submissionAggregate->getSubmissionValuesByAggregatedKey($aggregatedKey);

        $populatedfieldDefinitionCollection = $singleSubmission->getSubmissionFieldCollection();

        foreach($populatedfieldDefinitionCollection as $populatedSubmissionField){
            $this->extractSubmissionFieldData($populatedSubmissionField,false);
        }

        return $this->columnValues;
    }

    /**
     * Construct labels/adminLabels from submission aggregate
     *
     * @return void
     */
    protected function constructFieldLookups(): void
    {
        // Initializee;
        $this->labels = [];
        $this->adminLabels = [];
        $this->fieldTypes = [];

        $fieldDefinitionCollection = $this->submissionAggregate->getFieldDefinitionCollection();

        /** @var SubmissionField $submissionField */
        foreach ($fieldDefinitionCollection as $submissionField) {

            $this->extractSubmissionFieldData($submissionField, true);
        }
    }

    /**
     * Given submission field, extract labels + meta, or submission values
     *
     * @param SubmissionField $submissionField
     * @param boolean|null $labelsOnly
     * @return void
     */
    protected function extractSubmissionFieldData(SubmissionField  $submissionField, ?bool $labelsOnly = false): void
    {
        // omit from collection if type is part of hidden field type collection
        if (
            isset($this->hiddenFieldTypes) &&
            in_array($submissionField->getType(), $this->hiddenFieldTypes)
        ) {
            return;
        }

        if ('repeater' === $submissionField->getType()) {
 
            $this->extractRepeaterFieldColumns($submissionField, $labelsOnly);
            return;
        }

        if ($labelsOnly) {

            $this->setFieldMetaData($submissionField);

            $this->setFieldLabels($submissionField);
            return;
        }

        $key = $submissionField->getSlug();

        if(''==$key){
            $key=$submissionField->getId();
        }

        $rawValue = $submissionField->getValue();

        $nfField = $this->convertSubmissionFieldToNfField($submissionField);

        $filteredValue = $this->filterRawValue($key,$rawValue, $nfField);

        if(\is_array($filteredValue)){
            $filteredValue = \implode(',',$filteredValue);
        }

        $this->setColumnValue($key,$filteredValue);
    }


    /**
     * Construct NF Field from SubmissionField
     * 
     * NF Field is needed to apply existing NF filters
     *
     *
     * @param SubmissionField $submissionField
     * @return Field
     */
    protected function convertSubmissionFieldToNfField(SubmissionField $submissionField):Field
    {      
        $nfField = Field::fromArray(
            [
                'id'=>$submissionField->getId(),
                'type'=>$submissionField->getType(),
                'settings'=>$submissionField->getOriginal()           ]
        );

        return $nfField;
    }

    /**
     * Extract repeater field column headers
     *
     * @todo Enable external setting of in-CSV delimiter
     * @todo ~L258 - adjust deconstructed value to handle listmultiselect arrays (other fields may also have arrays)
     * @param SubmissionField $submissionField
     * @return void
     */
    protected function extractRepeaterFieldColumns(SubmissionField $submissionField, bool $labelsOnly): void
    {
        $repeaterFieldsCollection =$this->extractRepeaterFieldsFromSubmisionField($submissionField);
        
        if (empty($repeaterFieldsCollection)) {
            return;
        }

        $deconstructedValues = $this->deconstructRepeaterFieldValue((array)$submissionField->getValue());
       
        // iterate each SubmissionField within the repeater fields collection
        foreach ($repeaterFieldsCollection as $repeaterField) {
                
            if( !in_array($repeaterField->type, $this->hiddenFieldTypes) ){

                if($labelsOnly){

                    $this->setFieldMetaData($repeaterField);
                    $this->setFieldLabels($repeaterField);
                    continue;
                }

                $nfField = $this->convertSubmissionFieldToNfField($repeaterField);
                
                $filteredValues = [];
                $filteredValue = "";
                $id = $repeaterField->getId();
                $key = $repeaterField->getSlug();

                if(''==$key){
                    $key=$repeaterField->getId();
                }

                if(isset($deconstructedValues[$id])){

                    foreach($deconstructedValues[$id] as $rawRepeatedValueArray){
            
                        $rawRepeatedValue = $rawRepeatedValueArray['value'];

                        $filteredValue = $this->filterRawValue($key,$rawRepeatedValue, $nfField);

                        if(\is_array($filteredValue)){
                            $filteredValue = \implode(',',$filteredValue);
                        }

                        $filteredValues[] = $filteredValue;

                    }
                }

                //Construct an array of rows instead of column value if repeater was repeated (index that array by "repeater" for later reference)
                if( count($filteredValues) > 0){
                    $this->setColumnValue("repeater", [$key, $filteredValues]);
                } else {
                    $this->setColumnValue($key, $filteredValue);
                }

            }
        }
    }

    /**
     * Extracts collection of SubmissionFields within a fieldset repeater
     *
     * @param SubmissionField $submissionField
     * @return SubmissionField[]
     */
    protected function extractRepeaterFieldsFromSubmisionField(SubmissionField $submissionField): array
    {
        $return =[];

        $repeaterFieldsArray = $submissionField->getFieldsetRepeaterFields();
        $keyedFieldSettings = $this->constructRepeaterFieldSettingsLookup($submissionField);

        // iterate each SubmissionField within the repeater fields collection
        foreach ($repeaterFieldsArray as $repeaterFieldArray) {

            if (isset($repeaterFieldArray['key'])) {
                $repeaterFieldArray['slug'] = $repeaterFieldArray['key'];
                unset($repeaterFieldArray['key']);
            }

            if (isset($repeaterFieldArray['admin_label'])) {
                $repeaterFieldArray['adminLabel'] = $repeaterFieldArray['admin_label'];
                unset($repeaterFieldArray['admin_label']);
            }

            $id = isset($repeaterFieldArray)?$repeaterFieldArray['id']:'';

            if (isset($keyedFieldSettings[$id])) {
                $repeaterFieldArray['original'] = $keyedFieldSettings[$id];
            } else {
                $repeaterFieldArray['original'] = [];
            }


            $repeaterField = SubmissionField::fromArray($repeaterFieldArray);

            $return[] = $repeaterField;
        }

        return $return;
    }


    /**
     * Construct lookup of field settings for fields within fieldset repeater field
     *
     * @param SubmissionField $submissionField
     * @return array
     */
    protected function constructRepeaterFieldSettingsLookup(SubmissionField $submissionField): array
    {
        $keyedFieldSettings = [];

        $originalFieldSettings = $submissionField->getOriginal();

        if (!empty($originalFieldSettings['fields'])) {

            $nestedFieldKeys = \array_column($originalFieldSettings['fields'], 'id');

            $keyedFieldSettings = \array_combine($nestedFieldKeys, $originalFieldSettings['fields']);
        }

        return $keyedFieldSettings;
    }

    /**
     * Ensure each repeater field value is a string
     *
     * @param SubmissionField $submissionField
     * @param array $deconstructedValue
     * @param string $repeatedValueDelimiter
     * @return string
     */
    protected function getStringedValue(SubmissionField $submissionField, array $deconstructedValue, string $repeatedValueDelimiter): string
    {
        $arrayTypes =['listmultiselect'];

        $valueColumn = \array_column( $deconstructedValue,'value');

        if(\in_array($submissionField->getType(),$arrayTypes)){
        
            foreach($valueColumn as &$value){
                $value=implode(',',$value);
            }
        }
        $return = implode($repeatedValueDelimiter,$valueColumn);

        return $return;
    }

    /**
     * Deconstruct repeater field array by repeated fields
     * 
     * 
     * @todo Add exception handling for unexpected key structure
     * @param array $constructedValue
     * @return array
     */
    protected function deconstructRepeaterFieldValue(array $constructedValue): array
    {
        $delimiter = '_';

        $return = [];
        foreach ($constructedValue as $constructedKey => $submissionValue) {

            $exploded = explode($delimiter,$constructedKey);

            if(isset($exploded[1])){

                $return[$exploded[0]][$exploded[1]]=$submissionValue;
            }
        }

        return $return;
    }

    /**
     * Add key value lookups for labels and admin labels
     *
     * @param string $slug
     * @param string $label
     * @param string $adminLabel
     * @return void
     */
    protected function setFieldLabels(SubmissionField $submissionField): void
    {
        $slug = $submissionField->getSlug();

        if(''==$slug){
            $slug=$submissionField->getId();
        }

        $label = $submissionField->getLabel();

        $adminLabel = $submissionField->getAdminLabel();

        $this->labels[$slug] = \WPN_Helper::maybe_escape_csv_column( $label );

        if ('' !== $adminLabel) {
            $this->adminLabels[$slug] = \WPN_Helper::maybe_escape_csv_column( $adminLabel );
        } else {
            // set adminLabel default as field label
            $this->adminLabels[$slug] = $this->labels[$slug];
        }
    }

    /**
     * Add key value lookups for Id and type on all fields in collection
     * 
     * This includes hidden fields and parent fieldset repeater fields
     *
     * @param SubmissionField $submissionField
     * @return void
     */
    protected function setFieldMetaData(SubmissionField $submissionField): void
    {
        $slug = $submissionField->getSlug();

        if(''==$slug){
            $slug = $submissionField->getId();
        }

        $this->fieldTypes[$slug] = $submissionField->getType();
        $this->fieldIds[$slug] = $submissionField->getId();
    }

    /**
     * Set column value for a given field
     *
     * @param string $key
     * @param string||array $value
     * @return void
     */
    protected function setColumnValue(string $key, $value): void
    {

        if($key === "repeater" && is_array($value)){
            $this->columnValues['repeater'][$value[0]] = $value[1];
        } else {
            $this->columnValues[$key] = $value;
        }
        
    }

    protected function filterRawValue( string $key, $rawValue, Field $nfField)
    {
        $formId = $this->submissionAggregate->getMasterFormId();

        $fieldId=$this->fieldIds[$key];
        $fieldType = $this->fieldTypes[$key];

        $filtered = apply_filters('nf_subs_export_pre_value', $rawValue, $fieldId);
        $filtered = apply_filters('ninja_forms_subs_export_pre_value', $filtered, $fieldId, $formId);
        $filtered = apply_filters('ninja_forms_subs_export_field_value_' . $fieldType, $filtered, $nfField);  

        return $filtered;
    }
    /**
     * Set indexed array of field types to be omitted in output
     *
     * @param  array  $hiddenFieldTypes  Indexed array of field types to be omitted in output
     *
     * @return  SubmissionAggregateCsvExportAdapter
     */
    public function setHiddenFieldTypes(array $hiddenFieldTypes): SubmissionAggregateCsvExportAdapter
    {
        $this->hiddenFieldTypes = $hiddenFieldTypes;

        return $this;
    }
}
