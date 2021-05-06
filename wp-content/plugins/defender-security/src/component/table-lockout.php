<?php

namespace WP_Defender\Component;

use Calotes\Helper\HTTP;
use WP_Defender\Component;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Traits\Formats;

class Table_Lockout extends Component {
	use Formats;

	const STATUS_BAN = 'ban', STATUS_NOT_BAN = 'not_ban', STATUS_ALLOWLIST = 'allowlist';

	/**
	 * Queue hooks when this class init
	 */
	public function add_hooks() {
		add_filter( 'defender_ip_lockout_assets', array( &$this, 'output_scripts_data' ) );
		add_action( 'defender_ip_lockout_action_assets', array( &$this, 'script_data' ) );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function output_scripts_data( $data ) {
		$data['table'] = array(
			'date_from' => Http::get( 'date_from', gmdate( 'm/d/Y', strtotime( 'today midnight', strtotime( '-14 days', current_time( 'timestamp' ) ) ) ) ), // phpcs:ignore
			'date_to'   => Http::get( 'date_to', gmdate( 'm/d/Y', current_time( 'timestamp' ) ) ), // phpcs:ignore
			'misc'      => array(
				'lockout_types' => $this->get_types(),
				'ban_status'    => $this->ban_status(),
			),
		);

		return $data;
	}

	public function script_data() {
		wp_enqueue_script( 'def-momentjs', defender_asset_url( '/assets/js/vendor/moment/moment.min.js' ) );
		wp_enqueue_script( 'def-daterangepicker', defender_asset_url( '/assets/js/vendor/daterangepicker/daterangepicker.js' ) );
	}

	/**
	 * Get IP status
	 *
	 * @param string $ip
	 *
	 * @return string|void
	 */
	public function get_ip_status_text( $ip ) {
		$bl_component = new \WP_Defender\Component\Blacklist_Lockout();
		if ( $bl_component->is_ip_whitelisted( $ip ) ) {
			return __( 'Is allowlisted', 'wpdef' );
		}
		if ( $bl_component->is_blacklist( $ip ) ) {
			return __( 'Is blocklisted', 'wpdef' );
		}

		$model = Lockout_Ip::get( $ip );
		if ( ! is_object( $model ) ) {
			return __( 'Not banned', 'wpdef' );
		}

		if ( Lockout_Ip::STATUS_BLOCKED === $model->status ) {
			return __( 'Banned', 'wpdef' );
		} elseif ( Lockout_Ip::STATUS_NORMAL === $model->status ) {
			return __( 'Not banned', 'wpdef' );
		}
	}


	/**
	 * Compare IP status with filter status
	 *
	 * @param string $ip
	 * @param string $key_status
	 *
	 * @return bool
	 */
	public function ip_has_status_text( $ip, $key_status ) {
		$result = false;
		switch ( $key_status ) {
			case self::STATUS_ALLOWLIST:
				$bl_component = new \WP_Defender\Component\Blacklist_Lockout();
				$result       = $bl_component->is_ip_whitelisted( $ip );
				break;
			case self::STATUS_BAN:
				$model  = Lockout_Ip::get( $ip );
				$result = Lockout_Ip::STATUS_BLOCKED === $model->status;
				break;
			case self::STATUS_NOT_BAN:
				$model  = Lockout_Ip::get( $ip );
				$result = Lockout_Ip::STATUS_NORMAL === $model->status;
				break;
			default:
				break;
		}

		return $result;
	}

	/**
	 * Get current status of the ip due to allowlist|blocklist data
	 *
	 * @param string $ip
	 *
	 * @return string
	 */
	public function black_or_white( $ip ) {
		$model = new \WP_Defender\Model\Setting\Blacklist_Lockout();
		if ( in_array( $ip, $model->get_list( 'allowlist' ), true ) ) {
			return 'allowlist';
		} elseif ( in_array( $ip, $model->get_list( 'blocklist' ), true ) ) {
			return 'blocklist';
		}

		return 'na';
	}

	/**
	 * Get types
	 *
	 * @return array
	 */
	private function get_types() {
		return array(
			''                       => __( 'All', 'wpdef' ),
			Lockout_Log::ERROR_404   => __( '404 error', 'wpdef' ),
			Lockout_Log::LOCKOUT_404 => __( '404 lockout', 'wpdef' ),
			Lockout_Log::AUTH_FAIL   => __( 'Login error', 'wpdef' ),
			Lockout_Log::AUTH_LOCK   => __( 'Login lockout', 'wpdef' ),
		);
	}

	/**
	 * Get ban statuses
	 *
	 * @return array
	 */
	private function ban_status() {
		return array(
			''                     => __( 'All', 'wpdef' ),
			self::STATUS_NOT_BAN   => __( 'Not Banned', 'wpdef' ),
			self::STATUS_BAN       => __( 'Banned', 'wpdef' ),
			self::STATUS_ALLOWLIST => __( 'Allowlisted', 'wpdef' ),
		);
	}

	/**
	 * Get type
	 *
	 * @param string $type
	 *
	 * @return mixed|null
	 */
	public function get_type( $type ) {
		$types = array(
			Lockout_Log::AUTH_FAIL        => __( 'Failed login attempts', 'wpdef' ),
			Lockout_Log::AUTH_LOCK        => __( 'Login lockout', 'wpdef' ),
			Lockout_Log::ERROR_404        => __( '404 error', 'wpdef' ),
			Lockout_Log::ERROR_404_IGNORE => __( '404 error', 'wpdef' ),
			Lockout_Log::LOCKOUT_404      => __( '404 lockout', 'wpdef' ),
		);

		if ( isset( $types[ $type ] ) ) {
			return $types[ $type ];
		}

		return null;
	}
}
