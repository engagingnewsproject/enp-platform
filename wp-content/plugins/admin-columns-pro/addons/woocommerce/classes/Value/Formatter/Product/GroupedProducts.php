<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Value\ExtendedValue;
use WC_Product_Grouped;

class GroupedProducts implements Formatter
{

    public function format(Value $value)
    {
        $product = wc_get_product($value->get_id());

        if ( ! $product instanceof WC_Product_Grouped) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $ids = $product->get_children();

        if (empty($ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $link = new ExtendedValue\Product\GroupedProducts();
        $link = $link->get_link($value->get_id(), (string)count($ids))
                     ->with_title(
                         sprintf(
                             __('Grouped products for %s', 'codepress-admin-columns'),
                             sprintf('”%s”', $product->get_title())
                         )
                     );

        $edit = get_edit_post_link($product->get_id());

        if ($edit) {
            $link->with_edit_link($edit);
        }

        return $value->with_value($link->render());
    }

}