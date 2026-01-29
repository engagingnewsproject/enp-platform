<?php

declare(strict_types=1);

namespace ACA\WC\Editing\ProductVariation;

use ACA\WC\Editing\Storage;
use ACP;
use ACP\Editing\View;
use WC_Product_Variation;

class Description implements ACP\Editing\Service
{

    public function get_view(string $context): ?View
    {
        return (new ACP\Editing\View\TextArea())->set_clear_button(true);
    }

    public function get_value(int $id): string
    {
        $product = new WC_Product_Variation($id);

        return $product->get_description();
    }

    public function update(int $id, $data): void
    {
        $product = new WC_Product_Variation($id);
        $product->set_description($data);
        $product->save() > 0;
    }

}