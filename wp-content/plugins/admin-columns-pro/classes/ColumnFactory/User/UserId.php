<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;
use ACP\Sorting;

class UserId extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;

    public function __construct(
        AC\ColumnFactory\User\UserIdFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\ID();
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\OrderBy('ID');
    }

}