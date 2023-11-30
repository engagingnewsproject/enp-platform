<?php
namespace Engage\Models;

class Event extends Article {
	
	public $startDate = false;
	public $startTime = false;
	public $endDate = false;
	public $endTime = false;
	public $location = false;
	public $venue = [];
	
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
			$venueName = tribe_get_venue($this->ID, false);
			$venueAddress = tribe_get_address($this->ID, false);
			if(!empty($venueName) && !empty($venueAddress)) {
				$this->venue = [];
				
				if(!empty($venueName)) {
					$this->venue['name'] = $venueName;
				}
				
				if(!empty($venueAddress)) {
					$this->venue['address'] = tribe_get_address($this->ID, false) .'<br/>'. tribe_get_city($this->ID, false) .', '.tribe_get_state($this->ID, false) . ' '.tribe_get_zip($this->ID, false);
				}
			}
		}
		
		return $this->venue;
	}
	
	public function getFormattedDate() {
		$date = '<div class="event__start-date">'.$this->getStartDate().'</div>';
		
		if($this->getStartDate() == $this->getEndDate()) {
			$date .= '<span class="event__start-time">'.$this->getStartTime.'</span> - <span class="event__end-time">'.$this->getEndTime.'</span>';
		}
		else {
			$date .= '<div class="event__start-time">'.$this->getStartTime.'</div>
			<div class="event__to">&mdash;</div>
			<div class="event__end-date">'.$this->getEndDate.'</div> 
			<div class="event__end-time">'.$this->getEndTime.'</div>';
		}
		return $date;
	}
	
}