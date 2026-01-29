<?php

declare(strict_types=1);

namespace ACP\Editing\Registerable;

use AC\Registerable;
use AC\Request;
use AC\Vendor\DI;
use ACP\Editing\RequestHandlerAjaxFactory;
use ACP\Editing\RequestHandlerFactory;

class RequestHandlers implements Registerable
{

    private $container;

    public function __construct(DI\Container $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        add_action('ac/table/list_screen', [$this, 'handle_request_query']);
        add_action('wp_ajax_acp_editing_request', [$this, 'ajax_edit_request']);
    }

    public function handle_request_query(): void
    {
        $request = new Request();

        $request_handler = $this->container->get(RequestHandlerFactory::class)
                                           ->create($request);

        if ($request_handler) {
            $request_handler->handle($request);
        }
    }

    public function ajax_edit_request(): void
    {
        check_ajax_referer('ac-ajax');

        $request = new Request();
        $factory = $this->container->get(RequestHandlerAjaxFactory::class);

        $factory->create($request)
                ->handle($request);
    }
}