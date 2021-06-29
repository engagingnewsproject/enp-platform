<?php

/**
 * Entity of parameters to filter a submission collection
 * 
 * Provides a guarantee that any requested or required parameters have a known
 *  default value and type.  The default value for all unset parameters is `null`
 *  to differentiate an unset or purposefully null value from a boolean false or
 *  an empty array.  This tells the caller that the value is not to be used in
 *  a filter, which is different than filtering on false, or empty array.
 * 
 */
class NF_Exports_SubmissionCollectionFilterParameters {

    /**
     * Start date for filtering submissions
     * @var int|null
     */
    protected $startDate = null;

    /**
     * End date for filtering submissions
     * @var int|null
     */
    protected $endDate = null;

    /**
     * Boolean to filter submission on `is unread?`
     * @var bool|null
     */
    protected $isUnread = null;

    /**
     * Boolean to filter submission on `has been previously exported?`
     * @var bool
     */
    protected $previouslyExported = null;

    /**
     * Maximum records to return
     * @var int
     */
    protected $maxReturnCount = null;

    /**
     * Boolean to use admin labels in lieu of labels
     * @var bool
     */
    protected $useAdminLabels = null;

    /**
     * Indexed array of field types to be omitted in output
     * @var array
     */
    protected $hiddenFieldTypes = null;

    /**
     * Get start date
     * @return int|null
     */
    public function getStartDate() {
        return $this->startDate;
    }

    /**
     * Get end date
     * @return int|null
     */
    public function getEndDate() {
        return $this->endDate;
    }

    /**
     * Get IsUnread? boolean
     * @return bool|null
     */
    public function getIsUnread() {
        return $this->isUnread;
    }

    /**
     * Get IsPreviouslyExported? boolean
     * @return bool|null
     */
    public function getPreviouslyExported() {
        return $this->previouslyExported;
    }

    /**
     * Get MaxReturnCount
     * @return int|null
     */
    public function getMaxReturnCount() {
        return $this->maxReturnCount;
    }

    /**
     * Get UseAdminLabels? boolean
     * @return bool|null
     */
    public function getUseAdminLabels() {
        return $this->useAdminLabels;
    }

    /**
     * Get hidden field types array
     * @return array
     */
    public function getHiddenFieldTypes() {
        return $this->hiddenFieldTypes;
    }

    /**
     * Set start date
     * 
     * @param int|null $startDate
     * @return NF_Exports_SubmissionCollectionFilterParameters
     */
    public function setStartDate(/* int*/ $startDate=null) {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * Set end date
     * 
     * @param int|null $endDate
     * @return NF_Exports_SubmissionCollectionFilterParameters
     */
    public function setEndDate(/*int */ $endDate=null) {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * Set IsUnread? boolean
     * 
     * @param bool $isUnread
     * @return NF_Exports_SubmissionCollectionFilterParameters
     */
    public function setIsUnread(bool $isUnread=null) {
        $this->isUnread = $isUnread;
        return $this;
    }

    /**
     * Set PreviouslyExported? boolean
     * 
     * @param boolean $previouslyExported
     * @return NF_Exports_SubmissionCollectionFilterParameters
     */
    public function setPreviouslyExported(bool $previouslyExported=null) {
        $this->previouslyExported = $previouslyExported;
        return $this;
    }

    /**
     * Set Maximum return count
     * 
     * @param int $maxReturnCount
     * @return NF_Exports_SubmissionCollectionFilterParameters
     */
    public function setMaxReturnCount(int $maxReturnCount=null) {
        $this->maxReturnCount = $maxReturnCount;
        return $this;
    }

    /**
     * Set UseAdminLabels? boolean
     * 
     * @param bool $useAdminLabels
     * @return NF_Exports_SubmissionCollectionFilterParameters
     */
    public function setUseAdminLabels(bool $useAdminLabels=null) {
        $this->useAdminLabels = $useAdminLabels;
        return $this;
    }

    /**
     * Set HiddenFieldTypes array
     * 
     * @param array $hiddenFieldTypes
     * @return NF_Exports_SubmissionCollectionFilterParameters
     */
    public function setHiddenFieldTypes(array $hiddenFieldTypes=null) {
        $this->hiddenFieldTypes = $hiddenFieldTypes;
        return $this;
    }

    /**
     * Convert instance to associative array
     * @return array
     */
    public function toArray()/* : array */ {
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

    /**
     * Instantiate instance from associative array
     * 
     * @param array $items
     * @return NF_Exports_SubmissionCollectionFilterParameters
     */
    public static function fromArray(array $items)/* : NF_Exports_SubmissionCollectionFilterParameters */ {
        $obj = new static();
        foreach ($items as $property => $value) {
            $obj = $obj->__set($property, $value);
        }
        return $obj;
    }

    /**
     * Magic method to return property
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return call_user_func([$this, $getter]);
        }
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    /**
     * 
     * @param string $name
     * @param mixed $value
     * @return NF_Exports_SubmissionCollectionFilterParameters
     */
    public function __set($name, $value)/* : NF_Exports_SubmissionCollectionFilterParameters */ {
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
