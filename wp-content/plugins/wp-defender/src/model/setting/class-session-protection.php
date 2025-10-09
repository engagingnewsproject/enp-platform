<?php
/**
 * This file contains the class for the session protection settings.
 *
 * @package WP_Defender\Model\Setting
 */

namespace WP_Defender\Model\Setting;

use Calotes\Model\Setting;
use WP_Defender\Traits\User;
use WP_Defender\Component\Security_Tweaks\Login_Duration;

/**
 * This class handles the settings for session protection in WP Defender.
 */
class Session_Protection extends Setting {
	use User;

	/**
	 * Feature status
	 *
	 * @defender_property
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Idle Session Timeout.
	 *
	 * Set the idle timeout period (in hours) after which inactive sessions will be automatically logged out.
	 *
	 * @defender_property
	 * @var int
	 */
	public $idle_timeout = 1;

	/**
	 * User Session Lock.
	 *
	 * Lock and automatically end user sessions if any selected properties change.
	 *
	 * @defender_property
	 * @var array
	 */
	public $lock_properties = array();

	/**
	 * Login duration.
	 *
	 * Set a login duration override for all user sessions. Once the duration expires, users will be logged out automatically.
	 * The process related to Login Duration will be added in the next releases.
	 *
	 * @defender_property
	 * @var int
	 */
	public $login_duration = Login_Duration::DEFAULT_DAYS;

	/**
	 * Select the user roles to apply above session protection to.
	 *
	 * @defender_property
	 * @var array
	 */
	public $user_roles = array();

	/**
	 * Option name.
	 *
	 * @var string
	 */
	protected $table = 'wd_session_protection_settings';

	/**
	 * Initializes the object before loading.
	 *
	 * @return void
	 */
	protected function before_load(): void {
		$this->login_duration = $this->get_default_duration();
		$this->user_roles     = array( 'administrator' );
	}

	/**
	 * Has lock properties?
	 *
	 * @return bool
	 */
	public function has_properties(): bool {
		return array() !== $this->lock_properties;
	}

	/**
	 * Is property locked?
	 *
	 * @param string $property The property to check.
	 *
	 * @return bool
	 */
	public function is_property_locked( $property ): bool {
		return in_array( $property, $this->lock_properties, true );
	}

	/**
	 * Return the module slug.
	 *
	 * @return string
	 */
	public static function get_module_slug(): string {
		return 'session-protection';
	}

	/**
	 * Determines whether session protection is currently active.
	 *
	 * @return bool True if enabled and roles are set; otherwise, false.
	 */
	public function is_active(): bool {
		return $this->enabled && count( $this->user_roles ) > 0;
	}

	/**
	 * Retrieves the default login duration.
	 *
	 * @return int Default login duration in days.
	 */
	public function get_default_duration(): int {
		$tweak_duration = wd_di()->get( Login_Duration::class )->get_tweak_duration();

		return $tweak_duration > 0
			? $tweak_duration
			: $this->login_duration;
	}
}