<?php
/**
 * Plugin Name: WPMU DEV Dashboard
 * Plugin URI:  https://wpmudev.com/project/wpmu-dev-dashboard/
 * Description: Brings the powers of WPMU DEV directly to you. It will revolutionize how you use WordPress. Activate now!
 * Author:      WPMU DEV
 * Version:     4.11.2
 * Author URI:  https://wpmudev.com/
 * Text Domain: wpmudev
 * Domain Path: includes/languages/
 * Network:     true
 * WDP ID:      119
 *
 * @package WPMUDEV_Dashboard
 */

/*
Copyright 2007-2018 Incsub (http://incsub.com)
Author - Aaron Edwards
Contributors - Philipp Stracker, Victor Ivanov, Vladislav Bailovic, Jeffri H, Marko Miljus

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * The main Dashboard class that behaves as an interface to the other Dashboard
 * classes.
 */
class WPMUDEV_Dashboard {

	/**
	 * The current plugin version. Must match the plugin header.
	 *
	 * @var string (Version number)
	 */
	public static $version = '4.11.2';

	/**
	 * The current SUI version.
	 *
	 * @var string (SUI Version number)
	 *
	 * Required for the body class on admin pages
	 * Use sui followed by version number
	 * Use dash instead of dots as number seperator
	 */
	public static $sui_version = 'sui-2-10-9';

	/**
	 * The current plugin base file name.
	 *
	 * @var string $file File name.
	 */
	public static $basename;

	/**
	 * Holds the API module.
	 * Handles all the remote calls to the WPMUDEV Server.
	 *
	 * @var   WPMUDEV_Dashboard_Api
	 * @since 4.0.0
	 */
	public static $api = null;

	/**
	 * Holds the Remote module.
	 * Handles all the Hub calls from the WPMUDEV Servers.
	 *
	 * @var   WPMUDEV_Dashboard_Remote
	 * @since 4.0.0
	 */
	public static $remote = null;

	/**
	 * Holds the Site/Settings module.
	 * Handles all local things like storing/fetching settings.
	 *
	 * @var   WPMUDEV_Dashboard_Site
	 * @since 4.0.0
	 */
	public static $site = null;

	/**
	 * Holds the UI module.
	 * Handles all the UI tasks, like displaying a specific Dashboard page.
	 *
	 * @var   WPMUDEV_Dashboard_Ui
	 * @since 4.0.0
	 */
	public static $ui = null;

	/**
	 * Holds the Upgrader module.
	 * Handles all upgrade/installation relevant tasks.
	 *
	 * @var   WPMUDEV_Dashboard_Upgrader
	 * @since 4.1.0
	 */
	public static $upgrader = null;

	/**
	 * Holds the Notification module.
	 * Handles all the dashboard notifications.
	 *
	 * @var   WPMUDEV_Dashboard_Notice
	 * @since 4.0.0
	 */
	public static $notice = null;

	/**
	 * Whitelabel functionality class.
	 *
	 * @var WPMUDEV_Dashboard_Whitelabel
	 * @since 4.11.1
	 */
	public static $whitelabel = null;

	/**
	 * Creates and returns the WPMUDEV Dashboard object.
	 * We'll have only one of those ;)
	 *
	 * Important: This function must be called BEFORE the plugins_loaded hook!
	 *
	 * @since  4.0.0
	 * @return WPMUDEV_Dashboard
	 */
	public static function instance() {
		static $inst = null;

		if ( null === $inst ) {
			$inst = new WPMUDEV_Dashboard();
		}

		return $inst;
	}

	/**
	 * The singleton constructor will initialize the modules.
	 *
	 * Important: This function must be called BEFORE the plugins_loaded hook!
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Plugin base name.
		self::$basename = plugin_basename( __FILE__ );

		require_once 'shared-ui/plugin-ui.php';

		require_once 'includes/class-wpmudev-dashboard-site.php';
		require_once 'includes/class-wpmudev-dashboard-api.php';
		require_once 'includes/class-wpmudev-dashboard-remote.php';
		require_once 'includes/class-wpmudev-dashboard-ui.php';
		require_once 'includes/class-wpmudev-dashboard-upgrader.php';
		require_once 'includes/class-wpmudev-dashboard-notice.php';
		require_once 'includes/class-wpmudev-dashboard-whitelabel.php';

		self::$site       = new WPMUDEV_Dashboard_Site( __FILE__ );
		self::$api        = new WPMUDEV_Dashboard_Api();
		self::$remote     = new WPMUDEV_Dashboard_Remote();
		self::$notice     = new WPMUDEV_Dashboard_Message();
		self::$upgrader   = new WPMUDEV_Dashboard_Upgrader();
		self::$whitelabel = new WPMUDEV_Dashboard_Whitelabel();

		/*
		 * The UI module sets up all the WP hooks when it is created.
		 * So it should stay the last module to create, so it can access the
		 * other modules already in the constructor.
		 */

		self::$ui = new WPMUDEV_Dashboard_Ui();

		// Register the plugin activation hook.
		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );

		// Register the plugin deactivation hook.
		register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

		// Register the plugin uninstall hook.
		register_uninstall_hook( __FILE__, array( 'WPMUDEV_Dashboard', 'uninstall_plugin' ) );

		// Get db version.
		$version = self::$site->get_option( 'version' );

		// If existing version is not same, upgrade.
		if ( ! empty( $version ) && version_compare( $version, self::$version, '<' ) ) {
			$this->upgrade_plugin( $version );
		}

		/**
		 * Custom code can be executed after Dashboard is initialized with the
		 * default settings.
		 *
		 * @since  4.0.0
		 * @var  WPMUDEV_Dashboard The initialized dashboard object.
		 */
		do_action( 'wpmudev_dashboard_init', $this );
	}

	/**
	 * Run code on plugin activation.
	 *
	 * @since  1.0.0
	 * @internal Action hook
	 */
	public function activate_plugin() {
		global $current_user;

		// If first time activation.
		if ( self::$site->get_option( 'first_setup', true, true ) ) {
			/**
			 * Action hook to execute on first plugin activation.
			 *
			 * @since 4.11.2
			 */
			do_action( 'wpmudev_dashboard_first_activation' );

			// Not a first time activation anymore.
			self::$site->set_option( 'first_setup', false );
		}

		// Make sure all Dashboard settings exist in the DB.
		self::$site->init_options();

		// Reset the admin-user when plugin is activated.
		if ( $current_user && $current_user->ID ) {
			self::$site->add_allowed_user( $current_user->ID );
		}

		// On next page load we want to redirect user to login page.
		self::$site->set_option( 'redirected_v4', 0 );

		// Set plugin version on activation.
		self::$site->set_option( 'version', self::$version );

		// Force refresh of all data when plugin is activated.
		self::$site->set_option( 'refresh_profile_flag', 1 );

		// This needs to trigger after init to prevent Call to undefined function wp_get_current_user() errors.
		add_action( 'shutdown', array( self::$api, 'refresh_projects_data' ) );

		self::$site->schedule_shutdown_refresh();
	}

	/**
	 * Run code on plugin deactivation.
	 *
	 * @since  4.1.1
	 * @internal Action hook
	 */
	public function deactivate_plugin() {
		// On next page load we want to redirect user to login page.
		self::$site->set_option( 'redirected_v4', 0 );
	}

	/**
	 * Run code on plugin uninstall.
	 *
	 * @since  4.5
	 * @internal Action hook
	 */
	public static function uninstall_plugin() {
		// On next page load we want to redirect user to login page.
		self::$site->logout( false );
		// TODO Delete all options from DB.
	}

	/**
	 * Run code on plugin version upgrade.
	 *
	 * @param string $version Old version.
	 *
	 * @since  4.11
	 *
	 * @return void
	 */
	private function upgrade_plugin( $version ) {
		// Set new version.
		self::$site->set_option( 'version', self::$version );

		// Show upgrade highlights modal.
		// self::$site->set_option( 'highlights_dismissed', false );

		/**
		 * Action hook to execute upgrade functions.
		 *
		 * @param string $version     Old version.
		 * @param string $new_version New version.
		 *
		 * @since  4.11
		 */
		do_action( 'wpmudev_dashboard_version_upgrade', $version, self::$version );
	}
};

// Initialize the WPMUDEV Dashboard.
WPMUDEV_Dashboard::instance();

if ( ! class_exists( 'WPMUDEV_Update_Notifications' ) ) {

	/**
	 * Dummy class for backwards compatibility to stone-age.
	 */
	class WPMUDEV_Update_Notifications {};
}