<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\PostType;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreen;
use AC\Type\TaxonomySlug;
use AC\Vendor\DI\Container;
use ACP;

final class PostTaxonomyFactory extends AC\ColumnFactories\BaseFactory
{

    private OriginalColumnsRepository $original_columns_repository;

    public function __construct(
        Container $container,
        OriginalColumnsRepository $original_columns_repository
    ) {
        parent::__construct($container);

        $this->original_columns_repository = $original_columns_repository;
    }

    private function get_native_taxonomies(): array
    {
        return get_taxonomies(
            [
                'show_ui'           => 1,
                'show_admin_column' => 1,
                '_builtin'          => 0,
            ]
        );
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof PostType) {
            return $collection;
        }

        $native_taxonomies = $this->get_native_taxonomies();

        foreach ($this->original_columns_repository->find_all_cached($table_screen->get_id()) as $column) {
            $type = $column->get_name();

            if ( ! ac_helper()->string->starts_with($type, 'taxonomy-')) {
                continue;
            }

            $taxonomy_slug = ac_helper()->string->remove_prefix($type, 'taxonomy-');

            if ( ! in_array($taxonomy_slug, $native_taxonomies, true)) {
                continue;
            }

            $collection->add(
                new AC\Type\ColumnFactoryDefinition(
                    ACP\ColumnFactory\Post\Original\NativeTaxonomy::class,
                    [
                        'type'     => 'taxonomy-' . $taxonomy_slug,
                        'label'    => $column->get_label(),
                        'taxonomy' => new TaxonomySlug($taxonomy_slug),
                    ]
                )
            );
        }

        return $collection;
    }

}