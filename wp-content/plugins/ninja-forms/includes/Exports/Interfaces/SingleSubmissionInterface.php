<?php

/**
 * Contract defining single submission storage and retrieval
 * 
 * NOTE: File location would not permanently be in the Models folder;  it
 *  currently resides here during initial development. 
 */
interface NF_Exports_Interfaces_SingleSubmissionInterface {

    /**
     * Get Field Value
     *
     * Return a single submission value by field ID or field key.
     *
     * @param int|string $field_ref
     * @return string
     */
    public function getFieldValue($field_ref);

    /**
     * Get all submission field values for a single, pre-defined submission 
     *
     * @return array|mixed
     */
    public function getFieldValues();

    /**
     * Return the submission Id
     * @return int
     */
    public function getId();

    /**
     * Return the Sequence Number of a predefined submission Id
     */
    public function getSeqNum();

    /**
     * Return the submission date for predefined submission Id
     * @param string $format Optional date format
     */
    public function getSubmissionDate($format = 'm/d/Y');

        /**
     * Filter field values to return only provided keys
     * 
     * NOTE: filter is performed on array KEYS of incoming parameter.  This
     *  enables use of `field labels` array generated at the collection level, 
     *  which is keyed off the same field keys as the submission for perfect 
     *  matching of array columns.
     * 
     * @param array $fieldKeys Array keyed on field keys with optional value
     * @return array
     */
    public function filterFieldValues($fieldKeys)/* :array */;


    /**
     * Set timestamp of export
     * @param int $unixTimestamp
     */
    public function setExportDatetime(int $unixTimestamp);

    /**
     * Return true if submission has been exported
     * 
     * @return bool
     */
    public function wasExported();

    /**
     * Return bool `true` if submission is unread
     * @return bool
     */
    public function isUnread();
}
