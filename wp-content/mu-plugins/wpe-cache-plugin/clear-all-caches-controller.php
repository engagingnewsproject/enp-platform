<?php

declare(strict_types=1);

namespace wpengine\cache_plugin;

require_once __DIR__ . '/security/security-checks.php';
require_once __DIR__ . '/wpe-common-adapter.php';
require_once __DIR__ . '/logging-trait.php';
require_once __DIR__ . '/max-cdn-provider.php';

\wpengine\cache_plugin\check_security();

class ClearAllCachesController {

	use CachePluginLoggingTrait;

	private $wpe_common_adapter;
	private $cache_db_settings;
	private $date_time_helper;

	public function __construct( $wpe_common_adapter, $cache_db_settings, $date_time_helper ) {
		$this->wpe_common_adapter = $wpe_common_adapter;
		$this->cache_db_settings  = $cache_db_settings;
		$this->date_time_helper   = $date_time_helper;
	}

	public function rate_limit_status() {
		try {
			return \rest_ensure_response(
				array(
					'success'            => true,
					'rate_limit_expired' => $this->is_rate_limit_expired(),
				)
			);
		} catch ( \Exception $e ) {
			$this->log_error( 'Caught exception: ' . $e->getMessage() );
			return \rest_ensure_response(
				array(
					'success' => false,
				)
			);
		}
	}

	public function clear_all_caches() {
		try {
			$this->wpe_common_adapter->purge_memcached();
			if ( $this->is_max_cdn_enabled() ) {
				$this->wpe_common_adapter->clear_maxcdn_cache();
			}
			$this->wpe_common_adapter->purge_varnish_cache();

			$this->log_info( 'event=clear-all-cache' );
			$cleared_at_time = $this->cache_db_settings->update_cache_last_cleared();

			return rest_ensure_response(
				array(
					'success'      => true,
					'time_cleared' => $cleared_at_time,
				)
			);
		} catch ( \Exception $e ) {
			$this->log_error( 'Caught exception: ' . $e->getMessage() );
			$cleared_at_time = $this->cache_db_settings->update_cache_last_error();

			return rest_ensure_response(
				array(
					'success'       => false,
					'last_error_at' => $cleared_at_time,
				)
			);
		}
	}

	public function is_rate_limit_expired() {
		if ( $this->should_rate_limit_be_skipped() ) {
			return true;
		}
		$last_date_time          = $this->get_later_date_last_cleared_or_last_error();
		$last_date_time_plus_5_m = $this->date_time_helper->add_minutes_to_date( $last_date_time, 5 );
		$now_utc                 = $this->date_time_helper->now_date_time_utc();

		return $now_utc > $last_date_time_plus_5_m;
	}

	public function should_rate_limit_be_skipped() {
		if ( ! $this->is_max_cdn_enabled() ) {
			return true;
		}
		return false;
	}

	public function is_max_cdn_enabled() {
		$cdn_provider = new MaxCDNProvider();

		return $cdn_provider->is_enabled();
	}

	private function get_later_date_last_cleared_or_last_error() {
		$last_cleared = $this->cache_db_settings->get_cache_last_cleared();
		$last_error   = $this->cache_db_settings->get_cache_last_error();

		return $this->date_time_helper->get_later_date( $last_error, $last_cleared );
	}
}
