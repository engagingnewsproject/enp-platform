<?php

declare(strict_types=1);

namespace ACA\WC\Editing\Product;

use ACA\WC\Editing\PostTrait;
use ACA\WC\Editing\Storage;
use ACP;
use ACP\Editing\View;

class GlobalUniqueId implements ACP\Editing\Service
{

    use ProductNotSupportedReasonTrait;
    use PostTrait;

    public function get_view(string $context): ?View
    {
        return (new ACP\Editing\View\Text())->set_clear_button(true);
    }

    public function get_value(int $id)
    {
        $product = wc_get_product($id);

        return $product ? $product->get_global_unique_id() : '';
    }

    public function update(int $id, $data): void
    {
        $product = wc_get_product($id);

        if ( ! $product) {
            return;
        }

        $product->set_global_unique_id($data);
        $product->save();
    }

}