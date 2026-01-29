<?php

declare(strict_types=1);

namespace ACA\WC\Editing\Storage\Product\Attributes;

use ACA\WC\Editing\Storage;
use ACA\WC\Helper\Attributes;
use WC_Product_Attribute;

class Custom extends Storage\Product\Attributes
{

    /**
     * @var array
     */
    private $custom_labels;

    public function __construct(string $attribute)
    {
        parent::__construct($attribute);

        $this->custom_labels = $this->get_custom_labels();
    }

    public function get_custom_labels(): array
    {
        $attributes = (new Attributes())->get_custom_attributes();

        return array_map(static function ($attribute) {
            return $attribute['name'] ?? '';
        }, $attributes);
    }

    protected function create_attribute(): ?WC_Product_Attribute
    {
        $labels = $this->custom_labels;

        if ( ! isset($labels[$this->attribute])) {
            return null;
        }

        $label = $labels[$this->attribute];

        $attribute = new WC_Product_Attribute();
        $attribute->set_name($label);

        return $attribute;
    }

}