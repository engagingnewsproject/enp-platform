<?php

declare(strict_types=1);
namespace wpengine\cache_plugin;

\wpengine\cache_plugin\check_security();

class WpeCommonAdapter {
	private static $instance = null;
	private $wpe_common;
	public function __construct( \WpeCommon $wpe_common_instance ) {
		$this->wpe_common = $wpe_common_instance;
	}

	public static function is_mu_common_plugin_present(): bool {
		return class_exists( '\WpeCommon' );
	}

	public static function get_instance() {
		if ( ! self::is_mu_common_plugin_present() ) {
			return null;
		}
		if ( null === self::$instance ) {
			self::$instance = new WpeCommonAdapter( \WpeCommon::instance() );
		}

		return self::$instance;
	}

	public function get_site_name(): string {
		return $this->wpe_common->get_site_info()->name;
	}

	public function purge_memcached() {
		$this->wpe_common::purge_memcached();
	}

	public function clear_maxcdn_cache() {
		return $this->wpe_common::clear_maxcdn_cache();
	}
	public function purge_varnish_cache() {
		return $this->wpe_common::purge_varnish_cache();
	}
}
