<?php
/**
 * Abstract class for defining security tweaks.
 *
 * @package WP_Defender\Component\Security_Tweaks
 */

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;

/**
 * Abstract class for Security Tweaks.
 */
abstract class Abstract_Security_Tweaks extends Component {

	/**
	 * Unique identifier for the tweak.
	 *
	 * @var string $slug
	 */
	public string $slug;

	/**
	 * Retrieve the tweak's label.
	 *
	 * @return string
	 */
	abstract public function get_label(): string;

	/**
	 * Get the error reason.
	 *
	 * @return string
	 */
	abstract public function get_error_reason(): string;

	/**
	 * Return the tweak's summary data.
	 *
	 * @return array
	 */
	abstract public function to_array(): array;
}