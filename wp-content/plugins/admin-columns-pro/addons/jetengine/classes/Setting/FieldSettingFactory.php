<?php

declare(strict_types=1);

namespace ACA\JetEngine\Setting;

use AC\Setting\ComponentFactory;
use ACA\JetEngine\Field;

class FieldSettingFactory
{

    private ComponentFactory\DateFormat\Date $date_format;

    private ComponentFactory\NumberFormat $number_format;

    private ComponentFactory\StringLimit $string_limit;

    private ComponentFactory\ImageSize $image_size;

    private ComponentFactory\LinkablePostProperty $post_property;

    private ComponentFactory\NumberOfItems $number_of_items;

    public function __construct(
        ComponentFactory\DateFormat\Date $date_format,
        ComponentFactory\NumberFormat $number_format,
        ComponentFactory\StringLimit $string_limit,
        ComponentFactory\ImageSize $image_size,
        ComponentFactory\LinkablePostProperty $post_property,
        ComponentFactory\NumberOfItems $number_of_items
    ) {
        $this->date_format = $date_format;
        $this->number_format = $number_format;
        $this->string_limit = $string_limit;
        $this->image_size = $image_size;
        $this->post_property = $post_property;
        $this->number_of_items = $number_of_items;
    }

    public function create(Field\Field $field): array
    {
        switch ($field->get_type()) {
            case Field\Type\Checkbox::TYPE:
            case Field\Type\Select::TYPE:
                return [
                    $this->number_of_items,
                ];
            case Field\Type\Date::TYPE:
            case Field\Type\DateTime::TYPE:
                return [
                    $this->date_format,
                ];
            case Field\Type\Number::TYPE:
                return [
                    $this->number_format,
                ];
            case Field\Type\Textarea::TYPE:
            case Field\Type\Text::TYPE:
            case Field\Type\Wysiwyg::TYPE:
                return [
                    $this->string_limit,
                ];

            case Field\Type\Gallery::TYPE:
                return [
                    $this->image_size,
                ];
            case Field\Type\Posts::TYPE:
                return [
                    $this->post_property,
                ];
            default:
                return [];
        }
    }
}