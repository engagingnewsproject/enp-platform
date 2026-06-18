<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class EventStatusReasonFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    private const META_KEY = '_tribe_events_status_reason';

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_status_reason';
    }

    public function get_label(): string
    {
        return __('Event Status Reason', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta(self::META_KEY),
        ]);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\Text(),
            new Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\Text(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Meta(self::META_KEY);
    }
}