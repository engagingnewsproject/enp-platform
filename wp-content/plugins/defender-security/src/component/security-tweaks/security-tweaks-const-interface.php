<?php
/**
 * Common constants and method signature for security tweaks component.
 *
 * @package WP_Defender\Component\Security_Tweaks
 */

namespace WP_Defender\Component\Security_Tweaks;

/**
 * Interface Security_Key_Const_Interface
 *
 * @package WP_Defender\Component\Security_Tweaks
 */
interface Security_Key_Const_Interface {

	/**
	 * Prefix used in db option meta key.
	 *
	 * @var string
	 */
	public const OPTION_PREFIX = 'defender_security_tweaks_';
}
