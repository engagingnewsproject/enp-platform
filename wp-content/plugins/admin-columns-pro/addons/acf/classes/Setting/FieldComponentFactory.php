<?php

declare(strict_types=1);

namespace ACA\ACF\Setting;

use AC;
use ACA\ACF\Field;
use ACA\ACF\FieldType;
use ACA\ACF\Setting\ComponentFactory\Date;
use ACA\ACF\Setting\ComponentFactory\OembedDisplay;

class FieldComponentFactory
{

    private AC\Setting\ComponentFactory\NumberFormat $number_format;

    private AC\Setting\ComponentFactory\StringLimit $string_limit;

    private AC\Setting\ComponentFactory\LinkablePostProperty $post_property;

    private AC\Setting\ComponentFactory\NumberOfItems $number_of_items;

    private AC\Setting\ComponentFactory\Separator $separator;

    private AC\Setting\ComponentFactory\UserProperty $user_property;

    private AC\Setting\ComponentFactory\UserLink $user_link;

    private AC\Setting\ComponentFactory\LinkLabel $link_label;

    private AC\Setting\ComponentFactory\TermProperty $term_property;

    private AC\Setting\ComponentFactory\TermLink $term_link;

    private AC\Setting\ComponentFactory\ImageSize $image_size;

    private AC\Setting\ComponentFactory\Password $password;

    private ComponentFactory\FlexDisplay $flex_display;

    private ComponentFactory\BeforeAfterExtendedFactory $before_after_factory;

    private OembedDisplay $oembed_display;

    public function __construct(
        AC\Setting\ComponentFactory\NumberFormat $number_format,
        AC\Setting\ComponentFactory\StringLimit $string_limit,
        AC\Setting\ComponentFactory\LinkablePostProperty $post_property,
        AC\Setting\ComponentFactory\Separator $separator,
        AC\Setting\ComponentFactory\NumberOfItems $number_of_items,
        AC\Setting\ComponentFactory\UserProperty $user_property,
        AC\Setting\ComponentFactory\UserLink $user_link,
        AC\Setting\ComponentFactory\LinkLabel $link_label,
        AC\Setting\ComponentFactory\TermProperty $term_property,
        AC\Setting\ComponentFactory\TermLink $term_link,
        AC\Setting\ComponentFactory\ImageSize $image_size,
        AC\Setting\ComponentFactory\Password $password,
        ComponentFactory\FlexDisplay $flex_display,
        ComponentFactory\BeforeAfterExtendedFactory $before_after_factory,
        OembedDisplay $oembed_display
    ) {
        $this->number_format = $number_format;
        $this->string_limit = $string_limit;
        $this->post_property = $post_property;
        $this->number_of_items = $number_of_items;
        $this->separator = $separator;
        $this->user_property = $user_property;
        $this->user_link = $user_link;
        $this->link_label = $link_label;
        $this->term_property = $term_property;
        $this->term_link = $term_link;
        $this->image_size = $image_size;
        $this->password = $password;
        $this->flex_display = $flex_display;
        $this->before_after_factory = $before_after_factory;
        $this->oembed_display = $oembed_display;
    }

    public function create(Field $field): array
    {
        $settings = $this->create_settings($field);

        if ($field instanceof Field\ValueWrapper) {
            $settings[] = $this->before_after_factory->create($field->get_prepend(), $field->get_append());
        }

        return $settings;
    }

    public function create_settings(Field $field): array
    {
        switch ($field->get_type()) {
            case FieldType::TYPE_NUMBER:
                return [
                    $this->number_format,
                ];
            case FieldType::TYPE_DATE_PICKER:
            case FieldType::TYPE_DATE_TIME_PICKER:
                $date_format = $field instanceof Field\Date
                    ? $field->get_display_format()
                    : 'y-m-d';

                return [
                    new Date($date_format),
                ];
            case FieldType::TYPE_OEMBED:
                return [
                    $this->oembed_display,
                ];
            case FieldType::TYPE_PASSWORD:
                return [
                    $this->password,
                ];
            case FieldType::TYPE_IMAGE:
                return [
                    $this->image_size,
                ];
            case FieldType::TYPE_GALLERY:
                return [
                    $this->image_size,
                    $this->number_of_items,
                ];
            case FieldType::TYPE_SELECT:
                if ( ! $this->is_multiple($field)) {
                    return [];
                }

                return [
                    $this->number_of_items,
                    $this->separator,
                ];
            case FieldType::TYPE_TEXT:
            case FieldType::TYPE_TEXTAREA:
            case FieldType::TYPE_WYSIWYG:
                return [
                    $this->string_limit,
                ];
            case FieldType::TYPE_FLEXIBLE_CONTENT:
                return [$this->flex_display];
            case FieldType::TYPE_POST:
            case FieldType::TYPE_RELATIONSHIP:
                $settings = [
                    $this->post_property,
                ];

                if ($this->is_multiple($field)) {
                    $settings[] = $this->number_of_items;
                    $settings[] = $this->separator;
                }

                return $settings;
            case FieldType::TYPE_USER:
                $settings = [
                    $this->user_property,
                    $this->user_link,
                ];

                if ($this->is_multiple($field)) {
                    $settings[] = $this->number_of_items;
                    $settings[] = $this->separator;
                }

                return $settings;
            case FieldType::TYPE_URL:
                return [$this->link_label];
            case FieldType::TYPE_TAXONOMY:
                $settings = [
                    $this->term_property,
                    $this->term_link,
                ];

                if ($this->is_multiple($field)) {
                    $settings[] = $this->number_of_items;
                    $settings[] = $this->separator;
                }

                return $settings;
            case FieldType::TYPE_TIME_PICKER:
            case FieldType::TYPE_PAGE_LINK:
            default:
                return [];
        }
    }

    private function is_multiple(Field $field): bool
    {
        if ($field instanceof Field\Type\GroupSubField) {
            return (bool)($field->get_settings()['multiple'] ?? false);
        }

        return $field instanceof Field\Multiple && $field->is_multiple();
    }

}