<?php

namespace NinjaForms\Includes\Contracts;
use NinjaForms\Includes\Entities\SingleSubmission;
/**
 * Contract to perform actions on a Single Submission
 *
 * Used on the Submissions Page, these handlers provide a standard means for
 * registering additional actions to be performed on a single submission.  For
 * example, PDF Submissions would register a handler that receives the
 * submission and export a PDF
 */
interface SubmissionHandler{

    /**
     * Return an identifying slug for the handler
     * @return string 
     */
    public function getSlug( ): string;

    /**
     * Return a label for the handler
     * @return string 
     */
    public function getLabel( ): string;

    /**
     * Return class name of SubmissionHandler
     * @return string 
     */
    public function getHandlerClassName( ): string;

    /**
     * Perform action on a single submission
     *
     * @param SingleSubmission $singleSubmission
     * @return void
     */
    public function handle(SingleSubmission $singleSubmission): array;

    /**
     * Returns downloadable
     * 
     * @return string 
     */
    public function getDownload( ): string;
}