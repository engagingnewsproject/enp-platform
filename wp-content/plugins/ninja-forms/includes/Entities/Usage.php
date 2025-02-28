<?php

namespace NinjaForms\Includes\Entities;

use JsonSerializable;

class Usage implements JsonSerializable
{
    const STRUCTURE = [
        'site_id' => 'int',
        'plugin' => 'array',
        'forms' => 'array',
        'fields' => 'array',
        'field_settings' => 'array',
        'actions' => 'array',
        'action_settings' => 'array',
        'display_settings' => 'array',
        'restrictions' => 'array',
        'calculations' => 'array',
        'submissions' => 'array',
        'settings' => 'array'
    ];

    public int $site_id = 0;
    public array $plugin = [];
    public array $forms = [];
    public array $fields = [];
    public array $field_settings = [];
    public array $actions = [];
    public array $action_settings = [];
    public array $display_settings = [];
    public array $restrictions = [];
    public array $calculations = [];
    public array $submissions = [];
    public array $settings = [];

    /**
     * Construct entity from array
     *
     * @param array $array
     * @return Usage
     */
    public static function fromArray(array $array): Usage
    {
        $obj = new static();

        foreach (self::STRUCTURE as $property => $propertyType) {

            $addThis = false;

            if (isset($array[$property])) {

                switch ($propertyType) {
                    case 'string':
                        if (is_string($array[$property])) {
                            $addThis = true;
                        }
                        break;
                    case 'array':
                        if (\is_array($array[$property])) {
                            $addThis = true;
                        } elseif (\is_string($array[$property])) {
                            // allowed to come in as JSON
                            $array[$property] = \json_decode($array[$property], true);
                            $addThis = true;
                        }
                    case 'bool':
                        if (\is_bool($array[$property])) {
                            $addThis = true;
                        }
                        break;
                    case 'int':
                        if (\is_int($array[$property])) {
                            $addThis = true;
                        }
                        break;
                    default:
                        // do not add if property type is not specified    
                }
            }

            if ($addThis) {
                $obj->$property = $array[$property];
            }
        }

        return  $obj;
    }

    /** @inheritDoc */
    public function toArray()
    {
        $return = [];

        foreach (array_keys(self::STRUCTURE) as $property) {

            $return[$property] = $this->$property;
        }

        return $return;
    }

    /** @inheritDoc */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
