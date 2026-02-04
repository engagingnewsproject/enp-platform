<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\EC\Value\Formatter\Event\Duration;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;

class DurationFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_duration';
    }

    public function get_label(): string
    {
        return __('Duration', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Duration(),
        ]);
    }

}