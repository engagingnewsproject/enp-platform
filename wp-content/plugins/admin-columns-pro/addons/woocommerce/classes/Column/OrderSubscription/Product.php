<?php

namespace ACA\WC\Column\OrderSubscription;

use ACA\WC\Column;
use ACA\WC\Search;

class Product extends Column\Order\Product
{

    public function search()
    {
        return new Search\OrderSubscription\Product();
    }

}