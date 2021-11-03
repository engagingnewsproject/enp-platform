<?php

namespace NinjaForms\Includes\Entities;

use NinjaForms\Includes\Entities\SimpleEntity;

/**
 * Define paramters used to filter submissions
 */
class SubmissionFilter extends SimpleEntity
{
    /**
     * Indexed array of string NF form Ids
     *
     * @var array
     */
    protected $nfFormIds = [];

    /**
     * Start date for filter submissions
     *
     * @var int
     */
    protected $startDate = 0;

    /**
     * End date for filter submissions
     *
     * @var int
     */
    protected $endDate = 0;

    /**
     * Search string
     *
     * @var string
     */
    protected $searchString = '';

    /**
     *  Filter submissions by status
     * 
     * @var array
     */
    protected $status = [];
    
     /**
     * Submissions IDs
     *
     * @var array
     */
    protected $submissionsIDs = [];
    
    /**
     * Construct entity from associative array
     *
     * @param array $items
     * @return SubmissionFilter
     */
    public static function fromArray(array $items): SubmissionFilter
    {
        $obj = new static();

        foreach ($items as $property => $value) {

            $obj = $obj->__set($property, $value);
        }

        return $obj;
    }

    /**
     * Get indexed array of string NF form Ids
     *
     * @return  array
     */
    public function getNfFormIds():array
    {
        return $this->nfFormIds;
    }

    /**
     * Set indexed array of string NF form Ids
     *
     * @param  array  $nfFormIds  Indexed array of string NF form Ids
     *
     * @return  self
     */
    public function setNfFormIds(array $nfFormIds): SubmissionFilter
    {
        $this->nfFormIds = $nfFormIds;

        return $this;
    }

    /**
     * Get start date for filter submissions
     *
     * @return  int
     */
    public function getStartDate(): int
    {
        return $this->startDate;
    }

    /**
     * Set start date for filter submissions
     *
     * @param  int  $startDate  Start date for filter submissions
     *
     * @return  SubmissionFilter
     */
    public function setStartDate(int $startDate): SubmissionFilter
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get end date for filter submissions
     *
     * @return  int
     */
    public function getEndDate(): int
    {
        return $this->endDate;
    }

    /**
     * Set end date for filter submissions
     *
     * @param  int  $endDate  End date for filter submissions
     *
     * @return  SubmissionFilter
     */
    public function setEndDate(int $endDate): SubmissionFilter
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get search string
     *
     * @return  string
     */ 
    public function getSearchString():string
    {
        return $this->searchString;
    }

    /**
     * Set search string
     *
     * @param  string  $searchString  Search string
     *
     * @return  SubmissionFilter
     */ 
    public function setSearchString(string $searchString):SubmissionFilter
    {
        $this->searchString = $searchString;

        return $this;
    }

    /**
     * Get filter submissions by status
     *
     * @return  array
     */ 
    public function getStatus():array
    {
        return $this->status;
    }

    /**
     * Set filter submissions by status
     *
     * @param  array  $status  Filter submissions by status
     *
     * @return  SubmissionFilter
     */ 
    public function setStatus(array $status):SubmissionFilter
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get included submission IDs
     *
     * @return array
     */ 
    public function getSubmissionsIDs():array
    {
        return $this->submissionsIDs;
    }

    /**
     * Set submissions IDs
     *
     * @param array $submissionsIDs of Submissions IDs to include
     *
     * @return  SubmissionFilter
     */ 
    public function setSubmissionsIDs(array $submissionsIDs):SubmissionFilter
    {
        $this->submissionsIDs = $submissionsIDs;

        return $this;
    }
}
