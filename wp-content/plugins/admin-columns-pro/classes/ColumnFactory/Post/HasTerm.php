<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\Formatter\Message;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use AC\Type\TaxonomySlug;
use ACP;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Setting\ComponentFactory\Post\TaxonomyTermFactory;
use ACP\Value;

class HasTerm extends AdvancedColumnFactory
{

    private TaxonomyTermFactory $taxonomy_term_factory;

    private PostTypeSlug $post_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        PostTypeSlug $post_type,
        TaxonomyTermFactory $taxonomy_term_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->post_type = $post_type;
        $this->taxonomy_term_factory = $taxonomy_term_factory;
    }

    public function get_column_type(): string
    {
        return 'column-has_term';
    }

    public function get_label(): string
    {
        return __('Has Term', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->taxonomy_term_factory->create($this->post_type)->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        $taxonomy = $config->get('taxonomy');
        $term_id = (int)$config->get('term_id', 0);

        if ($term_id && $taxonomy) {
            $formatters->prepend(new ACP\Formatter\Post\HasTerm(new TaxonomySlug($taxonomy), $term_id));
        } else {
            $formatters->prepend(new Message(__('No term selected', 'codepress-admin-columns')));
        }

        return $formatters;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\HasTerm(
            (string)$config->get('taxonomy'),
            (int)$config->get('term_id', 0)
        );
    }

}