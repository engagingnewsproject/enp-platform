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
    public function constructRow( $aggregatedKey):array
    {
        $singleSubmission = $this->submissionAggregateCsvExportAdapter->submissionAggregate->getSubmissionValuesByAggregatedKey($aggregatedKey);

        $this->constructSeqNumLookup($aggregatedKey, $singleSubmission);

        $row = $this->constructSubmissionRow($aggregatedKey, $singleSubmission);
        //Can be array of $rows since repeaters are divided by rows for each fieldset
        return $row;
    }

    /**
     * Construct string output from previously set params, mark submissions read
     * @return string
     */
    protected function prepareCsv()
    {
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
            //Catch reference to an array or repeated fieldsets of repeater field to display each entry as a row
            if( array_key_exists('repeater', $row) && is_array($row['repeater']) ){
                foreach($row['repeater'] as $eachRow){
                    $this->csvValuesCollection[1][0][] = $eachRow;
                }
            } else {
                $this->csvValuesCollection[1][0][] = $row;
            }

        }
    }

    /**
     * For NF CPT, construct lookup from index for SeqNum
     *
     * @param string $aggregatedKey
     * @param SingleSubmission $singleSubmission
     * @return void
     */
    protected function constructSeqNumLookup(string $aggregatedKey, SingleSubmission $singleSubmission): void
    {
        $dataSource = $singleSubmission->getDataSource();

        // only add seq number for NF CPT
        if('nf_post'!== $dataSource){
            return;
        }

        $this->seqNumLookup[$aggregatedKey]= get_post_meta($singleSubmission->getSubmissionRecordId(), '_seq_num', TRUE); 
    }

    /**
     * Construct a single row in the CSV from a submission 
     * 
     * @todo Refactor to remove DB call for NF()->form()->field() on each iteration
     * @param SingleSubmission $submission
     * @return array
     */
    protected function constructSubmissionRow(string $aggregatedKey, SingleSubmission $submission)/* :array */ {

        // Add the standard fields
        $seqNum = '';

        if(isset($this->seqNumLookup[$aggregatedKey])){
            $seqNum = $this->seqNumLookup[$aggregatedKey];
        }

        $row['_seq_num'] = $seqNum; 

        $row['_date_submitted'] = $this->formatTimestamp($submission->getTimestamp());

        $columnValues = $this->submissionAggregateCsvExportAdapter->getColumnValuesByAggregatedKey($aggregatedKey);

        if( array_key_exists('repeater', $columnValues) ){
            $strippedRows = [];
            $newColumnValues = $columnValues;
            $repeaterValuesArray = [];
            unset($newColumnValues['repeater']);
            $row = array_merge($row, $newColumnValues);
            //Extract Repeater rows
            foreach($columnValues['repeater'] as $repeaterFieldID => $repeaterFieldsetRowValue){
                foreach($repeaterFieldsetRowValue as $index => $fieldsetValue){
                    $repeaterValuesArray[$index][$repeaterFieldID] = $fieldsetValue; 
                }
            }
            //insert global row data in repeater rows
            foreach($repeaterValuesArray as $rowIncludingRepeaterData){
                $row = array_merge($row, $rowIncludingRepeaterData);
                $strippedRows["repeater"][] = WPN_Helper::stripslashes($row);
            } 

            return $strippedRows;

        } else {
            $row = array_merge($row,$columnValues);
       
            $strippedRow = WPN_Helper::stripslashes($row);
    
            return $strippedRow;
        }

       
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
    protected function constructLabels() 
    {
        $this->csvLabels = array_merge($this->getFieldLabelsBeforeFields(), array_values($this->fieldLabels));
    }

    /**
     * Return labels for the CSV, including SeqNum and Date
     *
     * @return array
     */
    public function getLabels( ): array
    {   
        if(empty($this->csvLabels)){
            $this->constructLabels();
        }
        return $this->csvLabels;
    }
    /**
     * Return array of labels preceding fields
     * 
     * @return array
     */
    protected function getFieldLabelsBeforeFields()/* :array */ {
        $labels = array(
            '_seq_num' => '#',
            '_date_submitted' => esc_html__('Date Submitted', 'ninja-forms')
        );

        return $labels;
    }

    /**
     * Set submission collection used in generating the CSV
     * 
     * @todo Investigate reason for commented out type declarations
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
