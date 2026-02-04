<?php

declare(strict_types=1);

namespace ACA\WC\BulkDelete\Deletable\RequestHandler;

use ACP\Editing\BulkDelete\RequestHandler;
use RuntimeException;

class Order extends RequestHandler
{

    protected function delete($id, array $args = []): void
    {
        $id = (int)$id;

        $order = wc_get_order($id);

        if ( ! $order) {
            throw new RuntimeException(__('Order does not exists.', 'codepress-admin-columns'));
        }

        $force_delete = 'true' === ($args['force_delete'] ?? null);

        $order->delete($force_delete);
    }
}