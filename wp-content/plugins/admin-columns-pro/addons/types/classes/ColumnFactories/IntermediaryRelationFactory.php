<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactories;

use AC;
use AC\Collection\ColumnFactories;
use AC\DI\Container;
use AC\TableScreen;
use AC\Type\PostTypeSlug;
use ACA;
use ACA\Types\ColumnFactory;
use InvalidArgumentException;
use WP_Post_Type;

class IntermediaryRelationFactory implements AC\ColumnFactoryCollectionFactory
{

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(TableScreen $table_screen): ColumnFactories
    {
        $factories = new AC\Collection\ColumnFactories();

        if ( ! $table_screen instanceof AC\PostType) {
            return $factories;
        }

        if ( ! apply_filters('toolset_is_m2m_enabled', false)) {
            return $factories;
        }

        $post_type = $table_screen->get_post_type();

        try {
            /**
             * There are two different version for 'toolset_get_relationship()'
             * @see Toolset_Public_API_Loader::initialize()
             * The one giving an issue is /inc/public_api/legacy_relationships.php
             * This method only accepts an array containing parent post type and child post type and throws an InvalidArgumentException
             */
            $relationship = toolset_get_relationship((string)$post_type);
        } catch (InvalidArgumentException $e) {
            return $factories;
        }

        if (empty($relationship)) {
            return $factories;
        }

        $parent_post_type = get_post_type_object($relationship['roles']['parent']['types'][0] ?? null);

        if ($parent_post_type instanceof WP_Post_Type) {
            $factories->add(
                $this->create_intermediary_column_factory(
                    $post_type,
                    $parent_post_type,
                    'parent'
                )
            );
        }

        $child_post_type = get_post_type_object($relationship['roles']['child']['types'][0] ?? null);

        if ($child_post_type instanceof WP_Post_Type) {
            $factories->add(
                $this->create_intermediary_column_factory(
                    $post_type,
                    $child_post_type,
                    'child'
                )
            );
        }

        return $factories;
    }

    private function create_intermediary_column_factory(PostTypeSlug $post_type, WP_Post_Type $related_post_type, string $type): AC\Column\ColumnFactory
    {
        return $this->container->make(ColumnFactory\Post\IntermediaryRelationship::class, [
            'column_type'       => 'column-types_relationship_intermediary_' . $related_post_type->name,
            'label'             => sprintf(
                '%s: %s',
                __('Relationship', 'codepress-admin-colums'),
                $related_post_type->label
            ),
            'current_post_type' => $post_type,
            'related_post_type' => new PostTypeSlug($related_post_type->name),
            'relation_type'     => $type,
        ]);
    }

}