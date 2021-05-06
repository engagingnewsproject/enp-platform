<?php
/**
 * Author: Hoang Ngo
 */

namespace Calotes\Component;

use Calotes\Base\Component;

class Behavior extends Component {
	/**
	 * The component this behavior tied to
	 *
	 * @var object
	 */
	public $owner;

	/**
	 * Attach this behavior to a component
	 *
	 * @param $owner
	 */
	public function attach( Component $owner ) {
		$owner->attach_behavior( $this );
	}

	/**
	 * Detach behavior from a component
	 *
	 * @return $this|bool
	 */
	public function detach() {
		if ( ! $this->owner instanceof Component ) {
			return false;
		}
		$this->owner->detach_behavior( self::class );
		$this->owner = null;

		return $this;
	}
}
