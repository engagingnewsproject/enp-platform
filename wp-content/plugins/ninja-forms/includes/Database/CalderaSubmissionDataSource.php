<?php

namespace NinjaForms\Includes\Database;

use NinjaForms\Includes\Contracts\SubmissionDataSource as ContractsSubmissionDataSource;
use NinjaForms\Includes\Entities\SingleSubmission;
use NinjaForms\Includes\Entities\SubmissionFilter;

use Caldera_Forms_Entry_Update;
use Caldera_Forms_Entry_Bulk;
use Caldera_Forms;

/**
 * Retrieves a single Caldera Forms submission by its entry id 
 */
class CalderaSubmissionDataSource implements ContractsSubmissionDataSource
{
    
    const TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    /**
     * Identifier of where submission is stored
     *
     * @var string
     */
    protected $dataSource = 'cf_form_entries';

    /**
     * Collection of submissions keyed on Submission Record Id
     *
     * @var SingleSubmission[]
     */
    protected $submissionCollection = [];

    /** @var SubmissionFilter   */
    protected $submissionFilter;

    /**
     * Cross reference from NF Form Id to the CF form Id
     *
     * Array is in format:
     *   [ 
     *      nfFormId => cfFormId,  
     * ex:  '174' => 'CF60b547cfac102'
     *   ]
     * @var array
     */
    protected $formIdLookup = [];

    /** @inheritDoc */
    public function retrieveSubmissionMeta(SubmissionFilter $submissionFilter): array
    {
        $this->submissionFilter = $submissionFilter;

        if ([] !== $this->submissionFilter->getNfFormIds()) {

            $formIdCollection = $this->submissionFilter->getNfFormIds();

            $this->formId = $formIdCollection[0];

            foreach ($formIdCollection as $nfFormId) {
   
                $this->lookupCfFormIdByNfFormId($nfFormId);
                
                if (!\is_null($this->formIdLookup[$nfFormId])) {
                    $this->retrieveSubmissionMetaByCfFormId($this->formIdLookup[$nfFormId]);
                }
            }
        }

        if(''!==$this->submissionFilter->getSearchString()){
            $this->applySearchCriterion();
        }

        return $this->submissionCollection;
    }

    /** @inheritDoc */
    public function retrieveSingleSubmission(SingleSubmission $singleSubmission): SingleSubmission{

        $submissionRecordId = $singleSubmission->getSubmissionRecordId();

        // Initialize submission collection b/c retrievals only happen on submissions in collection
        $this->submissionCollection[$submissionRecordId]=$singleSubmission;

        // Populate values
        $singleSubmission = $this->retrieveSubmissionValues($singleSubmission);

        return $singleSubmission;
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
        global $wpdb;

        $entriesTable = $wpdb->prefix . 'cf_form_entries';
        $valuesTable = $wpdb->prefix . 'cf_form_entry_values';

        $searchString = '%'.$this->submissionFilter->getSearchString() .'%';

        /** @var array $searchCollection Array of submission Ids that match both form Id and search criterion */
        $searchCollection = [];

        foreach ($this->submissionFilter->getNfFormIds() as $nfFormId) {

            if (\is_null($this->formIdLookup[$nfFormId])) {
                continue;
            }

            $cfFormId = $this->formIdLookup[$nfFormId];

            $searchResultIds = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT 
                        $entriesTable.id 
                    FROM 
                        $entriesTable 
                    INNER JOIN $valuesTable ON
                        ($entriesTable.id = $valuesTable.entry_id) 
                    WHERE 
                        $entriesTable.form_id = '%s' AND $valuesTable.value LIKE '%s' 
                    GROUP BY 
                        $entriesTable.id
                    ",
                    $cfFormId,
                    $searchString
                ),
                ARRAY_A
            );

            $submissionIds = \array_column($searchResultIds, 'id');

            $searchCollection = array_merge($searchCollection,$submissionIds);
        }

        $this->submissionCollection = \array_intersect_key($this->submissionCollection, \array_flip($searchCollection));
    }

    /**
     * Adds CF lookup for given NF form id
     *
     * If given NF id lookup is not set, checks to see if there is a
     * corresponding CF form.  If not, the value is set to `null` so that
     * the instance knows that the form id has been checked.
     *
     * @param string $nfFormId
     * @return void
     */
    protected function lookupCfFormIdByNfFormId(string $nfFormId): void
    {
        if(isset($this->formIdLookup[$nfFormId])){
            return;
        }

        $form =Ninja_Forms()->form( $nfFormId )->get();
        
        $cfFormId= $form->get_setting('key',null);

        $this->formIdLookup[$nfFormId]=$cfFormId;
    }

    /** @inheritDoc */
    public function retrieveSubmissionValues(SingleSubmission $singleSubmission): SingleSubmission
    {
        $submissionRecordId = $singleSubmission->getSubmissionRecordId();

        global $wpdb;

        $table = $wpdb->prefix . 'cf_form_entry_values';

        $r = $wpdb->get_results($wpdb->prepare("SELECT `slug`,`value` FROM $table WHERE `entry_id` = '%s'", $submissionRecordId), ARRAY_A);
        $cfSub = array_combine(array_column($r, 'slug'), array_column($r, 'value'));

        $updatedFieldValueCollection = [];

        foreach ($singleSubmission->getSubmissionFieldCollection() as $fieldSlug => $submissionField) {

            $value = isset($cfSub[$fieldSlug]) ? $cfSub[$fieldSlug] : null;

            $submissionField->setValue($value);

            $updatedFieldValueCollection[$fieldSlug] = $submissionField;
        }

        $singleSubmission->setSubmissionFieldCollection($updatedFieldValueCollection);

        // only populate collection if submission is already present    
        if (isset($this->submissionCollection[$submissionRecordId])) {
            $this->submissionCollection[$submissionRecordId] = $singleSubmission;
        }

        return $singleSubmission;
    }

    /** @inheritDoc */
    public function deleteSubmission(SingleSubmission $singleSubmission): ContractsSubmissionDataSource
    {
        $submissionId = $singleSubmission->getSubmissionRecordId();
        $entry = Caldera_Forms::get_entry_detail( $submissionId );
        if($entry['status'] === "active"){
            Caldera_Forms_Entry_Update::update_entry_status( "trash", $submissionId);
        } else {
            Caldera_Forms_Entry_Bulk::delete_entries([$submissionId]);
        }

        return $this;
    }

    /** @inheritDoc */
    public function restoreSubmission(SingleSubmission $singleSubmission): ContractsSubmissionDataSource
    {
        $submissionId = $singleSubmission->getSubmissionRecordId();

        Caldera_Forms_Entry_Update::update_entry_status( "active", $submissionId);
 
        return $this;
    }

    /** @inheritDoc */
    public function updateSubmission(SingleSubmission $singleSubmission): ContractsSubmissionDataSource
    {
        // @TODO: Use CF API to delete submission
        return $this;
    }

    /**
     * Retrieve submissions for a given CF form Id
     * @return void 
     */
    protected function retrieveSubmissionMetaByCfFormId(string $formId): void
    {
        global $wpdb;
        
        $submissionRecordIdQuery = "select * from " . $wpdb->prefix . "cf_form_entries posts where form_id=%s";

        $recordCollection = $wpdb->get_results($wpdb->prepare($submissionRecordIdQuery, $formId));
        $statuses = $this->submissionFilter->getStatus();
        foreach ($recordCollection as $queryObject) {
            //filter by status
            if( empty($statuses) || in_array( $queryObject->status, $statuses ) ){
                $submissionRecordId = $queryObject->id;
                $subDate = $queryObject->datestamp;
                $status = $queryObject->status;

                $include = $this->includeByDateFilter($subDate);

                if (!$include) {
                    continue;
                }

                $this->submissionCollection[$submissionRecordId] = SingleSubmission::fromArray([
                    'submissionRecordId' => $submissionRecordId,
                    'timestamp' => $subDate,
                    'formId' => $formId,
                    'dataSource' => $this->dataSource,
                    'status'    =>  $status
                ]);
            }
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

        if ('' !== $startDate && $subDate < $startDate) {
            $include = false;
        }

        if ('' !== $endDate && $subDate > $endDate) {
            $include = false;
        }

        return $include;
    }

    /** @inheritDoc */
    public function getDataSource(): string
    {
        return $this->dataSource;
    }

}
