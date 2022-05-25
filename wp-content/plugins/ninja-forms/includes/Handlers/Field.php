<?php

namespace NinjaForms\Includes\Handlers;

use NinjaForms\Includes\Contracts\Field as ContractsField;

/**
 * Honor Field contract providing NF Field object methods
 *
 * Provides functionality normally handled by the field object created by
 * \Ninja_Forms()->form()->get_field( $fieldId )
 * 
 * 
 */
class Field implements ContractsField
{

    /**
     * Field Id
     *
     * @var int
     */
    protected $id = 0;

    /**
     * Field type
     *
     * @var string
     */
    protected $type = '';

    /**
     * Field settings
     *
     * @var array
     */
    protected $settings = [];

    /**
     * @inheritDoc
     */
    public function get_id(): int
    {
        return $this->id;
    }

    /** @inheritDoc */
    public function get_tmp_id()
    {
        return null;
    }


    /** @inheritDoc */
    public function get_type(): string
    {
        return $this->type;
    }

    /** @inheritDoc */
    public function get_setting($setting, $default = FALSE)
    {
        if (isset($this->settings[$setting])) {
            $return = $this->settings[$setting];
        } else {
            $return = $default;
        }

        return $return;
    }

    /** @inheritDoc */
    public function get_settings(): array
    {
        return $this->settings;
    }

    /** @inheritDoc */
    public function update_setting($key, $value): Field
    {
        return $this;
    }

    /** @inheritDoc */
    public function update_settings($data): Field
    {
        return $this;
    }

    /** @inheritDoc */
    public function delete()
    {
        return $this;
    }
    /** @inheritDoc */
    public function find($parent_id = '', array $where = array()): array
    {
        return [];
    }

    /** @inheritDoc */
    public function get_object_settings($obj_array): array
    {
        return [];
    }

    /** @inheritDoc */
    public function save()
    {
    }

    /** @inheritDoc */
    public function _insert_row($data = array()): void
    {
    }

    /** @inheritDoc */
    public function cache($cache = ''): Field
    {
        return $this;
    }

    /** @inheritDoc */
    public function add_parent($parent_id, $parent_type): Field
    {
        return $this;
    }

    /** @inheritDoc */
    public static function import(array $settings, $field_id = '', $is_conversion = FALSE): void
    {
    }

    /**
     * Construct entity from associative array
     *
     * @param array $items
     * @return Field
     */
    public static function fromArray(array $items): Field
    {
        $obj = new static();

        foreach ($items as $property => $value) {

            $obj = $obj->__set($property, $value);
        }

        return $obj;
    }

    /**
     * Magic method getter for properties
     *
     * @param string $name
     * @return void
     */
    public function __get($name)
    {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return call_user_func([$this, $getter]);
        }
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        if (isset($this->$name)) {
            return $this->$name;
        }
    }

    /**
     * Magic method setter for properties
     *
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            try {
                return call_user_func([$this, $setter], $value);
            } catch (\TypeError $e) {
                // Do not set invalid type
                return $this;
            }
        }

        if (property_exists($this, $name)) {
            $this->$name = $value;
            return $this;
        }

        return $this;
    }
}
