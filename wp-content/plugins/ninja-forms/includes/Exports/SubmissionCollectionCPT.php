<?php

if (!defined('ABSPATH'))
    exit;
use NF_Exports_Interfaces_SubmissionCollectionInterface as SubmissionCollectionInterface;
use NF_Exports_SingleSubmissionCPT as SingleSubmission;
/**
 * Provides and stores collection of submissions for a single form using CPT
 */
final class NF_Exports_SubmissionCollectionCPT implements SubmissionCollectionInterface {

    /**
     * Form Id
     * @var string
     */
    protected $formId = '';

    /**
     * Array of NF field objects for the form id
     * 
     * Constructed once, on first request requiring fields; after that, use
     *  previously retrieved property that can be filtered or adjusted as
     *  needed, reducing DB calls
     * 
     * @var array
     */
    protected $fields = [];

    /**
     * Array of labels keyed on field key
     * 
     * Option to use admin_label set on request
     * 
     * @var array
     */
    protected $labels = [];

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
     * Indexed array collection of single submissions
     * @var SingleSubmission[]
     */
    protected $submissionCollection = [];

    /**
     * Indexed array of field types to be omitted in output
     * @var array
     */
    protected $hiddenFieldTypes = [];

    /**
     * Starting date for filtering submissions
     * 
     * @var int|null
     */
    protected $startDate = null;

    /**
     * Ending date for filtering submissions
     * 
     * @var int|null
     */
    protected $endDate = null;

    /**
     * Boolean to filter submissions by `isUnread?`
     * @var bool
     */
    protected $isUnread = null;

    /**
     * Boolean to filter submissions by `previouslyExported?`
     * @var bool
     */
    protected $previouslyExported = null;

    /**
     * Maximum amount of submissions to return in a single request
     * 
     * @var int 
     */
    protected $maxReturnCount = null;

    /**
     * Total count of submissions
     * @var int
     */
    protected $totalCount;

    /**
     * Count of submissions that have been marked `Unread`
     * @var int
     */
    protected $isUnreadCount;

    /**
     * Count of submissions that have been previously exported
     * @var int
     */
    protected $previouslyExportedCount;

    /**
     * Construct submission collection with provided form Id
     * @param string|int $form_id
     */
    public function __construct($form_id) {
        $this->formId = $form_id;
    }

    /**
     * Return form Id
     * @return int
     */
    public function getFormId() {
        return intval($this->formId);
    }

    /**
     * Return form title
     * @return string
     */
    public function getFormTitle() {
        $form = Ninja_Forms()->form($this->formId)->get();
        return $form->get_setting('title');
    }



    /**
     * Set start and end dates to filter submissions
     * @param int|null $startDate Starting date
     * @param int|null $endDate Ending date
     */
    public function setDateParameters($startDate = null, $endDate = null) {
        
        if (!is_null($startDate)) {
            $this->startDate = $this->formatDate($startDate);
        }

        if (!is_null($endDate)) {
            $this->endDate = $this->formatDate($endDate);
        }

        return $this;
    }

    /**
     * Set parameter to filter submissions by `isUnread` = true or false
     * 
     * @param bool $isUnread
     */
    public function setIsUnreadParameter(bool $isUnread=null)/* :SubmissionCollectionInterface */ {
        $this->isUnread = $isUnread;

        return $this;
    }

    /**
     * Set parameter to filter submissions by `previouslyExported` = true or false
     * 
     * @param bool $previouslyExported
     */
    public function setPreviouslyExportedParameter(bool $previouslyExported=null)/* :SubmissionCollectionInterface */ {
        $this->previouslyExported = $previouslyExported;

        return $this;
    }

    /**
     * Set max number of submissions to return in a request
     * 
     * @param int $maxReturnCount
     */
    public function setMaxReturnCount($maxReturnCount=null)/* :SubmissionCollectionInterface */ {
        $this->maxReturnCount = $maxReturnCount;

        return $this;
    }

    /**
     * Set all parameters using NF_Database_Models_SubmissionCollectionInterfaceParameters
     * 
     * Using the object ensures all properties are set, with known defaults;
     *  this enables the requesting class to set only values that concerns it
     *  without needing to set any other value or default.  The Submission
     *  Collection class knows that all values have valid paramters and can
     *  immediately run the filter.
     * 
     * @param NF_Database_Models_SubmissionCollectionInterfaceParameters $params
     * @return SubmissionCollectionInterface
     */
    public function filterByParameters(/* NF_Database_Models_SubmissionCollectionInterfaceParameters*/ $params)/* :SubmissionCollectionInterface */  {
        /** @var NF_Database_Models_SubmissionCollectionInterfaceParameters $params */
        
        $this->setDateParameters($params->getStartDate(),$params->getEndDate());
        $this->setIsUnreadParameter($params->getIsUnread());
        $this->setPreviouslyExportedParameter($params->getPreviouslyExported());
        $this->setMaxReturnCount($params->getMaxReturnCount());
        $this->setHiddenFieldTypes($params->getHiddenFieldTypes());
        $this->filterSubmissions();
        return $this;
    }
    /**
     * Filter collection of submissions based on previously set parameters
     * 
     * @return array
     */
    public function filterSubmissions()/* :SubmissionCollectionInterface */ {

        $defaultFilter = $this->constructDefaultFilter();

        $filter = $this->addMetaFilters($defaultFilter);


        // set max number values to return
        if (!is_null($this->maxReturnCount)) {
            $filter['posts_per_page'] = $this->maxReturnCount;
        }

        $dateQuery = $this->constructDateQuery();
        if (!empty($dateQuery)) {
            $filter['date_query'] = $dateQuery;
        }

        $subs = get_posts($filter);

        $this->submissionCollection = [];

        foreach ($subs as $sub) {
            $this->submissionCollection[$sub->ID] = new SingleSubmission($sub->ID);
        }

        return $this;
    }

    /**
     * Return count of submissions, exported, and unread
     */
    public function getCounts():array {
        $defaultFilter = $this->constructDefaultFilter();

        $subs = get_posts($defaultFilter);

        $allSubmissions=[];
        $previouslyExported=[];
        $isUnread=[];
        
        foreach ($subs as $sub) {
            $singleSubmission = new SingleSubmission($sub->ID);

            $allSubmissions[] = $singleSubmission->getId();

            if ($singleSubmission->wasExported()) {
                $previouslyExported[] = $singleSubmission->getId();
            }

            if ($singleSubmission->isUnread()) {
                $isUnread[] = $singleSubmission->getId();
            }
        }

        $return = [
            'totalCount' => count($allSubmissions),
            'previouslyExported' => count($previouslyExported),
            'isUnread' => count($isUnread)
        ];
        return $return;
    }

    /**
     * Add meta query filters
     * 
     * @param array $filter
     * @return array
     */
    protected function addMetaFilters($filter)/* :array */ {
        // add previously exported criterion to meta query
        $previouslyExportedQuery = $this->constructPreviouslyExportedQuery();

        if (!empty($previouslyExportedQuery)) {
            $filter['meta_query'][] = $previouslyExportedQuery;
        }

        // add is unread criterion to meta query
        $isUnreadQuery = $this->constructIsUnreadQuery();
        if (!empty($isUnreadQuery)) {
            $filter['meta_query'][] = $isUnreadQuery;
        }

        // add condition for multiple meta queries
        if (1 < count($filter['meta_query'])) {
            $filter['meta_query']['relation'] = 'AND';
        }

        return $filter;
    }

    /**
     * Construct/return default filter args array for WP's get_post
     * @return array
     */
    protected function constructDefaultFilter()/* :array */ {

        $filter = [
            'post_type' => 'nf_sub',
            'posts_per_page' => -1, // return all
            'paged' => 1,
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_form_id',
                    'compare' => '=',
                    'value' => $this->formId
                ]
            ]
        ];

        return $filter;
    }

    /**
     * Construct meta query for isUnread; empty array if null param
     * 
     * @return array
     */
    protected function constructIsUnreadQuery() {

        $query = [];
        if (!is_null($this->isUnread)) {

            $query['key'] = SingleSubmission::UNREAD_KEY;

            $query['compare'] = 'LIKE';

            $query['value'] = $this->isUnread;
        }

        return $query;
    }

    /**
     * Construct meta query for previously exported; empty array if null parameter
     * 
     * @param bool|null $previouslyExported
     * @return array
     */
    protected function constructPreviouslyExportedQuery() {

        $query = [];
        if (!is_null($this->previouslyExported)) {
            $query['key'] = SingleSubmission::EXPORT_TIMESTAMP_KEY;

            if ($this->previouslyExported) {

                $query['compare'] = 'EXISTS';
            } else {

                $query['compare'] = 'NOT EXISTS';
            }
        }

        return $query;
    }

    /**
     * Construct meta query for start/end dates; empty array if null parameters
     * 
     * @return array
     */
    protected function constructDateQuery() {

        $query = [];

        if (!is_null($this->endDate)) {
            $query['before'] = $this->endDate;
        }

        if (!is_null($this->startDate)) {
            $query['after'] = $this->startDate;
        }

        return $query;
    }

    /**
     * Return submission collection array
     * @return \NF_Database_Models_SingleSubmissionInterface[]
     */
    public function getSubmissions()/*: SingleSubmission[]*/  {
        
        if(empty($this->submissionCollection)){
            $this->filterSubmissions();
        }
        return $this->submissionCollection;
    }

    /**
     * Return array of submission Ids in the collection
     * 
     * Generated at time of request to ensure it is up to date after last
     *  query / construction
     * @return array
     */
    public function getSubmissionIds() {
        $idArray = [];

        if (!empty($this->submissionCollection)) {

            foreach ($this->submissionCollection as $submission) {
                $idArray[] = $submission->getId();
            }
        }

        return $idArray;
    }

    /**
     * Return array of field labels keyed on field key
     * 
     * If hiddenFieldTypes array is set, labels filtered to hide those types
     * 
     * @param bool|null $useAdminLabels
     * @return array
     */
    public function getLabels($useAdminLabels = null)/* : array */ {

        $this->getFields();

        // if not explicitly requesting admin labels and labels previously
        // retrieved, use those, otherwise generate labels array
        if (is_null($useAdminLabels) && !empty($this->labels)) {
            return $this->labels;
        }

        foreach ($this->fields as $field) {
            // omit hidden field types
            if (!is_null($this->hiddenFieldTypes) && 
                    in_array($field->get_setting('type'), $this->hiddenFieldTypes)) {
                continue;
            }

            if ($useAdminLabels && '' !== $field->get_setting('admin_label')) {
                $labels[$field->get_setting('key')] = $field->get_setting('admin_label');
            } else {
                $labels[$field->get_setting('key')] = $field->get_setting('label');
            }
        }

        $this->labels = $labels;

        return $this->labels;
    }

    /**
     * Return array of field types keyed on field key
     * 
     * @return array
     */
    public function getFieldTypes()/* : array */ {

        if (empty($this->fieldTypes)) {
            $this->getFields();

            foreach ($this->fields as $field) {
                $key = $field->get_setting('key');
                $type = $field->get_setting('type');
                $this->fieldTypes[$key] = $type;
            }
        }

        return $this->fieldTypes;
    }
    /**
     * Return array of field Ids keyed on field keys
     */
    public function getFieldIds()/* :array */{
           if (empty($this->fieldIds)) {
            $this->getFields();

            foreach ($this->fields as $field) {
                $key = $field->get_setting('key');
                $id = $field->get_id();
                $this->fieldIds[$key] = $id;
            }
        }

        return $this->fieldIds;     
        
    }

    /**
     * Get all fields for the instantiated form Id
     * 
     * Stored as $this->fields for additional use w/o calling DB
     * 
     */
    protected function getFields() {

        if (empty($this->fields)) {
            $this->fields = Ninja_Forms()->form($this->formId)->get_fields();
        }

        // not needed internally, but available for public/static use
        return $this->fields;
    }

    /**
     * Convert Unix date stamp to Wordpress post date format
     * 
     * @param int|null $incoming
     * @return string
     */
    protected function formatDate(/* ?int */$incoming = null)/* :string */ {

        $return = $incoming;

        if (!is_null($incoming)) {
            
            $wpDateFormat = 'Y-m-d';

            // @TODO: remove integer casting when adding type hinting
            $return = date($wpDateFormat, (int) $incoming);
        }
        
        return $return;
    }

    /**
     * Set field types to be removed before output
     * 
     * Provided as indexed array of NF field types
     * 
     * @param array $hidden
     */
    public function setHiddenFieldTypes(array $hidden=null)/*: SubmissionCollectionInterface*/ {
        $this->hiddenFieldTypes = $hidden;

        return $this;
    }

}
