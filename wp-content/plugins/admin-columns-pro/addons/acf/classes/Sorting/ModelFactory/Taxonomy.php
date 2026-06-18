<?php

declare(strict_types=1);

namespace ACA\ACF\Sorting\ModelFactory;

use AC\Type\TableScreenContext;
use ACA\ACF\Field;
use ACA\ACF\Sorting;
use ACP;

class Taxonomy
{

    public function create(
        Field $field,
        string $meta_key,
        TableScreenContext $table_context
    ): ?ACP\Sorting\Model\QueryBindings {
        if ( ! $field instanceof Field\Type\Taxonomy) {
            return null;
        }

        if ($field->uses_native_term_relation()) {
            return null;
        }

        return (new ACP\Sorting\Model\MetaFormatFactory())->create(
            $table_context->get_meta_type(),
            $meta_key,
            new Sorting\FormatValue\Taxonomy(),
            null,
            [
                'post_type' => $table_context->has_post_type() ? (string)$table_context->get_post_type() : null,
                'taxonomy'  => $table_context->has_taxonomy() ? (string)$table_context->get_taxonomy() : null,
            ]
        );
    }

}