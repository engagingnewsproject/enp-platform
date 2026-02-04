<?php


use AC\Loader;
use AC\Vendor\DI\ContainerBuilder;

if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! is_admin()) {
    return;
}

define('AC_FILE', __FILE__);
define('AC_VERSION', '7.0.9');

require_once ABSPATH . 'wp-admin/includes/plugin.php';

add_action('after_setup_theme', static function () {
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/api.php';

    if ( ! defined('ACP_VERSION')) {
        $container = (new ContainerBuilder())
            ->addDefinitions(require __DIR__ . '/settings/container-definitions.php')
            ->build();

        new Loader($container);
    }
}, 1);

add_action('after_setup_theme', static function () {
    /**
     * For loading external resources, e.g. column settings.
     * Can be called from plugins and themes.
     */
    do_action('ac/ready');
}, 2);