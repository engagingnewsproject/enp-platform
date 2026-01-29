<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC\Formatter\Collection\Separator;
use AC\Formatter\Post\PostLink;
use AC\Formatter\Post\PostTitle;
use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\Config;
use ACA\EC\Search\Event\Series;
use ACA\EC\Value\Formatter\Event\EventSerieCollection;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Search;
use ACP\Sorting;

class EventSerieFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    private const META_KEY = '_EventSerieID';

    public function get_column_type(): string
    {
        return 'column-ec-event_serie_name';
    }

    public function get_label(): string
    {
        return __('Event Series Name', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new EventSerieCollection())
                     ->add(new PostTitle())
                     ->add(new PostLink('edit_post'))
                     ->add(new Separator(null, 1));
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Series();
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return (new Sorting\Model\MetaFormatFactory())->create(
            new MetaType(MetaType::POST),
            self::META_KEY,
            new Sorting\FormatValue\PostTitle()
        );
    }

}