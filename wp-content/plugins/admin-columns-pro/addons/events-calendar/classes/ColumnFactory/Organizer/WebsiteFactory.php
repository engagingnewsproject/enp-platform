<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Organizer;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;

class WebsiteFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_OrganizerWebsite';

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-organizer_website';
    }

    public function get_label(): string
    {
        return __('Website', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta(self::META_KEY),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Url())->set_clear_button(true),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
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