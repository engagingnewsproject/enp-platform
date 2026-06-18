<?php

declare(strict_types=1);

namespace ACP\Service;

use AC\Registerable;

final class DeprecatedAddons implements Registerable
{

    public function register(): void
    {
        $addons = [
            'acf',
            'beaver-builder',
            'buddypress',
            'events-calendar',
            'gravityforms',
            'jetengine',
            'media-library-assistant',
            'metabox',
            'pods',
            'polylang',
            'rankmath',
            'types',
            'woocommerce',
            'yoast-seo',
        ];

        $deactivate = [];

        foreach ($addons as $addon) {
            $filename = sprintf('%1$s%2$s/%1$s%2$s.php', 'ac-addon-', $addon);

            if (is_plugin_active($filename)) {
                $deactivate[] = $filename;
            }
        }

        // Reload to prevent duplicate loading of functions and classes
        if ($deactivate) {
            deactivate_plugins($deactivate);

            wp_safe_redirect(add_query_arg([]));
            exit;
        }
    }

}