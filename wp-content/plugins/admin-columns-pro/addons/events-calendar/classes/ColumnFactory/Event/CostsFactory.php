<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\EC\Value\Formatter\Event\FormattedCosts;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat;
use ACP\ConditionalFormat\Formatter\FloatFormatter;
use ACP\ConditionalFormat\Formatter\SanitizedFormatter;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;
use ACP\Sorting\Type\DataType;

class CostsFactory extends AdvancedColumnFactory
{

    use ConditionalFormat\ConditionalFormatTrait;

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_costs';
    }

    public function get_label(): string
    {
        return __('Costs', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new FormattedCosts(),
        ]);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Meta\Text('_EventCost');
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Meta('_EventCost', new DataType(DataType::NUMERIC));
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\Number('_EventCost');
    }

    protected function get_conditional_format(Config $config): ?ConditionalFormat\FormattableConfig
    {
        return new ConditionalFormat\FormattableConfig(SanitizedFormatter::from_ignore_strings(new FloatFormatter()));
    }

}