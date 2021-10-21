<?php
/**
 * Hummingbird plugin
 *
 * Hummingbird zips through your site finding new ways to make it load faster, from file compression and minification to browser caching – because when it comes to pagespeed, every millisecond counts.
 *
 * @link              https://wpmudev.com/project/wp-hummingbird/
 * @since             1.0.0
 * @package           Hummingbird
 *
 * @wordpress-plugin
 * Plugin Name:       Hummingbird
 * Plugin URI:        https://wpmudev.com/project/wp-hummingbird/
 * Description:       Hummingbird zips through your site finding new ways to make it load faster, from file compression and minification to browser caching – because when it comes to pagespeed, every millisecond counts.
 * Version:           3.1.1
 * Author:            WPMU DEV
 * Author URI:        https://profiles.wordpress.org/wpmudev/
 * Network:           true
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wphb
 * Domain Path:       /languages/
 */

/*
Copyright 2007-2021 Incsub (http://incsub.com)
Author – Ignacio Cruz (igmoweb), Ricardo Freitas (rtbfreitas), Anton Vanyukov (vanyukov)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 – GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

namespace Hummingbird;

if ( ! defined( 'WPHB_VERSION' ) ) {
	define( 'WPHB_VERSION', '3.1.1' );
}

if ( ! defined( 'WPHB_SUI_VERSION' ) ) {
	define( 'WPHB_SUI_VERSION', 'sui-2-10-12' );
}

if ( ! defined( 'WPHB_DIR_PATH' ) ) {
	define( 'WPHB_DIR_PATH', trailingslashit( dirname( __FILE__ ) ) );
}

if ( ! defined( 'WPHB_DIR_URL' ) ) {
	define( 'WPHB_DIR_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WPHB_BASENAME' ) ) {
	define( 'WPHB_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! class_exists( 'Hummingbird\\WP_Hummingbird' ) ) {
	/**
	 * Class WP_Hummingbird
	 *
	 * Main Plugin class. Acts as a loader of everything else and initializes the plugin
	 */
	class WP_Hummingbird {

		/**
		 * Plugin instance
		 *
		 * @var null
		 */
		private static $instance = null;

		/**
		 * Admin main class
		 *
		 * @var Admin\Admin
		 */
		public $admin;

		/**
		 * Pro modules
		 *
		 * @since 1.7.2
		 *
		 * @var Core\Pro\Pro
		 */
		public $pro;

		/**
		 * Core
		 *
		 * @var Core\Core
		 */
		public $core;

		/**
		 * Return the plugin instance
		 *
		 * @return WP_Hummingbird
		 */
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * WP_Hummingbird constructor.
		 */
		public function __construct() {
			require __DIR__ . '/vendor/autoload.php';

			$this->maybe_disable_free_version();

			$this->init();
			$this->init_pro();

			$this->load_textdomain();
		}

		/**
		 * Initialize the plugin.
		 */
		private function init() {
			// Initialize the plugin core.
			$this->core = new Core\Core();

			if ( is_admin() ) {
				// Initialize admin core files.
				$this->admin = new Admin\Admin();
			}

			// Triggered when WP Hummingbird is totally loaded.
			do_action( 'wp_hummingbird_loaded' );
		}

		/**
		 * Initialize pro modules.
		 *
		 * @since 1.7.2
		 */
		private function init_pro() {
			// Overwriting in wp-config.php file to exclude PRO.
			if ( defined( 'WPHB_LOAD_PRO' ) && false === WPHB_LOAD_PRO ) {
				return;
			}

			// Prevents from erroring out on free version.
			if ( ! is_readable( WPHB_DIR_PATH . 'core/pro/class-pro.php' ) ) {
				if ( ! defined( 'WPHB_WPORG' ) ) {
					define( 'WPHB_WPORG', true );
				}

				return;
			}

			$this->pro = Core\Pro\Pro::get_instance();

			// Modules are only needed on the backend or during cron.
			if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
				$this->pro->init();
			}

			$this->pro->load_ajax();
		}

		/**
		 * Flush all WP Hummingbird Cache
		 *
		 * @param bool $remove_data      Remove data.
		 * @param bool $remove_settings  Remove settings.
		 */
		public static function flush_cache( $remove_data = true, $remove_settings = true ) {
			$hummingbird = self::get_instance();

			/**
			 * Hummingbird module.
			 *
			 * @var Core\Module $module
			 */
			foreach ( $hummingbird->core->modules as $module ) {
				if ( ! $module->is_active() ) {
					continue;
				}

				if ( 'minify' === $module->get_slug() ) {
					/**
					 * Page caching module. Remove page cache files.
					 *
					 * @var Core\Modules\Minify $module
					 */
					$module->clear_cache( $remove_settings );
					continue;
				}

				$module->clear_cache();
			}

			if ( $remove_settings ) {
				Core\Module_Server::unsave_htaccess( 'gzip' );
				Core\Module_Server::unsave_htaccess( 'caching' );
			}

			if ( $remove_data ) {
				Core\Filesystem::instance()->clean_up();
				Core\Logger::cleanup();
			}
		}

		/**
		 * Load translations
		 */
		private function load_textdomain() {
			load_plugin_textdomain( 'wphb', false, 'wp-hummingbird/languages/' );
		}

		/**
		 * Moved from above to class.
		 *
		 * Checks if HB has both the free and Pro versions installed and disables the Free version.
		 *
		 * @since 2.0.1
		 */
		private function maybe_disable_free_version() {
			// Free is not installed - exit check.
			if ( ! is_admin() || 'wp-hummingbird/wp-hummingbird.php' === WPHB_BASENAME ) {
				return;
			}

			define( 'WPHB_WPORG', true );

			// Add notice to rate the free version.
			$free_installation = get_site_option( 'wphb-free-install-date' );
			if ( empty( $free_installation ) ) {
				update_site_option( 'wphb-notice-free-rated-show', 'yes' );
				update_site_option( 'wphb-free-install-date', time() );
			}

			// This plugin is the free version so if the Pro version is activated we need to deactivate this one.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$pro_installed = false;
			if ( file_exists( WP_PLUGIN_DIR . '/wp-hummingbird/wp-hummingbird.php' ) ) {
				$pro_installed = true;
			}

			if ( ( is_plugin_active( 'wp-hummingbird/wp-hummingbird.php' ) || $pro_installed ) && ! defined( 'WPHB_SWITCHING_VERSION' ) ) {
				define( 'WPHB_SWITCHING_VERSION', true );
			}

			// Check if the pro version exists and is activated.
			if ( is_plugin_active( 'wp-hummingbird/wp-hummingbird.php' ) ) {
				// Pro is activated, deactivate this one.
				deactivate_plugins( WPHB_BASENAME );
				update_site_option( 'wphb-notice-free-deactivated-show', 'yes' );
				return;
			} elseif ( $pro_installed ) {
				// Pro is installed but not activated, let's activate it.
				deactivate_plugins( WPHB_BASENAME );
				activate_plugin( 'wp-hummingbird/wp-hummingbird.php' );
			}
		}
	}
}

/* @noinspection PhpIncludeInspection */
require_once WPHB_DIR_PATH . 'core/class-installer.php';
register_activation_hook( __FILE__, array( 'Hummingbird\\Core\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Hummingbird\\Core\\Installer', 'deactivate' ) );

// Init the plugin and load the plugin instance for the first time.
add_action( 'plugins_loaded', array( 'Hummingbird\\WP_Hummingbird', 'get_instance' ) );
