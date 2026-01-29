<?php

namespace ACP\Editing\RequestHandler;

use AC;
use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\Response;
use AC\Type\ColumnId;
use AC\Type\ListScreenId;
use ACP\Column;
use ACP\Editing\PaginatedOptions;
use ACP\Editing\RemoteOptions;
use ACP\Editing\RequestHandler;
use ACP\Editing\Service;
use ACP\Editing\Strategy\AggregateFactory;

class SelectValues implements RequestHandler
{

    private Storage $storage;

    private AggregateFactory $aggregate_factory;

    public function __construct(Storage $storage, AggregateFactory $aggregate_factory)
    {
        $this->storage = $storage;
        $this->aggregate_factory = $aggregate_factory;
    }

    public function handle(Request $request)
    {
        $response = new Response\Json();

        $service = $this->get_service_from_request($request);

        if ( ! $service) {
            $response->error();
        }

        switch (true) {
            case $service instanceof RemoteOptions:
                $options = $service->get_remote_options(
                    $request->filter('item_id', null, FILTER_VALIDATE_INT) ?: null
                );

                $select = new AC\Helper\Select\Response($options, false);
                break;
            case $service instanceof PaginatedOptions:
                $options = $service->get_paginated_options(
                    (string)$request->filter('searchterm'),
                    (int)$request->filter('page', 1, FILTER_SANITIZE_NUMBER_INT),
                    $request->filter('item_id', null, FILTER_VALIDATE_INT) ?: null
                );
                $has_more = ! $options->is_last_page();

                $select = new AC\Helper\Select\Response($options, $has_more);
                break;
            default:
                $response->error();
                exit;
        }

        $response
            ->set_parameters($select())
            ->success();
    }

    private function get_service_from_request(Request $request): ?Service
    {
        $list_id = $request->get('layout');

        if ( ! ListScreenId::is_valid_id($list_id)) {
            return null;
        }

        $list_screen = $this->storage->find(new ListScreenId($list_id));

        if ( ! $list_screen || ! $list_screen->is_user_allowed(wp_get_current_user())) {
            return null;
        }

        $strategy = $this->aggregate_factory->create(
            $list_screen->get_table_screen()
        );

        if ( ! $strategy) {
            return null;
        }

        $column = $list_screen->get_column(new ColumnId((string)$request->get('column')));

        if ( ! $column instanceof Column) {
            return null;
        }

        return $column->editing();
    }

}