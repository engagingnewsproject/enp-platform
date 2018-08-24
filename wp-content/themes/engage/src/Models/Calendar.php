<?php
namespace Engage\Models;

class Calendar {

	public function __construct()
    {
        return self::getCalendar();
    }

    /*
     * For displaying the event calendar in a twig template. 
     * To use, you have to, in the set-up of the php file before calling
     * the twig template, do:
     * $context['calendar'] = TimberHelper::ob_function('\Engage\Models\Calendar::getCalendar');
     * then call it in side the twig template with {{ calendar }}
     * This mimics the functionality of the default event calendar set-up
     * and is necessary for getting all the posts to display right in the 
     * list view. 
     * Also, this fixes issues on the single event page of it trying to 
     * render a post with an ID of 0.
     * Parting words: UGH. This took too long to troubleshoot why
     * the event calendar was putting a post with an ID of 0 in the loop
     * and then trying (and failing) to display it in the event loop.
     */
    public static function getCalendar() {
    	echo '<main id="tribe-events-pg-template" class="tribe-events-pg-template">';
            tribe_events_before_html();
            tribe_get_view();
            tribe_events_after_html();
        echo '</main> <!-- #tribe-events-pg-template -->';
    }

}