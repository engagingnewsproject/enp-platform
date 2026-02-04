<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment;

use AC;
use AC\Setting\Config;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;
use ACP\Sorting;

class AuthorIp extends ACP\Column\EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Comment\AuthorIpFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Comment\OrderByNonUnique('comment_author_IP');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\IP();
    }

}