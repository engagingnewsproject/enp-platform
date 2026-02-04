<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\Plugin\Version;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreenFactory;
use ACP\ConditionalFormat;
use ACP\Storage\Decoder;

class Version700Factory extends Version630Factory
{

    protected ConditionalFormat\Decoder $conditional_format_decoder;

    public function __construct(
        VersionCompatibility $version_compatibility,
        TableScreenFactory $table_screen_factory,
        Aggregate $column_factory,
        OriginalColumnsRepository $original_columns_repository,
        ConditionalFormat\Decoder $conditional_format_decoder
    ) {
        parent::__construct(
            $version_compatibility,
            $table_screen_factory,
            $column_factory,
            $original_columns_repository,
        );

        $this->conditional_format_decoder = $conditional_format_decoder;
    }

    protected function get_version(): Version
    {
        return new Version(Version700::VERSION);
    }

    public function create_decoder(array $encoded_data): Decoder
    {
        return new Version700(
            $encoded_data,
            $this->version_compatibility,
            $this->conditional_format_decoder,
            $this->table_screen_factory,
            $this->column_factory,
            $this->original_columns_repository
        );
    }

}