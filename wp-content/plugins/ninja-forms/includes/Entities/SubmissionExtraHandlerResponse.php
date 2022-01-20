<?php

namespace NinjaForms\Includes\Entities;

use NinjaForms\Includes\Entities\SimpleEntity;

/**
 * Defines data passed from SubmissionHandlers to  submissions page NF>=3.6.1
 */
class SubmissionExtraHandlerResponse extends SimpleEntity
{


    /**
     * Response type - 'none' or `download`
     *
     * @var string
     */
    protected $responseType = '';

    /**
     * Base 64 encoded downloadable string
     *
     * @var string
     */
    protected $download = '';

    /**
     * Application type of download (for constructing download)
     *
     * @var string
     */
    protected $blobType = '';

    /**
     * Result of request (usu. 'ok' or failure message)
     *
     * @var string
     */
    protected $result = '';

    /**
     * Filename of the download, including file extension
     *
     * @var string
     */
    protected $filename = '';

    /**
     * Construct entity from associative array
     *
     * @param array $items
     * @return SubmissionExtraHandlerResponse
     */
    public static function fromArray(array $items): SubmissionExtraHandlerResponse
    {
        $obj = new static();

        foreach ($items as $property => $value) {

            $obj = $obj->__set($property, $value);
        }

        return $obj;
    }



    /**
     * Get response type
     *
     * @return  string
     */
    public function getResponseType(): string
    {
        return $this->responseType;
    }

    /**
     * Set response type
     *
     * @param  string  $responseType  Response type
     *
     * @return  SubmissionExtraHandlerResponse
     */
    public function setResponseType(string $responseType): SubmissionExtraHandlerResponse
    {
        $this->responseType = $responseType;

        return $this;
    }

    /**
     * Get download
     *
     * @return  string
     */
    public function getDownload(): string
    {
        return $this->download;
    }

    /**
     * Set download
     *
     * @param  string  $download  Download
     *
     * @return  SubmissionExtraHandlerResponse
     */
    public function setDownload(string $download): SubmissionExtraHandlerResponse
    {
        $this->download = $download;

        return $this;
    }

    /**
     * Get blob type
     *
     * @return  string
     */
    public function getBlobType(): string
    {
        return $this->blobType;
    }

    /**
     * Set blob type
     *
     * @param  string  $blobType  Blob type
     *
     * @return  SubmissionExtraHandlerResponse
     */
    public function setBlobType(string $blobType): SubmissionExtraHandlerResponse
    {
        $this->blobType = $blobType;

        return $this;
    }

    /**
     * Get result
     *
     * @return  string
     */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * Set result
     *
     * @param  string  $result  Result
     *
     * @return  SubmissionExtraHandlerResponse
     */
    public function setResult(string $result): SubmissionExtraHandlerResponse
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get filename of the download, including file extension
     *
     * @return  string
     */ 
    public function getFilename():string
    {
        return $this->filename;
    }

    /**
     * Set filename of the download, including file extension
     *
     * @param  string  $filename  Filename of the download, including file extension
     *
     * @return  SubmissionExtraHandlerResponse
     */ 
    public function setFilename(string $filename):SubmissionExtraHandlerResponse
    {
        $this->filename = $filename;

        return $this;
    }
}
