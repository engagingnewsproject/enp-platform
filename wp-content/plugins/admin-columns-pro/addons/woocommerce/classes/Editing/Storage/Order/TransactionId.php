<?php

declare(strict_types=1);

namespace ACA\WC\Editing\Storage\Order;

use ACP\Editing\Storage;
use WC_Order;

class TransactionId implements Storage
{

    public function get(int $id)
    {
        $order = wc_get_order($id);

        if ( ! $order instanceof WC_Order) {
            return null;
        }

        return $order->get_transaction_id();
    }

    public function update(int $id, $data): bool
    {
        $order = wc_get_order($id);

        $order->set_transaction_id($data);
        $order->save();

        return true;
    }

}