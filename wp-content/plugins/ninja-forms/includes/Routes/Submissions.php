<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Routes_SubmissionsActions
 */
final class NF_Routes_Submissions extends NF_Abstracts_Routes
{

    /**
    * Set the API routes for submissions
    *
    *  @since 3.4.33
    */
    public function __construct(){
        add_action('rest_api_init', [ $this, 'register_routes'] );
    }

    /**
     * Register REST API routes related to submissions actions
     * 
     * @since 3.4.33
     * 
     * @route "ninja-forms-submissions/export"
     * @route 'ninja-forms-submissions/email-action"
     */
    function register_routes() {

        register_rest_route('ninja-forms-submissions', 'export', array(
            'methods' => 'POST',
            'args' => [
                'form_ids' => [
                    'required' => true,
                    'description' => esc_attr__('Array of Form IDs we want to get the submissions from.', 'ninja-forms'),
                    'type' => 'JSON encoded array',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'start_date' => [
                    'required' => true,
                    'description' => esc_attr__('strtotime($date) that represents the start date we will retrieve submssions at.', 'ninja-forms'),
                    'type' => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
                'end_date' => [
                    'required' => true,
                    'description' => esc_attr__('strtotime($date) that represents the end date we will retrieve submssions at.', 'ninja-forms'),
                    'type' => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                ],
            ],
            'callback' => [ $this, 'bulk_export_submissions' ],
            'permission_callback' => [ $this, 'permission_callback' ],
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
    public function bulk_export_submissions(WP_REST_Request $request) {

        //Gather data from the request
        $data = json_decode($request->get_body());
        if( !empty( $data->form_ids ) && !empty( $data->start_date ) && !empty( $data->end_date ) ){
            $form_ids = explode( ",", $data->form_ids);
            $start_date = $data->start_date;
            $end_date = $data->end_date;
        } else if( !empty(  $_GET['form_ids']  ) && !empty( $_GET['start_date'] ) && !empty( $_GET['end_date'] ) ) {
            $form_ids = explode( ",", $_GET['form_ids'] );
            $start_date = $_GET['start_date'];
            $end_date = $_GET['end_date'];
        } else {
            return new WP_Error( 'malformed_request', __('This request is missing data', 'ninja-forms') );
        }

        //Set params to perform query
        $params = (new NF_Exports_SubmissionCollectionFilterParameters())
            ->setStartDate($start_date)
            ->setEndDate($end_date)
            ->setHiddenFieldTypes(['submit', 'html', 'divider'])
            ->setUseAdminLabels(true)
        ;

        // Construct a collection with provided parameters
        foreach ($form_ids as $formId) {

            $submissionsCollection = (new NF_Exports_SubmissionCollectionCPT($formId))
                ->filterByParameters($params);

            $csvObject = (new NF_Exports_SubmissionCsvExport())
                ->setSubmissionCollection($submissionsCollection);

            $csv[$formId] = $csvObject->handle(true);
        }

        // Return CSV objects
        return $csv;
        
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


}