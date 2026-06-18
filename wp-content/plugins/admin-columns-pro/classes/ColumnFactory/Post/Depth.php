<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting;

class Depth extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;

    private AC\Type\PostTypeSlug $post_type;

    public function __construct(
        AC\ColumnFactory\Post\DepthFactory $depth_status_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        AC\Type\PostTypeSlug $post_type
    ) {
        parent::__construct($depth_status_factory, $feature_setting_builder_factory);

        $this->post_type = $post_type;
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Depth((string)$this->post_type);
    }

}