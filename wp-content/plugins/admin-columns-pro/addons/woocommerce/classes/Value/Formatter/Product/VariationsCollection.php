<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use WC_Product_Variable;
use WC_Product_Variation;

class VariationsCollection implements Formatter
{

    public function format(Value $value)
    {
        $id = (int)$value->get_id();
        $product = wc_get_product($id);

        if ( ! $product instanceof WC_Product_Variable) {
            throw ValueNotFoundException::from_id($id);
        }

        $variations = new ValueCollection($id, []);

        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);

            if ($variation instanceof WC_Product_Variation && $variation->exists()) {
                $variations->add(new Value((int)$variation->get_id(), $variation));
            }
        }

        if ($variations->count() === 0) {
            throw ValueNotFoundException::from_id($id);
        }

        return $variations;
    }

}