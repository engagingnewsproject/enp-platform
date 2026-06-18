<?php

declare(strict_types=1);

namespace ACA\WC\Editing\Strategy;

use ACA\WC\Editing;
use ACP;

class Order implements ACP\Editing\Strategy
{

    public function user_can_edit_item(int $id): bool
    {
        return $this->user_can_edit();
    }

    public function user_can_edit(): bool
    {
        return current_user_can('edit_shop_orders');
    }

    public function get_query_request_handler(): ACP\Editing\RequestHandler
    {
        return new Editing\RequestHandler\Query\Order();
    }

}