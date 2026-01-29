<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\Plugin\Version;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreenFactory;
use ACP\Storage\Decoder;
use ACP\Storage\DecoderFactory;

class Version630Factory extends DecoderFactory
{

    protected TableScreenFactory $table_screen_factory;

    protected Aggregate $column_factory;

    protected OriginalColumnsRepository $original_columns_repository;

    public function __construct(
        VersionCompatibility $version_compatibility,
        TableScreenFactory $table_screen_factory,
        Aggregate $column_factory,
        OriginalColumnsRepository $original_columns_repository
    ) {
        parent::__construct($version_compatibility);

        $this->table_screen_factory = $table_screen_factory;
        $this->column_factory = $column_factory;
        $this->original_columns_repository = $original_columns_repository;
    }

    protected function get_version(): Version
    {
        return new Version(Version630::VERSION);
    }

    protected function create_decoder(array $encoded_data): Decoder
    {
        return new Version630(
            $encoded_data,
            $this->version_compatibility,
            $this->table_screen_factory,
            $this->column_factory,
            $this->original_columns_repository
        );
    }

}