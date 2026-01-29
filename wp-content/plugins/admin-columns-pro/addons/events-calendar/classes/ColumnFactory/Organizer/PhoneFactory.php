<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Organizer;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;

class PhoneFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_OrganizerPhone';

    use ConditionalFormatTrait;

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-organizer_phone';
    }

    public function get_label(): string
    {
        return __('Phone', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta(self::META_KEY),
        ]);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

}