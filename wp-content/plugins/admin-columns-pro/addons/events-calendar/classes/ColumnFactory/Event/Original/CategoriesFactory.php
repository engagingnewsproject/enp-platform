<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event\Original;

use AC\Formatter\Post\PostTerms;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing\Setting\ComponentFactory\InlineEditCreateTerms;

class CategoriesFactory extends OriginalColumnFactory
{

    private const TAXONOMY = 'tribe_events_cat';

    private InlineEditCreateTerms $term_creation_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        InlineEditCreateTerms $term_creation_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $type, $label);
        $this->term_creation_factory = $term_creation_factory;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_edit($this->term_creation_factory);
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