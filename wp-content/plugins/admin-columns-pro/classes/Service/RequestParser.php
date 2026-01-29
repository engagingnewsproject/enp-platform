<?php

namespace ACP\Service;

use AC\Registerable;
use AC\Request;
use AC\RequestHandlerFactory;

class RequestParser implements Registerable
{

    private RequestHandlerFactory $handler_factory;

    public function __construct(RequestHandlerFactory $handler_factory)
    {
        $this->handler_factory = $handler_factory;
    }

    public function register(): void
    {
        add_action('admin_init', [$this, 'handle']);
    }

    public function handle(): void
    {
        if ( ! $this->handler_factory->is_request()) {
            return;
        }

        $this->handler_factory
            ->create()
            ->handle(new Request());
    }

}