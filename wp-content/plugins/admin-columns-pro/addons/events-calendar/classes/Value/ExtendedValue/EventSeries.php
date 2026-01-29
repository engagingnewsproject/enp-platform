<?php

declare(strict_types=1);

namespace ACA\EC\Value\ExtendedValue;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;

class EventSeries implements ExtendedValue
{

    private const NAME = 'ec-event-series';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $series_post = get_post($params['series_id'] ?? 0);

        if ( ! $series_post) {
            return __('No series found', 'codepress-admin-columns');
        }

        $events = $this->get_series_events($series_post->ID);

        $series_post->post_title = '';

        $view = new AC\View([
            'series' => $series_post,
            'items'  => $events,
        ]);

        return $view->set_template('modal-value/events_series')->render();
    }

    private function get_series_events(int $series_id): array
    {
        $args = [
            'posts_per_page' => -1,
            'series'         => $series_id,
            'orderby'        => 'event_date',
            'order'          => 'ASC',
        ];

        $events = tribe_get_events($args);

        foreach ($events as &$event) {
            $event->admin_edit_link = get_edit_post_link($event->ID);
            $event->public_view_link = get_permalink($event->ID);
        }

        return $events;
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, self::NAME))
            ->with_class('-w-large');
    }
}

