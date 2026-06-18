<?php

namespace ACA\WC;

use AC\Asset\Location\Absolute;
use ACP\AdminColumnsPro;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

use function AC\Vendor\DI\autowire;
use function AC\Vendor\DI\get;

return [
    'addon.woocommerce.location' => static function (AdminColumnsPro $plugin): Absolute {
        return $plugin->get_addon_location('woocommerce');
    },

    Features::class => static function (): Features {
        $controller = null;

        if (function_exists('wc_get_container') && wc_get_container()->has(FeaturesController::class)) {
            $controller = wc_get_container()->get(FeaturesController::class);
        }

        return new Features($controller);
    },

    PostType\ProductVariation::class => autowire()
        ->constructorParameter(0, get('addon.woocommerce.location')),

    Service\ColumnGroups::class => autowire()
        ->constructorParameter(0, get('addon.woocommerce.location')),

    ListTable\ProductVariation::class => autowire()
        ->constructorParameter(0, get('addon.woocommerce.location')),

    Service\TableScreen::class => autowire()
        ->constructorParameter(1, get('addon.woocommerce.location')),
];