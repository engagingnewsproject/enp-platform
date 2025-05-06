<?php
/**
 * Base behavior class.
 *
 * @package Calotes\Component
 */

namespace Calotes\Component;

use Calotes\Base\Component;

/**
 * Base behavior class for all behaviors.
 */
class Behavior extends Component {

	/**
	 * The component this behavior tied to
	 *
	 * @var object
	 */
	public $owner;
}