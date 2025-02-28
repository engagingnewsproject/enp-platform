<?php if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'WP_CLI_Command' ) ) exit;

/**
 * The Ninja Forms WP-CLI Command
 */
class NF_WPCLI_NinjaFormsCommand extends WP_CLI_Command
{
    /**
     * Display Ninja Forms Information
     *
     * @subcommand info
     */
    function info()
    {
        $this->peeking_ninja();
        WP_CLI::success( 'Welcome to the Ninja Forms WP-CLI Extension!' );
        WP_CLI::line( '' );
        WP_CLI::line( '- Ninja Forms Version: ' . Ninja_Forms::VERSION );
        WP_CLI::line( '- Ninja Forms Directory: ' . Ninja_Forms::$dir );
        WP_CLI::line( '- Ninja Forms Public URL: ' . Ninja_Forms::$url );
        WP_CLI::line( '' );
    }

    /**
     * Creates a Form
     *
     * ## OPTIONS
     *
     * <title>
     * : The form title.
     *
     * ## EXAMPLES
     *
     *     wp ninja-forms form "My New Form"
     *
     * @synopsis <title>
     * @subcommand form
     * @alias create-form
     */
    public function create_form( $args, $assoc_args )
    {
        list( $title ) = $args;

        $form = Ninja_Forms()->form()->get();
        $form->update_setting( 'title', $title );
        $form->save();
    }

    /**
     * @subcommand list
     * @alias list-forms
     */
    public function list_forms( $args, $assoc_args )
    {
        foreach( Ninja_Forms()->form()->get_forms() as $form ){
            WP_CLI::line( '#' . $form->get_id() . ' - ' . $form->get_setting( 'title' ) );
        }
    }

    /**
     * @synopsis <id>
     * @subcommand get
     * @alias get-form
     */
    public function get_form( $args, $assoc_args )
    {
        list( $id ) = $args;

        $form = Ninja_Forms()->form( $id )->get();

        WP_CLI::line( '#' . $form->get_id() . ' - ' . $form->get_setting( 'title' ) );

        foreach( Ninja_Forms()->form( $id )->get_fields() as $field ){

            $key = $field->get_setting( 'key' );
            $label = $field->get_setting( 'label' );

            if( ! $key ) $key = strtolower( str_replace( ' ', '', $label ) );

            WP_CLI::line( "'$key': $label" );
        }
    }

    /**
     * Delete function
     * 
     * @param $type first argument determine the type of data we want to delete
     * @param $formID second argument determine the ID of the object we want to delete
     */
    public function delete( $args, $assoc_args )
    {
        $type = $args[0];
        $formID = $args[1];
        if(empty($formID) || empty($type)){
            WP_CLI::error( "Missing type or ID parameter", true );
        }
        
        if($type === "form"){
            foreach( Ninja_Forms()->form()->get_forms() as $form ){
                if($form->get_id() === (int) $formID){
                    WP_CLI::confirm( "Are you sure you want to delete form " . $form->get_setting("title") . " ?", $assoc_args );
                    Ninja_Forms()->form( $form->get_id() )->delete();
                    WP_CLI::success( 'Form deleted!' );
                    WP_CLI::halt(0);
                }
            }
        }
        WP_CLI::error( "Form ID not found", true );
    }

    /**
     * Delete all forms
     */
    public function delete_all_forms( $args, $assoc_args )
    {
        
        WP_CLI::confirm( "Are you sure you want to delete all forms?", $assoc_args );
        foreach( Ninja_Forms()->form()->get_forms() as $form ){
            Ninja_Forms()->form( $form->get_id() )->delete();
        }
        
        if ( count(Ninja_Forms()->form()->get_forms()) > 0 ) {
            WP_CLI::error( "Something went wrong" );
        } else {
            WP_CLI::success( 'All forms deleted!' );
        }
    }

    /**
     * Installs mock form data
     */
    public function mock()
    {
        $mock_data = new NF_Database_MockData();

        $mock_data->form_contact_form_1();
        $mock_data->form_contact_form_2();
        $mock_data->form_email_submission();
        $mock_data->form_long_form();
    }

    private function peeking_ninja()
    {
        $output = file_get_contents( Ninja_Forms::$dir . 'includes/Templates/wpcli-header-art.txt' );
        WP_CLI::line( $output );
    }

} // END CLASS NF_WPCLI_NinjaFormsCommand
