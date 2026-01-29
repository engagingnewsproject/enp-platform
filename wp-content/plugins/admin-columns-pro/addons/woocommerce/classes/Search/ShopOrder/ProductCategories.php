<?php

declare(strict_types=1);

namespace ACA\WC\Search\ShopOrder;

class ProductCategories extends ProductTaxonomy
{

    public function __construct()
    {
        parent::__construct('product_cat');
    }

}