<?php

namespace NinjaForms\Includes\Entities;
use JsonSerializable;

class NfSite implements JsonSerializable
{
    const STRUCTURE = [
        'url' => 'string',
        'ip_address' => 'string'
    ];

    public string $url='';
    public string $ip_address='';
    
    /**
     * Construct entity from array
     *
     * @param array $array
     * @return NfSite
     */
    public static function fromArray(array $array): NfSite
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
                        }
                        break;
                    case 'bool':
                        if (\is_bool($array[$property])) {
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
