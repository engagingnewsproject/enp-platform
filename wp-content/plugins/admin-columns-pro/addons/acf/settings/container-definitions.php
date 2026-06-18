<?php

namespace ACA\ACF;

use AC\Asset\Location\Absolute;
use ACP\AdminColumnsPro;
use AC\Acf\FieldGroup\QueryFactory;
use function AC\Vendor\DI\autowire;
use function AC\Vendor\DI\get;

return [
    QueryFactory::class => autowire(FieldGroup\QueryFactory::class),
    'addon.acf.location' => static function (AdminColumnsPro $plugin): Absolute {
        return $plugin->get_addon_location('acf');
    },

    Service\Scripts::class => autowire()
        ->constructorParameter(0, get('addon.acf.location')),

    Service\ColumnGroup::class => autowire()
        ->constructorParameter(0, get('addon.acf.location')),

    Service\FieldSettings::class => autowire()
        ->constructorParameter('location', get('addon.acf.location')),
];
