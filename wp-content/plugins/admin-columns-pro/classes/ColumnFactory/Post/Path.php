<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Sorting;

class Path extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private $post_type;

    public function __construct(
        AC\ColumnFactory\Post\PathFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        AC\Type\PostTypeSlug $post_type
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);

        $this->post_type = $post_type;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\Slug();
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return is_post_type_hierarchical((string)$this->post_type)
            ? new Sorting\Model\Post\Permalink((string)$this->post_type)
            : new Sorting\Model\Post\PostField('post_name');
    }

}