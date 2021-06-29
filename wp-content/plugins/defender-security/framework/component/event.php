<?php
/**
 * Author: Hoang Ngo
 *
 * @package Framework
 */

namespace Calotes\Component;

use Calotes\Base\Base;

/**
 * Every event class need to extend this
 *
 * Class Event
 *
 * @package Calotes\Base
 */
class Event extends Base {

	/**
	 * Contains the class
	 *
	 * @var object
	 */
	public $sender;

	/**
	 * A flag to say if this event handled or not
	 *
	 * @var bool
	 */
	public $handled = false;

	/**
	 * Passed args for this event
	 *
	 * @var mixed
	 */
	public $message = null;

	/**
	 * Use for return event result
	 *
	 * @var null
	 */
	public $result = null;
}
