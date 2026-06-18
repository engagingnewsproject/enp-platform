<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\ConditionalFormat\IntegerFormattableTrait;
use ACP\Sorting;

class CommentCount extends EnhancedColumnFactory
{

    use IntegerFormattableTrait;

    public function __construct(
        AC\ColumnFactory\User\CommentCountFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\CommentCount();
    }

    public function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\FilterHtmlFormatter(
                new ACP\ConditionalFormat\Formatter\IntegerFormatter()
            )
        );
    }

}