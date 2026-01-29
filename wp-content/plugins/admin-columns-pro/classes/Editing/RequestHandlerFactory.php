<?php

namespace ACP\Editing;

use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\Type\ListScreenId;
use ACP\Editing;

class RequestHandlerFactory
{

    private Storage $storage;

    private Strategy\AggregateFactory $aggregate_factory;

    private BulkDelete\AggregateFactory $aggregate_factory_delete;

    public function __construct(
        Storage $storage,
        Editing\Strategy\AggregateFactory $aggregate_factory,
        Editing\BulkDelete\AggregateFactory $aggregate_factory_delete
    ) {
        $this->storage = $storage;
        $this->aggregate_factory = $aggregate_factory;
        $this->aggregate_factory_delete = $aggregate_factory_delete;
    }

    public function create(Request $request): ?RequestHandler
    {
        switch ($request->get('method')) {
            case 'bulk-deletable-rows' :
                $list_screen = $this->storage->find(new ListScreenId($request->get('layout')));

                if ( ! $list_screen) {
                    return null;
                }

                $strategy = $this->aggregate_factory_delete->create($list_screen->get_table_screen());

                if ( ! $strategy) {
                    return null;
                }

                return $strategy->get_query_request_handler();

            case 'bulk-editable-rows' :
                $list_screen = $this->storage->find(new ListScreenId($request->get('layout')));

                if ( ! $list_screen) {
                    return null;
                }

                $strategy = $this->aggregate_factory->create($list_screen->get_table_screen());

                if ( ! $strategy) {
                    return null;
                }

                return $strategy->get_query_request_handler();
            default:
                return null;
        }
    }

}