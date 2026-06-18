<?php

declare(strict_types=1);

namespace ACA\MetaBox\Sorting;

use AC\Setting\Config;
use AC\Type\TableScreenContext;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACA\MetaBox\Sorting\ModelFactory\MetaFactory;
use ACA\MetaBox\Sorting\ModelFactory\TableStorageFactory;
use ACP;

final class ModelFactory
{

    private TableStorageFactory $table_storage_factory;

    private MetaFactory $meta_factory;

    public function __construct(TableStorageFactory $table_storage_factory, MetaFactory $meta_factory)
    {
        $this->table_storage_factory = $table_storage_factory;
        $this->meta_factory = $meta_factory;
    }

    public function create(
        Field\Field $field,
        TableScreenContext $table_context,
        Config $config
    ): ?ACP\Sorting\Model\QueryBindings {
        if (in_array($field->get_type(), $this->get_unsupported_field_types(), true)) {
            return null;
        }

        if ($field->is_cloneable()) {
            return null;
        }

        if ($field->is_table_storage()) {
            return $this->table_storage_factory->create($field, $table_context);
        }

        return $this->meta_factory->create($field, $table_context, $config);
    }

    private function get_unsupported_field_types(): array
    {
        return [
            MetaboxFieldTypes::AUTOCOMPLETE,
            MetaboxFieldTypes::CHECKBOX_LIST,
            MetaboxFieldTypes::FIELDSET_TEXT,
            MetaboxFieldTypes::TEXT_LIST,
            MetaboxFieldTypes::GOOGLE_MAPS,
            MetaboxFieldTypes::TAXONOMY,
            MetaboxFieldTypes::FILE,
            MetaboxFieldTypes::FILE_ADVANCED,
            MetaboxFieldTypes::IMAGE,
            MetaboxFieldTypes::IMAGE_ADVANCED,
            MetaboxFieldTypes::SINGLE_IMAGE,
            MetaboxFieldTypes::VIDEO,
        ];
    }

}