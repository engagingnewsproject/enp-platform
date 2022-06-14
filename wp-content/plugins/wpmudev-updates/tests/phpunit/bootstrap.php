<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WPMUDEV Dashboard
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // wpcs: XSS ok. CLI output
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	define( 'FS_METHOD', 'direct' );
	require 'update-notifications.php';
}
require 'util.php';
tests_add_filter( 'plugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

echo "PHP Version : " . PHP_VERSION . "\n";// wpcs xss ok.
echo "WordPress Version : " . $wp_version . "\n";// wpcs xss ok.