<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\SeoPress\Editing;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Helper\Select;

class PrimaryTaxonomy extends ACP\Column\AdvancedColumnFactory
{

    private AC\Type\PostTypeSlug $post_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        AC\Type\PostTypeSlug $post_type
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);
        $this->post_type = $post_type;
    }

    public function get_column_type(): string
    {
        return 'column-sp_primary_taxonomy';
    }

    public function get_label(): string
    {
        return __('Primary Term', 'codepress-admin-columns');
    }

    private function get_meta_key(): string
    {
        return '_seopress_robots_primary_cat';
    }

    private function get_related_taxonomy(): string
    {
        return 'category';
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\RelatedMeta\Taxonomy\TermField('name', $this->get_meta_key());
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new AC\Formatter\Post\Meta('_seopress_robots_primary_cat'))
                     ->add(new AC\Formatter\ForeignId())
                     ->add(new AC\Formatter\Term\TermProperty('name'))
                     ->add(new AC\Formatter\Term\TermLink('edit'));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Post\PrimaryTerm($this->get_meta_key(), $this->get_related_taxonomy());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\PrimaryTerm(
            $this->get_meta_key(),
            $this->get_related_taxonomy(),
            (new AC\Meta\QueryMetaFactory())->create_with_post_type($this->get_meta_key(), (string)$this->post_type)
        );
    }

}