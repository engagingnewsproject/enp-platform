<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC;
use AC\FormatterCollection;
use AC\Helper\Select\Option;
use AC\Setting\Config;
use AC\Type\ToggleOptions;
use ACA\EC;
use ACP\Column\AdvancedColumnFactory;
use ACP\Editing;
use ACP\Search;

class FeaturedFactory extends AdvancedColumnFactory
{

    private const META_KEY = '_tribe_featured';

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_featured';
    }

    public function get_label(): string
    {
        return __('Featured', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta('_tribe_featured'),
            new AC\Formatter\YesIcon(),
        ]);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        $options = new ToggleOptions(
            new Option('0', 'False'),
            new Option('1', 'True')
        );

        return new Editing\Service\Basic(
            new Editing\View\Toggle($options),
            new Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new EC\Search\Event\Featured(self::META_KEY);
    }
}