<?php
/**
 * Handles interactions with BuddyPress plugin.
 *
 * @package WP_Defender\Integrations
 */

namespace WP_Defender\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Buddypress integration module.
 *
 * @since 3.3.0
 */
class Buddypress {

	public const REGISTER_FORM = 'buddypress_register', NEW_GROUP_FORM = 'buddypress_new group';

	/**
	 * Check if Buddypress is activated.
	 *
	 * @return bool
	 */
	public function is_activated(): bool {
		return class_exists( 'buddypress' );
	}

	/**
	 * Get the Defender forms.
	 *
	 * @return array
	 */
	public static function get_forms(): array {
		return array(
			self::REGISTER_FORM  => esc_html__( 'Registration', 'wpdef' ),
			self::NEW_GROUP_FORM => esc_html__( 'Add new group', 'wpdef' ),
		);
	}
}