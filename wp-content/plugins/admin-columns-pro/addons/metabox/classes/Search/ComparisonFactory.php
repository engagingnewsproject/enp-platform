<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search;

use AC\Type\TableScreenContext;
use ACA\MetaBox\Field;
use ACP\Search\Comparison;

class ComparisonFactory
{

    private $table_storage_comparison_factory;

    private $meta_comparison_factory;

    public function __construct(
        TableStorageComparisonFactory $table_storage_comparison_factory,
        MetaComparisonFactory $meta_comparison_factory
    ) {
        $this->table_storage_comparison_factory = $table_storage_comparison_factory;
        $this->meta_comparison_factory = $meta_comparison_factory;
    }

    public function create(Field\Field $field, TableScreenContext $table_context): ?Comparison
    {
        if ($field->is_table_storage()) {
            return $this->table_storage_comparison_factory->create($field);
        }

        return $this->meta_comparison_factory->create($field, $table_context);
    }

}