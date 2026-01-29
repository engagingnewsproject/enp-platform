<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Taxonomy;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\LinkLabel;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACA\YoastSeo\Editing\Service\Taxonomy\SeoMeta;
use ACA\YoastSeo\Value\Formatter\SeoTermMeta;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;

class CanonicalUrlFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = 'wpseo_canonical';

    private TaxonomySlug $taxonomy;

    private LinkLabel $link_label;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        LinkLabel $link_label,
        TaxonomySlug $taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->link_label = $link_label;
        $this->taxonomy = $taxonomy;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->link_label->create($config));
    }

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'wpseo-tax_canonical_url';
    }

    public function get_label(): string
    {
        return __('Canonical URL', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new SeoTermMeta($this->taxonomy, self::META_KEY));
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new SeoMeta($this->taxonomy, self::META_KEY);
    }

}