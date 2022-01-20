<?php

namespace NinjaForms\Includes\Abstracts;

use NinjaForms\Includes\Contracts\SubmissionHandler as ContractSubmissionHandler;
use NinjaForms\Includes\Entities\SingleSubmission;
use NinjaForms\Includes\Entities\SubmissionExtraHandlerResponse;

/**
 * Abstract class implementing SubmissionHandler
 *
 * Child class sets responseType, download, blobType, filename
 *
 * $this->responseType can be 'none' or 'download'.  'none' means that some
 * action is performed but there is no returned data to be downloaded.
 * 'download' means that there is data to be downloaded, such as a PDF or CSV.
 *
 * $this->download is the data that is to be downloaded
 *
 * $this->blobType is the application type of the download, instructing the
 * fetch command the format of the downloadable data
 *
 * $this->result is a summary message for the request, usually 'ok'  or a
 * failure message
 */
abstract class SubmissionHandler implements ContractSubmissionHandler
{
    /**
     * camelCase slug of class
     */
    protected $slug = '';

    /**
     * Response can be 'none' or `download`
     */
    protected $responseType = 'none';

    /**
     * Result of request (usu. 'ok' or failure message)
     *
     * @var string
     */
    protected $result = '';

    /**
     * Base 64 encoded downloadable string
     *
     * @var string
     */
    protected $download = '';

    /**
     * Application type of download (for constructing download)
     */
    protected $blobType = '';

    /**
     * Filename of the download, including file extension
     *
     * @var string
     */
    protected $filename = '';

    /**
     * Label for single row command
     *
     * @var string
     */
    protected $label;


    /**
     * Registers command to export single submission as PDF
     * @return void 
     */
    public function __construct()
    {
        $this->constructLabel();

        \add_filter('nf_react_table_submission_handlers', [$this, 'addSubmissionHandler'], 10, 2);
    }

    /**
     * Construct translatable label property
     *
     * @return void
     */
    abstract protected function constructLabel(): void;

    public function addSubmissionHandler(array $handlerCollection, ?SingleSubmission $singleSubmission): array
    {
        if(!is_null($singleSubmission) && $this->doesAddHandler($singleSubmission)){

            $handlerCollection[$this->getSlug()] =
            [
                'handlerClassName' => $this->getHandlerClassName(),
                'handlerLabel' => $this->getLabel()
            ];
        }

        return $handlerCollection;
    }

    /**
     * Determine if handler is added to submission row
     *
     * @param SingleSubmission $singleSubmission
     * @return boolean
     */
    abstract protected function doesAddHandler(SingleSubmission $singleSubmission): bool;

    /**
     * Perform extra handler action on a single submission
     *
     * @param SingleSubmission $singleSubmission
     * @return array
     */
    public function handle(SingleSubmission $singleSubmission): array
    {
        $this->handleSubmission($singleSubmission);

        $returnArray = (SubmissionExtraHandlerResponse::fromArray([
            'responseType' => $this->responseType,
            'download' => $this->download,
            'blobType' => $this->blobType,
            'result' => $this->result,
            'filename' => $this->filename
        ]))->toArray();

        return $returnArray;
    }

    /**
     * Perform functionality on submission, update properties for return
     *
     * @param SingleSubmission $singleSubmission
     * @return void
     */
    abstract protected function handleSubmission(SingleSubmission $singleSubmission):void;

    /**
     * Returns payload for download
     * 
     * @return string 
     */
    public function getDownload(): string
    {
        return $this->download;
    }

    /**
     * Return an identifying slug for the handler
     * @return string 
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Return a label for the handler
     * @return string 
     */
    public function getLabel(): string
    {

        return $this->label;
    }

    /**
     * Return class name of SubmissionHandler
     * @return string 
     */
    abstract public function getHandlerClassName(): string;
}
