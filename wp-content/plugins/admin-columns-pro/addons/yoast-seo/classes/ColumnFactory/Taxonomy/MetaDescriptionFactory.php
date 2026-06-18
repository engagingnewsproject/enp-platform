<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Taxonomy;

use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACA\YoastSeo\Editing\Service\Taxonomy\SeoMeta;
use ACA\YoastSeo\Value\Formatter\SeoTermMeta;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;

class MetaDescriptionFactory extends AdvancedColumnFactory
{

    private const META_KEY = 'wpseo_desc';

    private TaxonomySlug $taxonomy;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        TaxonomySlug $taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->taxonomy = $taxonomy;
    }

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'wpseo-tax_metadesc';
    }

    public function get_label(): string
    {
        return __('Meta Desc.', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new SeoTermMeta($this->taxonomy, self::META_KEY));
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new SeoMeta(
            $this->taxonomy,
            self::META_KEY
        );
    }

}