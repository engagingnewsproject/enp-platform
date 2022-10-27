<?php
declare( strict_types=1 );

namespace Calotes\Base;

use Valitron\Validator;

abstract class Model extends \Calotes\Base\Component {

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
	 * Rules, e.g. for validator.
	 * If scenario is use, must be last position
	 * [
	 *      [['name','slug','url' ],'required'],
	 *      [['password','email' ],'required','scenario'=>'register'],
	 *      [['confirm_password' ],'equals','password'],
	 *      [['slug'],'length',10],
	 * ]
	 *
	 * @var array
	 * @deprecated
	 */
	protected $rules = [];

	/**
	 * Validation errors.
	 *
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Array of safe properties allow for mass assign, default is empty, mean accept all.
	 *
	 * @var array
	 */
	protected $safe = [];

	/**
	 * Store the attribute that should not be export.
	 *
	 * @var array
	 */
	protected $exclude = [];

	/**
	 * An array hold info to map class attributes to db field name.
	 *
	 * @var array
	 */
	protected $mapping = [];

	/**
	 * Run the validation.
	 *
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function validate(): bool {
		$this->before_validate();
		if ( empty( $this->annotations ) ) {
			$this->validate_oldway();
		}
		$validate = $this->get_validate_rules();
		$validate->validate();
		$this->errors = is_array( $validate->errors() ) ? $validate->errors() : [];
		$this->after_validate();

		return ! count( $this->errors );
	}

	/**
	 * Backward compatibility.
	 *
	 * @return void
	 * @deprecated
	 */
	private function validate_oldway(): void {
		if ( ! empty( $this->rules ) ) {
			$v = new Validator( $this->export() );
			foreach ( $this->rules as $item ) {
				$scenario = null;
				if ( isset( $item['scenario'] ) ) {
					$scenario = $item['scenario'];
					unset( $item['scenario'] );
				}
				if ( $scenario && $this->scenario !== $scenario ) {
					continue;
				}
				$fields = $item[0];
				$rule = $item[1];
				unset( $item[0], $item[1] );
				foreach ( $fields as $field ) {
					$v->rule( $rule, $field, ...$item );
				}
			}
			$v->validate();
			$this->errors = is_array( $v->errors() ) ? $v->errors() : [];
		}
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
		$return = [];
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
			$reflection = new \ReflectionClass( $this );
			$props = $reflection->getProperties( \ReflectionProperty::IS_PUBLIC );
			$values = [];
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

			$this->exclude = [];

			return $values;
		} catch ( \Exception $e ) {
			return [];
		}
	}

	/**
	 * @return array
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
	 * Set properties that we should not return on export. Note that this will be wiped after an export done.
	 *
	 * @param array $properties
	 */
	public function set_excludes( array $properties ) {
		$this->exclude = $properties;
	}

	/**
	 * Set properties allow for mass assign.
	 *
	 * @param array $properties
	 */
	public function set_safe( array $properties ) {
		$this->safe = $properties;
	}

	/**
	 * @param array $data
	 *
	 * @throws \ReflectionException
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
	 * @param $data
	 *
	 * @throws \ReflectionException
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
	 * This will return the key=>field for saving db, can be different with class attribute base on map.
	 *
	 * @param array $data
	 *
	 * @return array
	 * @throws \ReflectionException
	 */
	protected function prepare_data( $data = [] ): array {
		$scenario = 'import';
		if ( ! count( $data ) ) {
			$data = $this->export();
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

			if ( $meta['sanitize'] !== false ) {
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
	 * @param $arr
	 * @param $sanitize
	 *
	 * @return mixed
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
	 * @throws \ReflectionException
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
}
