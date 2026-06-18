<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Meta\Query;
use AC\Meta\QueryMetaFactory;

trait MetaQueryTrait
{

    protected function get_query_meta(?string $post_type = null): Query
    {
        $query = (new QueryMetaFactory())->create(
            $this->field->get_name(),
            $this->field->get_meta_type()
        );

        if ($post_type) {
            $query->where_post_type($post_type);
        }

        return $query;
    }
}