<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Taxonomy;

use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\Control\OptionCollection;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACA\YoastSeo\Value\Formatter\OptionLabel;
use ACA\YoastSeo\Value\Formatter\SeoTermMeta;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;

class NoIndexFactory extends AdvancedColumnFactory
{

    private TaxonomySlug $taxonomy;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        TaxonomySlug $taxonomy
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);

        $this->taxonomy = $taxonomy;
    }

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'wpseo-tax_noindex';
    }

    public function get_label(): string
    {
        return __('Noindex', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $options = OptionCollection::from_array([
            'index'   => __('Always index', 'wordpress-seo'),
            'noindex' => __('Always noindex', 'wordpress-seo'),
        ]);

        return parent::get_formatters($config)
                     ->add(new SeoTermMeta($this->taxonomy, 'wpseo_noindex'))
                     ->add(new OptionLabel($options, __('Use default', 'codepress-admin-columns')));
    }

}