<?php

declare(strict_types=1);

namespace ACA\WC\QuickAdd;

use AC\TableScreen;
use ACA\WC\QuickAdd\Create\Coupon;
use ACA\WC\QuickAdd\Create\Product;
use ACP\QuickAdd\Model\Create;
use ACP\QuickAdd\Model\ModelFactory;

class Factory implements ModelFactory
{

    public function create(TableScreen $table_screen): ?Create
    {
        switch ((string)$table_screen->get_id()) {
            case 'product':
                return new Product();
            case 'shop_coupon':
                return new Coupon();
            default:
                return null;
        }
    }

}