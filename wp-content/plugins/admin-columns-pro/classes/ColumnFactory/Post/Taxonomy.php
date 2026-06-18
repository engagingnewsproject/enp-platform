<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Taxonomy extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private Editing\Setting\ComponentFactory\InlineEditCreateTerms $inline_edit_factory;

    public function __construct(
        AC\ColumnFactory\Post\TaxonomyFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        Editing\Setting\ComponentFactory\InlineEditCreateTerms $inline_edit_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);

        $this->inline_edit_factory = $inline_edit_factory;
    }

    protected function get_feature_settings_builder(Config $config): ACP\Column\FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_edit(
                         $this->inline_edit_factory
                     );
    }

    private function get_taxonomy_from_config(Config $config): string
    {
        return (string)$config->get('taxonomy', '');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $taxonomy = $this->get_taxonomy_from_config($config);

        return $taxonomy
            ? new Editing\Service\Post\Taxonomy(
                $taxonomy,
                'on' === (string)$config->get('enable_term_creation', 'on'),
            ) : null;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        $taxonomy = $this->get_taxonomy_from_config($config);

        return $taxonomy
            ? new Search\Comparison\Post\Taxonomy($taxonomy)
            : null;
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Taxonomy($this->get_taxonomy_from_config($config));
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new AC\Formatter\Post\PostTerms(
                (string)$config->get('taxonomy', '')
            )
        );
    }

}