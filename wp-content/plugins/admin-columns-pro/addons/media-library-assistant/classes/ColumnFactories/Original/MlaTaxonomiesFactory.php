<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactories\Original;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA\MLA\ColumnFactory\Original\Taxonomy;
use MLACore;

final class MlaTaxonomiesFactory extends AC\ColumnFactories\BaseFactory
{

    private OriginalColumnsRepository $repository;

    public function __construct(Container $container, OriginalColumnsRepository $repository)
    {
        parent::__construct($container);

        $this->repository = $repository;
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\ThirdParty\MediaLibraryAssistant\TableScreen) {
            return $collection;
        }

        $mla_taxonomies = $this->get_mla_taxonomies();

        foreach ($this->repository->find_all_cached($table_screen->get_id()) as $type => $label) {
            if (array_key_exists($type, $mla_taxonomies)) {
                $collection->add(
                    new AC\Type\ColumnFactoryDefinition(
                        Taxonomy::class,
                        [
                            'type'     => $type,
                            'label'    => $label,
                            'taxonomy' => $mla_taxonomies[$type],
                        ]
                    )
                );
            }
        }

        return $collection;
    }

    private function get_mla_taxonomies(): array
    {
        $taxonomies = [];

        foreach (get_taxonomies(['show_ui' => true], 'objects') as $taxonomy) {
            if (MLACore::mla_taxonomy_support($taxonomy->name)) {
                $taxonomies['t_' . $taxonomy->name] = new AC\Type\TaxonomySlug($taxonomy->name);
            }
        }

        return $taxonomies;
    }

}