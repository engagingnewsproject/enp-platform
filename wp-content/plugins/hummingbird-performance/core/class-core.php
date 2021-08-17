<?php
/**
 * Core class.
 *
 * @package Hummingbird\Core
 */

namespace Hummingbird\Core;

use WP_Admin_Bar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Core
 */
class Core {

	/**
	 * API
	 *
	 * @var Api\API
	 */
	public $api;

	/**
	 * Hummingbird logs
	 *
	 * @since 1.9.2
	 * @var Logger
	 */
	public $logger;

	/**
	 * Saves the modules object instances
	 *
	 * @var array
	 */
	public $modules = array();

	/**
	 * Core constructor.
	 */
	public function __construct() {
		$this->init();
		$this->init_integrations();
		$this->load_modules();

		// Return is user has no proper permissions.
		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			return;
		}

		$this->add_menu_bar_actions();
	}

	/**
	 * Initialize core modules.
	 *
	 * @since 1.7.2
	 */
	private function init() {
		// Register private policy text.
		add_action( 'admin_init', array( $this, 'privacy_policy_content' ) );
		add_action( 'admin_init', array( $this, 'upsell_notice' ) );

		// Init the API.
		$this->api = new Api\API();

		// Init logger.
		$this->logger = Logger::get_instance();
	}

	/**
	 * Init integration modules.
	 *
	 * @since 2.1.0
	 */
	private function init_integrations() {
		new Integration\Builders();
		new Integration\Divi();
		new Integration\Gutenberg();
		new Integration\WPH();
		new Integration\SiteGround();
		Integration\Opcache::get_instance();
		new Integration\Wpengine();
		new Integration\WPMUDev();
	}

	/**
	 * Load WP Hummingbird modules
	 */
	private function load_modules() {
		/**
		 * Filters the modules slugs list
		 */
		$modules = apply_filters(
			'wp_hummingbird_modules',
			array( 'minify', 'gzip', 'caching', 'performance', 'uptime', 'cloudflare', 'gravatar', 'page_cache', 'advanced', 'rss', 'redis' )
		);

		array_walk( $modules, array( $this, 'load_module' ) );
	}

	/**
	 * Add menu bar actions.
	 */
	private function add_menu_bar_actions() {
		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			return;
		}

		$minify    = Settings::get_setting( 'enabled', 'minify' );
		$pc_module = Settings::get_setting( 'enabled', 'page_cache' );

		// Do not strict compare $pc_module to true, because it can also be 'blog-admins'.
		if ( ! is_multisite() || ( is_multisite() && ( ( 'super-admins' === $minify && is_super_admin() ) || true === $minify || true === (bool) $pc_module ) ) ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 100 );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_global' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_global' ) );

			// Defer the loading of the global js.
			add_filter( 'script_loader_tag', array( $this, 'add_defer_attribute' ), 10, 2 );
		}
	}

	/**
	 * Load a single module
	 *
	 * @param string $module  Module slug.
	 */
	public function load_module( $module ) {
		$parts = explode( '_', $module );
		$parts = array_map( 'ucfirst', $parts );
		$class = implode( '_', $parts );

		$class_name = 'Hummingbird\\Core\\Modules\\' . ucfirst( $class );

		/**
		 * Module.
		 *
		 * @var Module $module_obj
		 */
		$module_obj = new $class_name( $module );

		if ( $module_obj instanceof $class_name ) {
			if ( $module_obj->is_active() ) {
				$module_obj->run();
			}

			$this->modules[ $module ] = $module_obj;
			$this->logger->register_module( $module );
		}
	}

	/**
	 * Add a HB menu to the admin bar
	 *
	 * @param WP_Admin_Bar $admin_bar  Admin bar.
	 */
	public function admin_bar_menu( $admin_bar ) {
		$menu = array();

		$cache_control = Settings::get_setting( 'control', 'settings' );
		if ( $cache_control && ( ! is_multisite() || ! is_network_admin() ) ) {
			if ( true === $cache_control ) {
				$menu['wphb-clear-all-cache'] = array( 'title' => __( 'Clear all cache', 'wphb' ) );
			} else {
				$active_cache_modules = Utils::get_active_cache_modules();
				foreach ( $active_cache_modules as $module => $name ) {
					if ( ! in_array( $module, $cache_control, true ) ) {
						continue;
					}

					if ( 'cloudflare' === $module ) {
						if ( Utils::get_module( 'cloudflare' )->is_connected() && Utils::get_module( 'cloudflare' )->is_zone_selected() ) {
							$menu['wphb-clear-cloudflare'] = array( 'title' => __( 'Clear Cloudflare cache', 'wphb' ) );
						}

						continue;
					}

					$menu[ 'wphb-clear-cache-' . $module ] = array(
						'title' => __( 'Clear', 'wphb' ) . ' ' . strtolower( $name ),
						'meta'  => array(
							'onclick' => "WPHBGlobal.clearCache(\"{$module}\");",
						),
					);
				}
			}
		}

		if ( is_multisite() && is_network_admin() ) {
			$menu['wphb-clear-cache-network-wide'] = array( 'title' => __( 'Clear page cache on all subsites', 'wphb' ) );
		}

		if ( ! is_admin() ) {
			if ( Utils::get_module( 'minify' )->is_active() ) {
				$avoid_minify = filter_input( INPUT_GET, 'avoid-minify', FILTER_VALIDATE_BOOLEAN );

				$menu['wphb-page-minify'] = array(
					'title' => $avoid_minify ? __( 'See this page minified', 'wphb' ) : __( 'See this page unminified', 'wphb' ),
					'href'  => $avoid_minify ? remove_query_arg( 'avoid-minify' ) : add_query_arg( 'avoid-minify', 'true' ),
				);
			}
		}

		if ( empty( $menu ) ) {
			return;
		}

		$menu_args = array(
			'id'    => 'wphb',
			'title' => __( 'Hummingbird', 'wphb' ),
			'href'  => admin_url( 'admin.php?page=wphb' ),
		);

		if ( is_multisite() && is_main_site() ) {
			$menu_args['href'] = network_admin_url( 'admin.php?page=wphb' );
		} elseif ( is_multisite() && ! is_main_site() ) {
			unset( $menu_args['href'] );
		}

		$admin_bar->add_node( $menu_args );
		foreach ( $menu as $id => $tab ) {
			$admin_bar->add_node(
				array(
					'id'     => $id,
					'parent' => $menu_args['id'],
					'title'  => $tab['title'],
					'href'   => isset( $tab['href'] ) ? $tab['href'] : '#',
					'meta'   => isset( $tab['meta'] ) ? $tab['meta'] : '',
				)
			);
		}
	}

	/**
	 * Enqueue global scripts.
	 *
	 * @since 1.9.3
	 */
	public function enqueue_global() {
		wp_enqueue_script(
			'wphb-global',
			WPHB_DIR_URL . 'admin/assets/js/wphb-global.min.js',
			array( 'underscore', 'jquery' ),
			WPHB_VERSION,
			true
		);

		wp_localize_script(
			'wphb-global',
			'wphbGlobal',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wphb-fetch' ),
			)
		);
	}

	/**
	 * Defer global scripts.
	 *
	 * @since 1.9.3
	 *
	 * @param string $tag     HTML element tag.
	 * @param string $handle  Script handle.
	 *
	 * @return mixed
	 */
	public function add_defer_attribute( $tag, $handle ) {
		if ( 'wphb-global' !== $handle ) {
			return $tag;
		}
		return str_replace( ' src', ' defer="defer" src', $tag );
	}

	/**
	 * Register private policy text.
	 */
	public function privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			'<h3>%s</h3><p>%s</p>',
			__( 'Third parties', 'wphb' ),
			sprintf(
				/* translators: %s: start of a href tag, %s: end of a tag */
				__( 'Hummingbird uses the Stackpath Content Delivery Network (CDN). Stackpath may store web log information of site visitors, including IPs, UA, referrer, Location and ISP info of site visitors for 7 days. Files and images served by the CDN may be stored and served from countries other than your own. Stackpath’s privacy policy can be found %1$shere%2$s.', 'wphb' ),
				'<a href="https://www.stackpath.com/legal/privacy-statement/" target="_blank">',
				'</a>'
			)
		);

		wp_add_privacy_policy_content(
			__( 'Hummingbird', 'wphb' ),
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Show upsell notice for the newsletter.
	 *
	 * @since 2.5.0
	 */
	public function upsell_notice() {
		if ( ! defined( 'WPHB_WPORG' ) || ! WPHB_WPORG ) {
			return;
		}

		if ( ! file_exists( WPHB_DIR_PATH . 'core/externals/free-dashboard/module.php' ) ) {
			return;
		}

		/* @noinspection PhpIncludeInspection */
		require_once WPHB_DIR_PATH . 'core/externals/free-dashboard/module.php';

		// Add the Mailchimp group value.
		add_action(
			'frash_subscribe_form_fields',
			function ( $mc_list_id ) {
				if ( '4b14b58816' === $mc_list_id ) {
					echo '<input type="hidden" id="mce-group[53]-53-1" name="group[53][4]" value="4" />';
				}
			}
		);

		// Register the current plugin.
		do_action(
			'wdev-register-plugin',
			/* 1             Plugin ID */ WPHB_BASENAME,
			/* 2          Plugin Title */ 'Hummingbird',
			/* 3 https://wordpress.org */ '/plugins/hummingbird-performance/',
			/* 4      Email Button CTA */ __( 'Get Fast!', 'wphb' ),
			/* 5  Mailchimp List id for the plugin - e.g. 4b14b58816 is list id for Smush */ '4b14b58816'
		);

		// The email message contains 1 variable: plugin-name.
		add_filter(
			'wdev-email-message-' . WPHB_BASENAME,
			function () {
				return "You're awesome for installing %s! Make sure you get the most out of it, boost your Google PageSpeed score with these tips and tricks - just for users of Hummingbird!";
			}
		);
	}

}
