<?php

namespace Calotes\DB;

use Calotes\Base\Component;
use Calotes\Base\Model;

/**
 * This is a data mapper for CRUD
 *
 * Class DB
 *
 * @package Calotes\Helper
 */
class Mapper extends Component {

	/**
	 * Contain the current model class name
	 *
	 * @string
	 */
	private $repository;

	/**
	 * Where statements
	 *
	 * @var array
	 */
	private $where = array();

	/**
	 * @var string
	 */
	private $group = '';

	/**
	 * @var string
	 */
	private $order = '';

	/**
	 * @var string
	 */
	private $limit = '';

	/**
	 * Use this to cache the records
	 */
	private $known = array();

	/**
	 * Use to store last query
	 *
	 * @var string
	 */
	public $saved_queries = '';

	/**
	 *
	 * @param $class
	 *
	 * @return $this
	 */
	public function get_repository( $class ) {
		$this->repository = $class;

		return $this;
	}

	/**
	 * Define the conditions, this is for generic where
	 *
	 * @param mixed ...$args
	 *
	 * @return $this
	 */
	public function where( ...$args ) {
		global $wpdb;
		if ( 2 === count( $args ) ) {
			list( $key, $value ) = $args;
			$this->where[]       = $wpdb->prepare( "`$key` = " . $this->guess_var_type( $value ), $value );

			return $this;
		}

		list( $key, $operator, $value ) = $args;
		if ( ! $this->valid_operator( $operator ) ) {
			// prevent this operator
			return $this;
		}
		if ( in_array( strtolower( $operator ), array( 'in', 'not in' ), true ) ) {
			$tmp = $key . " {$operator} (" . implode( ', ', array_fill( 0, count( $value ), $this->guess_var_type( $value ) ) ) . ')';
			$sql = call_user_func_array(
				array(
					$wpdb,
					'prepare',
				),
				array_merge( array( $tmp ), $value )
			);
			$this->where[] = $sql;
		} elseif ( 'between' === strtolower( $operator ) ) {
			$this->where[] = $wpdb->prepare(
				"{$key} {$operator} {$this->guess_var_type($value[0])} AND {$this->guess_var_type($value[1])}",
				$value[0],
				$value[1]
			);
		} else {
			$this->where[] = $wpdb->prepare( "`$key` $operator {$this->guess_var_type($value)}", $value );
		}

		return $this;
	}

	/**
	 * Guess the type of value for correcting placeholder
	 *
	 * @param $value
	 *
	 * @return string
	 */
	private function guess_var_type( $value ) {
		if ( filter_var( $value, FILTER_VALIDATE_INT ) ) {
			return '%d';
		}

		if ( filter_var( $value, FILTER_VALIDATE_FLOAT ) ) {
			return '%f';
		}

		return '%s';
	}

	/**
	 * Find a record by it's ID
	 *
	 * @param $id
	 *
	 * @return $this
	 */
	public function find_by_id( $id ) {
		global $wpdb;
		$this->where[] = $wpdb->prepare( 'id = %d', $id );

		return $this;
	}

	/**
	 * @param string $group_by
	 *
	 * @return $this
	 */
	public function group_by( $group_by ) {
		global $wpdb;
		$this->group = str_replace(
			"'",
			"",
			$wpdb->prepare( 'GROUP BY %s', $group_by )
		);

		return $this;
	}

	/**
	 * @param string $order_by
	 * @param string $order
	 *
	 * @return $this
	 */
	public function order_by( $order_by, $order = 'asc' ) {
		global $wpdb;
		if ( ! in_array( $order, array( 'asc', 'desc' ) ) ) {
			// fall it back
			$order = 'asc';
		}
		$this->order = str_replace(
			"'",
			"",
			$wpdb->prepare( "ORDER BY %s %s", $order_by, $order )
		);

		return $this;
	}

	/**
	 * @param int|string $offset
	 *
	 * @return $this
	 */
	public function limit( $offset ) {
		global $wpdb;
		$this->limit = str_replace(
			"'",
			"",
			$wpdb->prepare( 'LIMIT ' . $this->guess_var_type( $offset ), $offset )
		);

		return $this;
	}

	/**
	 * Find the first
	 *
	 * @return null|Model
	 */
	public function first() {
		$this->limit         = 'LIMIT 0,1';
		$sql                 = $this->query_build();
		$this->saved_queries = $sql;
		global $wpdb;
		$data = $wpdb->get_row( $sql, ARRAY_A );
		if ( is_null( $data ) ) {
			return null;
		}
		// check if e have any json string in property
		foreach ( $data as &$datum ) {
			$tmp = json_decode( $datum, true );
			if ( is_array( $tmp ) ) {
				$datum = $tmp;
			}
		}
		$class_name = $this->repository;
		$model      = new $class_name();
		$model->import( $data );

		return $model;
	}

	/**
	 * @return array
	 */
	public function get() {
		$sql                 = $this->query_build();
		$this->saved_queries = $sql;
		global $wpdb;
		$data = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_null( $data ) ) {
			return array();
		}

		$models = array();
		foreach ( $data as $row ) {
			foreach ( $row as &$property ) {
				$tmp = json_decode( $property, true );
				if ( is_array( $tmp ) ) {
					$property = $tmp;
				}
			}
			$class_name = $this->repository;
			$model      = new $class_name();
			$model->import( $row );
			$models[] = $model;
		}

		return $models;
	}

	/**
	 * @return string|null
	 */
	public function count() {
		global $wpdb;
		$sql = $this->query_build( 'COUNT(*)' );

		return $wpdb->get_var( $sql );
	}

	/**
	 * Handle the insert/update of current model
	 *
	 * @param Model $model
	 *
	 * @return int The ID of current record
	 */
	public function save( Model &$model ) {
		global $wpdb;
		$data = $model->export();
		unset( $data['table'] );
		unset( $data['safe'] );
		foreach ( $data as $key => &$val ) {
			if ( is_array( $val ) ) {
				$val = json_encode( $val );
			}
		}
		$table = self::table( $model );
		if ( $model->id ) {
			$ret = $wpdb->update(
				$table,
				$data,
				array(
					'id' => $model->id,
				)
			);
		} else {
			$ret = $wpdb->insert( $table, $data );
			// bind this for later use
			$model->id = $wpdb->insert_id;
		}

		if ( false === $ret ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 *
	 * @param $where
	 *
	 * @return false|int
	 */
	public function delete( $where ) {
		$table = self::table();
		global $wpdb;

		return $wpdb->delete( $table, $where );
	}

	/**
	 * @return int|bool
	 */
	public function delete_all() {
		$table = self::table();
		global $wpdb;

		$where = implode( ' AND ', $this->where );
		$sql   = "DELETE FROM $table WHERE $where";
		$this->clear();

		return $wpdb->query( $sql );
	}

	/**
	 * @return int|bool
	 */
	public function delete_by_limit() {
		$table = self::table();
		global $wpdb;

		$where = implode( ' AND ', $this->where );
		$limit = $this->limit;
		$order = $this->order;
		$sql   = "DELETE FROM $table WHERE $where $order $limit";
		$this->clear();

		return $wpdb->query( $sql );
	}

	/**
	 * @return bool|int
	 */
	public function truncate() {
		$table = self::table();
		global $wpdb;

		return $wpdb->query( sprintf( 'TRUNCATE TABLE `%s`', $table ) );
	}

	private function table( $model = null ) {
		if ( is_null( $model ) ) {
			$class_name = $this->repository;
			$model      = $this->get_model();
		} else {
			$class_name = $model;
		}
		try {
			$refection = new \ReflectionClass( $class_name );
			if ( $refection->hasProperty( 'table' ) ) {
				$property = $refection->getProperty( 'table' );
				$property->setAccessible( true );

				$table = $property->getValue( $model );
				global $wpdb;

				// have to set the prefix
				return $wpdb->base_prefix . $table;
			}
		} catch ( \Exception $e ) {
			// this when class doesn't exists
			return false;
		}
	}

	/**
	 * Reset all the queries prepare after an action
	 */
	private function clear() {
		$this->where = array();
		$this->group = '';
		$this->order = '';
		$this->limit = '';
	}

	/**
	 * Join the stuff on the table to make a full query statement.
	 * SQL params e.g. WHERE, ORDER or LIMIT were escaped on separate methods
	 *
	 * @param string $select
	 *
	 * @return string
	 */
	private function query_build( $select = '*' ) {
		$table = $this->table();
		$where = implode( ' AND ', $this->where );

		$group_by    = $this->group;
		$order_by    = $this->order;
		$limit       = $this->limit;
		$sql         = "SELECT $select FROM $table WHERE $where $group_by $order_by $limit";
		$this->clear();

		return $sql;
	}

	/**
	 * @param $operator
	 *
	 * @return bool
	 */
	private function valid_operator( $operator ) {
		$operator = strtolower( $operator );
		$allowed  = array(
			'in',
			'not in',
			'>',
			'<',
			'=',
			'<=',
			'>=',
			'like',
			'between',
			'regexp',
			'not regexp',
		);

		return in_array( $operator, $allowed );
	}

	/**
	 * Cache the model instance for clone & reference use
	 *
	 * @return mixed
	 */
	private function get_model() {
		if ( isset( $this->known[ $this->repository ] ) ) {
			return $this->known[ $this->repository ];
		}
		$class                            = $this->repository;
		$model                            = new $class();
		$this->known[ $this->repository ] = $model;

		return $model;
	}
}
