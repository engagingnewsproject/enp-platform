<?php

declare(strict_types=1);

namespace ACA\EC\Editing\Service\Event;

use AC\Helper\Select\Option;
use AC\Type\ToggleOptions;
use ACA\EC\Editing;
use ACP;
use ACP\Editing\View;

class AllDayEvent implements ACP\Editing\Service
{

    public function get_view(string $context): ?View
    {
        $options = new ToggleOptions(
            new Option('0'),
            new Option('1')
        );

        return new ACP\Editing\View\Toggle($options);
    }

    public function get_value(int $id)
    {
        return get_post_meta($id, '_EventAllDay', true);
    }

    public function update(int $id, $data): void
    {
        if ('0' === $data) {
            delete_post_meta($id, '_EventAllDay');
        }

        update_post_meta($id, '_EventAllDay', $data);
    }

}