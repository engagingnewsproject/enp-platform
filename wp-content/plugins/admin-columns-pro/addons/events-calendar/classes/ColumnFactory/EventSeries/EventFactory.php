<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\EventSeries;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\EC\Value;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;

class EventFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-ec-event_series_event';
    }

    public function get_label(): string
    {
        return __('Event Series', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Value\Formatter\EventSeries\SeriesWithTooltip(new Value\ExtendedValue\EventSeries()),
        ]);
    }
}
