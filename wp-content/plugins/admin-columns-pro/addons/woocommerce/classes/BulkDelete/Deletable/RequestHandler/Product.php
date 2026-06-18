<?php

declare(strict_types=1);

namespace ACA\WC\BulkDelete\Deletable\RequestHandler;

use ACP\Editing\BulkDelete\RequestHandler;
use RuntimeException;

class Product extends RequestHandler
{

    protected function delete($id, array $args = []): void
    {
        $id = (int)$id;

        $product = wc_get_product($id);

        if ( ! $product) {
            throw new RuntimeException(
                sprintf('%s %d', __('Product does not exists.', 'codepress-admin-columns'), $id)
            );
        }

        $force_delete = 'true' === ($args['force_delete'] ?? null);

        $product->delete($force_delete);
    }
}