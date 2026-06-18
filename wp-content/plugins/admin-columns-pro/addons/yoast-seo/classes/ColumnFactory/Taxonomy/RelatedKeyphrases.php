<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Taxonomy;

use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACA\YoastSeo;
use ACA\YoastSeo\Value\Formatter;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;

class RelatedKeyphrases extends AdvancedColumnFactory
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
        return 'wpseo-score-related_keyphrases';
    }

    public function get_label(): string
    {
        return __('Related Keyphrases', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Formatter\SeoTermMeta($this->taxonomy, 'wpseo_focuskeywords'),
            new YoastSeo\Value\Formatter\RelatedKeyphrases(),
        ]);
    }

}