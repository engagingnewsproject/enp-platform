<?php if (!defined('ABSPATH')) exit;


use NinjaForms\Includes\Factories\SubmissionAggregateFactory;
use NinjaForms\Includes\Entities\SubmissionFilter;

/**
 * Class NF_Abstracts_Batch_Process
 */
class NF_Admin_Processes_ExportSubmissions extends NF_Abstracts_BatchProcess
{
    protected $_slug = 'export_submissions';
    protected $form = '';
    protected $subs_per_step = 25;
    protected $sub_count = 0;
    protected $format = 'csv';
    protected $offset = 0;
    protected $delimiter = ',';
    protected $enclosure = '"';
    protected $terminator = "\n";

    protected $currentPosition = 0;

    /**
     * Filepath of downloaded file in default directory
     *
     * @param array $forms
     */
    protected $file_path = '';

    /**
     * File URL of downloaded file in default directory
     *
     * @param array $forms
     */
    protected $fileUrl = '';


    /**
     * @var NF_Exports_SubmissionCsvExport
     */
    protected $csvObject;

    /**
     * Aggregated submission keys in output order
     *
     * @var array
     */
    protected $indexedLookup;

    /**
     * Override parent construct to pass form Ids
     *
     * @param array $forms
     */
    public function __construct($form = '')
    {
        global $wpdb;

        $this->form = $form;

        /**
         * Set $_db to $wpdb.
         * This helps us by not requiring us to declare global $wpdb in every class method.
         */
        $this->_db = $wpdb;

        // Run init.
        $this->init();
    }

    /**
     * Function to run any setup steps necessary to begin processing.
     */
    public function startup()
    {

        //TODO: Verify what type of export this is (filter) later
        //TODO: Read in submission IDs later
        //TODO: Read in start/end date params later
        // Right now, we assume that we have a single form ID and that the export is always a csv file.

        // Verify filterable values.
        $this->subs_per_step = apply_filters('ninja_forms_export_subs_per_step', $this->subs_per_step);
        $this->delimiter = apply_filters('nf_sub_csv_delimiter', $this->delimiter);
        $this->enclosure = apply_filters('nf_sub_csv_enclosure', $this->enclosure);
        $this->terminator = apply_filters('nf_sub_csv_terminator', $this->terminator);

        // Construct a new submission aggregate.
        $params = (new SubmissionFilter())->setNfFormIds([$this->form]);
        $params->setEndDate(time());
        $params->setStartDate(0);
        $params->setStatus(["active", "publish"]);

        $submissionAggregateCsvAdapter = (new SubmissionAggregateFactory())->SubmissionAggregateCsvExportAdapter();
        $submissionAggregateCsvAdapter->submissionAggregate->filterSubmissions($params);

        $this->csvObject = (new NF_Exports_SubmissionCsvExport())->setUseAdminLabels(true)->setSubmissionAggregateCsvExportAdapter($submissionAggregateCsvAdapter);
        $this->indexedLookup = $this->csvObject->reverseSubmissionOrder();



        //Get a count of how many submissions we're dealing with.
        $this->sub_count = $submissionAggregateCsvAdapter->submissionAggregate->getSubmissionCount();

        // If there are no subs, bail.
        if (0 === $this->sub_count) {
            $this->add_error('no_submissions', esc_html__('No Submissions to export.', 'ninja-forms'), 'fatal');
            $this->batch_complete();
        }

        // Initialize our file.
        $this->file_path = $this->constructFilepath();
        // Use 'w' to delete the original file if one exists and replace it with a new one.
        if (!$file = fopen($this->file_path, 'w')) {
            $this->add_error('write_failure', esc_html__('Unable to write file.', 'ninja-forms'), 'fatal');
            $this->batch_complete();
        }

        // Add headers to the file.
        // We can only do this outside of the process method under the assumption that a single form ID is provided.
        $labels = $this->csvObject->getLabels();
        $glue = $this->enclosure . $this->delimiter . $this->enclosure;
        $constructed = $this->enclosure . implode($glue, $labels) . $this->enclosure . $this->terminator;
        fwrite($file, $constructed);
        fclose($file);
    }

    /**
     * Function to run any setup steps necessary to begin processing for steps after the first.
     *
     * @since 3.5.0
     * @return  void 
     */
    public function restart()
    {
        //TODO: Read back in our unfinished data.
    }

    /**
     * Function to loop over the batch.
     *
     * @since 3.5.0
     * @return  void 
     */
    public function process()
    {
        if($this->currentPosition >= $this->sub_count-1){
            $this->batch_complete();
            return;
        }

        $this->writeBatch();

        // Continue looping until end
        $this->process();

    }

    /** 
     * Delete temp file before calling parent method
     * @inheritDoc 
     */
    public function batch_complete( ): void
    {
        parent::batch_complete();
    }

    public function writeBatch( ): void
    {
        if (!$file = fopen($this->file_path, 'a')) {
            $this->add_error('write_failure', esc_html__('Unable to write file.', 'ninja-forms'), 'fatal');
            $this->batch_complete();
        }

        $glue = $this->enclosure . $this->delimiter . $this->enclosure;

        // for each submission within the step
        for ($i = 0; $i < $this->subs_per_step; $i++) {
            if (!isset($this->indexedLookup[$this->currentPosition])) {
                continue;
            }

            $aggregatedKey = $this->indexedLookup[$this->currentPosition];
            $row = $this->csvObject->constructRow($aggregatedKey);

            $constructed = $this->enclosure . implode($glue, $row) . $this->enclosure . $this->terminator;
            fwrite($file, $constructed);

            $this->currentPosition++;
        }
    }
    /**
     * Method that encodes $this->response and sends the data to the front-end.
     * 
     * @since 3.4.0
     * @updated 3.4.11
     * @return  void 
     */
    public function respond()
    {
        if (!empty($this->response['errors'])) {
            $this->response['errors'] = array_unique($this->response['errors']);
        }

        return wp_json_encode($this->response);
    }
    /**
     * Function to cleanup any lingering temporary elements of a batch process after completion.
     *
     * @since 3.5.0
     * @return  void 
     */
    public function cleanup()
    {
        //TODO: Get rid of our data option.
        /**
         * We shouldn't need to delete our csv file,
         * as that will be overwritten the next time this process is called.
         */
    }

    /**
     * Get Steps
     * Determines the amount of steps needed for the step processors.
     *
     * @since 3.5.0
     * @return int of the number of steps.
     */
    public function get_steps()
    {
        //TODO: Refactor this when multiple form IDs are introduced.

        // Ensure we convent our numbers from int to float to ensure that ceil works.
        // Get the amount of steps and return.
        $steps = ceil(floatval($this->sub_count) / floatval($this->subs_per_step));
        return $steps;
    }

    /**
     * Get the filepath of our constructed csv.
     * 
     * @return string
     */
    protected function constructFilepath()
    {
        $filename = time() . base64_encode( 'form-' . $this->form . '-all-subs' );
        $upload_dir = wp_upload_dir();
        $file_path = trailingslashit($upload_dir['path']) . $filename . '.' . $this->format;

        $this->fileUrl = trailingslashit($upload_dir['url']) . $filename . '.' . $this->format;
        return $file_path;
    }

    /**
     * Overwrites the default flag method to use user options
     * instead of using the default options.
     * 
     * @since 3.5.0
     * @param $flag (String) The flag to check
     * @param $action (String) The type of interaction to be performed
     * @return Mixed
     */
    public function flag($flag, $action)
    {
        switch ($action) {
            case 'add':
                return update_user_option(get_current_user_id(), $flag, true);
            case 'remove':
                return delete_user_option(get_current_user_id(), $flag);
            default:
                // Default to 'check'.
                return get_user_option(get_current_user_id(), $flag);
        }
    }

    /**
     * Get file URL of downloaded file in default directory
     */
    public function getFileUrl(): string
    {
        return $this->fileUrl;
    }

    /**
     * Get filepath of downloaded file in default directory
     */
    public function getFilePath(): string
    {
        return $this->file_path;
    }
}
