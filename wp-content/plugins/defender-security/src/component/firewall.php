<?php

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Model\Lockout_Ip;

class Firewall extends Component {
	/**
	 * Queue hooks when this class init
	 */
	public function add_hooks() {
		add_filter( 'defender_ip_lockout_assets', array( &$this, 'output_scripts_data' ) );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function output_scripts_data( $data ) {
		$model            = new \WP_Defender\Model\Setting\Firewall();
		$data['settings'] = array(
			'storage_days' => isset( $model->storage_days ) ? $model->storage_days : 30,
			'class'        => \WP_Defender\Model\Setting\Firewall::class,
		);

		return $data;
	}

	/**
	 * Cron for delete old log
	 */
	public function firewall_clean_up_logs() {
		$settings = new \WP_Defender\Model\Setting\Firewall();
		/**
		 * Filter Count days for IP logs to be saved to DB
		 *
		 * @since 2.3
		 *
		 * @param string
		 */
		$storage_days = apply_filters( 'ip_lockout_logs_store_backward', $settings->storage_days );

		if ( ! is_numeric( $storage_days ) ) {
			return;
		}
		$time_string = '-' . $storage_days . ' days';
		$timestamp   = $this->local_to_utc( $time_string );
		\WP_Defender\Model\Lockout_Log::remove_logs( $timestamp, 50 );
	}

	/**
	 * Cron for clean up temporary IP block list
	 */
	public function firewall_clean_up_temporary_ip_blocklist() {
		$models = Lockout_Ip::get_bulk( Lockout_Ip::STATUS_BLOCKED );
		foreach( $models as $model )  {
			$model->status = Lockout_Ip::STATUS_NORMAL;
			$model->save();
		}
	}

	/**
	 * Update the firewall temporary IP blocklist clear cron job 
	 * Once the interval settings value is updated
	 * 
	 * @param string $new_interval
	 */
	public function update_cron_schedule_interval( $new_interval ) {
		$settings = new \WP_Defender\Model\Setting\Firewall();
		// if new interval is different than the saved value then we need to clear the cron job
		if ( $new_interval !== $settings->ip_blocklist_cleanup_interval ) {
			update_site_option( 'wpdef_clear_schedule_firewall_cleanup_temp_blocklist_ips', true );
		}
	}

}
