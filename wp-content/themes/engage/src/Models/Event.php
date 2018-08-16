<?php
namespace Engage\Models;

class Event extends Article {

	public $startDate = false,
           $startTime = false,
		   $endDate = false,
           $endTime = false,
		   $location = false,
           $venue = [];

	public function __construct($postID = null)
    {
        parent::__construct($postID);
    }

    public function getStartDate() {
    	if($this->startDate === false) {
    		$this->startDate = tribe_get_start_date($this->ID, false, "F j, Y");
    	}
    	return $this->startDate;
    }

    public function getStartTime() {
        if($this->startTime === false) {
            $this->startTime = tribe_get_start_date($this->ID, false, 'g:ia' );
        }
        return $this->startTime;
    }

    public function getEndDate() {
        if($this->endDate === false) {
            $this->endDate = tribe_get_end_date($this->ID, false, "F j, Y");
        }
        return $this->endDate;
    }
    
    public function getEndTime() {
        if($this->endTime === false) {
            $this->endTime = tribe_get_end_date($this->ID, false, 'g:ia' );
        }
        return $this->endTime;
    }

    public function getVenue() {
    	if(empty($this->venue)) {
            $this->venue['name'] = tribe_get_venue($this->ID, false);
            $this->venue['address'] = tribe_get_full_address($this->ID, false);
        }
        return $this->venue;
    }

}