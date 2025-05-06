<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Wp_Smush_Pro
 */

require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

require_once dirname( __DIR__ ) . '/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

$content_dir = getenv( 'WP_CONTENT_DIR' );
if ( ! $content_dir ) {
	echo "WP_CONTENT_DIR constant needs to be defined as an environment variable" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
/* @noinspection PhpIncludeInspection */
require_once $_tests_dir . '/includes/functions.php';

// Start up the WP testing environment.
/* @noinspection PhpIncludeInspection */
require $_tests_dir . '/includes/bootstrap.php';