<?php

class NF_Adapters_SubmissionsFields implements ArrayAccess, Iterator
{
    protected $fields;
    protected $fields_by_key = [];

    public function __construct($fields = [], $form_id)
    {
        foreach ($fields as $field) {
            if (is_array($field)) {
                if (!isset($field['key'])) {
                    continue;
                }
                $key = $field['key'];
            } else {
                if (!method_exists($field, 'get_setting')) {
                    continue;
                }
                $key = $field->get_setting('key');
            }
            $this->fields_by_key[$key] = $field;
        }
        $fields_sorted = apply_filters('ninja_forms_get_fields_sorted', array(), $this->fields, $this->fields_by_key, $form_id);

        if (!empty($fields_sorted)) {
            $this->fields = $fields_sorted;
        } else {
            $this->fields = $fields;
        }
    }

    public function get_value($id)
    {
        return $this->fields[$id]['value'];
    }

    /*
    |--------------------------------------------------------------------------
    | ArrayAccess
    |--------------------------------------------------------------------------
    */

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->fields[] = $value;
        } else {
            $this->fields[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        if (isset($this->fields[$offset])) {
            return true;
        }
        if (isset($this->fields_by_key[$offset])) {
            return true;
        }
        return false;
    }

    public function offsetUnset($offset)
    {
        unset($this->fields[ $offset ]);
    }

    public function offsetGet($offset)
    {
        if (isset($this->fields[$offset])) {
            return $this->fields[$offset];
        }
        if (isset($this->fields_by_key[$offset])) {
            return $this->fields_by_key[$offset];
        }
        return array(
            'type' => '',
            'label' => '',
            'admin_label' => '',
            'value' => ''
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Iterator
    |--------------------------------------------------------------------------
    */

    public function key()
    {
        return key($this->fields);
    }

    public function current()
    {
        return current($this->fields);
    }

    public function next()
    {
        next($this->fields);
    }

    public function rewind()
    {
        reset($this->fields);
    }

    public function valid()
    {
        return current($this->fields);
    }
}
