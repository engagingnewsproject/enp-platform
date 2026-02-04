<?php

declare(strict_types=1);

namespace ACA\WC\Admin;

use AC\Type\TableId;
use AC\Type\TableIdCollection;

class WcListKeysFactory extends TableIdsFactory
{

    public function create(): TableIdCollection
    {
        $keys = parent::create();

        $keys->add(new TableId('wp-users'));
        $keys->add(new TableId('product'));
        $keys->add(new TableId('product_variation'));
        $keys->add(new TableId('shop_coupon'));
        $keys->add(new TableId('shop_order'));
        $keys->add(new TableId('shop_subscription'));

        return $keys;
    }

}