<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\Product;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use WC_Product_Grouped;

class GroupedProducts implements ExtendedValue
{

    public function can_render(string $view): bool
    {
        return $view === 'grouped-products';
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $product = wc_get_product($id);

        if ( ! $product instanceof WC_Product_Grouped) {
            return '';
        }

        $items = [];

        $ids = $product->get_children();

        foreach ($ids as $child_id) {
            $child = wc_get_product($child_id);

            if ( ! $child) {
                continue;
            }

            $label = $child->get_title();

            $edit = get_edit_post_link($child->get_id());

            if ($edit) {
                $label = sprintf('<a href="%s">%s</a>', $edit, $label);
            }

            $items[] = [
                'title' => $label,
                'sku'   => $child->get_sku(),
                'stock' => $child->get_stock_quantity(),
                'price' => wc_price($child->get_price()),
            ];
        }

        $view = new AC\View([
            'items'   => $items,
            'message' => '',
        ]);

        return $view->set_template('modal-value/products')->render();
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, 'grouped-products'))
            ->with_class('-nopadding -w-large');
    }

}