<?php
/**
 * Database API base class.
 */
abstract class Wslm_Database {
	/**
	 * Execute a query and return all matching rows.
	 *
	 * @param string $query
	 * @param array $parameters
	 * @return array
	 */
	abstract public function getResults($query, $parameters = array());

	public function getRow($query, $parameters) {
		$results = $this->getResults($query, $parameters);
		if ( !empty($results) ) {
			return reset($results);
		} else {
			return null;
		}
	}
}
