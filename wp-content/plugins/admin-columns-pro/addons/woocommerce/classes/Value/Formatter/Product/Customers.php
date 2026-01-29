<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Value\ExtendedValue;

class Customers implements Formatter
{

    public function format(Value $value)
    {
        $product_id = (int)$value->get_id();
        $label = $value->get_value();

        if ( ! $label) {
            throw ValueNotFoundException::from_id($product_id);
        }

        $link = new ExtendedValue\Product\Customers();

        $link = $link->get_link($product_id, (string)$label)
                     ->with_title(strip_tags(get_the_title($product_id)));

        $edit_link = get_edit_post_link($product_id);

        if ($edit_link) {
            $link->with_edit_link(get_edit_post_link($product_id));
        }

        return $value->with_value(
            $link->render()
        );
    }

}