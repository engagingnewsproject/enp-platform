<?php

if (!defined('ABSPATH'))
    exit;

use NF_Exports_Interfaces_SingleSubmissionInterface as SingleSubmissionInterface;

/**
 * Provides and stores single submission data using Custom Post Type
 * 
 * 
 */
final class NF_Exports_SingleSubmissionCPT implements SingleSubmissionInterface {

    /**
     * Key under which export timestamp is stored
     * 
     * Submissions without this value set are considered `not exported`
     */
    const EXPORT_TIMESTAMP_KEY = 'export_timestamp';

    /**
     * Key under which isRead boolean is stored
     * 
     * `true` indicates submission is unread; `false` indicates submission has
     *  been read
     */
    const UNREAD_KEY = 'is_unread';

    /**
     * Submission Id
     * @var string
     */
    protected $subId = '';

    /**
     * Status of the submission
     * 
     * Modeled after Wordpress' post status.  Such modeling is required when
     *  using Wordpress' custom post type, but not required in other 
     *  database structures
     * 
     * @var string
     */
    protected $_status = '';

    /**
     * User Id of the submission
     * 
     * Not sure where this is used.
     * @var string
     */
    protected $_user_id = '';

    /**
     * Form Id for the submission
     * @var string
     */
    protected $_form_id = '';

    /**
     * Submission sequence number
     * @var string
     */
    protected $_seq_num = '';

    /**
     * Submission date
     * @var string
     */
    protected $_sub_date = '';

    /**
     * Submission Modified on date
     * @var string
     */
    protected $_mod_date = '';

    /**
     * Array of field submission values using NF_Database_Models_Submission
     * 
     * 
     * @var array
     */
    protected $_field_values = array();

    /**
     * Array of non-field data stored with submission
     * @var array
     */
    protected $extraValues = array();

    /**
     * 
     * @param string|int $id
     */
    public function __construct($id) {
        $this->subId = $id;
        $this->_seq_num = get_post_meta($this->subId, '_seq_num', TRUE);
        $this->_form_id = get_post_meta($this->subId, '_form_id', TRUE);

        $sub = get_post($this->subId);

        if ($sub) {
            $this->_status = $sub->post_status;
            $this->_user_id = $sub->post_author;
            $this->_sub_date = $sub->post_date;
            $this->_mod_date = $sub->post_modified;
        }
    }

    /**
     * Get Submission ID
     *
     * @return int
     */
    public function getId() {
        return intval($this->subId);
    }

    /**
     * @inheritDoc
     * @return int
     */
    public function getSeqNum() {
        return intval($this->_seq_num);
    }

    /**
     * @inheritDoc
     * @param string $format
     * @return string
     */
    public function getSubmissionDate($format = 'm/d/Y') {
        return date($format, strtotime($this->_sub_date));
    }

    /**
     * Filter field values to return only provided keys
     * 
     * NOTE: filter is performed on array KEYS of incoming parameter.  This
     *  enables use of a previously generated `field labels` array, which is
     *  keyed off the same field keys as the submission for perfect matching
     *  of array columns.
     * 
     * @param array $fieldKeys Array keyed on field keys with optional value
     * @return array
     */
    public function filterFieldValues($fieldKeys) {

        $fieldValues = $this->getFieldValues();

        $filtered = array_intersect_key($fieldValues, $fieldKeys);

        return $filtered;
    }

    /**
     * Get Field Value
     *
     * Returns a single submission value by field ID or field key.
     *
     * @param int|string $field_ref
     * @return string
     */
    public function getFieldValue($field_ref) {
        $field_id = ( is_numeric($field_ref) ) ? $field_ref : $this->getFieldIdByKey($field_ref);

        $field = '_field_' . $field_id;

        if (isset($this->_field_values[$field]))
            return $this->_field_values[$field];

        $this->_field_values[$field] = get_post_meta($this->subId, $field, TRUE);
        $this->_field_values[$field_ref] = get_post_meta($this->subId, $field, TRUE);

        return WPN_Helper::htmlspecialchars($this->_field_values[$field]);
    }

    /**
     * Get Field Values - from existing NF_Database_Models_Submission
     * 
     * Returns all post meta
     *
     * @return array|mixed
     */
    public function getFieldValues() {
        if (!empty($this->_field_values))
            return $this->_field_values;

        $field_values = get_post_meta($this->subId, '');

        foreach ($field_values as $field_id => $field_value) {
            $this->_field_values[$field_id] = implode(', ', $field_value);

            if (0 === strpos($field_id, '_field_')) {
                $field_id = substr($field_id, 7);
            }

            if (!is_numeric($field_id))
                continue;

            $field = Ninja_Forms()->form()->get_field($field_id);
            $key = $field->get_setting('key');
            if ($key) {
                $this->_field_values[$key] = implode(', ', $field_value);
            }
        }

        return $this->_field_values;
    }


    public function getExtraValue($key) {
        if (!isset($this->extraValues[$key]) || !$this->extraValues[$key]) {
            $id = ( $this->subId ) ? $this->subId : 0;
            $this->extraValues[$key] = get_post_meta($id, $key, TRUE);
        }

        return $this->extraValues[$key];
    }

    /**
     * Get Field ID By Key
     *
     * @param $field_key
     * @return mixed
     */
    protected function getFieldIdByKey($field_key) {
        global $wpdb;

        $field_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}nf3_fields WHERE `key` = '{$field_key}' AND `parent_id` = {$this->_form_id}");

        return $field_id;
    }


    /**
     * Set timestamp of export
     * 
     * @param int $unixTimestamp
     * @return NF_Database_Models_SingleSubmissionInterface
     */
    public function setExportDatetime(int $unixTimestamp) {
        update_post_meta($this->subId, self::EXPORT_TIMESTAMP_KEY, $unixTimestamp);

        return $this;
    }

    /**
     * Return bool true if submission has exported datetime set
     * @return bool
     */
    public function wasExported() {
        $bool = false;

        $test = $this->getExtraValue(self::EXPORT_TIMESTAMP_KEY);

        if ($test) {
            $bool = true;
        }
        return $bool;
    }

    /**
     * Return bool `is submission unread?`
     * 
     * If not set, default is false (submission has been read).  Thus new
     *  submissions must be explicitly set as unread.  Without this, all 
     *  preexisting submissions will be marked as unread and can confuse
     *  existing installations.
     * 
     * @return boolean
     */
    public function isUnread() {
        $default = false;

        $return = $this->getExtraValue(self::UNREAD_KEY);

        if (true != $return) {
            $return = $default;
        }

        return $return;
    }

    /**
     * Mark the submission as `unread` via post_meta
     */
    public function markAsRead() {
        update_post_meta($this->subId, self::UNREAD_KEY, false);
    }

    /**
     * Mark the submission as `read` via post_meta
     */
    public function markAsUnread() {
        update_post_meta($this->subId, self::UNREAD_KEY, true);
    }

}
