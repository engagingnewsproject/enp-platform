<?php
/**
 * The base component class.
 *
 * @package WP_Defender
 */

namespace WP_Defender;

use WP_Defender\Traits\IP;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\User;
use WP_Defender\Traits\Formats;

/**
 * This class extends the base Component class from Calotes\Base\Component and uses multiple traits:
 * - IO for input/output related methods
 * - User for user-related methods
 * - Formats for formatting related methods
 * - IP for IP address related methods
 */
class Component extends \Calotes\Base\Component {

	use IO;
	use User;
	use Formats;
	use IP;
}