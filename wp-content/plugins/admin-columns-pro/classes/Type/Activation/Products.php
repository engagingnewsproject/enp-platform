<?php

namespace ACP\Type\Activation;

class Products
{

    /**
     * @var array e.g. `ac-addon-acf`
     */
    private array $product_slugs;

    public function __construct(array $product_slugs)
    {
        $this->product_slugs = $product_slugs;
    }

    public function get_value(): array
    {
        return $this->product_slugs;
    }

}