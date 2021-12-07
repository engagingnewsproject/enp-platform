<?php
use NF_Exports_Interfaces_SubmissionCollectionInterface as SubmissionCollectionInterface;
/**
 * Contract defining required methods CSV export
 */
interface NF_Exports_Interfaces_SubmissionCsvExportInterface {

    /**
     * Generate CSV output and return
     * 
     * @return string
     */
    public function handle()/* :string */;

    
    /**
     * Provide submissionCollection indices in reverse order
     * 
     * CSV output sorts earliest to current; submissionAggregate returns in reverse order
     *
     * @return void
     */
    public function reverseSubmissionOrder(): array;

    /**
     * Construct a CSV row for record at given submission aggregate's index
     *
     * @param mixed $aggregatedKey
     * @return array
     */
    public function constructRow( $aggregatedKey):array;
    
    /**
     * Set submission collection used in generating the CSV
     * @param SubmissionCollectionInterface $submissionCollection
     */
    public function setSubmissionCollection(/* SubmissionCollectionInterface */ $submissionCollection)/* :NF_Exports_Interfaces_SubmissionCsvExportInterface */;

    /**
     * Set boolean useAdminLabels
     * @param bool $useAdminLabels
     * @return NF_Exports_Interfaces_SubmissionCsvExportInterface
     */
    public function setUseAdminLabels($useAdminLabels) :NF_Exports_Interfaces_SubmissionCsvExportInterface;

    /**
     * Return array of labels
     * 
     * @return array 
     */
    public function getLabels( ): array;

    /**
     * Set date format
     * @param string $dateFormat
     */
    public function setDateFormat(/* string */$dateFormat)/* :NF_Exports_Interfaces_SubmissionCsvExportInterface */;
}
