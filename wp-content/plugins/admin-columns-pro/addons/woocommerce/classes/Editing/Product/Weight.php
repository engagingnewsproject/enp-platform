<?php

declare(strict_types=1);

namespace ACA\WC\Editing\Product;

use ACA\WC\Editing\Storage;
use ACP;
use ACP\Editing\View;

class Weight implements ACP\Editing\Service, ACP\Editing\Service\Editability
{

    public function is_editable(int $id): bool
    {
        $product = wc_get_product($id);

        return $product && ! $product->is_virtual();
    }

    public function get_not_editable_reason(int $id): string
    {
        return __('Virtual product can not be edited.', 'codepress-admin-columns');
    }

    public function get_view(string $context): ?View
    {
        $view = new ACP\Editing\View\Number();
        $view->set_step('any');
        $view->set_min(0);

        return $view;
    }

    public function get_value(int $id)
    {
        $product = wc_get_product($id);

        return $product ? $product->get_weight() : false;
    }

    public function update(int $id, $data): void
    {
        $product = wc_get_product($id);
        $product->set_weight($data);
        $product->save();
    }

}