<?php

declare(strict_types=1);

namespace ACP\Service;

use AC\Registerable;
use ACP\AddonFactory;
use ACP\Settings\General\IntegrationStatus;

final class Addon implements Registerable
{

    private $addons;

    private $addon_factory;

    private $integration_status;

    public function __construct(array $addons, AddonFactory $addon_factory, IntegrationStatus $integration_status)
    {
        $this->addons = $addons;
        $this->addon_factory = $addon_factory;
        $this->integration_status = $integration_status;
    }

    public function register(): void
    {
        $deactivate = [];

        foreach ($this->addons as $addon) {
            $filename = sprintf('%1$s%2$s/%1$s%2$s.php', 'ac-addon-', $addon);

            if (is_plugin_active($filename)) {
                $deactivate[] = $filename;
            }
        }

        // Reload to prevent duplicate loading of functions and classes
        if ($deactivate) {
            deactivate_plugins($deactivate);

            $protocol = is_ssl() ? 'https' : 'http';
            $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            wp_redirect($url);
            exit;
        }

        foreach ($this->addons as $addon) {
            if ($this->is_active($addon)) {
                $this->addon_factory->create($addon)
                                    ->register();
            }
        }
    }

    private function is_active(string $addon): bool
    {
        $is_active = $this->integration_status->is_active(sprintf('ac-addon-%s', $addon));

        return apply_filters('acp/addon/' . $addon . '/active', $is_active);
    }

}