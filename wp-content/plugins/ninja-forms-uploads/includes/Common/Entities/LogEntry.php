<?php

namespace NinjaForms\FileUploads\Common\Entities;

class LogEntry
{

    /**
     * PSR Log Level
     *
     * @var string
     */
    protected $level = '';

    /**
     * Summary message
     *
     * @var string
     */
    protected $summary = '';

    /**
     * Timestamp in epoch format
     *
     * @var integer
     */
    protected $timestamp = 0;

    /**
     * Situation under which entry happened
     *
     * This should uniquely identify the log origination.  This is important to
     * know how to unpack the supporting data
     * @var string
     */
    protected $logPoint = '';

    /**
     * Additional data
     *
     * Detailed supporting data stored as string, can be JSON, serialized, etc.
     * The unique `logPoint` identifier can be used to determine how to unpack
     * the data stored in this property
     *
     * @var string
     */
    protected $supportingData = '';

    /**
     * Expiration of log in seconds from request
     * 
     * Used for cleanup of records in DB
     *
     * @var integer
     */
    protected $expiration = 0;


    /**
     * Construct object from keyed array
     *
     * @param array $items
     * @return LogEntry
     */
    public static function fromArray(array $items): LogEntry
    {
        $obj = new static();
        foreach ($items as $property => $value) {
            if (null !== $value) {
                $obj = $obj->__set($property, $value);
            }
        }

        return $obj;
    }

    /**
     * Construct object from a string
     *
     * @param string $json
     * @return LogEntry
     */
    public static function fromString(string $json): LogEntry
    {
        try {
            $array = \json_decode($json, true);
            if (\is_null($array)) {
                $array = [];
            }
        } catch (\TypeError $e) {
            $array = [];
        } finally {

            $return = self::fromArray($array);
            return $return;
        }
    }

    /**
     * Constructs an array representation of an object
     *
     * Returns all properties; if properties are not set, then values defined by
     * setter method ensures required values are set.  Undefined properties are
     * returned as stored in object.  This enables passing of undefined
     * properties, enabling extension of object.
     */
    public function toArray(): array
    {
        $vars = \get_object_vars($this);
        $array = [];

        foreach ($vars as $property => $value) {
            if (is_object($value) && is_callable([$value, 'toArray'])) {
                $value = $value->toArray();
            }

            if (\is_null($value)) {
                $getter = 'get' . ucfirst($property);

                if (method_exists($this, $getter)) {
                    $value = call_user_func([$this, $getter]);
                }
            }

            $array[$property] = $value;
        }
        return $array;
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
        if (\method_exists($this, $getter)) {
            return call_user_func([$this, $getter]);
        }
        if (\property_exists($this, $name)) {
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
     * @return Void
     */
    public function __set($name, $value)
    {
        $setter = 'set' . ucfirst($name);
        if (\method_exists($this, $setter)) {
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

    /** @inheritDoc */
    public function __toString(): string
    {

        $vars = \get_object_vars($this);
        foreach ($vars as $property => $value) {
            if (empty($value)) {
                unset($vars[$property]);
            }
        }
        return \json_encode($vars);
    }

    /**
     * Get PSR log level
     *
     * @return  string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Set PSR log level
     *
     * @param  string  $level  PSR log level
     *
     * @return  LogEntry
     */
    public function setLevel(string $level): LogEntry
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get log summary
     *
     * @return  string
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * Set log summary
     *
     * @param  string  $summary  Log summary
     *
     * @return  LogEntry
     */
    public function setSummary(string $summary): LogEntry
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get timestamp in epoch format
     *
     * @return  int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Set timestamp in epoch format
     *
     * @param  integer  $timestamp  Timestamp in epoch format
     *
     * @return  LogEntry
     */
    public function setTimestamp($timestamp): LogEntry
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get situation under which entry happened
     *
     * @return  string
     */
    public function getLogPoint(): string
    {
        return $this->logPoint;
    }

    /**
     * Set situation under which entry happened
     *
     * @param  string  $logPoint  Situation under which entry happened
     *
     * @return  LogEntry
     */
    public function setLogPoint(string $logPoint): LogEntry
    {
        $this->logPoint = $logPoint;

        return $this;
    }

    /**
     * Get supporting data
     *
     * @return  string
     */
    public function getSupportingData(): string
    {
        return $this->supportingData;
    }

    /**
     * Set supporting data
     *
     * @param  string  $supportingData  
     *
     * @return  LogEntry
     */
    public function setSupportingData(string $supportingData): LogEntry
    {
        $this->supportingData = $supportingData;

        return $this;
    }

    /**
     * Get used for cleanup of records in DB
     *
     * @return  integer
     */
    public function getExpiration(): int
    {
        return $this->expiration;
    }

    /**
     * Set used for cleanup of records in DB
     *
     * @param  integer  $expiration  Used for cleanup of records in DB
     *
     * @return  LogEntry
     */
    public function setExpiration($expiration): LogEntry
    {
        $this->expiration = $expiration;

        return $this;
    }
}
