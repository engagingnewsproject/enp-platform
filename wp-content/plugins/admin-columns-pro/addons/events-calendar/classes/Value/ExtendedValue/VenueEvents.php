<?php

declare(strict_types=1);

namespace ACA\EC\Value\ExtendedValue;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;

class VenueEvents implements ExtendedValue
{

    private const NAME = 'ec-venue-events';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $display = $params['display'] ?? 'all';
        $items = $this->get_events($id, $display);

        $view = new AC\View([
            'items' => $items,
        ]);

        return $view->set_template('modal-value/events')->render();
    }

    private function get_events($id, $display)
    {
        $args = wp_parse_args([
            'posts_per_page' => -1,
            'venue'          => $id,
        ]);

        if ('future' === $display) {
            $args['start_date'] = date('Y-m-d H:i');
        }

        if ('past' === $display) {
            $args['end_date'] = date('Y-m-d H:i');
        }

        return tribe_get_events($args);
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return new ExtendedValueLink($label, $id, self::NAME);
    }

}