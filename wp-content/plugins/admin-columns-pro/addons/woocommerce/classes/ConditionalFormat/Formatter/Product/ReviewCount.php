<?php

declare(strict_types=1);

namespace ACA\WC\ConditionalFormat\Formatter\Product;

use ACP\ConditionalFormat\Formatter;
use WC_Product;

class ReviewCount implements Formatter
{

    public function get_type(): string
    {
        return self::INTEGER;
    }

    public function format(string $value, $id, string $operator_group): string
    {
        $product = wc_get_product($id);

        if ( ! $product instanceof WC_Product) {
            return '';
        }

        return (string)$product->get_review_count();
    }

}