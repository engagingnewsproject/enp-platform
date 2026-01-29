<?php

declare(strict_types=1);

namespace ACA\MetaBox;

use ACA\MetaBox\Field\Field;
use ACA\MetaBox\Field\Type;

final class FieldFactory
{

    public function create(array $settings): ?Field
    {
        $mapping = [
            MetaboxFieldTypes::CHECKBOX          => Type\Checkbox::class,
            MetaboxFieldTypes::CHECKBOX_LIST     => Type\CheckboxList::class,
            MetaboxFieldTypes::EMAIL             => Type\Email::class,
            MetaboxFieldTypes::NUMBER            => Type\Number::class,
            MetaboxFieldTypes::PASSWORD          => Type\Password::class,
            MetaboxFieldTypes::RADIO             => Type\Radio::class,
            MetaboxFieldTypes::RANGE             => Type\Range::class,
            MetaboxFieldTypes::SELECT            => Type\Select::class,
            MetaboxFieldTypes::SELECT_ADVANCED   => Type\SelectAdvanced::class,
            MetaboxFieldTypes::TEXT              => Type\Text::class,
            MetaboxFieldTypes::TEXTAREA          => Type\TextArea::class,
            MetaboxFieldTypes::URL               => Type\Url::class,

            // Advanced
            MetaboxFieldTypes::AUTOCOMPLETE      => Type\AutoComplete::class,
            MetaboxFieldTypes::COLORPICKER       => Type\Color::class,
            MetaboxFieldTypes::DATE              => Type\Date::class,
            MetaboxFieldTypes::DATETIME          => Type\DateTime::class,
            MetaboxFieldTypes::FIELDSET_TEXT     => Type\FieldsetText::class,
            MetaboxFieldTypes::GOOGLE_MAPS       => Type\GoogleMaps::class,
            MetaboxFieldTypes::IMAGE_SELECT      => Type\ImageSelect::class,
            MetaboxFieldTypes::OEMBED            => Type\Oembed::class,
            MetaboxFieldTypes::SLIDER            => Type\Slider::class,
            MetaboxFieldTypes::TEXT_LIST         => Type\TextList::class,
            MetaboxFieldTypes::TIME              => Type\Time::class,
            MetaboxFieldTypes::WYSIWYG           => Type\Wysiwyg::class,

            // WordPress
            MetaboxFieldTypes::POST              => Type\Post::class,
            MetaboxFieldTypes::TAXONOMY          => Type\Taxonomy::class,
            MetaboxFieldTypes::TAXONOMY_ADVANCED => Type\TaxonomyAdvanced::class,
            MetaboxFieldTypes::USER              => Type\User::class,

            // Upload
            MetaboxFieldTypes::FILE              => Type\File::class,
            MetaboxFieldTypes::FILE_ADVANCED     => Type\FileAdvanced::class,
            MetaboxFieldTypes::FILE_INPUT        => Type\FileInput::class,
            MetaboxFieldTypes::FILE_UPLOAD       => Type\FileUpload::class,
            MetaboxFieldTypes::IMAGE             => Field::class,
            MetaboxFieldTypes::IMAGE_ADVANCED    => Field::class,
            MetaboxFieldTypes::SINGLE_IMAGE      => Field::class,
            MetaboxFieldTypes::VIDEO             => Field::class,

            // Special
            MetaboxFieldTypes::GROUP             => Type\Group::class,
        ];

        if (array_key_exists($settings['type'], $mapping)) {
            $class = $mapping[$settings['type']];

            return new $class($settings);
        }

        return null;
    }

}