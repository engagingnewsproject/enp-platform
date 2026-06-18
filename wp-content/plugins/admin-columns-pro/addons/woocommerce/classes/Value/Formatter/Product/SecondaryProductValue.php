<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Value\Formatter\Order\Products;
use WC_Product;
use WC_Product_Variation;

class SecondaryProductValue implements Formatter
{

    private string $property;

    private string $taxonomy;

    public function __construct(string $property, string $taxonomy = '')
    {
        $this->property = $property;
        $this->taxonomy = $taxonomy;
    }

    public function format(Value $value): Value
    {
        $product = wc_get_product((int)$value->get_id());

        if ( ! $product) {
            return $value;
        }

        $secondary = $this->resolve_property($product);

        if ('' === $secondary && $product instanceof WC_Product_Variation) {
            $parent = wc_get_product($product->get_parent_id());

            if ($parent) {
                $secondary = $this->resolve_property($parent);
            }
        }

        if ('' === $secondary) {
            return $value;
        }

        return $value->with_value(
            sprintf('<div class="ac-product-secondary">%s %s</div>', $value, $secondary)
        );
    }

    private function resolve_property(WC_Product $product): string
    {
        switch ($this->property) {
            case 'sku':
                $sku = (string)$product->get_sku();

                return '' !== $sku ? self::pill($sku) : '';
            case 'price':
                $price = $product->get_price();

                return '' !== (string)$price ? self::pill((string)wc_price($price)) : '';
            case 'stock_status':
                $statuses = wc_get_product_stock_status_options();
                $status = $statuses[$product->get_stock_status()] ?? '';

                return '' !== $status ? self::pill($status) : '';
            case 'taxonomy':
                $taxonomy = $this->taxonomy ?: 'product_cat';

                $terms = get_the_terms($product->get_id(), $taxonomy);

                if ( ! $terms || is_wp_error($terms)) {
                    return '';
                }

                $pills = [];
                foreach ($terms as $term) {
                    $pills[] = self::pill(sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(get_edit_term_link($term->term_id, $taxonomy)),
                        esc_html($term->name)
                    ));
                }

                return implode(' ', $pills);
            case 'quantity':
                $quantity = Products::get_quantity($product->get_id());

                return $quantity > 0 ? self::pill('x ' . $quantity) : '';
            default:
                return '';
        }
    }

    private static function pill(string $content): string
    {
        return sprintf('<span class="ac-product-secondary__pill">%s</span>', $content);
    }

}
