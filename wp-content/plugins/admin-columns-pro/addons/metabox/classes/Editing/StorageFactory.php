<?php

declare(strict_types=1);

namespace ACA\MetaBox\Editing;

use AC\Type\TableScreenContext;
use ACA\MetaBox\Field;
use ACP;

final class StorageFactory
{

    public function create(
        Field\Field $field,
        TableScreenContext $table_context,
        bool $single = true
    ): ACP\Editing\Storage {
        if ($field->is_table_storage()) {
            return $this->create_table_storage($field);
        }

        return $this->create_field_storage($field, $table_context, $single);
    }

    public function create_table_storage(Field\Field $field): ACP\Editing\Storage
    {
        return new Storage\CustomTable(
            $field->get_table_storage(),
            $field->get_id()
        );
    }

    private function create_field_storage(
        Field\Field $field,
        TableScreenContext $table_screen_context,
        bool $single
    ): ACP\Editing\Storage {
        return new Storage\Field(
            $field->get_id(),
            $table_screen_context->get_meta_type(),
            $field->get_settings(),
            $single
        );
    }

}