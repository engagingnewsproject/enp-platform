<?php

/**
 * Provides standardized response for Requests
 *
 * Enables any source of a response to minimally handle the response and pass
 * it on to the requester, which will know how to process it as required
 *
 */
class NF_ConstantContact_Admin_HandledResponse {

    /**
     * Explains why the request was made
     *
     * Gives idea of where to look in troubleshooting issues
     * @var string
     */
    protected $context = '';

    /**
     * Unix-based integer timestamp
     * @var int
     */
    protected $timestamp = 0;

    /**
     * Specifies if the intent of the request was satisfied
     *
     * @var bool
     */
    protected $isSuccessful = true;

    /**
     * Indicates if the response is a WP_Error
     *
     * @var bool
     */
    protected $isWpError = false;

    /**
     * Indicates if the response is an error from the API
     * @var bool
     */
    protected $isApiError = false;

    /**
     * Indicates if the response was an Exception (non WP_Error)
     * @var bool
     */
    protected $isException = false;

    /**
     * Indicates if the response provided no results
     * @var bool
     */
    protected $hasNoData = false;

    /**
     * Count of the records returned
     * @var int
     */
    protected $recordCount = 0;

    /**
     * Collection of record data
     * @var array
     */
    protected $records = [];

    /**
     * Error code returned
     * @var int
     */
    protected $errorCode = 0;

    /**
     * Collection of error message strings
     * @var array
     */
    protected $errorMessages = [];

    /**
     * Body of response, usually in JSON format
     * @var string
     */
    protected $responseBody = '';

    /**
     * Set Context
     * @param string $string
     */
    public function setContext($string) {
        $this->context = $string;
        return $this;
    }

    /**
     * Get context
     * @return string
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * Set Record Count
     * @param int $int
     */
    public function setRecordCount($int) {
        $this->recordCount = $int;
        return $this;
    }

    /**
     * Get Record Count
     * @return int
     */
    public function getRecordCount() {
        return $this->recordCount;
    }

    /**
     * Set Records
     * @param array $array
     */
    public function setRecords($array) {
        $this->records = $array;
        return $this;
    }

    /**
     * Append a specific record, usually as a JSON string
     * 
     * @param string $string
     */
    public function appendRecord($string) {
        $this->records[] = $string;
        return $this;
    }

    /**
     * Appened several records as an array of strings
     * 
     * @param array $array
     */
    public function appendRecords($array) {
        $this->records = array_merge($this->records, $array);
        return $this;
    }

    /**
     * Get records array
     * @return array
     */
    public function getRecords() {
        return $this->records;
    }

    /**
     * Set timestamp as UNIX timestamp
     * @param int $int
     */
    public function setTimestamp($int) {
        $this->timestamp = $int;
        return $this;
    }

    /**
     * Get timestamp
     * 
     * @return int
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Set the isSuccessful boolean
     * @param bool $bool
     */
    public function setIsSuccessful($bool) {
        $this->isSuccessful = $bool;
        return $this;
    }

    /**
     * Get isSuccessful boolean
     * @return bool
     */
    public function isSuccessful() {
        return $this->isSuccessful;
    }

    /**
     * Set the isWpError boolean
     * @param bool $bool
     */
    public function setIsWpError($bool) {
        $this->isWpError = $bool;
        return $this;
    }
    /**
     * Return isWpError boolean
     * @return bool
     */
    public function isWpError() {
        return $this->isWpError;
    }

    /**
     * Set the isApiError boolean
     * @param bool $bool
     */
    public function setIsApiError($bool) {
        $this->isApiError = $bool;
        return $this;
    }
    /**
     * Return isApiError boolean
     * @return bool
     */
    public function isApiError() {
        return $this->isApiError;
    }

    /**
     * Set the isException boolean
     * @param bool $bool
     */
    public function setIsException($bool) {
        $this->isException = $bool;
        return $this;
    }
    /**
     * Return isException boolean
     * @return bool
     */
    public function isException() {
        return $this->isException;
    }

    /**
     * Set the hasNoData boolean
     * @param bool $bool
     */
    public function setHasNoData($bool) {
        $this->hasNoData = $bool;
        return $this;
    }
    /**
     * Return hasNoData boolean
     * @return bool
     */
    public function hasNoData() {
        return $this->hasNoData;
    }

/**
 * Set the Error Code
 * @param int $int
 */
    public function setErrorCode($int) {
        $this->errorCode = $int;
        return $this;
    }

    /**
     * Get the error code
     * @return int
     */
    public function getErrorCode() {
        return $this->errorCode;
    }

    /**
     * Set the error messages array
     * @param array $array
     */
    public function setErrorMessages($array) {
        $this->errorMessages = $array;
        return $this;
    }

    /**
     * Append a single error message string
     * @param string $string
     */
    public function appendErrorMessage($string) {
        $this->errorMessages[] = $string;
        return $this;
    }

    /**
     * Return the error messages array
     * @return array
     */
    public function getErrorMessages() {
        return $this->errorMessages;
    }

    /**
     * Set the response body, usually a JSON string
     * @param string $string
     */
    public function setResponseBody($string) {
        $this->responseBody = $string;
        return $this;
    }

    /**
     * Get the response body
     * @return string
     */
    public function getResponseBody() {
        return $this->responseBody;
    }

    /**
     * Convert entity to a keyed array
     * 
     * @return array
     */
    public function toArray() {
        $vars = get_object_vars($this);
        $array = [];
        foreach ($vars as $property => $value) {
            if (is_object($value) && is_callable([$value, 'toArray'])) {
                $value = $value->toArray();
            }
            $array[$property] = $value;
        }
        return $array;
    }

    public static function fromArray($items) {
        $obj = new static();
        foreach ($items as $property => $value) {
            $obj = $obj->__set($property, $value);
        }
        return $obj;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }

    /** @inheritdoc */
    public function __get($name) {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return call_user_func([$this, $getter]);
        }
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    /** @inheritdoc */
    public function __set($name, $value) {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            return call_user_func([$this, $setter], $value);
        }
        if (property_exists($this, $name)) {
            $this->$name = $value;
            return $this;
        }
        return $this;
    }

}
