<?php

namespace ACP\Admin\PageFactory;

use AC;
use AC\Admin\Page;
use AC\Admin\PageFactoryInterface;
use AC\Request;
use AC\TableScreen;
use AC\Type\ListScreenId;
use ACP\Admin;
use InvalidArgumentException;

class Columns implements PageFactoryInterface
{

    private AC\AdminColumns $plugin;

    private AC\Admin\MenuListFactory $menu_list_factory;

    private Admin\MenuFactory $menu_factory;

    private AC\Admin\UninitializedScreens $uninitialized_screens;

    private AC\Table\TableScreenRepository $table_screen_repository;

    private AC\Storage\Repository\EditorFavorites $favorite_repository;

    private AC\ColumnGroups $column_groups;

    private AC\Promo\PromoRepository $promos;

    private AC\Integration\IntegrationRepository $integration_repository;

    public function __construct(
        AC\AdminColumns $plugin,
        Admin\MenuFactory $menu_factory,
        AC\Admin\MenuListFactory $menu_list_factory,
        AC\Admin\UninitializedScreens $uninitialized_screens,
        AC\Table\TableScreenRepository $table_screen_repository,
        AC\Storage\Repository\EditorFavorites $favorite_repository,
        AC\ColumnGroups $column_groups,
        AC\Promo\PromoRepository $promos,
        AC\Integration\IntegrationRepository $integration_repository
    ) {
        $this->plugin = $plugin;
        $this->menu_factory = $menu_factory;
        $this->menu_list_factory = $menu_list_factory;
        $this->uninitialized_screens = $uninitialized_screens;
        $this->table_screen_repository = $table_screen_repository;
        $this->favorite_repository = $favorite_repository;
        $this->column_groups = $column_groups;
        $this->promos = $promos;
        $this->integration_repository = $integration_repository;
    }

    public function create(): Page\Columns
    {
        $request = new Request();

        $request->add_middleware(
            new Request\Middleware\TableScreenAdmin(
                new AC\Admin\Preference\EditorPreference(),
                $this->table_screen_repository->find_all_site()
            )
        );

        $table_screen = $request->get('table_screen');

        if ( ! $table_screen instanceof TableScreen) {
            throw new InvalidArgumentException('Invalid screen.');
        }

        $list_id = ListScreenId::is_valid_id($request->get('layout_id'))
            ? new ListScreenId($request->get('layout_id'))
            : null;

        return new Page\Columns(
            $this->plugin,
            $this->uninitialized_screens->find_all_site(),
            new AC\Admin\View\Menu($this->menu_factory->create('columns')),
            $table_screen,
            $this->menu_list_factory->create($this->table_screen_repository->find_all_site()),
            $this->favorite_repository,
            $this->table_screen_repository,
            $this->column_groups,
            $this->promos,
            $this->integration_repository,
            $list_id
        );
    }

}