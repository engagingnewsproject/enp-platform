<?php
/**
 * Uninstaller
 *
 * Uninstall the plugin by removing any options from the database
 *
 * @package  fme
 * @since    1.0
 */

use FME\Helpers\Settings;
use FME\Helpers\Review_Plugin;

// If the uninstall was not called by WordPress, exit.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

require_once __DIR__ . '/footnotes-made-easy.php';

// Delete any saved data.
\delete_option( FME_SETTINGS_NAME );
\delete_option( Settings::SETTINGS_VERSION );
\delete_option( Review_Plugin::REVIEW_OPTION_KEY );
