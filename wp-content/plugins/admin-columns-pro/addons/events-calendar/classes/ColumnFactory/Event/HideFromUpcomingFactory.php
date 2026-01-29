<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\EC;
use ACP\Column\AdvancedColumnFactory;
use ACP\Editing;
use ACP\Search;

class HideFromUpcomingFactory extends AdvancedColumnFactory
{

    private const META_KEY = '_EventHideFromUpcoming';

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_hide_from_upcoming';
    }

    public function get_label(): string
    {
        return __('Hide from Event Listing', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta('_EventHideFromUpcoming'),
            new AC\Formatter\YesNoIcon(),
        ]);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new EC\Editing\Service\Event\HideFromUpcoming();
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\Checkmark(self::META_KEY);
    }

}