<?php

namespace NinjaForms\Includes\Contracts;


/**
 * Contract that mimics methods from NF_Database_Models_Field
 *
 * Provides functionality normally handled by the field object created by
 * \Ninja_Forms()->form()->get_field( $fieldId )
 *
 *
 */
Interface Field
{

    /**
     * Get field type
     *
     * @return string
     */
    public function get_type(): string;

    /** 
     * Get a field setting
     *
     * @param string $setting
     * @param mixed $default
     * @return mixed
     */
    public function get_setting($setting, $default = FALSE);

    /**
     * Get all field settings
     * 
     * @return array
     */
    public function get_settings(): array;

    /** 
     * Update a field setting
     *
     * @param string $key
     * @param mixed $value
     */
    public function update_setting($key, $value): Field;
    

    /**
     * Update all settings
     *
     * @param array $data
     * @return Field
     */
    public function update_settings($data): Field;

    /**
     * Delete field
     *
     * @return void
     */
    public function delete();

    /**
     * Find
     *
     * @param string $parent_id
     * @param array $where
     * @return array
     */
    public function find($parent_id = '', array $where = array()): array;

    /**
     * Get object settings
     *
     * @param [type] $obj_array
     * @return array
     */
    public function get_object_settings($obj_array): array;

    /**
     * Save field
     *
     * @return void
     */
    public function save();

    /**
     * Insert row
     *
     * @param array $data
     * @return void
     */
    public function _insert_row($data = array()): void;
    
    /**
     * Cache
     *
     * @param string $cache
     * @return Field
     */
    public function cache($cache = ''): Field;

    /**
     * Add parent
     *
     * @param [type] $parent_id
     * @param [type] $parent_type
     * @return Field
     */
    public function add_parent($parent_id, $parent_type): Field;

    /**
     * Import field
     *
     * @param array $settings
     * @param string $field_id
     * @param boolean $is_conversion
     * @return void
     */
    public static function import(array $settings, $field_id = '', $is_conversion = FALSE): void;



}
