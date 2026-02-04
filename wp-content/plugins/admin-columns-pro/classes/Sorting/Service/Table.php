<?php

declare(strict_types=1);

namespace ACP\Sorting\Service;

use AC;
use AC\Registerable;
use AC\Services;
use ACP\AdminColumnsPro;
use ACP\ColumnRepository;
use ACP\Sorting;
use ACP\Sorting\Controller\ManageQueryHandlerFactory;
use ACP\Sorting\Controller\RequestSetterHandler;
use ACP\Sorting\TableScreen\ManageHeading\ScreenColumns;

class Table implements Registerable
{

    private AC\Asset\Location $location;

    private ManageQueryHandlerFactory $manage_query_handler_factory;

    private ColumnRepository $column_repository;

    private RequestSetterHandler $request_setter_handler;

    public function __construct(
        AdminColumnsPro $plugin,
        ManageQueryHandlerFactory $manage_query_handler_factory,
        ColumnRepository $column_repository,
        RequestSetterHandler $request_setter_handler
    ) {
        $this->location = $plugin->get_location();
        $this->manage_query_handler_factory = $manage_query_handler_factory;
        $this->column_repository = $column_repository;
        $this->request_setter_handler = $request_setter_handler;
    }

    public function register(): void
    {
        add_action('ac/table/list_screen', [$this, 'init_table'], 11); // After filtering
    }

    public function init_table(AC\ListScreen $list_screen): void
    {
        // this needs to come first, because it overwrites order preference
        $this->request_setter_handler->handle($list_screen);

        $this->manage_query_handler_factory->create($list_screen)
                                           ->handle();

        $services = new Services([
            new Sorting\Service\AdminScripts($this->location, $list_screen),
            new Sorting\Service\SaveSortingPreference($list_screen),
            new ScreenColumns(
                $list_screen->get_screen_id(),
                $this->column_repository->find_all_with_sorting($list_screen)
            ),
        ]);

        $services->register();
    }

}