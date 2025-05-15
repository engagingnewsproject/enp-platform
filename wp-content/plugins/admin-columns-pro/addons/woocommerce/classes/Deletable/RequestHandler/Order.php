<?php

declare(strict_types=1);

namespace ACA\WC\Deletable\RequestHandler;

use ACP\Editing\BulkDelete\RequestHandler;

class Order extends RequestHandler
{

    protected function delete($id, array $args = []): void
    {
        $id = (int)$id;

        $force_delete = 'true' === ($args['force_delete'] ?? null);

        wc_get_order($id)->delete($force_delete);;
    }
}