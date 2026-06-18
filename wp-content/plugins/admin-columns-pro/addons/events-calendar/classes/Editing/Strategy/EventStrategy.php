<?php

declare(strict_types=1);

namespace ACA\EC\Editing\Strategy;

use AC\PostType;
use AC\TableScreen;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;

class EventStrategy implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof PostType || ! $table_screen->get_post_type()->equals('tribe_events')) {
            return null;
        }

        $post_type_object = get_post_type_object('tribe_events');

        if ( ! $post_type_object) {
            return null;
        }

        return new Event($post_type_object);
    }

}