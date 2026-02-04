<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Taxonomy;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\IntegerFormattableTrait;
use ACP\Formatter\Taxonomy\FilteredPostTypeLink;
use ACP\Formatter\Taxonomy\PostCountForTerm;
use ACP\Setting\ComponentFactory\Post\StatusFactory;
use ACP\Setting\ComponentFactory\TaxonomyPostTypeFactory;

class CountForPostType extends AdvancedColumnFactory
{

    use IntegerFormattableTrait;

    private TaxonomySlug $taxonomy;

    private TaxonomyPostTypeFactory $taxonomy_post_type_factory;

    private StatusFactory $status_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        TaxonomyPostTypeFactory $taxonomy_post_type_factory,
        StatusFactory $status_factory,
        TaxonomySlug $taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->taxonomy = $taxonomy;
        $this->taxonomy_post_type_factory = $taxonomy_post_type_factory;
        $this->status_factory = $status_factory;
    }

    public function get_column_type(): string
    {
        return 'column-term_count_for_post_type';
    }

    public function get_label(): string
    {
        return __('Count for Post Type', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $settings = parent::get_settings($config);

        $settings->add($this->taxonomy_post_type_factory->create($this->taxonomy)->create($config));
        $settings->add($this->status_factory->create()->create($config));

        return $settings;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        $formatters->add(
            new PostCountForTerm(
                (string)$this->taxonomy,
                (string)$config->get('taxonomy_post_type'),
                (string)$config->get('post_status')
            )
        );
        $formatters->add(
            new FilteredPostTypeLink(
                (string)$this->taxonomy,
                (string)$config->get('taxonomy_post_type'),
                (string)$config->get('post_status')
            )
        );

        return $formatters;
    }

}