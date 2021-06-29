<?php
use NF_Exports_Interfaces_SubmissionCsvExportInterface As SubmissionCsvExportInterface;
use NF_Exports_Interfaces_SubmissionCollectionInterface As SubmissionCollectionInterface;
use NF_Exports_Interfaces_SingleSubmissionInterface as SingleSubmissionInterface;
/**
 * 
 */
class NF_Exports_SubmissionCsvExport implements SubmissionCsvExportInterface {

    /**
     * Submission Collection
     * @var SubmissionCollectionInterface
     */
    public $submissionCollection;

    /**
     * Use admin labels boolean
     * @var bool
     */
    protected $useAdminLabels = false;

    /**
     * Date format
     * @var string
     */
    protected $dateFormat = 'm/d/Y';

    /**
     * Array of submission ids contained in collection
     * 
     * @var array
     */
    protected $submissionIds;

    /**
     * Field labels keyed on field key
     * @var array
     */
    protected $fieldLabels = [];

    /**
     * Field types keyed on field key
     * @var array
     */
    protected $fieldTypes = [];

    /**
     * Field Ids keyed on field key
     * @var array
     */
    protected $fieldIds = [];

    /**
     * Labels row for CSV
     * @var array
     */
    protected $csvLabels = [];

    /**
     * Complete array for CSV, including labels row
     * @var array
     */
    protected $csvValuesCollection = [];

    /**
     * Generate CSV output and return
     * @return string
     */
    public function handle()/* :string*/ {
        $this->constructLabels();
        $this->csvValuesCollection[] = $this->csvLabels;
        $this->appendCsvRows();
        $returned = $this->prepareCsv();

        return $returned;
    }

    /**
     * Construct string output from previously set params, mark submissions read
     * @return string
     */
    protected function prepareCsv(){
        
      // Get any extra data from our other plugins...
        $csv_array = apply_filters( 'nf_subs_csv_extra_values', $this->csvValuesCollection, $this->submissionIds, $this->submissionCollection->getFormId() );

            $this->markSubmmissionsExported();
            
            $output =    WPN_Helper::str_putcsv( $csv_array,
                apply_filters( 'nf_sub_csv_delimiter', ',' ),
                apply_filters( 'nf_sub_csv_enclosure', '"' ),
                apply_filters( 'nf_sub_csv_terminator', "\n" )
            );
            
            return $output;
    }

    
    /**
     * Record that each submission in the 
     */
    protected function markSubmmissionsExported() {
        foreach ($this->submissionCollection->getSubmissions() as $submission) {
            $submission->setExportDatetime(time());
        }
    }
    
    
    /**
     * Construct all the CSV rows from the submission collection
     */
    protected function appendCsvRows() {
        $collection = $this->submissionCollection
                ->getSubmissions();

        foreach ($collection as $submission) {
            $row = $this->constructSubmissionRow($submission);
            $this->csvValuesCollection[] = $row;
        }
    }

    /**
     * Construct a single row in the CSV from a submission 
     * @param SingleSubmissionInterface $submission
     * @return array
     */
    protected function constructSubmissionRow(/*SingleSubmissionInterface*/ $submission)/* :array */ {
        $row['_seq_num'] = $submission->getSeqNum();
        $row['_date_submitted'] = $submission->getSubmissionDate($this->dateFormat);

        $submissionValues = $submission->filterFieldValues($this->fieldLabels);
        $formId = $this->submissionCollection->getFormId();
        foreach ($submissionValues as $fieldKey => $rawValue) {

            $fieldId = $this->fieldIds[$fieldKey];
            $fieldType = $this->fieldTypes[$fieldKey];
            $field_value = maybe_unserialize($rawValue);
            $field = Ninja_Forms()->form()->field($fieldId)->get();
            
            $field_value = apply_filters('nf_subs_export_pre_value', $field_value, $fieldId);
            $field_value = apply_filters('ninja_forms_subs_export_pre_value', $field_value, $fieldId, $formId);
            $field_value = apply_filters('ninja_forms_subs_export_field_value_' . $fieldType, $field_value, $field);

            if (is_array($field_value)) {
                $field_value = implode(',', $field_value);
            }

            $row[$fieldId] = $field_value;
        }
        $strippedRow = WPN_Helper::stripslashes($row);
        // Legacy Filter from 2.9.*
        $filteredRow = apply_filters('nf_subs_csv_value_array', $strippedRow, $this->submissionIds);

        return $filteredRow;
    }

    /**
     * Construct labels array
     */
    protected function constructLabels() {

        $this->csvLabels = array_merge($this->getFieldLabelsBeforeFields(), array_values($this->fieldLabels));
    }

    /**
     * Return filtered array of labels preceding fields
     * 
     * @return array
     */
    protected function getFieldLabelsBeforeFields()/* :array */ {
        $preFilterLabels = array(
            '_seq_num' => '#',
            '_date_submitted' => esc_html__('Date Submitted', 'ninja-forms')
        );

        // Legacy Filter from 2.9.*
        $return = apply_filters('nf_subs_csv_label_array_before_fields', $preFilterLabels, $this->submissionIds);

        return $return;
    }

    /**
     * Set submission collection used in generating the CSV
     * @param SubmissionCollectionInterface $submissionCollection
     * @return SubmissionCsvExportInterface
     */
    public function setSubmissionCollection(/* SubmissionCollectionInterface */ $submissionCollection)/* :SubmissionCsvExportInterface */ {
        $this->submissionCollection = $submissionCollection;
        $this->fieldLabels = $this->submissionCollection->getLabels($this->useAdminLabels);
        $this->fieldTypes = $this->submissionCollection->getFieldTypes();
        $this->fieldIds = $this->submissionCollection->getFieldIds();
        $this->submissionIds = $this->submissionCollection->getSubmissionIds();
        return $this;
    }

    /**
     * Set boolean useAdminLabels
     * 
     * @param bool $useAdminLabels
     * @return SubmissionCsvExportInterface
     */
    public function setUseAdminLabels($useAdminLabels)/* :SubmissionCsvExportInterface */ {
        $this->useAdminLabels = $useAdminLabels;
        return $this;
    }

    /**
     * Set date format
     * 
     * @param string $dateFormat
     * @return SubmissionCsvExportInterface
     */
    public function setDateFormat(/* string */$dateFormat)/* :SubmissionCsvExportInterface */ {
        $this->dateFormat = $dateFormat;
        return $this;
    }

}
