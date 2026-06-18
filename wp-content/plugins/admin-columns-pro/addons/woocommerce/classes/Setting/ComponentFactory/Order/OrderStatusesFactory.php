<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

class OrderStatusesFactory
{

    public function create(array $default_statuses = []): OrderStatuses
    {
        return new OrderStatuses($default_statuses);
    }

}