<?php

namespace NinjaForms\Includes\Database;

use NinjaForms\Includes\Contracts\SubmissionDataSource as ContractsSubmissionDataSource;
use NinjaForms\Includes\Entities\SingleSubmission;
use NinjaForms\Includes\Entities\SubmissionFilter;
use NinjaForms\Includes\Entities\SubmissionField;

use \NF_Database_Models_Submission;

/**
 * Retrieves a CPT Ninja Forms submission by its form id
 * 
 * CPT indicates NF submissions stored as custom post type
 */
class CptSubmissionDataSource implements ContractsSubmissionDataSource
{

    const TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    /**
     * Identifier of where submission is stored
     *
     * @var string
     */
    protected $dataSource = 'nf_post';

    /**
     * Collection of submissions
     *
     * @var SingleSubmission[]
     */
    protected $submissionCollection = [];

    /** @var SubmissionFilter   */
    protected $submissionFilter;

    /**
     * Form Id of the primary form
     *
     * Form Id used to define fields; other forms can be included in results.
     *
     * @var [type]
     */
    protected $formId = null;

    /** @inheritDoc */
    public function retrieveSubmissionMeta(SubmissionFilter $submissionFilter): array
    {
        $this->submissionFilter = $submissionFilter;

        $this->submissionCollection = [];

        if ([] !== $submissionFilter->getNfFormIds()) {

            $formIdCollection = $submissionFilter->getNfFormIds();

            $this->formId = $formIdCollection[0];

            foreach ($formIdCollection as $formIdFromCollection) {

                $this->retrieveSubmissionMetaByNfFormId($formIdFromCollection);
            }
        }

        if(''!==$this->submissionFilter->getSearchString()){
            $this->applySearchCriterion();
        }
        
        return $this->submissionCollection;
    }

    /** @inheritDoc */
    public function retrieveSingleSubmission(SingleSubmission $singleSubmission): SingleSubmission
    {
        /** @var NF_Database_Models_Submission $nfSub */
        $nfSub = $this->getNfSub($singleSubmission);

        $subDate = $nfSub->get_sub_date(SingleSubmission::TIMESTAMP_FORMAT);
        $singleSubmission->setTimestamp($subDate);

        $singleSubmission = $this->fullyPopulateSingleSubmission($singleSubmission, $nfSub);

        return $singleSubmission;
    }

    /** @inheritDoc */
    public function retrieveSubmissionValues(SingleSubmission $singleSubmission): SingleSubmission
    {
        $submissionRecordId = $singleSubmission->getSubmissionRecordId();

        if (isset($this->submissionCollection[$submissionRecordId])) {

            $nfSub  = $this->getNfSub($singleSubmission);

            $singleSubmission = $this->fullyPopulateSingleSubmission($singleSubmission, $nfSub);

            $this->submissionCollection[$submissionRecordId] = $singleSubmission;
        }

        return $singleSubmission;
    }

    /** @inheritDoc */
    public function deleteSubmission(SingleSubmission $singleSubmission): ContractsSubmissionDataSource
    {
        $submissionId = $singleSubmission->getSubmissionRecordId();
        
        $submission = Ninja_Forms()->form()->get_sub( $submissionId );

        $status = ["publish"];
        if( in_array($submission->get_status(), $status) ){
            $submission->trash();
        } else {
            $submission->delete();
        }
  
        return $this;
    }

    /** @inheritDoc */
    public function restoreSubmission(SingleSubmission $singleSubmission): ContractsSubmissionDataSource
    {
        $submissionId = $singleSubmission->getSubmissionRecordId();
        
        wp_update_post([
            "ID"            =>  $submissionId,
            "post_status"   =>  "publish"
        ]);
  
        return $this;
    }

    /** @inheritDoc */
    public function updateSubmission(SingleSubmission $singleSubmission): ContractsSubmissionDataSource
    {  
        $submissionId = $singleSubmission->getSubmissionRecordId();
      
        $submission = Ninja_Forms()->form()->get_sub( $submissionId );
        
        $updatedFieldsCollection = [];
        
        /** @var SubmissionField $submissionField */
        foreach($singleSubmission->getSubmissionFieldCollection() as $submissionField){
            $updatedFieldsCollection[$submissionField->getSlug()]=$submissionField->getValue();         
        }

        $submission->update_field_values($updatedFieldsCollection)->save();
        
        $submission->save();

        return $this;
    }

    /**
     * Get the NF_Submission for a SingleSubmission entity
     */
    protected function getNfSub(SingleSubmission $singleSubmission): NF_Database_Models_Submission
    {
        $nfSub = Ninja_Forms()->form()->get_sub($singleSubmission->getSubmissionRecordId(), null);

        return $nfSub;
    }

    /**
     * Populate submission values, extra values, handlers
     *
     * @param SingleSubmission $singleSubmission
     * @param NF_Database_Models_Submission $nfSub
     * @return SingleSubmission
     */
    protected function fullyPopulateSingleSubmission(SingleSubmission $singleSubmission, NF_Database_Models_Submission $nfSub): SingleSubmission
    {
        $singleSubmission = $this->populateSubmissionValues($singleSubmission, $nfSub);

        $extraValues = $this->retrieveExtraValues($nfSub, $this->getExtraValueHandlers());

        $singleSubmission->setExtraValues($extraValues);

        $submissionHandlers = \apply_filters('nf_react_table_submission_handlers',[],$singleSubmission);

        $singleSubmission->setSubmissionHandlers($submissionHandlers);
        
        return $singleSubmission;
    }

    /**
     * Populate a single submissin with submitted values
  
     * @param SingleSubmission $singleSubmission
     * @param NF_Database_Models_Submission $nfSub
     * @return SingleSubmission
     */
    protected function populateSubmissionValues(SingleSubmission $singleSubmission,NF_Database_Models_Submission $nfSub): SingleSubmission
    {
        $updatedFieldValueCollection = [];

        foreach ($singleSubmission->getSubmissionFieldCollection() as $fieldSlug => $submissionField) {

            $value = $nfSub->get_field_value($fieldSlug);
            $submissionField->setValue($value);

            $updatedFieldValueCollection[$fieldSlug] = $submissionField;
        }

        $singleSubmission->setSubmissionFieldCollection($updatedFieldValueCollection);

        return $singleSubmission;
    }

    /**
     * Provide a filtered list of extra value keys storing extra data
     * 
     * Non-core actions can filter the list to provide their keys for retrieval
     * 
     * @return array 
     */
    protected function getExtraValueHandlers( ): array
    {   
        $metaboxHandlers = [];

        $return = \apply_filters('nf_react_table_extra_value_keys',$metaboxHandlers);
        
        // Example of adding an extraValue handler
        // $metaboxHandlers['nfacds']='\NinjaForms\ActiveCampaign\Admin\MetaboxEntityConstructor';
        
        return $return;
    }

    /**
     * Retrieve extraValues as constructed into MetaboxOutputEntities
     *
     * @param NF_Database_Models_Submission $nfSub
     * @param array $extraValueHandlers
     * @return array
     */
    protected function retrieveExtraValues($nfSub, array $extraValueHandlers): array
    {
        $return =[];

        foreach($extraValueHandlers as $extraValueKey=>$extraValueHandler){

            $extraValue =$nfSub->get_extra_value($extraValueKey);

            if(\class_exists($extraValueHandler)){
                
                $structured = (new $extraValueHandler())->handle($extraValue, $nfSub);
                
                if(!is_null($structured)){
                    $return[$extraValueKey]=$structured;
                }

            }
        }
        return $return;
    }
    
    /**
     * Apply search string filter to submission collection
     *
     * Runs a WP Query to search for all post Ids with both a form Id from the
     * submission filter and also a search string in any meta value of the same
     * form.  It then filters the submission collection to only those
     * submissions that meet these additional requirements.
     *
     * @return void
     */
    protected function applySearchCriterion(): void
    {
        $searchQuery = new \WP_Query(
            [
                'post_type' => 'nf_sub',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'AND', // both of below conditions must match
                    [
                        'key' => '_form_id',
                        'value' => $this->submissionFilter->getNfFormIds()
                    ],
                    [
                        'value' => $this->submissionFilter->getSearchString(),
                        'compare' => 'LIKE'
                    ]
                ]
            ]
        );

        /** @var array $searchCollection Array of submission Ids that match both form Id and search criterion */
        $searchCollection = [];

        foreach ($searchQuery->posts as $post) {
            $searchCollection[] = $post->ID;
        }

        $this->submissionCollection = \array_intersect_key($this->submissionCollection, \array_flip($searchCollection));
    }

    /**
     * Retrieve submissions Ids for a given NF form Id
     * 
     * @return void 
     */
    protected function retrieveSubmissionMetaByNfFormId(string $formId): void
    {
        global $wpdb;

        $startDate = date('Y-m-d H:i:s', $this->submissionFilter->getStartDate());
        $endDate = date('Y-m-d H:i:s', $this->submissionFilter->getEndDate());
        $statuses = $this->submissionFilter->getStatus();

        $status = !empty($statuses) && in_array( "trash", $statuses ) ? "trash" : "publish";

        $submissionRecordIdQuery = "SELECT p.ID, p.post_date, mm.meta_value AS seq"
            ." FROM `" . $wpdb->prefix . "posts` AS p"
            ." INNER JOIN `" . $wpdb->prefix . "postmeta` AS m"
            ." ON p.ID = m.post_id"
            ." INNER JOIN `" . $wpdb->prefix . "postmeta` AS mm"
            ." ON p.ID = mm.post_id"
            ." WHERE p.post_type = 'nf_sub'"
            ." AND p.post_status = %s"
            ." AND m.meta_key =  '_form_id'"
            ." AND m.meta_value =  %s"
            ." AND mm.meta_key = '_seq_num'"
            ." AND CAST(p.post_modified AS DATE) BETWEEN %s AND %s";

        $recordCollection = $wpdb->get_results($wpdb->prepare($submissionRecordIdQuery, $status, $formId, $startDate, $endDate));
        
        foreach ($recordCollection as $queryObject) {
            $submissionRecordId = $queryObject->ID;
            $subDate = $queryObject->post_date;
            $seq = $queryObject->seq;
            $this->submissionCollection[$submissionRecordId] = SingleSubmission::fromArray([
                'submissionRecordId' => $submissionRecordId,
                'timestamp' => $subDate,
                'formId' => $formId,
                'dataSource' => $this->dataSource,
                'status' =>  $status,
                'seq_num' => $seq
            ]);
        }
    }

    /**
     * Boolean to include as per date filter
     * 
     * true=>include, false=>omit
     *
     * @return boolean
     */
    protected function includeByDateFilter($subDate): bool
    {
        $include = true;
        
        $startDateUnix = $this->submissionFilter->getStartDate();
        $startDate = (new \DateTime())->setTimestamp($startDateUnix)->format(self::TIMESTAMP_FORMAT);
        
        $endDateUnix = $this->submissionFilter->getEndDate();
        $endDate = (new \DateTime())->setTimestamp($endDateUnix)->format(self::TIMESTAMP_FORMAT);

        if(''!==$startDate && $subDate<$startDate){
            $include=false;
        }

        if(''!==$endDate && $subDate>$endDate){
            $include=false;
        }

        return $include;
    }

    /** @inheritDoc */
    public function getDataSource(): string
    {
        return $this->dataSource;
    }
}
