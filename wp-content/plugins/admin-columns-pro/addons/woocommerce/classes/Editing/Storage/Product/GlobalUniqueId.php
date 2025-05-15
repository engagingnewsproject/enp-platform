<?php

namespace ACA\WC\Editing\Storage\Product;

use ACP\Editing\Storage;

class GlobalUniqueId implements Storage
{

    public function get(int $id)
    {
        $product = wc_get_product($id);

        return $product ? $product->get_global_unique_id() : false;
    }

    public function update(int $id, $data): bool
    {
        $product = wc_get_product($id);
        $product->set_global_unique_id($data);

        return $product->save() > 0;
    }

}