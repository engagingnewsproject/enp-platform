<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value;

use AC;
use AC\Formatter\Aggregate;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACA\MetaBox\Value;

class ValueFormatterFactory
{

    public function create(FormatterCollection $formatters, Field\Field $field, Config $config): FormatterCollection
    {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::AUTOCOMPLETE:
                return $formatters->prepend(
                    Aggregate::from_array([
                        new AC\Formatter\ArrayToCollection(),
                        new AC\Formatter\MapOptionLabel($field instanceof Field\Choices ? $field->get_choices() : []),
                    ])
                );
            case MetaboxFieldTypes::COLORPICKER:
                return $formatters->add(new AC\Formatter\Color());
            case MetaboxFieldTypes::CHECKBOX:
                return $formatters->prepend(new AC\Formatter\YesNoIcon());
            case MetaboxFieldTypes::CHECKBOX_LIST:
                $formatter = Aggregate::from_array([
                    new AC\Formatter\ArrayToCollection(),
                    new AC\Formatter\MapOptionLabel($field instanceof Field\Choices ? $field->get_choices() : []),
                    new AC\Formatter\Collection\Separator(', '),
                ]);

                return $formatters->prepend($formatter);
            case MetaboxFieldTypes::DATETIME:
            case MetaboxFieldTypes::DATE:
                return $formatters->prepend(new Value\Formatter\GroupDateFix());
            case MetaboxFieldTypes::TEXT_LIST:
                return $formatters->add(
                    new Value\Formatter\TextList($field instanceof Field\Choices ? $field->get_choices() : [])
                );
            case MetaboxFieldTypes::FIELDSET_TEXT:
                return $formatters->add(new Value\Formatter\FieldsetValues());
            case MetaboxFieldTypes::GOOGLE_MAPS:
                return $formatters->add(new Value\Formatter\Maps());
            case MetaboxFieldTypes::RADIO:
                return $formatters->prepend(
                    new AC\Formatter\MapOptionLabel($field instanceof Field\Choices ? $field->get_choices() : [])
                );
            case MetaboxFieldTypes::SELECT:
            case MetaboxFieldTypes::SELECT_ADVANCED:
                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    return $formatters->prepend(
                        Aggregate::from_array([
                                new AC\Formatter\ArrayToCollection(),
                                new AC\Formatter\MapOptionLabel(
                                    $field instanceof Field\Choices ? $field->get_choices() : []
                                ),
                            ]
                        )
                    );
                }

                return $formatters->prepend(
                    new AC\Formatter\MapOptionLabel($field instanceof Field\Choices ? $field->get_choices() : [])
                );
            case MetaboxFieldTypes::OEMBED:
            case MetaboxFieldTypes::URL:
                return $formatters->add(new Value\Formatter\LinkableUrlDecode());
            case MetaboxFieldTypes::POST:
                $aggregate = $this->create_aggregate_formatter_collection($formatters);
                $aggregate->prepend(new AC\Formatter\RelationIdsCollection());

                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    $aggregate->add(AC\Formatter\Collection\Separator::create_from_config($config));
                }

                return new FormatterCollection([new Aggregate($aggregate)]);
            case MetaboxFieldTypes::TAXONOMY:
            case MetaboxFieldTypes::TAXONOMY_ADVANCED:
                $aggregate = $this->create_aggregate_formatter_collection($formatters);
                $aggregate->prepend(new Value\Formatter\TermIds());

                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    $aggregate->add(AC\Formatter\Collection\Separator::create_from_config($config));
                }

                return new FormatterCollection([new Aggregate($aggregate)]);

            case MetaboxFieldTypes::USER:
                $aggregate = $this->create_aggregate_formatter_collection($formatters);
                $aggregate->prepend(new AC\Formatter\RelationIdsCollection());

                return new FormatterCollection([new Aggregate($aggregate)]);

            case MetaboxFieldTypes::FILE:
            case MetaboxFieldTypes::FILE_ADVANCED:
            case MetaboxFieldTypes::FILE_UPLOAD:
                return $formatters->prepend(new Aggregate(new FormatterCollection([
                        new AC\Formatter\ArrayToCollection(),
                        new Value\Formatter\FileDownload(),
                    ])
                ));
            case MetaboxFieldTypes::IMAGE:
            case MetaboxFieldTypes::IMAGE_ADVANCED:
                $aggregate = $this->create_aggregate_formatter_collection($formatters);
                $aggregate->prepend(new Value\Formatter\ImageIds());
                $aggregate->add(new AC\Formatter\Collection\Separator(''));

                return new FormatterCollection([new Aggregate($aggregate)]);
            case MetaboxFieldTypes::SINGLE_IMAGE:
                return $formatters->prepend(new Value\Formatter\ImageId());
            case MetaboxFieldTypes::VIDEO:
                return $formatters->prepend(new Aggregate(new FormatterCollection([
                        new AC\Formatter\ArrayToCollection(),
                        new Value\Formatter\VideoLink(),
                    ])
                ));

            default:
                return $formatters;
        }
    }

    private function create_aggregate_formatter_collection(FormatterCollection $component_formatters
    ): FormatterCollection {
        $collection = new FormatterCollection();

        foreach ($component_formatters as $formatter) {
            $collection->add($formatter);
        }

        return $collection;
    }

}