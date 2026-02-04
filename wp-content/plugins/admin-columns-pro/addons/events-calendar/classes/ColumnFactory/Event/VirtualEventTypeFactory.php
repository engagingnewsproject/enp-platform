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
use Tribe\Events\Virtual\Event_Meta;

class VirtualEventTypeFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    private const META_KEY = '_tribe_virtual_events_type';

    public function get_column_type(): string
    {
        return 'column-ec-event_virtual_event_type';
    }

    public function get_label(): string
    {
        return __('Virtual Event Type', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta(self::META_KEY),
            new AC\Formatter\MapOptionLabel($this->get_virtual_types()),
        ]);
    }

    private function get_virtual_types(): array
    {
        if ( ! class_exists(Event_Meta::class)) {
            return [];
        }

        return [
            Event_Meta::$value_hybrid_event_type  => tribe_get_hybrid_event_label_singular(),
            Event_Meta::$value_virtual_event_type => tribe_get_virtual_event_label_singular(),
        ];
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\Select($this->get_virtual_types()),
            new Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\Select(self::META_KEY, $this->get_virtual_types());
    }

    protected function get_sorting(Config $config): ?Sorting\Model\Post\MetaMapping
    {
        $fields = $this->get_virtual_types();
        natcasesort($fields);

        return new Sorting\Model\Post\MetaMapping(self::META_KEY, array_keys($fields));
    }
}
