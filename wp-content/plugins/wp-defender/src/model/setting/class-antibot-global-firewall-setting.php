<?php
/**
 * Handles AntiBot Global Firewall settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;

/**
 * Class Antibot_Global_Firewall_Setting
 *
 * @package WP_Defender\Model\Setting
 */
class Antibot_Global_Firewall_Setting extends Setting {
	public const MODULE_SLUG        = 'global-ip';
	public const MANAGED_BY_ALLOWED = array( '', 'plugin', 'hosting' );
	public const MODE_BASIC         = 'basic';
	public const MODE_STRICT        = 'strict';
	public const MODE_BASIC_LABEL   = 'Basic';
	public const MODE_STRICT_LABEL  = 'Strict';

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_antibot_global_firewall_settings';

	/**
	 * Is module enabled?
	 *
	 * @var bool
	 * @defender_property
	 */
	public $enabled = false;

	/**
	 * The AntiBot will be managed by WPMU DEV or Defender plugin.
	 * By default '', 'plugin' if managed by Defender plugin, 'hosting' if managed by WPMU DEV.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[plugin,hosting]
	 */
	public $managed_by = '';

	/**
	 * Mode for fetching AntiBot blockist.
	 *
	 * Allowed values:
	 * - self::MODE_BASIC: Excludes 404 and user agent IPs from the blocklist.
	 * - self::MODE_STRICT: Includes all IPs in the blocklist.
	 *
	 * Default is self::MODE_BASIC.
	 *
	 * @var string
	 * @defender_property
	 * @rule in[basic,strict]
	 */
	public string $mode = self::MODE_BASIC;

	/**
	 * Validation rules.
	 *
	 * @var array
	 */
	protected $rules = array(
		array( array( 'enabled' ), 'boolean' ),
		array( array( 'managed_by' ), 'in', self::MANAGED_BY_ALLOWED ),
		array( array( 'mode' ), 'in', array( self::MODE_BASIC, self::MODE_STRICT ) ),
	);

	/**
	 * Define settings labels.
	 *
	 * @return array
	 */
	public function labels(): array {
		return array(
			'enabled' => self::get_module_name(),
		);
	}

	/**
	 * Retrieves the module name.
	 *
	 * @return string The module name.
	 */
	public static function get_module_name(): string {
		return __( 'AntiBot Global Firewall', 'wpdef' );
	}

	/**
	 * Get the module state based on the given flag.
	 *
	 * @param  bool $flag  The flag indicating the module state.
	 *
	 * @return string The module state, either 'active' or 'inactive'.
	 */
	public static function get_module_state( $flag ): string {
		return $flag ? __( 'active', 'wpdef' ) : __( 'inactive', 'wpdef' );
	}

	/**
	 * Retrieves the module slug.
	 *
	 * @return string The module slug.
	 */
	public static function get_module_slug(): string {
		return self::MODULE_SLUG;
	}

	/**
	 * Retrieves the mode label.
	 *
	 * @return string The mode label.
	 */
	public function get_mode_label(): string {
		return self::MODE_STRICT === $this->mode ? self::MODE_STRICT_LABEL : self::MODE_BASIC_LABEL;
	}

	/**
	 * Retrieves the valid modes.
	 *
	 * @return array The valid modes.
	 */
	public static function get_valid_modes(): array {
		return array( self::MODE_BASIC, self::MODE_STRICT );
	}
}