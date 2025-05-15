<?php

namespace ACA\EC\Column\Organizer;

use ACA\EC\Column;
use ACA\EC\Search;
use ACP\Search\Searchable;

class Events extends Column\Events implements Searchable
{

    public function __construct()
    {
        parent::__construct();

        $this->set_type('column-ec-organizer_events')
             ->set_label('Events');
    }

    protected function get_events_by_id($id, array $args = [])
    {
        $args = wp_parse_args($args, [
            'fields'    => 'ids',
            'organizer' => $id,
        ]);

        return $this->get_events($args);
    }

    public function search()
    {
        switch ($this->get_option('event_display')) {
            case 'future':
                return new Search\UpcomingEvent('_EventOrganizerID');
            case 'past':
                return new Search\PastEvents('_EventOrganizerID');
            default:
                return new Search\RelatedEvents('_EventOrganizerID');
        }
    }

}