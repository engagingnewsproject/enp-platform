<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class LastModifiedDate extends EnhancedColumnFactory
{

    private AC\Type\PostTypeSlug $post_type;

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date;

    public function __construct(
        AC\ColumnFactory\Post\LastModifiedFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        AC\Type\PostTypeSlug $post_type,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);

        $this->post_type = $post_type;
        $this->filtering_date = $filtering_date;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_search(null, $this->filtering_date);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Property('post_modified'));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\Modified();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Date\PostModified((string)$this->post_type);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\OrderBy('modified');
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                new AC\FormatterCollection([
                    new AC\Formatter\Post\ModifiedDate(),
                ]),
                'U',
            )
        );
    }

}