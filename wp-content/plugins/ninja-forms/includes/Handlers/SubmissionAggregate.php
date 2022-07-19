<?php

namespace NinjaForms\Includes\Handlers;

use NinjaForms\Includes\Contracts\SubmissionDataSource;
use NinjaForms\Includes\Entities\SingleSubmission;
use NinjaForms\Includes\Entities\SubmissionField;
use NinjaForms\Includes\Entities\SubmissionFilter;
use NinjaForms\Includes\Handlers\Field;

/**
 * Aggregates submissions from all provided data sources
 *
 * Data sources include NF submissions stored as posts; may include pre-existing
 * Caldera Forms submissions
 */
class SubmissionAggregate
{

    /**
     * NF Id of the master form
     *
     * There can be multiple forms in the aggregate; the master form defines the
     * fields and the data within the aggregate.
     *
     * @var string
     */
    protected $masterFormId = '';

    /** @var SubmissionFilter */
    protected $submissionFilter;

    /**
     * Collection of submission data sources
     *
     * @var SubmissionDataSource[];
     */
    protected $dataSourceCollection = [];

    /**
     * Collection of all SingleSubmissions
     * 
     * Indexed collection of submissions meeting the filtering parameters
     *
     * @var SingleSubmission[]
     */
    protected $aggregatedSubmissions = [];

    /**
     * Collection of SubmissionFields defining the form
     *
     * @var SubmissionField[]
     */
    protected $submissionFieldCollection = [];

    /**
     * Filter submissions to return a collection of SingleSubmission meta data only
     *
     * @param string $formId
     * @return array
     */
    public function filterSubmissions(SubmissionFilter $submissionFilter): array
    {
        $this->submissionFilter = $submissionFilter;

        $formIdCollection = $this->submissionFilter->getNfFormIds();

        if (!empty($formIdCollection)) {
            $masterFormId = $formIdCollection[0];
            $this->constructFieldDefinitionCollection($masterFormId);
        }

        $aggregated = [];

        if (!empty($this->dataSourceCollection)) {
            foreach ($this->dataSourceCollection as $dataSource) {

                $submissionsFromDataSource = $dataSource->retrieveSubmissionMeta($this->submissionFilter);

                $aggregated = array_merge($aggregated, $submissionsFromDataSource);
            }
        }

        foreach ($aggregated as $singleSubmission) {
            $key = $this->constructUniqueAgreggatedSubmissionKey($singleSubmission);

            $submissionIDs = $this->submissionFilter->getSubmissionsIDs();

            //Add singleSubmission to aggregated collection if no submissions IDs were set or if it has correct ID
            if( in_array( $singleSubmission->submissionRecordId, $submissionIDs ) || empty( $submissionIDs ) ){
                $this->aggregatedSubmissions[$key] = $singleSubmission;
            }
        }

        uasort($this->aggregatedSubmissions, function ($a, $b) {
            return $b->getTimestamp() <=> $a->getTimestamp();
        });

        return $this->aggregatedSubmissions;
    }

    /**
     * Retrieve a single submission populated with submission/extra values
     *
     * Also populates the submissionAggregate such that the aggregate can be
     * passed for handling elsewhere
     * 
     * @param SingleSubmission $singleSubmission
     * @return SingleSubmission
     */
    public function requestSingleSubmission(SingleSubmission $singleSubmission): SingleSubmission
    {        
        $this->masterFormId = $singleSubmission->getFormId();
        
        $this->constructFieldDefinitionCollection($this->masterFormId);

        $singleSubmission->setSubmissionFieldCollection($this->submissionFieldCollection);

        $dataSourceKey = $singleSubmission->getDataSource();
        
        if(isset($this->dataSourceCollection[$dataSourceKey])){
        
            $dataSource=$this->dataSourceCollection[$dataSourceKey];

            $singleSubmission = $dataSource->retrieveSingleSubmission($singleSubmission);

        }

        $key = $this->constructUniqueAgreggatedSubmissionKey($singleSubmission);

        $this->aggregatedSubmissions[$key] = $singleSubmission;
 
        return $singleSubmission;
    }

    /**
     * Retrieve a submissions by precise list of submissions IDs
     * 
     * @param SubmissionFilter $submissionFilter
     * @return SubmissionFilter
     */
    public function requestSubmissionsByIds(SubmissionFilter $submissionFilter): SubmissionFilter
    {        
        $this->masterFormId = $singleSubmission->getFormId();
        
        $this->constructFieldDefinitionCollection($this->masterFormId);

        $submissions->setSubmissionFieldCollection($this->submissionFieldCollection);

        $dataSourceKey = $singleSubmission->getDataSource();
        
        if(isset($this->dataSourceCollection[$dataSourceKey])){
        
            $dataSource=$this->dataSourceCollection[$dataSourceKey];

            $singleSubmission = $dataSource->retrieveSingleSubmission($singleSubmission);

        }

        $key = $this->constructUniqueAgreggatedSubmissionKey($singleSubmission);

        $this->aggregatedSubmissions[$key] = $singleSubmission;
 
        return $singleSubmission;
    }

    /**
     * Retrieve submissionValues from submission at a given aggregated key
     *
     * @param string $key
     * @return SingleSubmission
     * @see constructUniqueAgreggatedSubmissionKey()
     */
    public function getSubmissionValuesByAggregatedKey(string $key): SingleSubmission
    {
        /** @var SubmissionDataSource $dataSource */

        if (isset($this->aggregatedSubmissions[$key])) {

            $singleSubmission = $this->aggregatedSubmissions[$key];

            if (empty($singleSubmission->getSubmissionFieldCollection())) {

                $singleSubmission->setSubmissionFieldCollection($this->submissionFieldCollection);
                $dataSource = $this->dataSourceCollection[$singleSubmission->getDataSource()];

                $populatedSingleSubmission = $dataSource->retrieveSubmissionValues($singleSubmission);
            } else {

                $populatedSingleSubmission = $singleSubmission;
            }
        } else {

            $populatedSingleSubmission = SingleSubmission::fromArray([]);
        }

        // create a new object to avoid object-by-reference that updates all submissions in the collection
        $this->aggregatedSubmissions[$key] = SingleSubmission::fromArray($populatedSingleSubmission->toArray());

        return $this->aggregatedSubmissions[$key];
    }

    /**
     * Delete a single submission
     *
     * @param SingleSubmission $singleSubmission
     * @return SubmissionAggregate
     */
    public function deleteSingleSubmission(SingleSubmission $singleSubmission): SubmissionAggregate
    {
        $dataSourceKey = $singleSubmission->getDataSource();
        
        if(isset($this->dataSourceCollection[$dataSourceKey])){

            $dataSource = $this->dataSourceCollection[$dataSourceKey];
            
            $dataSource->deleteSubmission($singleSubmission);
        }

        return $this;
    }

    /**
     * Restore a single submission
     *
     * @param SingleSubmission $singleSubmission
     * @return SubmissionAggregate
     */
    public function restoreSingleSubmission(SingleSubmission $singleSubmission): SubmissionAggregate
    {
        $dataSourceKey = $singleSubmission->getDataSource();
        
        if(isset($this->dataSourceCollection[$dataSourceKey])){

            $dataSource = $this->dataSourceCollection[$dataSourceKey];
            
            $dataSource->restoreSubmission($singleSubmission);
        }

        return $this;
    }

    /**
     * Update a single submission
     *
     * @param SingleSubmission $singleSubmission
     * @return SubmissionAggregate
     */
    public function updateSingleSubmission(SingleSubmission $singleSubmission): SubmissionAggregate
    {
        $dataSourceKey = $singleSubmission->getDataSource();
 
        if(isset($this->dataSourceCollection[$dataSourceKey])){

            $dataSource = $this->dataSourceCollection[$dataSourceKey];
            
            $dataSource->updateSubmission($singleSubmission);
        }

        return $this;
    }


    /**
     * Construct field definition collection from formId
     *
     * @param string $formId
     * @return void
     */
    protected function constructFieldDefinitionCollection(string $formId): void
    {
        $nfFieldsCollection = $this->getFieldsCollection($formId);

        if (!empty($nfFieldsCollection)) {

            /** @var Field $nfField */
            foreach ($nfFieldsCollection as $id => $nfField) {
                $slug = $nfField->get_setting('key');
                $fieldSettings = $nfField->get_settings();
                
                $fieldOptionDefinition = $nfField->get_setting('options',[]);
                $fieldsetRepeaterFields = $nfField->get_setting('fields',[]);

                if(!empty($fieldOptionDefinition)){
                    foreach($fieldOptionDefinition as $optionDefinition){
                        $options = [
                            'label'=> $optionDefinition['label'],
                            'value'=> $optionDefinition['value'],
                            'calc'=> $optionDefinition['calc'],
                            'selected'=> $optionDefinition['selected'],
                            'order'=> $optionDefinition['order']
                         ];

                         $optionsCollection[]=$options;
                    }

                }else{
                    $optionsCollection = [];
                }

                $array = [
                    'id'            => (string)$id,
                    'slug'          => $slug,
                    'label'         => $nfField->get_setting('label'),
                    'adminLabel'    => $nfField->get_setting('admin_label'),
                    'type'          => $nfField->get_setting('type'),
                    'options'       => $optionsCollection,
                    'fieldsetRepeaterFields'=>$fieldsetRepeaterFields,
                    'original'      => $fieldSettings
                ];

                $this->submissionFieldCollection[$slug] = SubmissionField::fromArray($array);
            }

        }
    }

    /**
     * Return the Ninja Forms field collection
     *
     * @param string $formId
     * @return array
     */
    protected function getFieldsCollection(string $formId): array
    {
        $return = \Ninja_Forms()->form($formId)->get_fields();

        return $return;
    }

    /**
     * Construct a unique aggregated submission key for each submission
     *
     * Uses the dataSource's id plus the submission record id.  Each submission
     * is is unique within its dataSource, and each dataSource is unique, thus
     * the combined string is unique
     *
     * @param SingleSubmission $singleSubmission
     * @return string
     */
    protected function constructUniqueAgreggatedSubmissionKey(SingleSubmission $singleSubmission): string
    {
        $key = $singleSubmission->getDataSource() . '__' . $singleSubmission->getSubmissionRecordId();

        return $key;
    }

    /**
     * Set collection of submission data sources
     *
     * @param  SubmissionDataSource  $dataSource Submission data source
     *
     * @return  SubmissionAggregate
     */
    public function addDataSource(SubmissionDataSource $dataSource): SubmissionAggregate
    {
        $this->dataSourceCollection[$dataSource->getDataSource()] = $dataSource;

        return $this;
    }

    /**
     * Get collection of SubmissionFields
     *
     * @return  SubmissionField[]
     */
    public function getFieldDefinitionCollection(): array
    {
        return $this->submissionFieldCollection;
    }

    /**
     * Get submission count
     *
     * @return integer
     */
    public function getSubmissionCount(): int
    {
        return count($this->aggregatedSubmissions);
    }

    /**
     * Get indexed collection of submissions meeting the filtering parameters
     *
     * @return  SingleSubmission[]
     */
    public function getAggregatedSubmissions(): array
    {
        return $this->aggregatedSubmissions;
    }

    /**
     * Get fields and the data within the aggregate.
     *
     * @return  string
     */
    public function getMasterFormId(): string
    {
        return $this->masterFormId;
    }

    /**
     * Set keyed collection of submissions
     *
     * This method enables re-setting the aggregated submissions after
     * performing array methods on it.  This is useful to get a subset of the
     * collection without needing to re-filter and run DB requests
     *
     * @param  Array  $aggregatedSubmissions  Keyed collection of
     * submissions meeting the filtering parameters
     *
     * @return  self
     */ 
    public function setAggregatedSubmissions(Array $aggregatedSubmissions)
    {
        $this->aggregatedSubmissions = $aggregatedSubmissions;

        return $this;
    }
}
