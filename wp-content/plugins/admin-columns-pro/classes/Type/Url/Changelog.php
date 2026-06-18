<?php

namespace ACP\Type\Url;

use AC\Type;

class Changelog extends Type\Uri
{

    public function __construct(string $url, string $plugin_name)
    {
        parent::__construct($url);

        $this->add('tab', 'plugin-information');
        $this->add('section', 'changelog');
        $this->add('plugin', $plugin_name);
    }

    public static function create_network(string $plugin_name): self
    {
        return new self(network_admin_url('plugin-install.php'), $plugin_name);
    }

    public static function create_site(string $plugin_name): self
    {
        return new self(admin_url('plugin-install.php'), $plugin_name);
    }

}