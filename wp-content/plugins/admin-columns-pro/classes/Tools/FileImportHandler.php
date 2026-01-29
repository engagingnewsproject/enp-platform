<?php

declare(strict_types=1);

namespace ACP\Tools;

use AC\ListScreenCollection;
use AC\Type\ListScreenId;
use ACP\Storage\Unserializer;
use RuntimeException;

class FileImportHandler
{

    private Unserializer\JsonUnserializer $json_unserializer;

    private ImportHandler $import_handler;

    public function __construct(
        Unserializer\JsonUnserializer $json_unserializer,
        ImportHandler $import_handler
    ) {
        $this->json_unserializer = $json_unserializer;
        $this->import_handler = $import_handler;
    }

    public function handle(string $file_path, ?ListScreenId $id = null, array $overwrites = []): ListScreenCollection
    {
        if ( ! file_exists($file_path) || ! is_readable($file_path)) {
            throw new RuntimeException('Invalid file path or file is not readable.');
        }

        $encoded_data = $this->json_unserializer->unserialize(
            file_get_contents($file_path)
        );

        return $this->import_handler->handle(
            $encoded_data,
            $id,
            $overwrites
        );
    }
}