<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use ACA\YoastSeo;
use ACA\YoastSeo\Editing;
use ACA\YoastSeo\Value\Formatter\IsIndexed;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;
use WPSEO_Post_Type;

class IsIndexedFactory extends ACP\Column\AdvancedColumnFactory
{

    private PostTypeSlug $post_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        PostTypeSlug $post_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->post_type = $post_type;
    }

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_is_indexed';
    }

    public function get_label(): string
    {
        return __('Is Indexed', 'codepress-admin-columns');
    }

    private function get_default_post_type_index(): bool
    {
        if ( ! class_exists('WPSEO_Post_Type', false)) {
            return false;
        }

        return WPSEO_Post_Type::is_post_type_indexable((string)$this->post_type);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new IsIndexed((string)$this->post_type));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\IsIndexed(
            (string)$this->post_type,
            $this->get_default_post_type_index()
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        $null_value = $this->get_default_post_type_index() ? 2 : 1;

        return new YoastSeo\Search\Post\IsIndexed('_yoast_wpseo_meta-robots-noindex', $null_value);
    }

}