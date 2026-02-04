<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Product;
use WC_Product_Attribute;

class AllProductAttributes implements Formatter
{

    public function format(Value $value)
    {
        $product = wc_get_product($value->get_id());

        if ( ! $product instanceof WC_Product) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $attributes = [];

        foreach ($product->get_attributes() as $attribute) {
            if ($attribute->is_taxonomy()) {
                $label = wc_attribute_label($attribute->get_name(), $product);
                $options = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
            } else {
                $label = $attribute->get_name();
                $options = $attribute->get_options();
            }

            $tooltip = $this->get_tooltip($attribute);

            if ($label && $tooltip) {
                $label = '<span ' . ac_helper()->html->get_tooltip_attr($tooltip) . '>' . esc_html($label) . '</span>';
            }

            $attributes[] = '
				<div class="attribute">
					<strong class="label">' . $label . ':</strong>
					<span class="values">' . implode(', ', $options) . '</span>
				</div>
				';
        }

        $attributes = array_filter($attributes);

        if ( ! $attributes) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(implode('', $attributes));
    }

    private function get_tooltip(WC_Product_Attribute $attribute): string
    {
        // Tooltip
        $tooltip = [];

        if ($attribute->get_visible()) {
            $tooltip[] = __('Visible on the product page', 'woocommerce');
        }

        if ($attribute->get_variation()) {
            $tooltip[] = __('Used for variations', 'woocommerce');
        }

        if ($attribute->is_taxonomy()) {
            $tooltip[] = __('Is a taxonomy', 'codepress-admin-columns');
        }

        return implode('<br/>', $tooltip);
    }

}