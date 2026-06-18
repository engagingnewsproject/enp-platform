<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use ACA\YoastSeo;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;

class PrimaryTaxonomyFactory extends ACP\Column\AdvancedColumnFactory
{

    private YoastSeo\Setting\ComponentFactory\PrimaryTaxonomyFactory $primary_taxonomy_factory;

    private PostTypeSlug $post_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        YoastSeo\Setting\ComponentFactory\PrimaryTaxonomyFactory $primary_taxonomy_factory,
        PostTypeSlug $post_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->primary_taxonomy_factory = $primary_taxonomy_factory;
        $this->post_type = $post_type;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add(
            $this->primary_taxonomy_factory->create($this->post_type)->create($config)
        );
    }

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-wpseo_column_taxonomy';
    }

    public function get_label(): string
    {
        return __('Primary Taxonomy', 'codepress-admin-columns');
    }

    private function get_meta_key(Config $config): string
    {
        $taxonomy = $config->get('primary_taxonomy', '');

        return '_yoast_wpseo_primary_' . $taxonomy;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new YoastSeo\Value\Formatter\PrimaryTaxonomy(
                (string)$config->get('primary_taxonomy'),
                $this->post_type
            )
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\RelatedMeta\Taxonomy\TermField('name', $this->get_meta_key($config));
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        $taxonomy = $config->get('primary_taxonomy', '');

        return new ACP\Editing\Service\Post\PrimaryTerm(
            $this->get_meta_key($config),
            $taxonomy
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        $taxonomy = $config->get('primary_taxonomy', '');
        $meta_key = '_yoast_wpseo_primary_' . $taxonomy;

        return new ACP\Search\Comparison\Post\PrimaryTerm(
            $meta_key,
            $taxonomy,
            (new AC\Meta\QueryMetaFactory())->create_with_post_type($meta_key, (string)$this->post_type)
        );
    }

}