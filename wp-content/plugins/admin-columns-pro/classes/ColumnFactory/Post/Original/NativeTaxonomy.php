<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post\Original;

use AC\Formatter\Post\PostTermsOriginal;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;
use ACP\Editing\Setting\ComponentFactory\InlineEditCreateTerms;
use ACP\Search;
use ACP\Sorting;

class NativeTaxonomy extends OriginalColumnFactory
{

    private InlineEditCreateTerms $editing_component;

    private TaxonomySlug $taxonomy_slug;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        TaxonomySlug $taxonomy,
        InlineEditCreateTerms $editing_component
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $type, $label);
        $this->taxonomy_slug = $taxonomy;
        $this->editing_component = $editing_component;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_edit($this->editing_component);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new PostTermsOriginal((string)$this->taxonomy_slug));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Taxonomy((string)$this->taxonomy_slug);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Taxonomy((string)$this->taxonomy_slug);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\Taxonomy(
            (string)$this->taxonomy_slug,
            'on' === $config->get('enable_term_creation')
        );
    }

}