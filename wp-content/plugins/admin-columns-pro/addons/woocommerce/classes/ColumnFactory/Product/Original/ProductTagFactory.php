<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product\Original;

use AC\Formatter\Post\PostTerms;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing\Setting\ComponentFactory\InlineEditCreateTerms;

class ProductTagFactory extends OriginalColumnFactory
{

    private const TAXONOMY = 'product_tag';

    private $editing_terms_component;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        InlineEditCreateTerms $editing_terms_component
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $type, $label);
        $this->editing_terms_component = $editing_terms_component;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_edit($this->editing_terms_component);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Taxonomy(self::TAXONOMY);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Post\Taxonomy(
            self::TAXONOMY,
            'on' === $config->get('enable_term_creation')
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new PostTerms(self::TAXONOMY));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Taxonomy(self::TAXONOMY);
    }

}