<?php
declare(strict_types=1);

namespace wpengine\cache_plugin;

require_once __DIR__ . '/security/security-checks.php';
require_once __DIR__ . '/plugin-rest-paths.php';
require_once __DIR__ . '/cache-control.php';
require_once __DIR__ . '/logging-trait.php';
require_once __DIR__ . '/max-cdn-provider.php';

\wpengine\cache_plugin\check_security();

class WpeAdmin {
	use CachePluginLoggingTrait;

	const TOP_LEVEL_PAGE_WPE_COMMON = 'toplevel_page_wpengine-common';

	public function initialize() {
		try {
			$this->define_plugin_urls();
			$this->include_cache_admin_page();
			$this->register_cache_db_settings();
			$this->enqueue_scripts();
			$this->setup_toasts();
		} catch ( \Exception $e ) {
			$this->log_error( "Caught exception while calling WpeAdmin::initialize: {$e->getMessage()} {$e->getTraceAsString()}" );
		}
	}

	private function register_cache_db_settings() {
		$db_settings = CacheDbSettings::get_instance();
		add_action( 'admin_init', array( $db_settings, 'register_settings' ) );
	}

	protected function define_plugin_urls() {
		if ( is_multisite() ) {
			define( 'WPE_CACHE_PLUGIN_URL', network_site_url( '/wp-content/mu-plugins/wpe-cache-plugin', 'relative' ) );
		} else {
			define( 'WPE_CACHE_PLUGIN_URL', content_url( '/mu-plugins/wpe-cache-plugin' ) );
		}
	}

	private function include_cache_admin_page() {
		include_once __DIR__ . '/view/wpe-cache-page.php';
	}
	/**
	 * @codeCoverageIgnore
	 */
	private function enqueue_scripts() {
		add_action(
			'admin_enqueue_scripts',
			function ( $hook ) {
				$cache_db_settings = CacheDbSettings::get_instance();
				wp_register_script(
					'wpe-cache-plugin',
					WPE_CACHE_PLUGIN_URL . '/js/dist/wpe-cache-plugin-admin.js',
					array( 'wp-api', 'wp-api-request' ),
					WPE_CACHE_PLUGIN_VERSION,
					true
				);
				if ( WpeAdmin::TOP_LEVEL_PAGE_WPE_COMMON === $hook ) {
					$cdn_provider   = new MaxCDNProvider();
					$variable_to_js = array(
						'clear_all_caches_path'        => RegisterRestEndpoint::get_clear_all_caches_path(),
						'clear_all_cache_last_cleared' => $cache_db_settings->get_cache_last_cleared(),
						'clear_all_cache_last_cleared_error' => $cache_db_settings->get_cache_last_error(),
						'max_cdn_enabled'              => $cdn_provider->is_enabled(),
					);
					wp_enqueue_script( 'wpe-cache-plugin' );
					wp_localize_script( 'wpe-cache-plugin', 'WPECachePlugin', $variable_to_js );
				}
			}
		);
	}

	private function setup_toasts() {
		add_action( 'wpe_common_admin_notices', array( 'wpengine\cache_plugin\WpeCachePage', 'setup_error_toasts' ) );
	}
}
