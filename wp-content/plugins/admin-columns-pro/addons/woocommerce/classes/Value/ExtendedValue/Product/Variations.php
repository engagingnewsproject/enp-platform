<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\Product;

use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use AC\View;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;

class Variations implements ExtendedValue
{

    private const NAME = 'product-variations';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $view = new View([
            'items' => $this->get_variation_items($id),
        ]);

        return $view->set_template('modal-value/variations')->render();
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, self::NAME))
            ->with_class('-nopadding -w-large');
    }

    private function get_variation_items(int $id): array
    {
        $items = [];

        foreach ($this->get_variations($id) as $variation) {
            $name = $variation->get_name();
            $edit = get_edit_post_link($id);

            if ($edit) {
                $name = sprintf('<a target="_blank" href="%s#variation_%d">%s</a>', $edit, $variation->get_id(), $name);
            }

            $items[] = [
                'name'       => $name,
                'sku'        => $variation->get_sku(),
                'price'      => $variation->get_price_html(),
                'attributes' => implode(
                    '&nbsp;&nbsp;-&nbsp;&nbsp;',
                    $this->get_attributes($id, $variation->get_attributes())
                ),
                'stock'      => $this->get_stock($variation),
            ];
        }

        return $items;
    }

    private function get_attributes(int $product_id, array $attributes): array
    {
        $product = wc_get_product($product_id);
        $labels = [];

        if ( ! $product) {
            return $labels;
        }

        $attribute_objects = $product->get_attributes();

        foreach ($attributes as $name => $value) {
            $attribute = $attribute_objects[$name] ?? null;

            if ( ! $attribute instanceof WC_Product_Attribute) {
                continue;
            }

            $label = $attribute->get_name();

            if ($attribute->is_taxonomy()) {
                $term = get_term_by('slug', $value, $attribute->get_taxonomy());
                $label = $attribute->get_taxonomy_object()->attribute_label;

                if ($term) {
                    $value = $term->name;
                }
            }

            $labels[] = sprintf('<strong>%s</strong>: %s', $label, $value);
        }

        return $labels;
    }

    private function get_variation_ids(int $product_id): array
    {
        $product = wc_get_product($product_id);

        return $product instanceof WC_Product_Variable
            ? $product->get_children()
            : [];
    }

    private function get_variations(int $product_id): array
    {
        $variations = [];

        foreach ($this->get_variation_ids($product_id) as $variation_id) {
            $variation = wc_get_product($variation_id);

            if ($variation instanceof WC_Product_Variation && $variation->exists()) {
                $variations[] = $variation;
            }
        }

        return $variations;
    }

    private function get_stock(WC_Product_Variation $variation): string
    {
        if ( ! $variation->managing_stock()) {
            return sprintf('<mark class="instock">%s</mark>', __('In stock', 'woocommerce'));
        }

        $qty = $variation->get_stock_quantity();

        if ( ! $variation->is_in_stock() || $qty < 1) {
            return sprintf('<mark class="outofstock">%s</mark>', __('Out of stock', 'woocommerce'));
        }

        return sprintf('<mark class="instock">%s</mark> (%d)', __('In stock', 'woocommerce'), $qty);
    }

}