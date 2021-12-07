<?php
/**
 * The cache.php file is used by wp-content/advanced-cache.php.
 *
 * This allows us to make any updates to the plugin structure, without ever requiring users to make changes to their
 * advanced-cache.php files.
 *
 * @since 2.7.1
 * @package Hummingbird
 */

if (
	! file_exists( __DIR__ . '/class-utils.php' ) ||
	! file_exists( __DIR__ . '/class-module.php' ) ||
	! file_exists( __DIR__ . '/traits/trait-wpconfig.php' ) ||
	! file_exists( __DIR__ . '/modules/class-page-cache.php' )
) {
	return;
}

require_once __DIR__ . '/class-utils.php';
require_once __DIR__ . '/class-module.php';
require_once __DIR__ . '/traits/trait-wpconfig.php';
require_once __DIR__ . '/modules/class-page-cache.php';

if ( ! method_exists( 'Hummingbird\\Core\\Modules\\Page_Cache', 'serve_cache' ) ) {
	return;
}

\Hummingbird\Core\Modules\Page_Cache::serve_cache();
