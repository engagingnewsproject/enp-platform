<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactories\Original;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA\MLA\ColumnFactory\Original;
use MLACore;

final class MlaCustomFieldsFactory extends AC\ColumnFactories\BaseFactory
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

        $mla_custom_fields = MLACore::mla_custom_field_support('custom_columns');

        foreach ($this->repository->find_all_cached($table_screen->get_id()) as $type => $label) {
            if (array_key_exists($type, $mla_custom_fields)) {
                $collection->add(
                    new AC\Type\ColumnFactoryDefinition(Original\CustomField::class, [
                        'type'  => $type,
                        'label' => $label,
                    ])
                );
            }
        }

        return $collection;
    }

}