<?php
use NF_Exports_SubmissionCollectionFilterParameters as SubmissionCollectionFilterParameters;
use NF_Exports_Interfaces_SingleSubmissionInterface as SingleSubmissionInterface;
/**
 * Contract defining required methods for handling submission collection
 * 
 */
interface NF_Exports_Interfaces_SubmissionCollectionInterface {

    /**
     * Return the form Id
     * @return int
     */
    public function getFormId();

    /**
     * Return the form title
     * @return string
     */
    public function getFormTitle();

    /**
     * Return count of submissions, exported, and unread
     */
    public function getCounts():array ;

    /**
     * Set all parameters using NF_Database_Models_SubmissionCollectionInterfaceParameters
     * 
     * Using the object ensures all properties are set, with known defaults;
     *  this enables the requesting class to set only values that concerns it
     *  without needing to set any other value or default.  The Submission
     *  Collection class knows that all values have valid parameters and can
     *  immediately run the filter.
     * 
     * @param SubmissionCollectionFilterParameters $params
     */
    public function filterByParameters(/* SubmissionCollectionFilterParameters */ $params)/* :NF_Database_Models_SubmissionCollectionInterface*/ ;
    /**
     * Set start and end dates to filter submissions
     * @param int|null $startDate Starting date
     * @param int|null $endDate Ending date
     */
    public function setDateParameters($startDate = null, $endDate = null)/* :NF_Exports_Interfaces_SubmissionCollectionInterface */;

    /**
     * Set parameter to filter submissions by `isUnread` = true or false
     * 
     * @param bool $isUnread
     */
    public function setIsUnreadParameter(bool $isUnread=null)/* :NF_Exports_Interfaces_SubmissionCollectionInterface */;

    /**
     * Set parameter to filter submissions by `previouslyExported` = true or false
     * 
     * @param bool $previouslyExported
     */
    public function setPreviouslyExportedParameter(bool $previouslyExported=null)/* :NF_Exports_Interfaces_SubmissionCollectionInterface */;

    /**
     * Set max number of submissions to return in a request
     * 
     * @param int $maxReturnCount
     */
    public function setMaxReturnCount($maxReturnCount=null)/* :NF_Exports_Interfaces_SubmissionCollectionInterface */;

    /**
     * Filter collection of submissions based on previously set parameters
     * 
     * @return array
     */
    public function filterSubmissions()/* :NF_Exports_Interfaces_SubmissionCollectionInterface */;

    /**
     * Return submission collection array
     * @return SingleSubmissionInterface[]
     */
    public function getSubmissions() /* : SingleSubmissionInterface[] */;

    /**
     * Return array of submission Ids in the collection
     */
    public function getSubmissionIds() /* :array */;

    /**
     * Return array of field labels keyed on field keys
     * 
     * If hiddenFieldTypes array is set, labels filtered to hide those types
     * 
     * @param bool $useAdminLabels Optionally use admin_labels
     * @return array
     */
    public function getLabels(?bool $useAdminLabels = false)/* : array */;

    /**
     * Return array of field types keyed on field keys
     * 
     * @return array
     */
    public function getFieldTypes()/* : array */;

    /**
     * Return array of field Ids keyed on field keys
     */
    public function getFieldIds()/* :array */;

    /**
     * Set field types to be removed before output
     * 
     * Provided as indexed array of NF field types
     * 
     * @param array $hidden
     */
    public function setHiddenFieldTypes(array $hidden)/* : NF_Exports_Interfaces_SubmissionCollectionInterface */;
}
