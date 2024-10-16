<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Admin_Menus_Licenses
 */
final class NF_Admin_Menus_Licenses
{
    private $licenses = array();

    public function __construct()
    {
        add_action( 'admin_init', array( $this, 'register_licenses' ), 10 );
        add_action( 'admin_init', array( $this, 'submit_listener'   ), 11 );
        add_action( 'admin_init', array( $this, 'add_meta_boxes'    ), 12 );
    }

    public function submit_listener()
    {
        if( ! current_user_can( apply_filters( 'ninja_forms_admin_license_update_capabilities', 'manage_options' ) ) ) return;

        if( ! isset( $_POST[ 'ninja_forms_license' ] ) || ! $_POST[ 'ninja_forms_license' ] ) return;

        if ( ! wp_verify_nonce( $_POST[ 'ninja_forms_license' ][ 'nonce' ], $_POST[ 'ninja_forms_license' ][ 'name' ] . "-nonce" ) ) die( __( 'Security failed', 'ninja-forms' ) ); 

        $key    = sanitize_text_field( $_POST[ 'ninja_forms_license' ][ 'key' ]    );
        $name   = sanitize_text_field( $_POST[ 'ninja_forms_license' ][ 'name' ]   );
        $action = sanitize_text_field( $_POST[ 'ninja_forms_license' ][ 'action' ] );

        switch( $action ){
            case 'activate':
                $this->activate_license( $name, $key );
                break;
            case 'deactivate':
                $this->deactivate_license( $name );
                break;
        }
    }

    public function register_licenses()
    {
        $this->licenses = apply_filters( 'ninja_forms_settings_licenses_addons', array() );
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'nf_settings_licenses',
            esc_html__( 'Add-On Licenses', 'ninja-forms' ) . ' <a href="
            https://ninjaforms.com/docs/licensing/?utm_source=Ninja+Forms+Plugin&utm_medium=Settings&utm_campaign=Documentation&utm_content=Licensing+Documentation" target="_blank"><img src="' . Ninja_Forms::$url . 'assets/img/help_icon.png" alt="Documentation Link" width="35" height="35"></a>',
            array( $this, 'display' ),
            'nf_settings_licenses'
        );
    }

    public function display()
    {
        $data = array();
        foreach( $this->licenses as $license ){
            $data[] = array(
                'id' => $license->product_name,
                'name' => $license->product_nice_name,
                'version' => $license->version,
                'is_valid' => $license->is_valid(),
                'license' => $this->get_license( $license->product_name ),
                'error' => Ninja_Forms()->get_setting( $license->product_name . '_license_error' ),
            );
        }

        Ninja_Forms()->template( 'admin-menu-settings-licenses.html.php', array( 'licenses' => $data ) );
    }

    private function get_license( $name )
    {
        return Ninja_Forms()->get_setting( $name . '_license' );
    }

    private function activate_license( $name, $key )
    {
        foreach( $this->licenses as $license ){

            if( $name != $license->product_name ) continue;

            $license->activate_license( $key );
        }
    }

    private function deactivate_license( $name )
    {
        foreach( $this->licenses as $license ){

            if( $name != $license->product_name ) continue;

            $license->deactivate_license();
        }
    }

} // End Class NF_Admin_Menus_Licenses
