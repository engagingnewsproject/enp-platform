<?php
/**
 * Handles audit logging settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for audit logging settings.
 */
class Audit_Logging extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_audit_settings';
	/**
	 * Is module enabled?
	 *
	 * @defender_property
	 * @var bool
	 */
	public $enabled = false;
	/**
	 * Table column for storage days.
	 *
	 * @defender_property
	 * @var string
	 */
	public $storage_days = '6 months';

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'enabled'      => self::get_module_name(),
			'storage_days' => esc_html__( 'Storage for', 'wpdef' ),
		);
	}

	/**
	 * Checks if the audit logging is active.
	 *
	 * @return bool Returns true if the audit logging is active, false otherwise.
	 * @since 2.6.5
	 */
	public function is_active(): bool {
		return (bool) apply_filters( 'wd_audit_enable', $this->enabled );
	}

	/**
	 * Retrieves the module name for audit logging.
	 *
	 * @return string The module name for audit logging.
	 */
	public static function get_module_name(): string {
		return esc_html__( 'Audit Logging', 'wpdef' );
	}
}