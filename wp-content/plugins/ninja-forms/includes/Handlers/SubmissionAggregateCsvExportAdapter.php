<?php

namespace NinjaForms\Includes\Handlers;

use NinjaForms\Includes\Entities\SubmissionField;
use NinjaForms\Includes\Entities\SingleSubmission;

use NinjaForms\Includes\Handlers\SubmissionAggregate;

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
     * @var SubmissionAggregate
     */
    public $submissionAggregate;

    /**
     * Construct with SubmissionAggregate
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
            $slug = $submissionField->getSlug();

            $this->fieldTypes[$slug] = $submissionField->getType();
            $this->fieldIds[$slug]=$submissionField->getId();

            // omit from collection if type is part of hidden field type collection
            if (
                isset($this->hiddenFieldTypes) &&
                in_array($submissionField->getType(), $this->hiddenFieldTypes)
            ) {
                continue;
            }

            $this->labels[$slug] = $submissionField->getLabel();

            // set adminLabel default as field label
            $this->adminLabels[$slug] = $this->labels[$slug];
            
            // If adminLabel is not empty, use that value for admin
            $adminLabel = $submissionField->getAdminLabel();
            if(''!==$adminLabel){
                $this->adminLabels[$slug] = $adminLabel;
            }
        }
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
