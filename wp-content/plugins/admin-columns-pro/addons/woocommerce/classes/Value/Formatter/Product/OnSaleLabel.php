<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class OnSaleLabel extends ProductMethod
{

    public function is_scheduled(WC_Product $product): bool
    {
        return $product->get_date_on_sale_from() || $product->get_date_on_sale_to();
    }

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        if ( ! $product->is_on_sale() || ! $this->is_scheduled($product)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $date_from = $product->get_date_on_sale_from('edit') ? $product->get_date_on_sale_from('edit')->format(
            'Y-m-d'
        ) : null;
        $date_to = $product->get_date_on_sale_to('edit') ? $product->get_date_on_sale_to('edit')->format(
            'Y-m-d'
        ) : null;

        if ($date_from && $date_to) {
            return $value->with_value(sprintf('%s / %s', $date_from, $date_to));
        }

        if ($date_from) {
            return $value->with_value(
                _x('From', 'Product on sale from (date)', 'codepress-admin-columns') . ' ' . $date_from
            );
        }

        if ($date_to) {
            return $value->with_value(
                _x('Until', 'Product on sale from (date)', 'codepress-admin-columns') . ' ' . $date_to
            );
        }

        return $value->with_value('');
    }

}