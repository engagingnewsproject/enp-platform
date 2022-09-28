<?php if ( ! defined( 'ABSPATH' ) ) exit;

use NinjaForms\Includes\Admin\Processes\DeleteBatchFile;
use NinjaForms\Includes\Contracts\SubmissionHandler;

use NinjaForms\Includes\Entities\SingleSubmission;
use NinjaForms\Includes\Entities\SubmissionExtraHandlerResponse;
use NinjaForms\Includes\Factories\SubmissionAggregateFactory;
use NinjaForms\Includes\Factories\SubmissionFilterFactory;


/**
 * Class NF_Routes_SubmissionsActions
 */
final class NF_Routes_Submissions extends NF_Abstracts_Routes
{

    /**
     * Register REST API routes related to submissions actions
     * 
     * @since 3.4.33
     * 
     * @route "ninja-forms-submissions/export"
     * @route 'ninja-forms-submissions/email-action"
     */
    function register_routes() {

        register_rest_route('ninja-forms-submissions', 'submissions/get', array(
            'methods' => 'GET',
            'args' => [
                'form_ids' => [
                    'required' => true,
                    'description' => esc_attr__('Form IDs', 'ninja-forms'),
                    'type' => 'array',
                    'validate_callback' => 'rest_validate_request_arg',
                ]
            ],
            'callback' => [ $this, 'get_submissions' ],
            'permission_callback' => [ $this, 'get_submissions_permission_callback' ]
        ));

        register_rest_route('ninja-forms-submissions', 'submissions/delete', array(
            'methods' => 'POST',
            'args' => [
                'submissions' => [
                    'required' => true,
                    'description' => esc_attr__('Submissions', 'ninja-forms'),
                    'type' => 'array',
                    'validate_callback' => 'rest_validate_request_arg',
                ]
            ],
            'callback' => [ $this, 'delete_submissions' ],
            'permission_callback' => [ $this, 'delete_submissions_permission_callback' ]
        ));

        register_rest_route('ninja-forms-submissions', 'submissions/update', array(
            'methods' => 'POST',
            'args' => [
                'singleSubmission' => [
                    'required' => true,
                    'description' => esc_attr__('Update single Submission', 'ninja-forms'),
                    'type' => 'JSON encoded array',
                    'validate_callback' => 'rest_validate_request_arg',
                ]
            ],
            'callback' => [ $this, 'update_submission' ],
            'permission_callback' => [ $this, 'update_submission_permission_callback' ]
        ));

        register_rest_route('ninja-forms-submissions', 'submissions/restore', array(
            'methods' => 'POST',
            'args' => [
                'restore' => [
                    'required' => true,
                    'description' => esc_attr__('Update Submission', 'ninja-forms'),
                    'type' => 'boolean',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'submissions' => [
                    'required' => true,
                    'description' => esc_attr__('Array of Submissions', 'ninja-forms'),
                    'type' => 'array',
                    'validate_callback' => 'rest_validate_request_arg',
                ]
            ],
            'callback' => [ $this, 'restore_submissions' ],
            'permission_callback' => [ $this, 'update_submission_permission_callback' ]
        ));

        register_rest_route('ninja-forms-submissions', 'submissions/handle-extra', array(
            'methods' => 'POST',
            'args' => [
                'singleSubmission' => [
                    'required' => true,
                    'description' => esc_attr__('Update Submission', 'ninja-forms'),
                    'type' => 'JSON encoded array',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'handleExtra' => [
                    'required' => true,
                    'description' => esc_attr__('Extra Handler of Submission', 'ninja-forms'),
                    'type' => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                ]
            ],
            'callback' => [ $this, 'handle_extra_submission' ],
            'permission_callback' => [ $this, 'handle_extra_submission_permission_callback' ]
        ));

        register_rest_route('ninja-forms-submissions', 'export', array(
            'methods' => 'POST',
            'args' => [
                'form_ids' => [
                    'required' => false,
                    'description' => esc_attr__('Array of Form IDs we want to get the submissions from.', 'ninja-forms'),
                    'type' => 'array',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'start_date' => [
                    'required' => false,
                    'description' => esc_attr__('strtotime($date) that represents the start date we will retrieve submssions at.', 'ninja-forms'),
                    'type' => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'end_date' => [
                    'required' => false,
                    'description' => esc_attr__('strtotime($date) that represents the end date we will retrieve submssions at.', 'ninja-forms'),
                    'type' => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'singleSubmission' => [
                    'required' => false,
                    'description' => esc_attr__('Export single submission if a JSON encoded submission is passed', 'ninja-forms'),
                    'type' => 'JSON encoded array',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'submissions' => [
                    'required' => false,
                    'description' => esc_attr__('Export submissions based on array of submissions IDs', 'ninja-forms'),
                    'type' => 'array',
                    'validate_callback' => 'rest_validate_request_arg',
                ]             
            ],
            'callback' => [ $this, 'bulk_export_submissions' ],
            'permission_callback' => [ $this, 'get_submissions_permission_callback' ],
        ));

        register_rest_route('ninja-forms-submissions', 'download-all', array(
            'methods' => 'POST',
            'args' => [
                'form_ids' => [
                    'required' => false,
                    'description' => esc_attr__('Array of Form IDs we want to get the submissions from.', 'ninja-forms'),
                    'type' => 'array',
                    'validate_callback' => 'rest_validate_request_arg',
                ],           
            ],
            'callback' => [ $this, 'download_all_submissions' ],
            'permission_callback' => [ $this, 'get_submissions_permission_callback' ],
        ));

        /**
         * Delete the temp file created from the `download-all` request
         */
        register_rest_route('ninja-forms-submissions', 'delete-download-file', array(
            'methods' => 'POST',
            'args' => [
                'file_path' => [
                    'required' => true,
                    'description' => esc_attr__('File path of the file to delete', 'ninja-forms'),
                    'type' => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                ],           
            ],
            'callback' => [ $this, 'delete_download_file' ],
            // Uses the same permissions as the `download-all` request
            'permission_callback' => [ $this, 'get_submissions_permission_callback' ],
        ));

        register_rest_route('ninja-forms-submissions', 'set-submissions-settings', array(
            'methods' => 'POST',
            'args' => [
                'settingName' => [
                    'required' => true,
                    'description' => esc_attr__('Setting name in the submissionsSettings array', 'ninja-forms'),
                    'type' => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'data' => [
                    'required' => false,
                    'description' => esc_attr__('Settings data', 'ninja-forms'),
                    'type' => 'object',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'formID' => [
                    'required' => false,
                    'description' => esc_attr__('Form ID of the setting saved', 'ninja-forms'),
                    'type' => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                ]
            ],
            'callback' => [ $this, 'set_submissions_settings' ],
            'permission_callback' => [ $this, 'get_submissions_permission_callback' ],
        ));

        register_rest_route('ninja-forms-submissions', 'get-submissions-settings', array(
            'methods' => 'GET',
            'callback' => [ $this, 'get_submissions_settings' ],
            'permission_callback' => [ $this, 'get_submissions_permission_callback' ],
        ));

        register_rest_route('ninja-forms-submissions', 'email-action', array(
            'methods' => 'POST',
            'args' => [
                'submission' => [
                    'required' => true,
                    'description' => esc_attr__('Submission ID', 'ninja-forms'),
                    'type' => 'int',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'action_settings' => [
                    'required' => true,
                    'description' => esc_attr__('Email Action Settings', 'ninja-forms'),
                    'type' => 'object',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
            ],
            'callback' => [ $this, 'trigger_email_action' ],
            'permission_callback' => [ $this, 'permission_callback' ],
        ));

    }

   /**
     * Secure endpoint to allow users to read submissions
     * 
     * @since 3.5.8
     * 
     * Already passed Nonce validation via wp_rest and x_wp_nonce header checked against rest_cookie_check_errors()
     */
    public function get_submissions_permission_callback(WP_REST_Request $request) {
        
        //Set default to false
        $allowed = false;

        // Allow only admin to export personally identifiable data
        $permissionLevel = 'manage_options';  
        $allowed= \current_user_can($permissionLevel);
        
		/**
		 * Filter permissions for Reading Submissions
		 *
		 * @param bool $allowed Is request authorized?
		 * @param WP_REST_Request $request The current request
		 */
		return apply_filters( 'ninja_forms_api_allow_get_submissions', $allowed, $request );
    }

    /**
     * Secure endpoint to allow users to delete submissions
     * 
     * @since 3.5.8
     * 
     * Already passed Nonce validation via wp_rest and x_wp_nonce header checked against rest_cookie_check_errors()
     */
    public function delete_submissions_permission_callback(WP_REST_Request $request) {
        
        //Set default to false
        $allowed = false;

        // Allow only admin to export personally identifiable data
        $permissionLevel = 'manage_options';  
        $allowed= \current_user_can($permissionLevel);
        
		/**
		 * Filter permissions for Reading Submissions
		 *
		 * @param bool $allowed Is request authorized?
		 * @param WP_REST_Request $request The current request
		 */
		return apply_filters( 'ninja_forms_api_allow_delete_submissions', $allowed, $request );
    }

    /**
     * Secure endpoint to allow users to update a submission
     * 
     * @since 3.5.8
     * 
     * Already passed Nonce validation via wp_rest and x_wp_nonce header checked against rest_cookie_check_errors()
     */
    public function update_submission_permission_callback(WP_REST_Request $request) {
        
        //Set default to false
        $allowed = false;

        // Allow only admin to export personally identifiable data
        $permissionLevel = 'manage_options';  
        $allowed= \current_user_can($permissionLevel);
        
		/**
		 * Filter permissions for updating a submission
		 *
		 * @param bool $allowed Is request authorized?
		 * @param WP_REST_Request $request The current request
		 */
		return apply_filters( 'ninja_forms_api_allow_update_submission', $allowed, $request );
    }

    /**
     * Secure endpoint to allow users to perform extra handling
     * 
     * @since 3.5.8
     * 
     * Already passed Nonce validation via wp_rest and x_wp_nonce header checked against rest_cookie_check_errors()
     */
    public function handle_extra_submission_permission_callback(WP_REST_Request $request) {
        
        //Set default to false
        $allowed = false;

        // Allow only admin to export personally identifiable data
        $permissionLevel = 'manage_options';  
        $allowed= \current_user_can($permissionLevel);
        
		/**
		 * Filter permissions for updating a submission
		 *
		 * @param bool $allowed Is request authorized?
		 * @param WP_REST_Request $request The current request
		 */
		return apply_filters( 'ninja_forms_api_allow_handle_extra_submission', $allowed, $request );
    }

    /**
     * Secure endpoint to allowed users
     *
     * Security disclosure regarding <=3.5.7 showed that any logged in user
     * could export form data, possibly exposing personally identifiable
     * information.  Permissions changed such that only admin can export
     * submission data; a filter enables one to override that permission if
     * desired.
     * 
     * @since 3.4.33
     *
     * Already passed Nonce validation via wp_rest and x_wp_nonce header checked
     * against rest_cookie_check_errors()
     */
    public function permission_callback(WP_REST_Request $request) {
        
        //Set default to false
        $allowed = false;

        // Allow only admin to export personally identifiable data
        $permissionLevel = 'manage_options';  
        $allowed= \current_user_can($permissionLevel);
        
		/**
		 * Filter permissions for Triggering Email Actions
		 *
		 * @param bool $allowed Is request authorized?
		 * @param WP_REST_Request $request The current request
		 */
		return apply_filters( 'ninja_forms_api_allow_email_action', $allowed, $request );
    }

     /**
     * Bulk_export_submissions
     * 
     * @since 3.4.33
     * 
     * @return array of CSVs by form
     */
    public function bulk_export_submissions(WP_REST_Request $request)
    {
        //Gather data from the request
        $data = json_decode($request->get_body());

        //TODO organize queries to work with a single method instead of defining a method depending on the data received 
        if (!empty($data->form_ids) && !empty($data->start_date) && !empty($data->end_date)) { // export by date from multiple forms

            $form_ids = explode(",", $data->form_ids);
            $start_date = $data->start_date;
            $end_date = $data->end_date;
            $requestType = 'filterByDates';

        } elseif (!empty($data->singleSubmission)) { // export a single submission

            $singleSubmissionJson = $data->singleSubmission;
            $requestType = 'getSingleSubmission';

        } else if (!empty($data->submissions) && !empty($data->form_ids)) { // export multiple submissions from a single form

            $form_ids = explode(",", $data->form_ids);
            $submissionsIDs = $data->submissions;
            $requestType = 'getSubmissions';

        } else {

            return new WP_Error('malformed_request', __('This request is missing data', 'ninja-forms'));
        }

        if ('filterByDates' === $requestType) {

            foreach ($form_ids as $formId) {
                $params = (new SubmissionFilterFactory())->maybeLimitByLoggedInUser()
                    ->setStartDate($start_date)
                    ->setEndDate($end_date)
                    ->setNfFormIds([$formId])
                    ->setStatus(['active', 'publish']);

                // construct aggregate within CSV adapter, applying filter to aggregate
                $submissionAggregateCsvExportAdapter = (new SubmissionAggregateFactory())->SubmissionAggregateCsvExportAdapter();
                $submissionAggregateCsvExportAdapter->submissionAggregate->filterSubmissions($params);
                $csvObject = (new NF_Exports_SubmissionCsvExport())->setUseAdminLabels(true)
                    ->setSubmissionAggregateCsvExportAdapter($submissionAggregateCsvExportAdapter);

                $csv[$formId] = $csvObject->handle();
            }
        } elseif ('getSingleSubmission' === $requestType) {
            $singleSubmission = SingleSubmission::fromArray(json_decode($singleSubmissionJson, true));
            $formId = $singleSubmission->getFormId();
            // construct aggregate within CSV adapter, applying filter to aggregate
            $submissionAggregateCsvExportAdapter = (new SubmissionAggregateFactory())->SubmissionAggregateCsvExportAdapter();
            $submissionAggregateCsvExportAdapter->submissionAggregate->requestSingleSubmission($singleSubmission);
            $csvObject = (new NF_Exports_SubmissionCsvExport())->setUseAdminLabels(true)
                ->setSubmissionAggregateCsvExportAdapter($submissionAggregateCsvExportAdapter);

            $csv[$formId] = $csvObject->handle();
        } elseif ('getSubmissions' === $requestType) {
            $params = (new SubmissionFilterFactory())->maybeLimitByLoggedInUser()
                ->setNfFormIds($form_ids)
                ->setStartDate(0)
                ->setEndDate(time())
                ->setSubmissionsIDs($submissionsIDs);

            $submissionAggregateCsvExportAdapter = (new SubmissionAggregateFactory())->SubmissionAggregateCsvExportAdapter();
            $submissionAggregateCsvExportAdapter->submissionAggregate->filterSubmissions($params);
            $csvObject = (new NF_Exports_SubmissionCsvExport())->setUseAdminLabels(true)->setSubmissionAggregateCsvExportAdapter($submissionAggregateCsvExportAdapter);

            $csv[$form_ids[0]] = $csvObject->handle();

        }


        // Return CSV objects
        return $csv;
    }

    /**
     * Download all submissions
     * 
     * 
     * @return array 
     */
    public function download_all_submissions(WP_REST_Request $request)
    {
        //Gather data from the request
        $data = json_decode($request->get_body());

        if (!empty($data->form_ids)) { // download all submissions
            $form_ids = explode(",", $data->form_ids);
            $formId = $form_ids[0];
        } else {

            return new WP_Error('malformed_request', __('This request is missing data', 'ninja-forms'));
        }

        $export = (new NF_Admin_Processes_ExportSubmissions($formId));
        $downloadUrl = $export->getFileUrl();
        $filePath = $export->getFilePath();
        $response =[
            'formId'=>$formId,
            'downloadUrl'=>$downloadUrl,
            'filePath'=>$filePath
        ];
        
        return $response;
    }


    /**
     * Delete a file when provided valid file path
     *
     * @param WP_REST_Request $request Object with `file_path` property
     * @return void
     */
    public function delete_download_file(WP_REST_Request $request)
    {
        //Gather data from the request
        $data = json_decode($request->get_body());

        if(!empty($data->file_path)){
            
            $filePath = (string)$data->file_path;

            $deleteFile = (new DeleteBatchFile())->delete($filePath);
            
            $response=[
                'result'=>$deleteFile
            ];

            return $response;

        }else{
            return new WP_Error('malformed_request', __('This request is missing data for method delete_download_file', 'ninja-forms'));
        }

    }


    /**
     * Trigger Email Action endpoint callback
     * 
     * @since 3.4.33
     * 
     * @return bool|int depending on the value returned by wp_mail
     */
    public function trigger_email_action(WP_REST_Request $request) {
        //Extract required data
        $data = json_decode($request->get_body());  
        $form = Ninja_Forms()->form( $data->formID );
        $sub = $form->get_sub( $data->submission );
        $field_values = $sub->get_field_values();

        //Throw error if we're missing data
        if( !isset($data) || empty($form) || empty($sub) ) {
            return new WP_Error( 'malformed_request', __('This request is missing data', 'ninja-forms') );
        }
        
        //Process Merge tags       
        $action_settings = $this->process_merge_tags( $data->action_settings, $data->formID, $sub );
        //Process Email Action
        $email_action = new NF_Actions_Email();
        $result = $email_action->process( (array) $action_settings, $data->formID, (array) $field_values );

        //Return true if wp_mail returned true or the submission ID if it failed.
        $return = !empty($result['actions']['email']['sent']) && true === $result['actions']['email']['sent'] ? $result['actions']['email']['sent'] : $sub->get_seq_num();
        
        return $return;
        
    }

    /**
     * Process Merge tags for a given Value
     * 
     * @since 3.4.33
     * 
     * @return object of Email Action Model with merge tags settingsprocessed
     * 
     */
    public function process_merge_tags( $data, $form_id, $sub) {
        
        // Init Field Merge Tags.
        $fields_merge_tag_object = Ninja_Forms()->merge_tags[ 'fields' ];
        $fields_merge_tag_object->set_form_id($form_id);
            
        //Process Fields Merge Tags
        $fields = Ninja_Forms()->form( $form_id )->get_fields();
        $fields = new NF_Adapters_SubmissionsSubmission( $fields, $form_id, $sub );
        foreach( $fields as $field_id => $field){
            $fields_merge_tag_object->add_field( $field );
        }
        //Add All Fields merge tags
        $fields_merge_tag_object->include_all_fields_merge_tags();
        //include fields to the {all_fields_table} and {fields_table} mrerge tags
        foreach( $fields as $field_id => $field){
            $fields_merge_tag_object->add_field( $field );
        }
        //Loop through Action settings and apply merge tags
        $array_data = (array) $data;
        foreach( $array_data as $ind => $value ){
            if( !empty($value) && is_string($value) ){
                //Merge tag
                $data->$ind = apply_filters( 'ninja_forms_merge_tags', $value );
            } 
        }

        return $data;
    }

    /**
     * Get Submissions
     * 
     * @since 3.5.8
     * 
     * @return array of submissions for a Form
     */
    public function get_submissions(WP_REST_Request $request) {

        //Gather data from the request
        if( empty( $_GET["type"] ) || empty( $_GET["form_ids"] )){

            return new WP_Error( 'malformed_request', __('This request is missing data', 'ninja-forms') );

        } else {
            //Set Usefull data
            $type = $_GET["type"];
            $form_ids = $_GET["form_ids"];
            $page_size = !empty( $_GET["page_size"] ) ? $_GET["page_size"] : "10";
            $current_page = !empty( $_GET["current_page"] ) ? $_GET["current_page"] : "1";
            $start_date = $_GET["start_date"] != 0 ? $_GET["start_date"] : 378687600;

            // If no end date is specified, then use current time plus one day
            // to ensure it goes beyond all time zones
            $end_date = $_GET["end_date"] != 0 ? $_GET["end_date"] : time()+(60*60*24);
            $search_term = !empty($_GET["search_term"]) ? $_GET["search_term"] : null;
            $status = !empty($_GET["status"]) && $_GET["status"] === "trash" ? ["trash"] : ['active', 'publish'];
        }

        //Get aggregated submissions
        $params = (new SubmissionFilterFactory())->maybeLimitByLoggedInUser()->setNfFormIds([$form_ids]);

        if(!empty($start_date) && !empty($end_date)){
            $params->setStartDate($start_date);
            $params->setEndDate($end_date);
        }
        if($search_term){
            $params->setSearchString( $search_term );
        }
        $params->setStatus( $status );
        $submissionAggregate = (new SubmissionAggregateFactory())->submissionAggregate();
        $filteredSubmissions = $submissionAggregate->filterSubmissions( $params );
        
        //Get values of needed submissions
        if( $type === "columns" ){

            $response = $submissionAggregate->getFieldDefinitionCollection();

        } else if( $type === "data" ){

            $response = [];
            $aggregatedSubmissions = $submissionAggregate->getAggregatedSubmissions();
            $offset = ($current_page - 1) * $page_size;
            $submissions_needed = array_slice( $aggregatedSubmissions, $offset, $page_size );
            foreach( $submissions_needed as $key => $params ){
                $response['data'][$key] = $submissionAggregate->getSubmissionValuesByAggregatedKey( $key );
            } 
            $response['count'] = $submissionAggregate->getSubmissionCount();

        }

        // Return submissions data from request
        return rest_ensure_response( $response );
    }

    /**
     * Save submissions interface settings
     *
     * Data passes a json string
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function set_submissions_settings(WP_REST_Request $request){
        //Extract required data
        $data = $request->get_json_params();
        $setting = $data['settingName'];
        $new_data = $data['data'];
        $form_id = $data['formID'];
        //Get data stored and create the new value for the correct setting
        $option = get_option( 'ninja_forms_submissions_settings' );
        $current_setting_value = $option[$form_id][$setting];
        $updated_option = $option;
        $updated_option[$form_id][$setting] = $new_data;

        $response = (object)[];
        if ( false !== $option ) {
            // option exist
            if ( $current_setting_value === $new_data ) {
                $response->message = "unchanged";
                $response->status = false;
            } else {
                $response->message = "update_option";
                $response->status = update_option( 'ninja_forms_submissions_settings', $updated_option );
            }
        } else {
            // option don't exist
            $response->message = "add_option";
            $response->status = add_option( 'ninja_forms_submissions_settings', $updated_option );
        }

        return rest_ensure_response( json_encode( $response ) );
    }
    /**
     * Get submissions interface settings
     *
     * @param WP_REST_Request $request
     * @return array of settings
     */
    public function get_submissions_settings(WP_REST_Request $request){

        $settings = get_option( 'ninja_forms_submissions_settings' );

        return rest_ensure_response( json_encode( $settings ) );
    }

    /**
     * Request deletion of a collection of submissions
     *
     * Data passes as a collection of single submission entities keyed
     * under submissions
     * 
     * {"submissions": SingleSubmission[]}
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function delete_submissions(WP_REST_Request $request){
        //Extract required data
        $data = json_decode($request->get_body());  
        
        $submissions = $data->submissions;
        
        $submissionAggregate = (new SubmissionAggregateFactory())->submissionAggregate();
        
        foreach($submissions as $obj){
            
            $singleSubmission = SingleSubmission::fromArray((array)$obj);
            
            $submissionAggregate->deleteSingleSubmission($singleSubmission);
        }

        return 'ok';
    }

    /**
     * Request restoration of a collection of submissions
     *
     * Data passes as a collection of single submission entities keyed
     * under submissions
     * 
     * {"submissions": SingleSubmission[]}
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function restore_submissions(WP_REST_Request $request){
        //Extract required data
        $data = json_decode($request->get_body());  
        
        $submissions = $data->submissions;
        
        $submissionAggregate = (new SubmissionAggregateFactory())->submissionAggregate();
        
        foreach($submissions as $obj){
            
            $singleSubmission = SingleSubmission::fromArray((array)$obj);
            
            $submissionAggregate->restoreSingleSubmission($singleSubmission);
        }

        return 'ok';
    }

    /**
     * Request update of a single submission
     *
     * Data passes as a single submission entity keyed under submission
     * 
     * {"submission": SingleSubmission}
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function update_submission(WP_REST_Request $request){
        //Extract required data
        $data = json_decode($request->get_body(),true);  

        $singleSubmissionArray = $data['singleSubmission'];

        $singleSubmission = SingleSubmission::fromArray($singleSubmissionArray);

        $submissionAggregate = (new SubmissionAggregateFactory())->submissionAggregate();
                    
        $submissionAggregate->updateSingleSubmission($singleSubmission);
        
        return 'ok';
    }
    /**
     * Handle extra request for submissions
     *
     * This is a starting proof of concept that triggers a download for the PDF submissions add-on
     * 
     * Data passes as a single submission entity keyed under submission and a Class to call the handler under handleExtra key
     * 
     * {
     *  "submission": SingleSubmission,
     *  "handleExtra": HandleExtraClassName
     * }
     * 
     * @param WP_REST_Request $request
     * @return object with string of responseType, blob of PDF download and string of blobType
     */
    public function handle_extra_submission(WP_REST_Request $request){
        
        // set default response
        $response = [ ];
        
        //Extract required data
        $data = json_decode($request->get_body(),true);  

        $singleSubmissionArray = $data['singleSubmission'];

        $singleSubmission = SingleSubmission::fromArray($singleSubmissionArray);
        $submissionAggregate = (new SubmissionAggregateFactory())->submissionAggregate();

        $populatedSubmission = $submissionAggregate->requestSingleSubmission($singleSubmission);
        $extraHandler = $data['handleExtra'];
        
        /** @var SubmissionHandler $object */
        if(class_exists($extraHandler)){
            $object = new $extraHandler;
            $response = $object->handle($populatedSubmission);
        }

        // Handlers using NinjaForms\Includes\Abstracts\SubmissionHandler
        // already pass through entity, but it is not guaranteed that all
        // handlers will use the abstract
        $arrayFromEntity = (SubmissionExtraHandlerResponse::fromArray($response))->toArray();

        return $arrayFromEntity;
    }

}