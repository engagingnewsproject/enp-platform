<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Organizer;

use AC;
use AC\Type\Value;

class RelatedEvents implements AC\Formatter
{

    private string $display;

    public function __construct(string $display)
    {
        $this->display = $display;
    }

    public function format(Value $value)
    {
        $events = $this->get_events($value);

        if ( ! $events) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return AC\Type\ValueCollection::from_ids($value->get_id(), $events);
    }

    private function get_events(Value $value): array
    {
        $args = wp_parse_args([
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'organizer'      => $value->get_id(),
        ]);

        if ('future' === $this->display) {
            $args['start_date'] = date('Y-m-d H:i');
        }

        if ('past' === $this->display) {
            $args['end_date'] = date('Y-m-d H:i');
        }

        return tribe_get_events($args);
    }

}