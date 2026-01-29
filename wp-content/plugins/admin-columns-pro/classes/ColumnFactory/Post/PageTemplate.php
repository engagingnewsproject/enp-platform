<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\ColumnFactory\Post\PageTemplateFactory;
use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Type\PostTypeSlug;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class PageTemplate extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private PostTypeSlug $post_type;

    public function __construct(
        PageTemplateFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        PostTypeSlug $post_type
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);

        $this->post_type = $post_type;
    }

    private function get_page_templates(): array
    {
        if ( ! function_exists('get_page_templates')) {
            return [];
        }

        return get_page_templates(null, (string)$this->post_type);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Meta('_wp_page_template'));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $page_templates = $this->get_page_templates();

        return $page_templates
            ? new Editing\Service\Post\PageTemplate((string)$this->post_type)
            : null;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        $templates = $this->get_page_templates();

        return ! empty($templates)
            ? new Search\Comparison\Post\PageTemplate($templates)
            : null;
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\PageTemplate((string)$this->post_type, '_wp_page_template');
    }

}