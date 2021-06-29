<?php
/**
 * Author: Hoang Ngo
 */

namespace Calotes\Base;

use Calotes\Component\Behavior;
use Calotes\Component\Event;

/**
 * Class Component
 *
 * Class should extend this if behavior and event required
 *
 * @package Calotes\Base
 */
class Component extends Base {
	/**
	 * Contains array of behaviors
	 *
	 * @var array
	 */
	protected $behaviors = array();

	/**
	 * Store events array
	 *
	 * @var array
	 */
	protected $events = array();

	/**
	 * Internal use only
	 *
	 * @var array
	 */
	protected $cached_object = array();

	/**
	 * Cache the annotations of properties
	 *
	 * @var array
	 */
	public $annotations = array();

	/**
	 * Defined a list of events and handler
	 *
	 * @return array
	 */
	public function events() {
		return array();
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function has_event( $name ) {
		if ( isset( $this->events[ $name ] ) && count( $this->events[ $name ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Queue an event
	 *
	 * @param  string  $name  Event name
	 * @param  callable  $handler
	 * @param  null|mixed  $data
	 * @param  bool  $append  If this is true, append the handler at the end, if not put at top
	 */
	public function on( $name, $handler, $data = null, $append = true ) {
		if ( true === $append ) {
			$this->events[ $name ][] = array( $handler, $data );
		} else {
			$this->events[ $name ] = array( $handler, $data ) + array( $this->events[ $name ] );
		}
	}

	/**
	 * Dequeue an event
	 *
	 * @param  string  $name
	 * @param  callable|null  $handler  if this is null, this event will be remove, else only remove the handler
	 *
	 * @return bool
	 */
	public function off( $name, $handler = null ) {
		if ( empty( $this->events ) ) {
			return false;
		}

		if ( null === $handler ) {
			unset( $this->events[ $name ] );

			return true;
		}

		$removed = false;

		foreach ( $this->events[ $name ] as $i => $event ) {
			if ( $event[0] === $handler ) {
				unset( $this->events[ $name ][ $i ] );
				$removed = true;
			}
		}

		if ( true === $removed ) {
			// reset order
			$this->events[ $name ] = array_values( $this->events[ $name ] );
		}

		return $removed;
	}

	/**
	 * Trigger an event
	 *
	 * @param  string  $name
	 * @param  null|Event  $event
	 *
	 * @return void
	 */
	public function trigger( $name, $event = null ) {
		// merge with the info we get from events() function
		$events = array_merge( $this->events, $this->events() );

		if ( ! isset( $events[ $name ] ) || empty( $events[ $name ] )
		) {
			return;
		}

		if ( null === $event ) {
			$event = new Event();
		}

		$event->sender  = $this;
		$event->handled = false;
		foreach ( $events[ $name ] as $e ) {
			if ( isset( $e[1] ) ) {
				$event->message = $e[1];
			}
			if ( ! is_array( $e[0] ) ) {
				call_user_func( $e[0], $event );
			} else {
				$ref_method = new \ReflectionMethod( $e[0][0], $e[0][1] );
				$ref_method->invoke( new $e[0][0](), $event );
			}
		}
	}

	/**
	 * Attach a behavior to current class, a behavior is a mixins, which useful in case of pro/free version
	 *
	 * @param  string  $name
	 * @param  Behavior|string  $behavior
	 */
	public function attach_behavior( $name, $behavior ) {
		// make a fast init
		if ( ! $behavior instanceof Behavior ) {
			$behavior = new $behavior();
		}
		$behavior->owner          = $this;
		$this->behaviors[ $name ] = $behavior;
	}

	/**
	 * Detach a behavior
	 *
	 * @param  string  $name
	 *
	 * @return mixed
	 */
	public function detach_behavior( $name ) {
		if ( ! isset( $this->behaviors[ $name ] ) ) {
			return null;
		}

		$behavior        = $this->behaviors[ $name ];
		$behavior->owner = null;
		unset( $this->behaviors[ $name ] );

		return $behavior;
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function has_behavior( $name ) {
		return isset( $this->behaviors[ $name ] );
	}

	/**
	 * @param $method
	 *
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function has_method( $method ) {
		$ref_class = new \ReflectionClass( $this );
		if ( $ref_class->hasMethod( $method ) ) {
			return true;
		}

		foreach ( $this->behaviors as $key => $behavior ) {
			$ref_class = new \ReflectionClass( $behavior );
			if ( $ref_class->hasMethod( $method ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __get( $name ) {
		// priority to current class properties
		if ( $this->has_property( $name ) ) {
			return $this->$name;
		}
		// check if behaviors already have
		foreach ( $this->behaviors as $key => $behavior ) {
			$ref_class = new \ReflectionClass( $behavior );

			if ( $ref_class->hasProperty( $name ) ) {
				return $ref_class->getProperty( $name )->getValue( $behavior );
			}
		}

		throw new \Exception( 'Getting unknown property: ' . get_class( $this ) . '::' . $name );
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __call( $name, $arguments ) {
		$ref_class = new \ReflectionClass( $this );
		if ( $ref_class->hasMethod( $name ) ) {
			$ref_method = new \ReflectionMethod( $this, $name );

			return $ref_method->invokeArgs( $this, $arguments );
		}
		foreach ( $this->behaviors as $key => $behavior ) {
			$ref_class = new \ReflectionClass( $behavior );
			if ( $ref_class->hasMethod( $name ) ) {
				$ref_method = new \ReflectionMethod( $behavior, $name );

				return $ref_method->invokeArgs( $behavior, $arguments );
			}
		}

		throw new \Exception( 'Getting unknown property: ' . get_class( $this ) . '::' . $name );
	}

	/**
	 * Do not call this directly, magic method for assign value to property, if property is not exist for this component, we will
	 * check its behavior
	 *
	 * @param $name
	 * @param $value
	 *
	 * @throws \Exception
	 */
	public function __set( $name, $value ) {
		$ref_class = new \ReflectionClass( $this );
		if ( $ref_class->hasProperty( $name ) ) {
			$ref_class->getProperty( $name )->setValue( $value );

			return;
		}

		foreach ( $this->behaviors as $key => $behavior ) {
			$ref_class = new \ReflectionClass( $behavior );
			if ( $ref_class->hasProperty( $name ) ) {
				$ref_class->getProperty( $name )->setValue( $behavior, $value );

				return;
			}
		}

		throw new \Exception( 'Setting unknown property: ' . get_class( $this ) . '::' . $name );
	}

	/**
	 * Parse the annotations of the class, and cache it. The list should be
	 * - type: for casting
	 * - sanitize_*: the list of sanitize_ functions, which should be run on this property
	 * - rule: the rule that we use for validation
	 */
	protected function parse_annotations() {
		$class      = new \ReflectionClass( static::class );
		$properties = $class->getProperties( \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED );
		foreach ( $properties as $property ) {
			$doc_block = $property->getDocComment();
			if ( ! stristr( $doc_block, '@defender_property' ) ) {
				continue;
			}
			$this->annotations[ $property->getName() ] = array(
				'type'     => $this->parse_annotations_var( $doc_block ),
				'sanitize' => $this->parse_annotation_sanitize( $doc_block ),
				'rule'     => $this->parse_annotation_rule( $doc_block ),
			);
		}
	}

	/**
	 * Get the variable type
	 *
	 * @param $docblock
	 *
	 * @return false|mixed
	 */
	private function parse_annotations_var( $docblock ) {
		$pattern = '/@var\s(.+)/';
		if ( preg_match( $pattern, $docblock, $matches ) ) {
			$type = trim( $matches[1] );

			// only allow right type
			if ( in_array(
				$type,
				array(
					'boolean',
					'bool',
					'integer',
					'int',
					'float',
					'double',
					'string',
					'array',
					'object',
				)
			) ) {
				return $type;
			}
		}

		return false;
	}

	/**
	 * Get the sanitize function
	 *
	 * @param $docblock
	 *
	 * @return false|mixed
	 */
	private function parse_annotation_sanitize( $docblock ) {
		$pattern = '/@(sanitize_.+)/';
		if ( preg_match( $pattern, $docblock, $matches ) ) {
			return trim( $matches[1] );
		}

		return false;
	}

	/**
	 * Get the validation rule
	 *
	 * @param $docblock
	 *
	 * @return false|mixed
	 */
	private function parse_annotation_rule( $docblock ) {
		$pattern = '/@(rule_.+)/';
		if ( preg_match( $pattern, $docblock, $matches ) ) {
			return trim( $matches[1] );
		}

		return false;
	}
}
