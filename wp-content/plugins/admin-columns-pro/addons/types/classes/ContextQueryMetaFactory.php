<?php

declare(strict_types=1);

namespace ACA\Types;

use AC\Meta\Query;
use AC\Meta\QueryMetaFactory;
use AC\Type\TableScreenContext;

class ContextQueryMetaFactory
{

    public function create_by_context(TableScreenContext $context, Field $field): Query
    {
        $query = (new QueryMetaFactory())->create(
            $field->get_meta_key(),
            $context->get_meta_type()
        );

        if ($context->has_post_type()) {
            $query->where_post_type((string)$context->get_post_type());
        }

        return $query;
    }

}