<?php
use NF_Exports_Interfaces_SubmissionCsvExportInterface As SubmissionCsvExportInterface;
use NF_Exports_Interfaces_SubmissionCollectionInterface As SubmissionCollectionInterface;

use NinjaForms\Includes\Entities\SubmissionField;
use NinjaForms\Includes\Entities\SingleSubmission;

use NinjaForms\Includes\Handlers\SubmissionAggregateCsvExportAdapter;
use NinjaForms\Includes\Handlers\SubmissionAggregate;
/**
 * 
 */
class NF_Exports_SubmissionCsvExport implements SubmissionCsvExportInterface {

    /**
     * Submission Collection
     * @var SubmissionCollectionInterface
     */
    public $submissionCollection;

    /** @var SubmissionAggregate */
    protected $submissionAggregate;

    /** @var  SubmissionAggregateCsvExportAdapter */
    protected $submissionAggregateCsvExportAdapter;

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
     * Lookup of NF submission SeqNum by collection Index
     * 
     * @var array
     */
    protected $seqNumLookup;

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
    public function handle()/* :string*/
    {

        $this->constructLabels();

        $this->csvValuesCollection[0][0] = $this->csvLabels;

        $this->appendRows();

        $returned = $this->prepareCsv();

        return $returned;
    }

    /** @inheritDoc */
    public function reverseSubmissionOrder(): array
    {
        $submissionCollection = $this->submissionAggregateCsvExportAdapter->submissionAggregate->getAggregatedSubmissions();

        $indicesOriginalOrder= array_keys($submissionCollection);

        $return = array_reverse($indicesOriginalOrder);

        return $return;
    }

    /** @inheritDoc */
    public function constructRow( $aggregatedKey):array{

        $singleSubmission = $this->submissionAggregateCsvExportAdapter->submissionAggregate->getSubmissionValuesByAggregatedKey($aggregatedKey);

        $this->constructSeqNumLookup($aggregatedKey, $singleSubmission);

        $row = $this->constructSubmissionRow($aggregatedKey, $singleSubmission);

        return $row;
    }

    /**
     * Construct string output from previously set params, mark submissions read
     * @return string
     */
    protected function prepareCsv(){
        $nfSubs = [];
        foreach($this->submissionIds as $submissionId){
            $nfSubs[]=Ninja_Forms()->form(  )->get_sub( $submissionId );
        }

      // Get any extra data from our other plugins...
        $csv_array = apply_filters( 'nf_subs_csv_extra_values', $this->csvValuesCollection, $nfSubs, $this->submissionAggregateCsvExportAdapter->submissionAggregate->getMasterFormId() );

            $output =    WPN_Helper::str_putcsv( $csv_array,
                apply_filters( 'nf_sub_csv_delimiter', ',' ),
                apply_filters( 'nf_sub_csv_enclosure', '"' ),
                apply_filters( 'nf_sub_csv_terminator', "\n" )
            );
            
            return $output;
    }

    /**
     * Append each submission from the collection as a row
     */
    protected function appendRows()
    {
        $indices = $this->reverseSubmissionOrder();
        // populate submission values for each submission in the collection, then append
        foreach ($indices as $index) {

            $row = $this->constructRow($index);

            $this->csvValuesCollection[1][0][] = $row;
        }
    }

    /**
     * For NF CPT, construct lookup from index for SeqNum
     *
     * @param string $index
     * @param SingleSubmission $singleSubmission
     * @return void
     */
    protected function constructSeqNumLookup(string $index, SingleSubmission $singleSubmission): void
    {
        $dataSource = $singleSubmission->getDataSource();

        // only add seq number for NF CPT
        if('nf_post'!== $dataSource){
            return;
        }

        $this->seqNumLookup[$index]= get_post_meta($singleSubmission->getSubmissionRecordId(), '_seq_num', TRUE);
    
    }

    /**
     * Construct a single row in the CSV from a submission 
     * 
     * @param SingleSubmission $submission
     * @return array
     */
    protected function constructSubmissionRow(string $index, SingleSubmission $submission)/* :array */ {

        // Add the standard fields
        $seqNum = '';

        if(isset($this->seqNumLookup[$index])){
            $seqNum = $this->seqNumLookup[$index];
        }

        $row['_seq_num'] = $seqNum; 

        $row['_date_submitted'] = $this->formatTimestamp($submission->getTimestamp());

        $submissionFields = $submission->getSubmissionFieldCollection();

        $formId = $this->submissionAggregateCsvExportAdapter->submissionAggregate->getMasterFormId();

        /** @var SubmissionField $submissionField */
        foreach ($submissionFields as $fieldKey => $submissionField) {

            if($this->useAdminLabels){
                $label = $submissionField->getAdminLabel();

                // If adminLabel is not set, default to fieldLabel
                if('' == $label){
                    $label = $submissionField->getLabel();
                }
                
            }else{ 
                $label = $submissionField->getLabel();
            }
 
            // skip if field's label is not in CSV header
            if(!in_array($label,array_values($this->fieldLabels))){
                continue;
            }

            $rawValue = $submissionField->getValue();

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

            // Append submission value into row
            $row[$fieldId] = $field_value;
        }
        $strippedRow = WPN_Helper::stripslashes($row);
        // Legacy Filter from 2.9.*
        $filteredRow = apply_filters('nf_subs_csv_value_array', $strippedRow, $this->submissionIds);

        return $filteredRow;
    }

    /**
     * Format timestamp for output
     *
     * @param string $timestamp
     * @return string
     */
    protected function formatTimestamp(string $timestamp): string
    {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s',$timestamp);

        $return = $dt->format($this->dateFormat);
        
        return $return;
    }
    /**
     * Construct labels array
     * 
     * Indexed array of labels, which serves as the column headers
     */
    protected function constructLabels() {

        $this->csvLabels = array_merge($this->getFieldLabelsBeforeFields(), array_values($this->fieldLabels));
    }

    public function getLabels( ): array
    {   
        if(empty($this->csvLabels)){
            $this->constructLabels();
        }
        return $this->csvLabels;
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
     * 
     * @param SubmissionCollectionInterface $submissionCollection
     * @return SubmissionCsvExportInterface
     */
    public function setSubmissionCollection(/* SubmissionCollectionInterface */$submissionCollection)/* :SubmissionCsvExportInterface */
    {

        return $this;
    }

    /**
     * Set SubmissionAggregateCsvExport Adapter used in generating the CSV
     *
     * @param SubmissionAggregateCsvExportAdapter $submissionAggregateCsvExportAdapter
     * @return SubmissionCsvExportInterface
     */
    public function setSubmissionAggregateCsvExportAdapter(SubmissionAggregateCsvExportAdapter $submissionAggregateCsvExportAdapter)/* :SubmissionCsvExportInterface */
    {
        $this->setDateFormat();
        
        $this->submissionAggregateCsvExportAdapter = $submissionAggregateCsvExportAdapter;

        $this->submissionAggregateCsvExportAdapter->setHiddenFieldTypes([
            'html', 'submit', 'divider', 'hr', 'note', 'unknown', 'button', 'confirm'
        ]);
        
        $this->fieldLabels = $this->submissionAggregateCsvExportAdapter->getLabels($this->useAdminLabels);

        $this->fieldTypes = $this->submissionAggregateCsvExportAdapter->getFieldTypes();
        $this->fieldIds = $this->submissionAggregateCsvExportAdapter->getFieldIds();
        $this->submissionIds = $this->submissionAggregateCsvExportAdapter->getSubmissionIds();
        return $this;
    }

    /**
     * Set boolean useAdminLabels
     * 
     * @param bool $useAdminLabels
     * @return SubmissionCsvExportInterface
     */
    public function setUseAdminLabels($useAdminLabels) :SubmissionCsvExportInterface  {
        $this->useAdminLabels = $useAdminLabels;
        return $this;
    }

    /**
     * Set date format
     * 
     * @param string $dateFormat
     * @return SubmissionCsvExportInterface
     */
    public function setDateFormat(/* string */$dateFormat = null)/* :SubmissionCsvExportInterface */ {
        if(!empty($dateFormat)) {
            //Set new date format
            $date_format = $dateFormat;
        } else if( !empty( Ninja_Forms()->get_setting( 'date_format' ) ) ) {
            //Or get NF Date format set
            $date_format = Ninja_Forms()->get_setting( 'date_format' );
        } else if(!empty( get_option('date_format'))) {
            //Or get WP date format set
            $date_format =  get_option('date_format');
        } else {
            //Or leave default
            $date_format = $this->dateFormat;
        }
        
        $this->dateFormat = $date_format;

        return $this;
    }

}
