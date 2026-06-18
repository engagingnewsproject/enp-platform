<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC\Formatter\YesNoIcon;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\EC;
use ACA\EC\Value\Formatter\Event\IsSticky;
use ACP;

class StickyFactory extends ACP\Column\AdvancedColumnFactory
{

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_sticky';
    }

    public function get_label(): string
    {
        return __('Sticky in Month View', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new IsSticky(),
            new YesNoIcon(),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new EC\Editing\Service\Event\Sticky();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new EC\Search\Event\Sticky();
    }

}