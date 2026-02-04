<?php

declare(strict_types=1);

namespace ACP;

use AC\Entity\Plugin;
use AC\Plugin\Version;

final class AdminColumnsPro extends Plugin
{

    public function __construct(string $file, Version $version)
    {
        parent::__construct(
            plugin_basename($file),
            plugin_dir_path($file),
            plugin_dir_url($file),
            $version
        );
    }

}