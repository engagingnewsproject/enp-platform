<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Redirect;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\SeoPress\Editing;
use ACP;

final class QueryParameters extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_seopress_redirections_param';

    protected function get_group(): ?string
    {
        return 'seopress';
    }

    public function get_label(): string
    {
        return __('Query parameters', 'wp-seopress');
    }

    public function get_column_type(): string
    {
        return 'column-sp_query_parameters';
    }

    private function get_parameter_options(): array
    {
        return [
            'exact_match'        => __('Exactly match all parameters', 'wp-seopress'),
            'without_param'      => __('Exclude all parameters', 'wp-seopress'),
            'with_ignored_param' => __('Exclude all parameters and pass them to the redirection', 'wp-seopress'),
        ];
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select($this->get_parameter_options()),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select(self::META_KEY, $this->get_parameter_options());
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new AC\Formatter\Post\Meta(self::META_KEY))
                     ->add(new AC\Formatter\MapOptionLabel($this->get_parameter_options()));
    }

}