<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Roles extends ACP\Column\AdvancedColumnFactory
{

    protected const META_KEY = 'admin_color';

    public function get_column_type(): string
    {
        return 'column-roles';
    }

    public function get_label(): string
    {
        return __('Roles', 'codepress-admin-columns');
    }

    public function get_meta_key(): string
    {
        global $wpdb;

        return $wpdb->get_blog_prefix() . 'capabilities'; // WPMU compatible
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\User\TranslatedRoles(),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\Role(true);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\User\Roles(true));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Roles($this->get_meta_key());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\Role($this->get_meta_key());
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\FilterHtmlFormatter(
                new ACP\ConditionalFormat\Formatter\StringFormatter()
            )
        );
    }

}