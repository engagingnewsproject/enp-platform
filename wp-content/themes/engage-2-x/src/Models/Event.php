<?php
namespace Engage\Models;

use Timber\Post;

class Event extends Article
{
	public $startDate;      // Event start date
	public $endDate;        // Event end date
	public $location;       // Event location
	public $cost;          // Event cost
	public $website;       // Event website
	public $organizer;     // Event organizer
	public $venue;         // Event venue
	public $isPast;        // Whether event is past
	
	public function __construct($pid = null)
	{
		parent::__construct($pid);
		
		$this->startDate = tribe_get_start_date($this->ID, false, 'Y-m-d H:i:s');
		$this->endDate = tribe_get_end_date($this->ID, false, 'Y-m-d H:i:s');
		$this->location = tribe_get_full_address($this->ID);
		$this->cost = tribe_get_cost($this->ID);
		$this->website = tribe_get_event_website_url($this->ID);
		$this->organizer = tribe_get_organizer($this->ID);
		$this->venue = tribe_get_venue($this->ID);
		$this->isPast = tribe_is_past_event($this->ID);
	}
	
	protected function getRelatedEvents()
	{
		$related_args = [
			'post_type' => 'tribe_events',
			'posts_per_page' => 3,
			'post__not_in' => [$this->ID],
			'orderby' => 'rand',
			'tax_query' => [
				[
					'taxonomy' => 'tribe_events_cat',
					'field' => 'term_id',
					'terms' => wp_list_pluck($this->categories, 'term_id')
				]
			]
		];

		return \Timber::get_posts($related_args, __CLASS__);
	}
	
	public function getEventTime()
	{
		return tribe_get_start_date($this->ID, false, 'g:i a');
	}
	
	public function getEventDate()
	{
		return tribe_get_start_date($this->ID, false, 'F j, Y');
	}
}