<?php
/**
 * Base model class.
 *
 * @package Calotes\Base
 */

namespace Calotes\Base;

use Exception;
use ReflectionClass;
use Valitron\Validator;
use ReflectionProperty;

/**
 * Base model class for all models.
 */
abstract class Model extends Component {


	/**
	 * Table name | option name | post type name.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Let validate to run on special scenario.
	 *
	 * @var string
	 */
	protected $scenario;

	/**
	 * Validation errors.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Array of safe properties allow for mass assign, default is empty, mean accept all.
	 *
	 * @var array
	 */
	protected $safe = array();

	/**
	 * Store the attribute that should not be export.
	 *
	 * @var array
	 */
	protected $exclude = array();

	/**
	 * An array hold info to map class attributes to db field name.
	 *
	 * @var array
	 */
	protected $mapping = array();

	/**
	 * Run the validation.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		$this->before_validate();
		if ( empty( $this->annotations ) ) {
			$this->log( 'Empty annotations.', wd_internal_log() );
		}
		$validate = $this->get_validate_rules();
		$validate->validate();
		$this->errors = is_array( $validate->errors() ) ? $validate->errors() : array();
		$this->after_validate();

		return ! count( $this->errors );
	}

	/**
	 * Override this if you want to trigger something before validation process.
	 */
	protected function before_validate(): void {
	}

	/**
	 * Override this if you want to trigger something after validation process.
	 */
	protected function after_validate(): void {
	}

	/**
	 * Export the class data.
	 *
	 * @return array
	 */
	public function export(): array {
		if ( empty( $this->annotations ) ) {
			return $this->export_oldway();
		}
		$return = array();
		foreach ( array_keys( $this->annotations ) as $property ) {
			if ( $this->has_property( $property ) ) {
				$return[ $property ] = $this->$property;
			}
		}

		return $return;
	}

	/**
	 * This is for backward compatibility.
	 *
	 * @return array
	 */
	private function export_oldway(): array {
		try {
			$reflection = new ReflectionClass( $this );
			$props      = $reflection->getProperties( ReflectionProperty::IS_PUBLIC );
			$values     = array();
			foreach ( $props as $prop ) {
				if ( 'annotations' === $prop->getName() ) {
					continue;
				}

				if ( in_array( $prop->getName(), $this->exclude, true ) ) {
					continue;
				}

				$value = $prop->getValue( $this );
				if ( is_null( $value ) ) {
					$value = '';
				}
				$values[ $prop->getName() ] = $value;
			}

			$this->exclude = array();

			return $values;
		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * Get the validation errors.
	 *
	 * @return array The validation errors.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Get error as formatted string.
	 *
	 * @return string
	 */
	public function get_formatted_errors(): string {
		return implode( '<br/>', $this->errors );
	}

	/**
	 * Get error as formatted string.
	 *
	 * @return int[]|string[]
	 */
	public function get_error_keys() {
		return array_keys( $this->errors );
	}

	/**
	 * Set properties that we should not return on export. Note that this will be wiped after an export done.
	 *
	 * @param  array $properties  Properties that we should not return on export.
	 */
	public function set_excludes( array $properties ) {
		$this->exclude = $properties;
	}

	/**
	 * Set properties allow for mass assign.
	 *
	 * @param  array $properties  Properties allow for mass assign.
	 */
	public function set_safe( array $properties ) {
		$this->safe = $properties;
	}

	/**
	 * Loops through the data array and imports the values if they meet certain conditions.
	 *
	 * @param  mixed $data  The data array to import values from.
	 *
	 * @return void
	 */
	public function import_old_way( $data ): void {
		foreach ( $data as $key => $val ) {
			// Check if we have a safe list.
			if ( ! empty( $this->safe ) && ! in_array( $key, $this->safe, true ) ) {
				continue;
			}

			$allowed = array_keys( $this->export() );
			if ( ! in_array( $key, $allowed, true ) ) {
				continue;
			}

			if ( $this->has_property( $key ) ) {
				$this->$key = $val;
			}
		}
	}

	/**
	 * Import data into the model.
	 *
	 * @param  mixed $data  The data array to import values from.
	 *
	 * @return void
	 */
	public function import( $data ): void {
		if ( empty( $this->annotations ) ) {
			$this->import_old_way( $data );
		} else {
			foreach ( array_keys( $this->annotations ) as $property ) {
				if ( isset( $data[ $property ] ) && $this->has_property( $property ) ) {
					$this->$property = $data[ $property ];
				}
			}

			$this->sanitize();
		}
	}

	/**
	 * This method prepares the data for saving in the database.
	 *
	 * @param  array $data  The data array to import values from.
	 *
	 * @return array The prepared data array.
	 */
	protected function prepare_data( $data = array() ): array {
		$scenario = 'import';
		if ( ! count( $data ) ) {
			$data     = $this->export();
			$scenario = 'export';
		}
		if ( empty( $this->mapping ) ) {
			return $data;
		}
		foreach ( $this->mapping as $key => $val ) {
			if ( 'export' === $scenario && isset( $data[ $key ] ) ) {
				$data[ $val ] = $data[ $key ];
				unset( $data[ $key ] );
			} elseif ( 'import' === $scenario && isset( $data[ $val ] ) ) {
				$data[ $key ] = $data[ $val ];
				unset( $data[ $val ] );
			}
		}

		return $data;
	}

	/**
	 * Run a filter for casting type.
	 *
	 * @return void
	 */
	protected function sanitize() {
		if ( empty( $this->annotations ) ) {
			return;
		}

		foreach ( $this->annotations as $property => $meta ) {
			if ( ! $this->has_property( $property ) ) {
				// Todo: log it as this is not a good behavior.
				continue;
			}
			$type = $meta['type'];
			if ( false === $type ) {
				// Without a type, won't allow it.
				$this->$property = null;
				continue;
			}

			$value = $this->$property;
			// Cast it first.
			if ( 'boolean' === $type || 'bool' === $type ) {
				$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			} else {
				settype( $value, $type );
			}

			if ( false !== $meta['sanitize'] ) {
				$func = $meta['sanitize'];
				if ( ! function_exists( $func ) ) {
					// The formatting.php still need to be included.
					include_once ABSPATH . WPINC . '/formatting.php';
				}
				if ( is_array( $value ) ) {
					$value = $this->sanitize_array( $value, $func );
				} else {
					$value = $func( $value );
				}
			}

			$this->$property = $value;
		}
	}

	/**
	 * Sanitize an array recursively.
	 *
	 * @param  array    $arr  The array to be sanitized.
	 * @param  callable $sanitize  The function to sanitize the array values.
	 *
	 * @return array The sanitized array.
	 */
	protected function sanitize_array( $arr, $sanitize ) {
		foreach ( $arr as &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->sanitize_array( $value, $sanitize );
			} else {
				$value = $sanitize( $value );
			}
		}

		return $arr;
	}

	/**
	 * Prepare the validation object.
	 *
	 * @return Validator
	 */
	protected function get_validate_rules(): Validator {
		$v = new Validator( $this->export() );
		foreach ( $this->annotations as $property => $meta ) {
			if ( ! $this->has_property( $property ) ) {
				continue;
			}
			if ( false === $meta['rule'] ) {
				continue;
			}

			$rules = explode( '|', $meta['rule'] );
			foreach ( $rules as $str ) {
				$v->rule( $str, $property );
			}
		}

		return $v;
	}

	/**
	 * Export the data type of public properties.
	 *
	 * @since 4.8.0
	 * @return array
	 */
	public function export_type(): array {
		$reflection = new ReflectionClass( $this );
		$props      = $reflection->getProperties( ReflectionProperty::IS_PUBLIC );

		if ( empty( $this->annotations ) ) {
			return $this->export_type_oldway();
		}

		$types = array();
		foreach ( array_keys( $this->annotations ) as $property ) {
			if ( $this->has_property( $property ) ) {
				$type = isset( $this->annotations[ $property ]['type'] )
					? (string) $this->annotations[ $property ]['type']
					: 'string';

				$types[ $property ] = $this->map_format( $type );
			}
		}

		return $types;
	}

	/**
	 * Backward compatibility for exporting the data type of class properties.
	 *
	 * @since 4.8.0
	 * @return array
	 */
	private function export_type_oldway(): array {
		$types      = array();
		$reflection = new ReflectionClass( $this );
		$props      = $reflection->getProperties( ReflectionProperty::IS_PUBLIC );

		foreach ( $props as $prop ) {
			$rp = new ReflectionProperty( $prop->class, $prop->name );
			if ( preg_match( '/@var\s+([^\s]+)/', $rp->getDocComment(), $matches ) ) {
				$type    = isset( $matches[1] ) ? (string) $matches[1] : 'string';
				$types[] = $this->map_format( $type );
			}
		}

		return $types;
	}

	/**
	 * Map the format for a given data type.
	 *
	 * @param string $type The data type.
	 *
	 * @since 4.8.0
	 * @return string
	 */
	private function map_format( string $type ): string {
		$format = '';
		switch ( $type ) {
			case 'int':
			case 'integer':
			case 'bool':
			case 'boolean':
				$format = '%d';
				break;
			case 'float':
				$format = '%f';
				break;
			// Handle other data types like array, object, string.
			default:
				$format = '%s';
				break;
		}
		return $format;
	}
}