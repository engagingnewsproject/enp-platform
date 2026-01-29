<?php

declare(strict_types=1);

namespace ACA\ACF\Editing;

use AC\Type\TableScreenContext;
use ACA\ACF\Editing;
use ACA\ACF\Editing\Storage\CloneField;
use ACA\ACF\Field;
use ACA\ACF\Storage\CloneFieldStorage;
use ACA\ACF\Storage\FieldStorage;
use ACP;

class StorageFactory
{

    public function create(Field $field, TableScreenContext $table_context): ACP\Editing\Storage
    {
        if ($field->is_deferred_clone()) {
            $parts = explode('_', $field->get_hash());
            $hash = sprintf('%s_%s', $parts[0], $parts[1]);
            $clonehash = sprintf('%s_%s', $parts[2], $parts[3]);

            return new CloneField(
                $hash,
                $clonehash,
                $field->get_meta_key(),
                new CloneFieldStorage($table_context)
            );
        }

        return new Editing\Storage\Field(
            $field->get_hash(),
            new FieldStorage($table_context)
        );
    }

}