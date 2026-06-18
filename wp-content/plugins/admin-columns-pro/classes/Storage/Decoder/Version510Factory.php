<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\Plugin\Version;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreenFactory;
use ACP\Storage\Decoder;
use ACP\Storage\DecoderFactory;

class Version510Factory implements DecoderFactory
{

    protected VersionCompatibility $version_compatibility;

    protected TableScreenFactory $table_screen_factory;

    protected Aggregate $column_factory;

    protected OriginalColumnsRepository $original_columns_repository;

    public function __construct(
        VersionCompatibility $versionCompatibility,
        TableScreenFactory $table_screen_factory,
        Aggregate $column_factory,
        OriginalColumnsRepository $original_columns_repository
    ) {
        $this->version_compatibility = $versionCompatibility;
        $this->table_screen_factory = $table_screen_factory;
        $this->column_factory = $column_factory;
        $this->original_columns_repository = $original_columns_repository;
    }

    protected function get_version(): Version
    {
        return new Version(Version510::VERSION);
    }

    public function create(array $encoded_data): Decoder
    {
        return $this->create_decoder($encoded_data);
    }

    public function supports(array $encoded_data): bool
    {
        return $this->version_compatibility->supports($encoded_data, $this->get_version());
    }

    protected function create_decoder(array $encoded_data): Decoder
    {
        return new Version510(
            $encoded_data,
            $this->table_screen_factory,
            $this->column_factory,
            $this->original_columns_repository
        );
    }

}