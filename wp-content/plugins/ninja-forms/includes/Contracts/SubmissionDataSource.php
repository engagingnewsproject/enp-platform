<?php

namespace NinjaForms\Includes\Contracts;

use NinjaForms\Includes\Entities\SingleSubmission;
use NinjaForms\Includes\Entities\SubmissionFilter;

/**
 * Contract to retrieve a collection of single submissions from a data source
 *
 * Note that the collection has two parts - the submission meta and the
 * submission values.  
 *
 * The meta describes details about the submission such as timestamp, NF form
 * id, data storage location, and a record id.
 *
 * The values portion is a collection of submission values associated with a
 * given submission.
 *
 * The two are retrieved separately.  This enables fast filtering of submissions
 * to generate a collection of submission records; then, after initial
 * filtering, the submission values for just the intended records can be
 * retrieved.
 */
interface SubmissionDataSource
{

    /**
     * Retrieve a collection of submissions meta given filtering args
     *
     * @param array $args
     * @return SingleSubmission[]
     */
    public function retrieveSubmissionMeta(SubmissionFilter $submissionFilter): array;

    /**
     * Populate with submission values a single submission in a collection
     *
     * Submission collection may initially only contain submission meta to save
     * time/memory.  When called, this method retrieves the complete submission
     * values for the provided SingleSubmision
     *
     * @param SingleSubmission $singleSubmission
     * @return SingleSubmission
     */
    public function retrieveSubmissionValues(SingleSubmission $singleSubmission): SingleSubmission;

    /**
     * Retrieve a single submission with values 
     *
     * Absent a previously filtered collection of single submissions within the
     * aggregate, request a single submission, fully populate it, and return.
     *
     * @param SingleSubmission $singleSubmission
     * @return SingleSubmission
     */
    public function retrieveSingleSubmission(SingleSubmission $singleSubmission): SingleSubmission;


    /**
     * Delete a submission from the data source
     *
     * @param SingleSubmission $singleSubmission
     * @return SubmissionDataSource
     */
    public function deleteSubmission(SingleSubmission $singleSubmission): SubmissionDataSource;

    /**
     * Update a submission from the data source
     *
     * @param SingleSubmission $singleSubmission
     * @return SubmissionDataSource
     */
    public function updateSubmission(SingleSubmission $singleSubmission): SubmissionDataSource;

    /**
     * Return the dataSource id
     *
     * Identifies the implementing class, enabling retrieval of submission
     * specifics by calling the implementing class
     * 
     * @return string 
     */
    public function getDataSource( ): string;
}
