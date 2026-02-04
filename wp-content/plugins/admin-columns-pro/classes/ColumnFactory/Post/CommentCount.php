<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;
use ACP\Sorting;

class CommentCount extends EnhancedColumnFactory
{

    public function __construct(
        AC\ColumnFactory\Post\CommentCountFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    private function get_comment_status(Config $config): string
    {
        return $config->get('comment_status', 'all');
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return (new Sorting\Model\Post\CommentCountFactory())->create(
            $this->get_comment_status($config)
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new AC\Formatter\Post\CommentCount(
                $this->get_comment_status($config)
            )
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return (new Search\Comparison\Post\CommentCountFactory())->create($this->get_comment_status($config));
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\FilterHtmlFormatter(
                new ACP\ConditionalFormat\Formatter\IntegerFormatter()
            )
        );
    }

}