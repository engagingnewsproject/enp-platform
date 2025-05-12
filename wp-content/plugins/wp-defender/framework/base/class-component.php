<?php
/**
 * Base component class.
 *
 * @package Calotes\Base
 */

namespace Calotes\Base;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Calotes\Component\Behavior;

/**
 * Base class for all components.
 */
class Component {
	/**
	 * Contains array of behaviors.
	 *
	 * @var array
	 */
	protected $behaviors = array();

	/**
	 * Cache the annotations of properties.
	 *
	 * @var array
	 */
	public $annotations = array();

	/**
	 * Store internal logging, mostly for debug.
	 *
	 * @var array
	 */
	protected $internal_logging = array();

	/**
	 * Attach a behavior to current class, a behavior is a mixins, which useful in case of pro/free version.
	 *
	 * @param string          $name     The name of the behavior.
	 * @param Behavior|string $behavior The behavior to attach.
	 */
	public function attach_behavior( string $name, $behavior ): void {
		// Make a fast init.
		if ( ! $behavior instanceof Behavior ) {
			$behavior = new $behavior();
		}
		$behavior->owner          = $this;
		$this->behaviors[ $name ] = $behavior;
	}

	/**
	 * Check if the object has a specific property.
	 *
	 * @param mixed $property The name of the property to check.
	 *
	 * @return bool
	 */
	public function has_property( $property ): bool {
		$ref = new ReflectionClass( $this );

		return $ref->hasProperty( $property );
	}

	/**
	 * Check if the object has a specific method.
	 *
	 * @param mixed $method The name of the method to check.
	 *
	 * @return bool
	 */
	public function has_method( $method ): bool {
		$ref_class = new ReflectionClass( $this );
		if ( $ref_class->hasMethod( $method ) ) {
			return true;
		}

		foreach ( $this->behaviors as $behavior ) {
			$ref_class = new ReflectionClass( $behavior );
			if ( $ref_class->hasMethod( $method ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the value of a property.
	 *
	 * @param mixed $name The name of the property to get.
	 *
	 * @return mixed The value of the property.
	 * @throws Exception If the property is not found.
	 */
	public function __get( $name ) {
		// Priority to current class properties.
		if ( $this->has_property( $name ) ) {
			return $this->$name;
		}
		// Check if behaviors already have.
		foreach ( $this->behaviors as $behavior ) {
			$ref_class = new ReflectionClass( $behavior );

			if ( $ref_class->hasProperty( $name ) ) {
				return $ref_class->getProperty( $name )->getValue( $behavior );
			}
		}

		throw new Exception( sprintf( 'Getting unknown property: %s::%s', esc_attr( get_class( $this ) ), esc_attr( $name ) ) );
	}

	/**
	 * Handles dynamic method calls on the object.
	 *
	 * @param mixed $name      The name of the method to call.
	 * @param mixed $arguments The arguments to pass to the method.
	 *
	 * @return mixed The result of the method call.
	 * @throws Exception If the method is not found.
	 */
	public function __call( $name, $arguments ) {
		$ref_class = new ReflectionClass( $this );
		if ( $ref_class->hasMethod( $name ) ) {
			$ref_method = new ReflectionMethod( $this, $name );

			return $ref_method->invokeArgs( $this, $arguments );
		}
		foreach ( $this->behaviors as $behavior ) {
			$ref_class = new ReflectionClass( $behavior );
			if ( $ref_class->hasMethod( $name ) ) {
				$ref_method = new ReflectionMethod( $behavior, $name );

				return $ref_method->invokeArgs( $behavior, $arguments );
			}
		}

		throw new Exception( sprintf( 'Getting unknown property: %s::%s', esc_attr( get_class( $this ) ), esc_attr( $name ) ) );
	}

	/**
	 * Sets the value of a property.
	 *
	 * Do not call this directly, magic method for assign value to property.
	 * If property is not exist for this component, we will check its behavior.
	 *
	 * @param mixed $name  The name of the property to set.
	 * @param mixed $value The value to set.
	 *
	 * @throws Exception If the property is not found.
	 */
	public function __set( $name, $value ) {
		$ref_class = new ReflectionClass( $this );
		if ( $ref_class->hasProperty( $name ) ) {
			$ref_class->getProperty( $name )->setValue( $value );

			return;
		}

		foreach ( $this->behaviors as $behavior ) {
			$ref_class = new ReflectionClass( $behavior );
			if ( $ref_class->hasProperty( $name ) ) {
				$ref_class->getProperty( $name )->setValue( $behavior, $value );

				return;
			}
		}

		throw new Exception( sprintf( 'Setting unknown property: %s::%s', esc_attr( get_class( $this ) ), esc_attr( $name ) ) );
	}

	/**
	 * It iterates over the properties of the class and checks if the property has a docblock containing the
	 * "@defender_property" tag. If the property has the tag, it extracts the type, sanitize, and rule information from
	 * the docblock and stores it in the "annotations" array.
	 *
	 * The list should be
	 * - type: for casting,
	 * - sanitize_*: the list of sanitize_ functions, which should be run on this property,
	 * - rule: the rule that we use for validation.
	 */
	protected function parse_annotations() {
		$class      = new ReflectionClass( static::class );
		$properties = $class->getProperties( ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED );
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
	 * Parses the variable type from a docblock.
	 *
	 * @param mixed $docblock The docblock to parse.
	 *
	 * @return string|bool The variable type if found, false otherwise.
	 */
	private function parse_annotations_var( $docblock ) {
		$pattern = '/@var\s(.+)/';
		if ( preg_match( $pattern, $docblock, $matches ) ) {
			$type = trim( $matches[1] );

			// Only allow right type.
			if ( in_array(
				$type,
				array( 'boolean', 'bool', 'integer', 'int', 'float', 'double', 'string', 'array', 'object' ),
				true
			) ) {
				return $type;
			}
		}

		return false;
	}

	/**
	 * Get the sanitize function.
	 *
	 * @param mixed $docblock The docblock to parse.
	 *
	 * @return bool|string
	 */
	private function parse_annotation_sanitize( $docblock ) {
		$pattern = '/@(sanitize_.+)/';
		if ( preg_match( $pattern, $docblock, $matches ) ) {
			return trim( $matches[1] );
		}

		return false;
	}

	/**
	 * Get the validation rule.
	 *
	 * @param mixed $docblock The docblock to parse.
	 *
	 * @return bool|string
	 */
	private function parse_annotation_rule( $docblock ) {
		$pattern = '/@(rule_.+)/';
		if ( preg_match( $pattern, $docblock, $matches ) ) {
			return trim( $matches[1] );
		}

		return false;
	}

	/**
	 * Logs a message with an optional category.
	 *
	 * @param mixed  $message  The message to log. If it is not a string, array, or object, it will be converted to a
	 *                         string using print_r().
	 * @param string $category Optional. The category of the log. If provided, the log will be saved to a file with the
	 *                         category as the filename. If not provided, the log will not be saved to a file.
	 *
	 * @return void
	 */
	protected function log( $message, $category = '' ): void {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if ( ! is_string( $message ) || is_array( $message ) || is_object( $message ) ) {
			$message = wp_json_encode( $message, JSON_PRETTY_PRINT );
		}

		$this->internal_logging[] = wp_date( 'Y-m-d H:i:s' ) . ' ' . $message;
		/**
		 * Uncomment it for detailed logging on wp cli.
		 * if ( 'cli' === PHP_SAPI ) {
		 * echo $message . PHP_EOL;
		 * }
		 */
		$message = '[' . wp_date( 'c' ) . '] ' . $message . PHP_EOL;

		if ( $this->has_method( 'get_log_path' ) ) {
			if ( ! empty( $category ) && 0 === preg_match( '/\.log$/', $category ) ) {
				$category .= '.log';
			}

			$file_path = $this->get_log_path( $category );
			$dir_name  = pathinfo( $file_path, PATHINFO_DIRNAME );

			if ( $wp_filesystem->exists( $file_path ) ) {
				$message = $wp_filesystem->get_contents( $file_path ) . $message;
			} elseif ( ! is_dir( $dir_name ) ) {
				wp_mkdir_p( $dir_name );
			}

			if ( $wp_filesystem->is_writable( $dir_name ) ) {
				$wp_filesystem->put_contents( $file_path, $message );
			}
		}
	}
}