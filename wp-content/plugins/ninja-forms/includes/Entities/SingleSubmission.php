<?php

namespace NinjaForms\Includes\Entities;

use NinjaForms\Includes\Entities\SubmissionField;
use NinjaForms\Includes\Entities\SimpleEntity;

/**
 * Entity defining Single Submission data structure
 */
class SingleSubmission extends SimpleEntity
{

    const TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    /**
     * Submission Id
     *
     * @var string
     */
    protected $submissionRecordId = '';

    /**
     * Submission time stamp
     * 
     *
     * @var string
     */
    protected $timestamp = '';

    /**
     * Form Id to which the submission belongs
     *
     * @var string
     */
    protected $formId = '';

    /**
     * Identifier of where submission is stored
     *
     * It is a programmatic name to uniquely identify any given storage
     * location, such as NF post, CF table
     * 
     * @var string
     */
    protected $dataSource = '';

    /**
     * Submission status
     *
     * @var array
     */
    protected $status = [];
    /**
     * Collection of submitted values stored as SubmissionField entities
     *
     * Keyed on field slug within the collection.  The collection may be empty
     * if the submission data has not been retrieved yet.
     *
     * @var SubmissionField[]
     */
    protected $submissionFieldCollection = [];

    /**
     * Extra data values stored with submission
     *
     * Data structured as indexed array of MetaboxOutputEntity->toArray()
     * @var array
     */
    protected $extraValues=[];    
    

    /**
     * Associative array (string) of classes providing additional submission handling
     *
     * @var array
     */
    protected $submissionHandlers=[];

    /**
     * Get a field value by the field slug
     *
     * @param string $fieldSlug
     * @return void
     */
    public function getSubmissionFieldValue(string $fieldSlug)
    {
        $return = null;

        if (isset($this->submissionFieldCollection[$fieldSlug])) {
            $submissionField = $this->submissionFieldCollection[$fieldSlug];
            $return = $submissionField->getValue();
        }

        return $return;
    }

    /**
     * Construct entity from associative array
     *
     * @param array $items
     * @return SingleSubmission
     */
    public static function fromArray(array $items): SingleSubmission
    {
        $obj = new static();

        foreach ($items as $property => $value) {

            // Pass field value through entity to validate, then add, keyed on slug
            if ('submissionFieldCollection' === $property) {
                foreach ($value as $fieldValueArray) {
                    $fieldValueObject = SubmissionField::fromArray($fieldValueArray);
                    $obj->submissionFieldCollection[$fieldValueObject->getSlug()] = $fieldValueObject;
                }
            } else {

                $obj = $obj->__set($property, $value);
            }
        }
        return $obj;
    }

    /**
     * Constructs an array representation
     */
    public function toArray(): array
    {
        $vars = get_object_vars($this);

        $array = ['submissionFieldCollection' => []];

        foreach ($vars as $property => $value) {
            if ('submissionFieldCollection' === $property) {
                foreach ($value as $submissionField) {
                    $submissionFieldArray = $submissionField->toArray();
                    $array['submissionFieldCollection'][$submissionField->getSlug()] = $submissionFieldArray;
                }
            } else {
                $array[$property] = $value;
            }
        }
        return $array;
    }

    /**
     * Get submission Id
     *
     * @return  string
     */
    public function getSubmissionRecordId(): string
    {
        return $this->submissionRecordId;
    }

    /**
     * Set submission Id
     *
     * @param  string  $submissionRecordId  Submission Id
     *
     * @return  self
     */
    public function setSubmissionId(string $submissionRecordId): SingleSubmission
    {
        $this->submissionRecordId = $submissionRecordId;

        return $this;
    }


    /**
     * Get submission time stamp
     *
     * @return  string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * Set submission time stamp
     *
     * Force standard format 
     * @param  string  $timestamp  Submission time stamp
     *
     * @return  self
     */
    public function setTimestamp(string $timestamp)
    {
        $this->timestamp = date(self::TIMESTAMP_FORMAT, \strtotime($timestamp));

        return $this;
    }

        /**
     * Get form Id to which the submission belongs
     *
     * @return  string
     */
    public function getFormId(): string
    {
        return $this->formId;
    }

    /**
     * Set form Id to which the submission belongs
     *
     * @param  string  $formId  Form Id to which the submission belongs
     *
     * @return  self
     */
    public function setFormId(string $formId): SingleSubmission
    {
        $this->formId = $formId;

        return $this;
    }


    /**
     * Get dataSource
     * 
     * Typical locations include NF post, CF table
     *
     * @return  string
     */
    public function getDataSource(): string
    {
        return $this->dataSource;
    }

    /**
     * Set submitted values as collection of SubmissionField entities
     *
     * @param  array  $submissionFieldCollection  SubmissionField entities keyed on field slug
     *
     * @return  SingleSubmission
     */
    public function setSubmissionFieldCollection(array $submissionFieldCollection): SingleSubmission
    {
        $this->submissionFieldCollection = $submissionFieldCollection;

        return $this;
    }

    /**
     * Get submitted values as collection of SubmissionField entities
     *
     * @return  array
     */
    public function getSubmissionFieldCollection(): array
    {
        return $this->submissionFieldCollection;
    }

    /**
     * Return array of field slugs for submissionFieldCollection
     * @return array 
     */
    public function getFieldSlugs(): array
    {
        $return = \array_keys($this->submissionFieldCollection);

        return $return;
    }

    /**
     * Get extra data values stored with submission
     *
     * @return  array
     */ 
    public function getExtraValues():array
    {
        return $this->extraValues;
    }

    /**
     * Set extra data values stored with submission
     *
     * @param  array  $extraValues  Extra data values stored with submission
     *
     * @return  SingleSubmission
     */ 
    public function setExtraValues(array $extraValues):SingleSubmission
    {
        $this->extraValues = $extraValues;

        return $this;
    }

    /**
     * Get associative array (string) of classes providing additional submission handling
     *
     * [slug]=>(string)ClassName implements SubmissionHandler
     * @return  array
     */ 
    public function getSubmissionHandlers():array
    {
        return $this->submissionHandlers;
    }

    /**
     * Set associative array (string) of classes providing additional submission handling
     *
     * @param  array  $submissionHandlers  Associative array (string) of classes providing additional submission handling
     *
     * @return  SingleSubmission
     */ 
    public function setSubmissionHandlers(array $submissionHandlers):SingleSubmission
    {
        $this->submissionHandlers = $submissionHandlers;

        return $this;
    }

    /**
     * Get submission status
     *
     * @return  array
     */ 
    public function getStatus():array
    {
        return $this->status;
    }

    /**
     * Set submission status
     *
     * @param  array  $status  Submission status
     *
     * @return  SingleSubmission
     */ 
    public function setStatus(array $status):SingleSubmission
    {
        $this->status = $status;

        return $this;
    }
}
