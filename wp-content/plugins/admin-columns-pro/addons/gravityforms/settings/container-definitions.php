<?php

namespace ACA\GravityForms;

use AC\Asset\Location\Absolute;
use ACP\AdminColumnsPro;

use function AC\Vendor\DI\autowire;
use function AC\Vendor\DI\get;

return [
    'addon.gravityforms.location' => static function (AdminColumnsPro $plugin): Absolute {
        return $plugin->get_addon_location('gravityforms');
    },

    Service\Scripts::class => autowire()
        ->constructorParameter(0, get('addon.gravityforms.location')),
];
