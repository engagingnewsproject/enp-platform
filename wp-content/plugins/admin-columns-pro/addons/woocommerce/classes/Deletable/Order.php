<?php

declare(strict_types=1);

namespace ACA\WC\Deletable;

use ACA\WC;
use ACP\Editing;
use ACP\Editing\BulkDelete\Deletable;

class Order implements Deletable
{

    public function user_can_delete(): bool
    {
        return current_user_can('delete_shop_orders');
    }

    public function get_delete_request_handler(): Editing\RequestHandler
    {
        return new RequestHandler\Order();
    }

    public function get_query_request_handler(): Editing\RequestHandler
    {
        return new WC\Editing\RequestHandler\Query\Order();
    }

}