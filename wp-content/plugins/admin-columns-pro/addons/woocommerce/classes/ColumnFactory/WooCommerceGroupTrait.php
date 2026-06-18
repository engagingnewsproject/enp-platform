<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory;

trait WooCommerceGroupTrait
{

    public function get_group(): string
    {
        return 'woocommerce';
    }
}