<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Taxonomy;

use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\Control\OptionCollection;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACA\YoastSeo\Value\Formatter\FallBack;
use ACA\YoastSeo\Value\Formatter\OptionLabel;
use ACA\YoastSeo\Value\Formatter\SeoTermMeta;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class IncludeInSitemapFactory extends ACP\Column\AdvancedColumnFactory
{

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
        return 'wpseo-tax_sitemap_include';
    }

    public function get_label(): string
    {
        return __('Include in Sitemap', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $options = OptionCollection::from_array([
            'always' => __('Always include', 'wordpress-seo'),
            'never'  => __('Never include', 'wordpress-seo'),
        ]);

        return parent::get_formatters($config)
                     ->add(new SeoTermMeta($this->taxonomy, 'wpseo_sitemap'))
                     ->add(new OptionLabel($options))
                     ->add(new FallBack(__('Auto detect', 'wordpress-seo')));
    }

}