<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\ConditionalComponentFactoryCollection;
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

    protected function add_edit_component_factory(
        ConditionalComponentFactoryCollection $factories,
        Config $config
    ): void {
        $factories->add($this->inline_edit_factory);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\Taxonomy(
            (string)$config->get('taxonomy', ''),
            'on' === (string)$config->get('enable_term_creation', 'on'),
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Taxonomy((string)$config->get('taxonomy', ''));
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Taxonomy((string)$config->get('taxonomy', ''));
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