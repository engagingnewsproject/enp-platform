<?php

declare(strict_types=1);

namespace ACP\Filtering\Service\Table;

use AC\ListScreen;
use AC\Registerable;
use AC\Request;
use ACP\Filtering\RequestHandler;

class FilterRequestHandler implements Registerable
{

    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function register(): void
    {
        add_action('ac/table/list_screen', [$this, 'handle_request']);
    }

    public function handle_request(ListScreen $list_screen): void
    {
        (new RequestHandler\Filters($list_screen))->handle($this->request);
    }

}