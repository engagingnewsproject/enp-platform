<?php

declare(strict_types=1);

namespace ACP\ColumnFactories;

use AC;
use AC\Collection;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACP\ColumnFactory\Media;

class MediaFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Media) {
            return $collection;
        }

        $free_factory = $this->container->get(AC\ColumnFactories\MediaFactory::class);
        $free_factories = $free_factory->create($table_screen);

        $enhanced_column_mapping = [
            Media\Album::class          => AC\ColumnFactory\Media\AlbumFactory::class,
            Media\AlternateText::class  => AC\ColumnFactory\Media\AlternateTextFactory::class,
            Media\AvailableSizes::class => AC\ColumnFactory\Media\AvailableSizesFactory::class,
            Media\Caption::class        => AC\ColumnFactory\Media\CaptionFactory::class,
            Media\Dimensions::class     => AC\ColumnFactory\Media\DimensionsFactory::class,
            Media\FileMetaAudio::class  => AC\ColumnFactory\Media\FileMetaAudioFactory::class,
            Media\FileMetaVideo::class  => AC\ColumnFactory\Media\FileMetaVideoFactory::class,
            Media\FileName::class       => AC\ColumnFactory\Media\FileNameFactory::class,
            Media\FileSize::class       => AC\ColumnFactory\Media\FileSizeFactory::class,
            Media\FullPath::class       => AC\ColumnFactory\Media\FullPathFactory::class,
            Media\Height::class         => AC\ColumnFactory\Media\HeightFactory::class,
            Media\MimeType::class       => AC\ColumnFactory\Media\MimeTypeFactory::class,
            Media\Width::class          => AC\ColumnFactory\Media\WidthFactory::class,
        ];

        if (function_exists('exif_read_data')) {
            $enhanced_column_mapping[Media\ExifData::class] = AC\ColumnFactory\Media\ExifDataFactory::class;
        }

        foreach ($enhanced_column_mapping as $factory_class => $mapped_factory_class) {
            $column_factory = $this->find_free_factory(
                $free_factories,
                $mapped_factory_class
            );

            if ( ! $column_factory) {
                continue;
            }

            $collection->add(new AC\Type\ColumnFactoryDefinition($factory_class, [
                'column_factory' => $column_factory,
            ]));
        }

        $factories = [
            Media\AspectRatio::class,
            Media\Description::class,
            Media\Orientation::class,
            Media\UploadedToPostType::class,
            Media\UsedAsFeaturedImage::class,
        ];

        foreach ($factories as $factory_class) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory_class));
        }

        return $collection;
    }

    private function find_free_factory(Collection\ColumnFactories $factories, string $type): ?AC\Column\ColumnFactory
    {
        foreach ($factories as $factory) {
            if ($factory instanceof $type) {
                return $factory;
            }
        }

        return null;
    }

}