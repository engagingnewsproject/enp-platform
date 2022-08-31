<?php
/*
Plugin Name: Force Strong Passwords - WPE Edition
Plugin URI: https://github.com/boogah/Force-Strong-Passwords
Description:  Forces privileged users to set a strong password. Changed the version_compare from the original code in order to support newer PHP versions.
Version:      1.8.0
Author: Jason Cosper
Author URI: http://jasoncosper.com
License: GPLv2
 */

// mu-plugins/slt-force-strong-passwords.php
if (getenv('WPENGINE_FORCE_STRONG_PASSWORDS') !== 'off') {
	require_once WPMU_PLUGIN_DIR.'/force-strong-passwords/slt-force-strong-passwords.php';
}
