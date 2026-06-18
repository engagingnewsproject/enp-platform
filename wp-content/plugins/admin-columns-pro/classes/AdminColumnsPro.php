<?php

declare(strict_types=1);

namespace ACP;

use AC\Asset\Location;
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

    public function get_addons_location(): Location
    {
        return $this->get_location()->with_suffix('addons');
    }

    public function get_addon_location(string $addon): Location
    {
        return $this->get_addons_location()->with_suffix($addon);
    }

}