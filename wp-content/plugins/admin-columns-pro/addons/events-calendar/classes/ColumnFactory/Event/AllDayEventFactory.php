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
use ACP\Sorting;

class AllDayEventFactory extends AdvancedColumnFactory
{

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_alldayevent';
    }

    public function get_label(): string
    {
        return __('All Day Event', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta('_EventAllDay'),
            new AC\Formatter\YesNoIcon(),
        ]);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new EC\Editing\Service\Event\AllDayEvent();
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new EC\Search\Event\AllDayEvent();
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Meta('_EventAllDay');
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Meta(AC\MetaType::create_post_meta(), '_EventAllDay'),
            new AC\Formatter\BooleanLabel('1', ''),
        ]);
    }

}