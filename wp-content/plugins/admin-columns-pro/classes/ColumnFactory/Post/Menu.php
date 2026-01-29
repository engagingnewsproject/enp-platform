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

class Menu extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;

    private $menu_factory;

    public function __construct(
        AC\ColumnFactory\Post\MenuFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);

        $this->menu_factory = $column_factory;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Menu(
            new Editing\Storage\Post\Menu((string)$this->menu_factory->get_post_type(), 'post_type')
        );
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Menu((string)$this->menu_factory->get_post_type());
    }

}