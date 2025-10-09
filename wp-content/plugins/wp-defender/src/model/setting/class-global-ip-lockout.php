<?php
/**
 * Handles global ip lockout settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Model for global ip lockout settings.
 */
class Global_Ip_Lockout extends Setting {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_global_ip_settings';

	/**
	 * Is module enabled.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $enabled = false;

	/**
	 * Allow self unlock?
	 *
	 * @var bool
	 * @defender_property
	 */
	public bool $allow_self_unlock = true;

	/**
	 * Table column for autosync.
	 *
	 * @var bool
	 * @defender_property
	 */
	public $blocklist_autosync = false;

	/**
	 * Validation rules.
	 *
	 * @var array
	 */
	protected $rules = array(
		array( array( 'enabled', 'blocklist_autosync', 'allow_self_unlock' ), 'boolean' ),
	);

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'enabled'            => self::get_module_name(),
			'blocklist_autosync' => esc_html__( 'Permanently Blocked IPs', 'wpdef' ),
		);
	}

	/**
	 * Get the module name.
	 *
	 * @return string The module name.
	 */
	public static function get_module_name(): string {
		return esc_html__( 'Custom IP List', 'wpdef' );
	}

	/**
	 * Get the module state based on the given flag.
	 *
	 * @param  bool $flag  The flag indicating the module state.
	 *
	 * @return string The module state, either 'active' or 'inactive'.
	 */
	public static function get_module_state( $flag ): string {
		return $flag ? esc_html__( 'active', 'wpdef' ) : esc_html__( 'inactive', 'wpdef' );
	}
}