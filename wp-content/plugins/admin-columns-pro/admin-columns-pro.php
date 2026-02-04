<?php
/*
Plugin Name: Admin Columns Pro
Version: 7.0.9
Description: Customize columns on the administration screens for post(types), users and other content. Filter and sort content, and edit posts directly from the posts overview. All via an intuitive, easy-to-use drag-and-drop interface.
Author: AdminColumns.com
Author URI: https://www.admincolumns.com
Plugin URI: https://www.admincolumns.com
Requires PHP: 7.4
Requires at least: 5.9
Text Domain: codepress-admin-columns
Domain Path: /languages/
*/

use AC\Vendor\DI\ContainerBuilder;
use ACP\Loader;

if ( ! defined('ABSPATH')) {
    exit;
}

if ( ! is_admin()) {
    return;
}

define('ACP_FILE', __FILE__);
define('ACP_VERSION', '7.0.9');

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Deactivate Admin Columns
 */
deactivate_plugins('codepress-admin-columns/codepress-admin-columns.php');

/**
 * Load Admin Columns
 */
add_action('plugins_loaded', static function () {
    require_once __DIR__ . '/admin-columns/codepress-admin-columns.php';
});

/**
 * Load Admin Columns Pro
 */
add_action('after_setup_theme', static function () {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/api.php';

    $definitions = array_merge(
        require __DIR__ . '/admin-columns/settings/container-definitions.php',
        require __DIR__ . '/settings/container-definitions.php'
    );

    $container = (new ContainerBuilder())
        ->addDefinitions($definitions)
        ->build();

    new Loader($container);
}, 2);

add_action('after_setup_theme', static function () {
    /**
     * For loading external resources like column settings.
     * Can be called from plugins and themes.
     */
    do_action('acp/ready');
}, 5);

