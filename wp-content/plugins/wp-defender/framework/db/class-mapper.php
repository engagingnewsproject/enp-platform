<?php
/**
 * Data mapper for CRUD.
 *
 * @package Calotes\DB
 */

namespace Calotes\DB;

use ReflectionClass;
use Calotes\Base\Model;
use Calotes\Base\Component;

/**
 * Responsible for performing CRUD operations.
 */
class Mapper extends Component {

	/**
	 * Contain the current model class name.
	 *
	 * @var string
	 */
	private $repository;

	/**
	 * The columns to select in the query.
	 *
	 * @var string
	 */
	private $select = '';

	/**
	 * Where statements for the query.
	 *
	 * @var array
	 */
	private $where = array();

	/**
	 * The grouping parameter for the query.
	 *
	 * @var string
	 */
	private $group = '';

	/**
	 * The ordering parameter for the query.
	 *
	 * @var string
	 */
	private $order = '';

	/**
	 * The limit for the query results.
	 *
	 * @var string
	 */
	private $limit = '';

	/**
	 * Cache for storing retrieved records.
	 *
	 * @var array
	 */
	private $known = array();

	/**
	 * Store the last executed query.
	 *
	 * @var string
	 */
	public $saved_queries = '';

	/**
	 * Set the repository class name.
	 *
	 * @param  mixed $class_name  The class name to set for the repository.
	 *
	 * @return $this
	 */
	public function get_repository( $class_name ) {
		$this->repository = $class_name;

		return $this;
	}

	/**
	 * Set the columns to select in the SQL query.
	 *
	 * @param  mixed $select  The columns to select.
	 *
	 * @return $this
	 */
	public function select( $select ) {
		$this->select = $select;

		return $this;
	}

	/**
	 * Set the WHERE clause for the query based on the provided arguments.
	 *
	 * @param  mixed ...$args  The conditions to apply in the WHERE clause.
	 *
	 * @return $this
	 */
	public function where( ...$args ) {
		global $wpdb;
		if ( 2 === count( $args ) ) {
			list($key, $value) = $args;
			$this->where[]     = $wpdb->prepare( "`$key` = " . $this->guess_var_type( $value ), $value ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			return $this;
		}

		[$key, $operator, $value] = $args;
		if ( ! $this->valid_operator( $operator ) ) {
			// Prevent this operator.
			return $this;
		}
		if ( in_array( strtolower( $operator ), array( 'in', 'not in' ), true ) ) {
			$tmp           = $key . " {$operator} (" . implode(
				', ',
				array_fill( 0, count( $value ), $this->guess_var_type( $value ) )
			) . ')';
			$sql           = call_user_func_array(
				array(
					$wpdb,
					'prepare',
				),
				array_merge( array( $tmp ), $value )
			);
			$this->where[] = $sql;
		} elseif ( 'between' === strtolower( $operator ) ) {
			$this->where[] = $wpdb->prepare(
				"{$key} {$operator} {$this->guess_var_type($value[0])} AND {$this->guess_var_type($value[1])}", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$value[0],
				$value[1]
			);
		} else {
			$this->where[] = $wpdb->prepare( "`$key` $operator {$this->guess_var_type($value)}", $value ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return $this;
	}

	/**
	 * Guess the type of value for correcting placeholder.
	 *
	 * @param  mixed $value  The value to guess.
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
	 * Find a record by its ID.
	 *
	 * @param  int $id  The ID of the record.
	 *
	 * @return $this
	 */
	public function find_by_id( $id ) {
		global $wpdb;
		$this->where[] = $wpdb->prepare( 'id = %d', $id );

		return $this;
	}

	/**
	 * Set the group by clause for the SQL query based on the provided argument.
	 *
	 * @param  string $group_by  The column to group by.
	 *
	 * @return $this
	 */
	public function group_by( $group_by ) {
		global $wpdb;
		$this->group = str_replace(
			"'",
			'',
			$wpdb->prepare( 'GROUP BY %s', $group_by )
		);

		return $this;
	}

	/**
	 * Set the order for the SQL query based on the provided arguments.
	 *
	 * @param  mixed  $order_by  The column to order by.
	 * @param  string $order  The order direction, defaults to 'asc'.
	 *
	 * @return $this
	 */
	public function order_by( $order_by, $order = 'asc' ) {
		global $wpdb;
		if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
			// Fall it back.
			$order = 'asc';
		}
		$this->order = str_replace(
			"'",
			'',
			$wpdb->prepare( 'ORDER BY %s %s', $order_by, $order )
		);

		return $this;
	}

	/**
	 * Set the limit for the SQL query based on the provided offset.
	 *
	 * @param  mixed $offset  The offset value for the query limit.
	 *
	 * @return $this
	 */
	public function limit( $offset ) {
		global $wpdb;
		$this->limit = str_replace(
			"'",
			'',
			$wpdb->prepare( 'LIMIT ' . $this->guess_var_type( $offset ), $offset ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.NotPrepared
		);

		return $this;
	}

	/**
	 * Find the first.
	 *
	 * @return null|Model
	 */
	public function first() {
		$this->limit         = 'LIMIT 0,1';
		$sql                 = $this->query_build(); // SQL is prepared here. We will ignore prepare rules.
		$this->saved_queries = $sql;
		global $wpdb;
		$data = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		if ( is_null( $data ) ) {
			return null;
		}
		// Check if we have any json string in property.
		foreach ( $data as &$datum ) {
			if ( is_string( $datum ) ) {
				$tmp = json_decode( $datum, true );
				if ( is_array( $tmp ) ) {
					$datum = $tmp;
				}
			}
		}
		$class_name = $this->repository;
		$model      = new $class_name();
		$model->import( $data );

		return $model;
	}

	/**
	 * Retrieves the models based on the data obtained from get_results().
	 *
	 * @return array
	 */
	public function get() {
		$data   = $this->get_results();
		$models = array();
		foreach ( $data as $row ) {
			foreach ( $row as &$property ) {
				if ( is_string( $property ) ) {
					$tmp = json_decode( $property, true );
					if ( is_array( $tmp ) ) {
						$property = $tmp;
					}
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
	 * Get records in array form.
	 *
	 * @return array
	 * @since 2.7.0
	 */
	public function get_results() {
		$sql                 = $this->query_build(); // SQL is prepared here.
		$this->saved_queries = $sql;

		global $wpdb;
		$data = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		if ( is_null( $data ) ) {
			$data = array();
		}

		return $data;
	}

	/**
	 * Get the count of records based on the provided query.
	 *
	 * @return string|null The count of records.
	 */
	public function count() {
		global $wpdb;
		$sql = $this->query_build( 'COUNT(*)' ); // SQL is prepared here.

		$result = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		return $result;
	}

	/**
	 * Handle the insert/update of current model.
	 *
	 * @param  Model $model  The model to save.
	 *
	 * @return int|bool The ID of current record OR false.
	 * @throws \ReflectionException If class is not defined.
	 */
	public function save( Model &$model ) {
		global $wpdb;
		$data          = $model->export();
		$data_type     = array();
		$exported_type = $model->export_type();
		unset( $data['table'] );
		unset( $data['safe'] );
		foreach ( $data as $key => &$val ) {
			if ( is_array( $val ) ) {
				$val = wp_json_encode( $val );
			} elseif ( is_bool( $val ) ) {
				$val = $val ? 1 : 0;
			}

			$data_type[] = $exported_type[ $key ] ?? '%s';
		}
		$table = self::table( $model );
		if ( $model->id ) {
			$ret = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				$data,
				array( 'id' => $model->id ),
				$data_type,
				array( '%d' )
			);
		} else {
			$ret = $wpdb->insert( $table, $data, $data_type ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			// Bind this for later use.
			$model->id = $wpdb->insert_id;
		}

		if ( false === $ret ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Delete a record from the database table based on the provided conditions.
	 *
	 * @param  mixed $where  The conditions to apply when deleting the record.
	 *
	 * @return int|false The number of rows affected or false on failure.
	 * @throws \ReflectionException If class is not defined.
	 */
	public function delete( $where ) {
		$table = self::table();
		global $wpdb;

		return $wpdb->delete( $table, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Delete all records from the database table based on the provided conditions.
	 *
	 * @return int|false The number of rows affected or false on failure.
	 * @throws \ReflectionException If class is not defined.
	 */
	public function delete_all() {
		$table = self::table();
		global $wpdb;

		$where = implode( ' AND ', $this->where );
		$sql   = "DELETE FROM $table WHERE $where";
		$this->clear();

		return $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Delete records from the database table based on the provided conditions and limit.
	 *
	 * @return int|false The number of rows affected or false on failure.
	 * @throws \ReflectionException If class is not defined.
	 */
	public function delete_by_limit() {
		$table = self::table();
		global $wpdb;

		$where = implode( ' AND ', $this->where );
		$limit = $this->limit;
		$order = $this->order;
		$sql   = "DELETE FROM $table WHERE $where $order $limit";
		$this->clear();

		return $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Handle the truncation of a database table.
	 *
	 * @return int|false The number of rows affected or false on failure.
	 * @throws \ReflectionException If class is not defined.
	 */
	public function truncate() {
		$table = self::table();
		global $wpdb;

		$query = "TRUNCATE TABLE $table"; // SQL is prepared here. so we can ignore WordPress.DB.PreparedSQL.NotPrepared.

		return $wpdb->query( $query );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * It is used to retrieve the table name associated with a given model.
	 *
	 * @param  mixed $model  (optional) The model object or class name. If not provided, it uses the "repository"
	 *                    property of the class.
	 *
	 * @return string|false The table name with the WordPress database prefix, or false if the table property doesn't
	 *     exist or an exception occurs.
	 * @throws \ReflectionException If the model class doesn't exist.
	 */
	private function table( $model = null ) {
		if ( is_null( $model ) ) {
			$class_name = $this->repository;
			$model      = $this->get_model();
		} else {
			$class_name = $model;
		}
		$refection = new ReflectionClass( $class_name );
		if ( $refection->hasProperty( 'table' ) ) {
			$property = $refection->getProperty( 'table' );
			$property->setAccessible( true );

			$table = $property->getValue( $model );
			global $wpdb;

			// Have to set the prefix.
			return $wpdb->base_prefix . $table;
		}
		// This when class doesn't exist.
		return false;
	}

	/**
	 * Reset all the queries prepare after an action.
	 */
	private function clear() {
		$this->select = '';
		$this->where  = array();
		$this->group  = '';
		$this->order  = '';
		$this->limit  = '';
	}

	/**
	 * Join the stuff on the table to make a full query statement.
	 * SQL params e.g. WHERE, ORDER or LIMIT were escaped on separate methods.
	 *
	 * @param  string $select  Columns to select.
	 *
	 * @return string
	 * @throws \ReflectionException If class is not defined.
	 */
	private function query_build( $select = '*' ) {
		$table = $this->table();
		$where = implode( ' AND ', $this->where );

		$select   = ! empty( $this->select ) ? $this->select : $select;
		$group_by = $this->group;
		$order_by = $this->order;
		$limit    = $this->limit;
		$sql      = "SELECT $select FROM $table WHERE $where $group_by $order_by $limit";
		$this->clear();

		return $sql;
	}

	/**
	 * Checks if the given operator is valid.
	 *
	 * @param  string $operator  The operator to check.
	 *
	 * @return bool True if the operator is valid, false otherwise.
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

		return in_array( $operator, $allowed, true );
	}

	/**
	 * Cache the model instance for clone & reference use.
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