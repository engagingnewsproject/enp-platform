<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment;

use AC;
use AC\Setting\Config;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;

class Post extends ACP\Column\EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Comment\PostFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\Post();
    }

}