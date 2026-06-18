<?php

declare(strict_types=1);

namespace ACA\WC\Service;

use AC\Entity\Plugin;
use AC\Registerable;
use ACA\WC\Features;
use ACP\AdminColumnsPro;

class Compatibility implements Registerable
{

    private Plugin $plugin;

    private Features $features;

    public function __construct(AdminColumnsPro $plugin, Features $features)
    {
        $this->plugin = $plugin;
        $this->features = $features;
    }

    public function register(): void
    {
        add_action('before_woocommerce_init', [$this, 'declare_compat']);
    }

    public function declare_compat(): void
    {
        $this->features->declare_compatibility_hpos($this->plugin->get_basename());
    }

}