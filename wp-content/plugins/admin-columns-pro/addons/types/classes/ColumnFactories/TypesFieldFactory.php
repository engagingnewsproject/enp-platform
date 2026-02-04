<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactories;

use AC;
use AC\Collection;
use AC\Collection\ColumnFactories;
use AC\ColumnFactoryCollectionFactory;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA;
use ACA\Types\ColumnFactory;
use ACA\Types\Field;

final class TypesFieldFactory implements ColumnFactoryCollectionFactory
{

    private Container $container;

    private ACA\Types\FieldRepository $field_repository;

    public function __construct(Container $container, ACA\Types\FieldRepository $field_repository)
    {
        $this->container = $container;
        $this->field_repository = $field_repository;
    }

    private function create_context_by_table_Screen(TableScreen $table_screen): ?AC\Type\TableScreenContext
    {
        if ( ! $table_screen instanceof AC\TableScreen\MetaType) {
            return null;
        }

        return AC\Type\TableScreenContext::from_table_screen($table_screen);
    }

    public function create(TableScreen $table_screen): ColumnFactories
    {
        $types_fields = $this->field_repository->find_all($table_screen);
        $context = $this->create_context_by_table_Screen($table_screen);
        $collection = new Collection\ColumnFactories();

        if ( ! $context || empty($types_fields)) {
            return $collection;
        }

        foreach ($types_fields as $field) {
            $column_factory = $this->create_by_field($field, $context);

            if ( ! $column_factory) {
                continue;
            }

            $collection->add($column_factory);
        }

        return $collection;
    }

    protected function create_by_field(
        Field $field,
        AC\Type\TableScreenContext $table_context
    ): ?AC\Column\ColumnFactory {
        $mapping = [
            ACA\Types\FieldTypes::AUDIO          => ColumnFactory\Field\Audio::class,
            ACA\Types\FieldTypes::CHECKBOX       => ColumnFactory\Field\Checkbox::class,
            ACA\Types\FieldTypes::CHECKBOXES     => ColumnFactory\Field\Checkboxes::class,
            ACA\Types\FieldTypes::COLORPICKER    => ColumnFactory\Field\Colorpicker::class,
            ACA\Types\FieldTypes::DATE           => ColumnFactory\Field\Date::class,
            ACA\Types\FieldTypes::EMAIL          => ColumnFactory\Field\Email::class,
            ACA\Types\FieldTypes::EMBED          => ColumnFactory\Field\Embed::class,
            ACA\Types\FieldTypes::FILE           => ColumnFactory\Field\File::class,
            ACA\Types\FieldTypes::IMAGE          => ColumnFactory\Field\Image::class,
            ACA\Types\FieldTypes::NUMERIC        => ColumnFactory\Field\Numeric::class,
            ACA\Types\FieldTypes::PHONE          => ColumnFactory\Field\Phone::class,
            ACA\Types\FieldTypes::POST_REFERENCE => ColumnFactory\Field\Post::class,
            ACA\Types\FieldTypes::RADIO          => ColumnFactory\Field\Select::class,
            ACA\Types\FieldTypes::SELECT         => ColumnFactory\Field\Select::class,
            ACA\Types\FieldTypes::SKYPE          => ColumnFactory\Field\Skype::class,
            ACA\Types\FieldTypes::TEXTFIELD      => ColumnFactory\Field\TextField::class,
            ACA\Types\FieldTypes::TEXTAREA       => ColumnFactory\Field\TextArea::class,
            ACA\Types\FieldTypes::URL            => ColumnFactory\Field\Url::class,
            ACA\Types\FieldTypes::VIDEO          => ColumnFactory\Field\Video::class,
            ACA\Types\FieldTypes::WYSIWYG        => ColumnFactory\Field\TextArea::class,
        ];

        if ( ! array_key_exists($field->get_type(), $mapping)) {
            return null;
        }

        $arguments = [
            'column_type'   => 'column-types_' . $field->get_id(),
            'label'         => $field->get_label(),
            'field'         => $field,
            'table_context' => $table_context,
        ];

        return $this->container->make($mapping[$field->get_type()], $arguments);
    }

}