<?php

namespace NinjaForms\Blocks\DataBuilder;

class SubmissionsBuilder
{

    protected $submissions;

    /**
     * Fieldset Repeater object to contain fieldset functionality 
     * 
     * @var \NF_Handlers_FieldsetRepeater 
     */
    protected $fieldsetRepeater;

    public static function make($formID)
    {
        $submissions = array_map(function ($submission) {
            return $submission->get_field_values();
        }, Ninja_Forms()->form($formID)->get_subs());
        return new self($submissions);
    }

    public function __construct($submissions)
    {
        $this->submissions = $submissions;
    }

    public function get()
    {
        // Initialize the fieldset repeater object in protected method
        $this->initializeFieldsetRepeater();

        return array_map([$this, 'toArray'], $this->submissions);
    }

    protected function toArray($values)
    {
        $values = array_map([$this, 'formatValue'], $values);
        $values = array_filter($values, function ($value, $key) {
            return 0 === strpos($key, '_field_');
        }, ARRAY_FILTER_USE_BOTH);
        return $this->normalizeArrayKeys($values);
    }

    protected function formatValue($value)
    {

        /**
         * Basic File Uploads support.
         * 
         * Auto-detect a file uploads value, by format, as a serialized array.
         * @note using a preliminary `is_serialized()` check to determine
         *       if the value is from File Uploads, since we do not have
         *       access to the field information in this context.
         */
        if (is_serialized($value)) {
            $unserialized = unserialize($value);
            if (is_array($unserialized)) {

                // This is the default value assuming it is a file upload
                $return = implode(', ', array_values($unserialized));

                // If it is fieldset repeater data, construct fieldset repeater output instead
                $isFieldsetData = $this->fieldsetRepeater->isFieldsetData($unserialized);

                if ($isFieldsetData) {
                    $return = $this->constructOutputForFieldset($unserialized);
                }

                return $return;
            }
        }

        return $value;
    }

    /**
     * Construct string representation of fieldset repeater data for block
     * 
     * 
     *
     * @param array $fieldsetSubmissionValues
     * @return string
     */
    protected function constructOutputForFieldset(array $fieldsetSubmissionValues): string
    {
        $outgoingValue = '';

        $list_fields_types = array('listcheckbox', 'listmultiselect', 'listradio', 'listselect', 'listcountry', 'listimage', 'liststate', 'terms');

        $extractedSubmissions = $this->fieldsetRepeater->extractSubmissions('', $fieldsetSubmissionValues, []);

        // Iterate submission indexes (each repeated fieldset in the submission)
        foreach ($extractedSubmissions as $submissionIndex => $fieldsetArray) {

            $outgoingValue .= '#' . ($submissionIndex + 1) . ' ';

            // Iterate each field within a submission index
            foreach ($fieldsetArray as $fieldsetFieldId => $submissionValueArray) {

                // Check to see if the type is a list field and if it is...
                if (in_array($submissionValueArray['type'], array_values($list_fields_types))) {

                    // implode the value if is_array
                    if (is_array($submissionValueArray['value'])) {
                        $submissionValueArray['value'] = implode(', ', $submissionValueArray['value']);
                    }
                }
                $outgoingValue .= $submissionValueArray['value'] . ' ';
            }

            $outgoingValue .= ' ' . ' ';
        }

        return $outgoingValue;
    }

    protected function normalizeArrayKeys($values)
    {
        $keys = array_map(function ($key) {
            return str_replace('_field_', '', $key);
        }, array_flip($values));
        return array_flip($keys);
    }

    /**
     * Isolate construction of FieldsetRepeater
     */
    protected function initializeFieldsetRepeater(): void
    {
        if (!isset($this->fieldsetRepeater)) {
            $this->fieldsetRepeater = \Ninja_Forms()->fieldsetRepeater;
        }
    }
}
