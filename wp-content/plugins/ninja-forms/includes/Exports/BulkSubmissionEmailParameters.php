<?php

/**
 * Entity to pass bulk export submission parameters
 * 
 * Provides a guarantee that any requested or required parameters have a known
 * default value and type
 *
 */
class NF_Exports_BulkSubmissionEmailParameters {

    /**
     * Form ID
     * @var int
     */
    protected $formId;

    /**
     * Comma-delimited email 'To' addresses
     * @var string
     */
    protected $emailTo;

    /**
     * Email 'From' address
     * @var string
     */
    protected $emailFrom;

    /**
     * Email 'Reply To' address
     * @var string
     */
    protected $emailReplyTo;

    /**
     * Email 'Subject'
     * @var string
     */
    protected $emailSubject;

    /**
     * Output format - e.g. CSV, PDF
     * @var string
     */
    protected $format;

    /**
     * Get output format
     * @return string
     */
    public function getFormat() {
        return $this->format;
    }

    /**
     * Get form Id
     * @return int
     */
    public function getFormId() {
        return $this->formId;
    }

    /**
     * Get Email `To` addresses string - comma delimited
     * @return string
     */
    public function getEmailTo() {
        if (!isset($this->emailTo)) {
            return '';
        }
        return $this->emailTo;
    }

    /**
     * Get Email `From` address
     * @return string
     */
    public function getEmailFrom() {
        if (!isset($this->emailFrom)) {
            return '';
        }
        return $this->emailFrom;
    }

    /**
     * Get Email `Reply To` address
     * @return string
     */
    public function getEmailReplyTo() {
        if (!isset($this->emailReplyTo)) {
            return '';
        }
        return $this->emailReplyTo;
    }

    /**
     * Get Email `Subject`
     * @return string
     */
    public function getEmailSubject() {
        if (!isset($this->emailSubject)) {
            return '';
        }
        return $this->emailSubject;
    }

    /**
     * Set Email `To` addresses - comma delimited
     * 
     * @param string $emailTo
     * @return $this
     */
    public function setEmailTo($emailTo) {
        $this->emailTo = $emailTo;
        return $this;
    }

    /**
     * Set Email `From` address
     * @param string $emailFrom
     * @return $this
     */
    public function setEmailFrom($emailFrom) {
        $this->emailFrom = $emailFrom;
        return $this;
    }

    /**
     * Set Email `Reply To` address
     * @param string $emailReplyTo
     * @return $this
     */
    public function setEmailReplyTo($emailReplyTo) {
        $this->emailReplyTo = $emailReplyTo;
        return $this;
    }

    /**
     * Set Email `Subject` address
     * @param string $emailSubject
     * @return $this
     */
    public function setEmailSubject($emailSubject) {
        $this->emailSubject = $emailSubject;
        return $this;
    }

    /**
     * Set form Id
     * @param int $formId
     * @return NF_Database_Models_SubmissionExportSettingsParameters
     */
    public function setFormId($formId) {
        $this->formId = $formId;
        return $this;
    }

    /**
     * Set output format
     * 
     * @param string $format
     * @return NF_Database_Models_SubmissionExportSettingsParameters
     */
    public function setFormat($format) {
        $this->format = $format;
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
     * @return NF_Database_Models_SubmissionCollectionInterfaceParameters
     */
    public static function fromArray(array $items)/* : NF_Database_Models_SubmissionCollectionInterfaceParameters */ {
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
     * @return NF_Database_Models_SubmissionCollectionInterfaceParameters
     */
    public function __set($name, $value)/* : NF_Database_Models_SubmissionCollectionInterfaceParameters */ {
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
