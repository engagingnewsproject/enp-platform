<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactories;

use AC;
use AC\Collection;
use AC\Collection\ColumnFactories;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA;
use ACA\Types\ColumnFactory;

class IntermediaryRelationFactory implements AC\ColumnFactoryCollectionFactory
{

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(TableScreen $table_screen): ColumnFactories
    {
        $collection = new AC\Collection\ColumnFactories();

        if ( ! $table_screen instanceof AC\PostType) {
            return $collection;
        }

        $relationship = toolset_get_relationship((string)$table_screen->get_post_type());

        if (empty($relationship)) {
            return $collection;
        }

        if ( ! apply_filters('toolset_is_m2m_enabled', false)) {
            return $collection;
        }

        $columns = new Collection\ColumnFactories();

        if ($relationship['roles']['parent']['types'][0]) {
            $factory = $this->create_intermediary_column(
                $table_screen,
                $relationship['roles']['parent']['types'][0],
                'parent'
            );

            if ($factory) {
                $columns->add($factory);
            }
        }

        if ($relationship['roles']['child']['types'][0]) {
            $factory = $this->create_intermediary_column(
                $table_screen,
                $relationship['roles']['child']['types'][0],
                'child'
            );

            if ($factory) {
                $columns->add($factory);
            }
        }

        return $columns;
    }

    private function create_intermediary_column(
        TableScreen $table_screen,
        string $post_type,
        string $type
    ): ?AC\Column\ColumnFactory {
        $related_post_type = get_post_type_object($post_type);

        if (null === $related_post_type) {
            return null;
        }

        if ( ! $table_screen instanceof AC\PostType) {
            return null;
        }

        return $this->container->make(ColumnFactory\Post\IntermediaryRelationship::class, [
            'column_type'       => 'column-types_relationship_intermediary_' . $related_post_type->name,
            'label'             => sprintf(
                '%s: %s',
                __('Relationship', 'codepress-admin-colums'),
                $related_post_type->label
            ),
            'current_post_type' => $table_screen->get_post_type(),
            'related_post_type' => new AC\Type\PostTypeSlug($post_type),
            'relation_type'     => $type,
        ]);
    }

}