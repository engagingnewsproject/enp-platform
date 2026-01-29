<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use WC_Product;

class UpsellCollection implements Formatter
{

    public function format(Value $value)
    {
        $product = wc_get_product($value->get_id());

        if ( ! $product instanceof WC_Product) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $upsell_ids = $product->get_upsell_ids();

        if (empty($upsell_ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $upsell_ids);
    }

}