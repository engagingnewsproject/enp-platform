<?php
/**
 * Handles the display and management of lockout-related data in tables.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Traits\Formats;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;

/**
 * Handles the display and management of lockout-related data in tables.
 */
class Table_Lockout extends Component {

	use Formats;

	public const STATUS_BAN = 'ban', STATUS_NOT_BAN = 'not_ban', STATUS_ALLOWLIST = 'allowlist';
	public const SORT_DESC  = 'latest', SORT_ASC = 'oldest', SORT_BY_IP = 'ip', SORT_BY_UA = 'user_agent';
	public const LIMIT_20   = '20', LIMIT_50 = '50', LIMIT_100 = '100', LIMIT_ALL = '-1';

	/**
	 * Retrieves the status text for a given IP address.
	 *
	 * @param  string $ip  The IP address to check.
	 *
	 * @return string The status text related to the IP address.
	 */
	public function get_ip_status_text( $ip ): string {
		$bl_component = new Blacklist_Lockout();
		if ( $bl_component->is_ip_whitelisted( $ip ) ) {
			return esc_html__( 'Is allowlisted', 'wpdef' );
		}
		if ( $bl_component->is_blacklist( $ip ) ) {
			return esc_html__( 'Is blocklisted', 'wpdef' );
		}

		$model = Lockout_Ip::get( $ip );
		if ( ! is_object( $model ) ) {
			return esc_html__( 'Not banned', 'wpdef' );
		}

		if ( Lockout_Ip::STATUS_BLOCKED === $model->status ) {
			return esc_html__( 'Banned', 'wpdef' );
		} elseif ( Lockout_Ip::STATUS_NORMAL === $model->status ) {
			return esc_html__( 'Not banned', 'wpdef' );
		}

		return '';
	}

	/**
	 * Get types.
	 *
	 * @return array
	 */
	private function get_types(): array {
		return array(
			'all'                    => esc_html__( 'All', 'wpdef' ),
			Lockout_Log::AUTH_FAIL   => esc_html__( 'Failed login attempts', 'wpdef' ),
			Lockout_Log::AUTH_LOCK   => esc_html__( 'Login lockout', 'wpdef' ),
			Lockout_Log::ERROR_404   => esc_html__( '404 error', 'wpdef' ),
			Lockout_Log::LOCKOUT_404 => esc_html__( '404 lockout', 'wpdef' ),
			Lockout_Log::LOCKOUT_UA  => esc_html__( 'User Agent Lockout', 'wpdef' ),
		);
	}

	/**
	 * Get ban statuses.
	 *
	 * @return array
	 */
	private function ban_status(): array {
		return array(
			'all'                  => esc_html__( 'All', 'wpdef' ),
			self::STATUS_NOT_BAN   => esc_html__( 'Not Banned', 'wpdef' ),
			self::STATUS_BAN       => esc_html__( 'Banned', 'wpdef' ),
			self::STATUS_ALLOWLIST => esc_html__( 'Allowlisted', 'wpdef' ),
		);
	}

	/**
	 * Retrieves the type description based on the type key.
	 *
	 * @param  string $type  The type key.
	 *
	 * @return string The description of the type.
	 */
	public function get_type( $type ): string {
		$types = array(
			Lockout_Log::AUTH_FAIL        => esc_html__( 'Failed login attempts', 'wpdef' ),
			Lockout_Log::AUTH_LOCK        => esc_html__( 'Login lockout', 'wpdef' ),
			Lockout_Log::ERROR_404        => esc_html__( '404 error', 'wpdef' ),
			Lockout_Log::ERROR_404_IGNORE => esc_html__( '404 error', 'wpdef' ),
			Lockout_Log::LOCKOUT_404      => esc_html__( '404 lockout', 'wpdef' ),
			Lockout_Log::LOCKOUT_UA       => esc_html__( 'User Agent Lockout', 'wpdef' ),
		);

		return $types[ $type ] ?? '';
	}

	/**
	 * Retrieves sorting values.
	 *
	 * @return array An associative array of sorting options.
	 */
	private function sort_values(): array {
		return array(
			self::SORT_DESC  => esc_html__( 'Latest', 'wpdef' ),
			self::SORT_ASC   => esc_html__( 'Oldest', 'wpdef' ),
			self::SORT_BY_IP => esc_html__( 'IP Address', 'wpdef' ),
			self::SORT_BY_UA => esc_html__( 'User agent', 'wpdef' ),
		);
	}

	/**
	 * Retrieves pagination limits.
	 *
	 * @return array An associative array of pagination limits.
	 */
	private function limit_per_page(): array {
		return array(
			self::LIMIT_20  => '20',
			self::LIMIT_50  => '50',
			self::LIMIT_100 => '100',
			self::LIMIT_ALL => esc_html__( 'All', 'wpdef' ),
		);
	}

	/**
	 * Retrieves all filters used in the lockout table.
	 *
	 * @return array An associative array of all filters.
	 */
	public function get_filters(): array {
		return array(
			'lockout_types' => $this->get_types(),
			'ban_status'    => $this->ban_status(),
			'sort_values'   => $this->sort_values(),
			'limit_logs'    => $this->limit_per_page(),
		);
	}
}