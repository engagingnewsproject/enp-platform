<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\Plugin\Version;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreenFactory;
use ACP\Storage\Decoder;
use ACP\Storage\DecoderFactory;

class Version510Factory extends DecoderFactory
{

    private TableScreenFactory $list_screen_factory;

    private Aggregate $column_factory;

    private OriginalColumnsRepository $original_columns_repository;

    public function __construct(
        VersionCompatibility $versionCompatibility,
        TableScreenFactory $list_screen_factory,
        Aggregate $column_factory,
        OriginalColumnsRepository $original_columns_repository
    ) {
        parent::__construct($versionCompatibility);

        $this->list_screen_factory = $list_screen_factory;
        $this->column_factory = $column_factory;
        $this->original_columns_repository = $original_columns_repository;
    }

    protected function get_version(): Version
    {
        return new Version(Version510::VERSION);
    }

    public function create_decoder(array $encoded_data): Decoder
    {
        return new Version510(
            $encoded_data,
            $this->version_compatibility,
            $this->list_screen_factory,
            $this->column_factory,
            $this->original_columns_repository
        );
    }

}