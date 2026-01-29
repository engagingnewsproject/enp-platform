<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories;

use AC\Type\TableScreenContext;
use AC\Vendor\DI\Container;
use ACA\ACF;
use ACA\ACF\Field\Type\GroupSubField;

class GroupFieldFactory
{

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(
        TableScreenContext $table_context,
        GroupSubField $field
    ): ?ACF\ColumnFactory\Meta\GroupFieldFactory {
        // Handle Group Fields
        return $this->container->make(ACF\ColumnFactory\Meta\GroupFieldFactory::class, [
            'column_type'   => sprintf(
                'acfgroup__%s-%s',
                $field->get_group_field()->get_hash(),
                $field->get_sub_field()->get_hash()
            ),
            'label'         => $field->get_label(),
            'field'         => $field,
            'table_context' => $table_context,
            'meta_key'      => $field->get_group_field()->get_meta_key(),
        ]);
    }

}