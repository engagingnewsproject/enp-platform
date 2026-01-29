<?php

declare(strict_types=1);

namespace ACA\MetaBox\Setting;

use AC\Setting\ComponentFactory;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use InvalidArgumentException;

class FieldComponentFactory
{

    private ComponentFactory\DateFormat\Date $date_format;

    private ComponentFactory\StringLimit $string_limit;

    private ComponentFactory\NumberFormat $number_format;

    private ComponentFactory\LinkablePostProperty $post_property;

    private ComponentFactory\TermProperty $term_property;

    private ComponentFactory\NumberOfItems $number_of_items;

    private ComponentFactory\UserProperty $user_property;

    private ComponentFactory\UserLink $user_link;

    private ComponentFactory\ImageSize $image_size;

    private ComponentFactory\TermLink $term_link;

    public function __construct(
        ComponentFactory\DateFormat\Date $date_format,
        ComponentFactory\StringLimit $string_limit,
        ComponentFactory\NumberFormat $number_format,
        ComponentFactory\LinkablePostProperty $post_property,
        ComponentFactory\TermProperty $term_property,
        ComponentFactory\NumberOfItems $number_of_items,
        ComponentFactory\UserProperty $user_property,
        ComponentFactory\UserLink $user_link,
        ComponentFactory\ImageSize $image_size,
        ComponentFactory\TermLink $term_link

    ) {
        $this->date_format = $date_format;
        $this->string_limit = $string_limit;
        $this->number_format = $number_format;
        $this->post_property = $post_property;
        $this->term_property = $term_property;
        $this->number_of_items = $number_of_items;
        $this->user_property = $user_property;
        $this->user_link = $user_link;
        $this->image_size = $image_size;
        $this->term_link = $term_link;
    }

    public function create(Field\Field $field): array
    {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::NUMBER:
                return [$this->number_format];
            case MetaboxFieldTypes::DATE:
                if ( ! $field instanceof Field\Type\Date) {
                    throw new InvalidArgumentException('Invalid field type');
                }

                return [new ComponentFactory\DateFormat\Date($field->get_date_format())];
            case MetaboxFieldTypes::DATETIME:
                if ( ! $field instanceof Field\Type\DateTime) {
                    throw new InvalidArgumentException('Invalid field type');
                }

                return [new ComponentFactory\DateFormat\Date($field->get_date_format())];
            case MetaboxFieldTypes::TEXT:
            case MetaboxFieldTypes::TEXTAREA:
                return [$this->string_limit];
            case MetaboxFieldTypes::POST:
                return [$this->post_property, $this->number_of_items];
            case MetaboxFieldTypes::TAXONOMY:
            case MetaboxFieldTypes::TAXONOMY_ADVANCED:
                return [$this->term_property, $this->term_link, $this->number_of_items];
            case MetaboxFieldTypes::USER:
                return [$this->user_property, $this->user_link];
            case MetaboxFieldTypes::IMAGE:
            case MetaboxFieldTypes::IMAGE_ADVANCED:
            case MetaboxFieldTypes::SINGLE_IMAGE:
                return [$this->image_size];
            default:
                return [];
        }
    }
}