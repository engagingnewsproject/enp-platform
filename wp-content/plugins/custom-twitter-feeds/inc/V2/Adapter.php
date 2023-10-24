<?php

/**
 * Convertor adapter, which is extended by other classes.
 *
 * @package twitter-api-v2
 */

namespace TwitterFeed\V2;

// Don't load directly.
if (! defined('ABSPATH')) {
	die('-1');
}

/**
 * Adapter class.
 */
class Adapter {
	/**
	 * Get mapped fields. Override on subclasses.
	 *
	 * @return array
	 */
	public function getMappedFields()
	{
		return [];
	}

	/**
	 * Get nested field from nested field names.
	 *
	 * @param string $field Field.
	 * @param array  $nested_field_names Nested field names.
	 *
	 * @return array
	 */
	public function getNestedFieldValue($field, $nested_field_names)
	{
		if (is_string($nested_field_names)) {
			return isset($field[ $nested_field_names ]) ? $field[ $nested_field_names ] : null;
		}

		foreach ($nested_field_names as $nested_field_name) {
			$field = $field[ $nested_field_name ];
		}

		return $field;
	}

	/**
	 * Convert entity.
	 *
	 * @param array $entity Entity.
	 *
	 * @return array
	 */
	public function convert($entity)
	{
		$converted_entity = $entity;
		foreach ($this->getMappedFields() as $field_v1 => $field_v2) {
			$converted_entity[ $field_v1 ] = $this->getNestedFieldValue($entity, $field_v2);
		}

		return $converted_entity;
	}
}
