<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\Plugin\Version;
use ACP\Storage\Decoder;

class Version630Factory extends Version510Factory
{

    protected function get_version(): Version
    {
        return new Version(Version630::VERSION);
    }

    protected function create_decoder(array $encoded_data): Decoder
    {
        return new Version630(
            $encoded_data,
            $this->table_screen_factory,
            $this->column_factory,
            $this->original_columns_repository
        );
    }

}