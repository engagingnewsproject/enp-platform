<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\NumberOfItems;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\Search;

class ChildPages extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private PostTypeSlug $post_type;

    private NumberOfItems $number_of_items;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        PostTypeSlug $post_type,
        NumberOfItems $number_of_items
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->post_type = $post_type;
        $this->number_of_items = $number_of_items;
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(
            new ACP\ConditionalFormat\Formatter\FilterHtmlFormatter(
                new ACP\ConditionalFormat\Formatter\StringFormatter()
            )
        );
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->number_of_items->create($config),
        ]);
    }

    public function get_label(): string
    {
        return __('Child Pages', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-child-pages';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Formatter\Post\ChildIds([(string)$this->post_type]),
            new AC\Formatter\Post\PostTitle(),
            new AC\Formatter\Post\PostLink('edit_post'),
            Separator::create_from_config($config),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Formatter\Post\ChildIds([(string)$this->post_type]),
            new AC\Formatter\Post\PostTitle(),
            new Separator(),
        ]);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\ChildPages((string)$this->post_type);
    }

}