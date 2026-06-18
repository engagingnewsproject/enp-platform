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
use Tribe\Events\Event_Status\Status_Labels;

class EventStatusFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    private const META_KEY = '_tribe_events_status';

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_status';
    }

    public function get_label(): string
    {
        return __('Event Status', 'codepress-admin-columns');
    }

    private function get_statuses(): array
    {
        $status_labels = new Status_Labels();
        $statuses_data = $status_labels->filter_event_statuses([], '');

        $statuses = [];
        foreach ($statuses_data as $status_data) {
            if (isset($status_data['value'], $status_data['text'])) {
                $statuses[$status_data['value']] = $status_data['text'];
            }
        }

        return $statuses;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta(self::META_KEY),
            new AC\Formatter\MapOptionLabel($this->get_statuses()),
        ]);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\Select($this->get_statuses()),
            new Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\Select(self::META_KEY, $this->get_statuses());
    }

    protected function get_sorting(Config $config): ?Sorting\Model\Post\MetaMapping
    {
        $fields = $this->get_statuses();
        natcasesort($fields);

        return new Sorting\Model\Post\MetaMapping(self::META_KEY, array_keys($fields));
    }
}
