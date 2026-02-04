<?php

declare(strict_types=1);

namespace ACA\ACF;

use ACA\ACF\Field\Type;

class FieldFactory
{

    public function create(array $settings): ?Field
    {
        if (isset($settings['_ac_type']) && $settings['_ac_group']) {
            $parent_group = $this->create($settings['_ac_group']);

            if ( ! $parent_group) {
                return null;
            }

            $sub_settings = $settings;

            unset($sub_settings['_ac_type']);

            $sub_field = $this->create($sub_settings);

            if ( ! $sub_field) {
                return null;
            }

            return new Type\GroupSubField($settings, $parent_group, $sub_field);
        }

        if ( ! Field::validate($settings)) {
            return null;
        }

        switch ($settings['type']) {
            case FieldType::TYPE_TAB:
            case FieldType::TYPE_MESSAGE:
                return null;

            case FieldType::TYPE_BUTTON_GROUP:
                return new Type\ButtonGroup($settings);

            case FieldType::TYPE_CHECKBOX:
                return new Type\Checkbox($settings);

            case FieldType::TYPE_COLOR_PICKER:
                return new Type\Color($settings);

            case FieldType::TYPE_DATE_PICKER:
                return new Type\Date($settings);

            case FieldType::TYPE_DATE_TIME_PICKER:
                return new Type\DateTime($settings);

            case FieldType::TYPE_EMAIL:
                return new Type\Email($settings);

            case FieldType::TYPE_FILE:
                return new Type\File($settings);

            case FieldType::TYPE_FLEXIBLE_CONTENT:
                return new Type\FlexibleContent($settings);

            case FieldType::TYPE_IMAGE:
            case FieldType::TYPE_IMAGE_CROP:
                return new Type\Image($settings);

            case FieldType::TYPE_NUMBER:
                return new Type\Number($settings);

            case FieldType::TYPE_PASSWORD:
                return new Type\Password($settings);

            case FieldType::TYPE_PAGE_LINK:
                return new Type\PageLinks($settings);

            case FieldType::TYPE_POST:
                return new Type\PostObject($settings);

            case FieldType::TYPE_RADIO:
                return new Type\Radio($settings);

            case FieldType::TYPE_RANGE:
                return new Type\Range($settings);

            case FieldType::TYPE_RELATIONSHIP:
                return new Type\Relationship($settings);

            case FieldType::TYPE_REPEATER:
                return new Type\Repeater($this, $settings);

            case FieldType::TYPE_SELECT:
                return new Type\Select($settings);

            case FieldType::TYPE_TAXONOMY:
                return new Type\Taxonomy($settings);

            case FieldType::TYPE_TEXT:
                return new Type\Text($settings);

            case FieldType::TYPE_TEXTAREA:
                return new Type\Textarea($settings);

            case FieldType::TYPE_URL:
                return new Type\Url($settings);

            case FieldType::TYPE_USER:
                return new Type\User($settings);

            case FieldType::TYPE_WYSIWYG:
                return new Type\Wysiwyg($settings);

            case FieldType::TYPE_BOOLEAN:
            case FieldType::TYPE_CLONE:
            case FieldType::TYPE_GALLERY:
            case FieldType::TYPE_LINK:
            case FieldType::TYPE_GOOGLE_MAP:
            case FieldType::TYPE_TIME_PICKER:
            case FieldType::TYPE_OEMBED:
            default:
                return new Field($settings);
        }
    }

}