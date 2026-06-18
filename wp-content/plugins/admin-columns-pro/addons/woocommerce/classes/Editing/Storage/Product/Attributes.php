<?php

declare(strict_types=1);

namespace ACA\WC\Editing\Storage\Product;

use ACA\WC\Editing\EditValue;
use ACA\WC\Editing\StorageModel;
use ACP\Editing\Storage;
use RuntimeException;
use WC_Product_Attribute;

abstract class Attributes implements Storage
{

    protected string $attribute;

    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }

    abstract protected function create_attribute(): ?WC_Product_Attribute;

    public function get(int $id)
    {
        $attribute = $this->get_attribute_object($id);

        return $attribute
            ? array_values($attribute->get_options())
            : [];
    }

    public function update(int $id, $data): bool
    {
        $attribute = $this->get_attribute_object($id);

        if ( ! $attribute) {
            $attribute = $this->create_attribute();
        }

        if ( ! $attribute) {
            throw new RuntimeException(__('Non existing attribute.', 'codepress-admin-columns'));
        }

        $attribute->set_options($data);

        $product = wc_get_product($id);

        $attributes = $product->get_attributes();
        $attributes[] = $attribute;

        $product->set_attributes($attributes);

        return $product->save() > 0;
    }

    protected function get_attribute_object(int $id): ?WC_Product_Attribute
    {
        $product = wc_get_product($id);

        if ( ! $product) {
            return null;
        }

        $attributes = (array)$product->get_attributes();

        $product_attribute = $attributes[$this->attribute] ?? null;

        if ( ! $product_attribute instanceof WC_Product_Attribute) {
            return null;
        }

        return $product_attribute;
    }
}