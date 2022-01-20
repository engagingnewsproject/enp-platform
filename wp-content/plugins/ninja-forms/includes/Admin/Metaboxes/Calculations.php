<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Metaboxes_Calculations extends NF_Abstracts_SubmissionMetabox
{
    public function __construct()
    {
        $this->registerReactMetabox();

        // Only load if we are editing a post.
        if( ! isset( $_GET[ 'post' ] ) ) return;

        parent::__construct();

        $this->_title = esc_html__( 'Calculations', 'ninja-forms' );

        if( $this->sub && ! $this->sub->get_extra_value( 'calculations' ) ){
            remove_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        }
    }

    public function render_metabox( $post, $metabox )
    {
        $data = $this->sub->get_extra_values( array( 'calculations' ) );


        Ninja_Forms::template( 'admin-metaboxes-calcs.html.php', $data[ 'calculations' ] );
    }

    /**
     * Register the React metabox for calculations
     *
     * @return void
     */
    protected function registerReactMetabox( ): void
    {
        add_filter('nf_react_table_extra_value_keys', [$this,'nfAddMetabox']);
    }

    /**
     * Add a metabox constructor to the react.js submissions page
     *
     * @param array $metaboxHandlers
     * @return array
     */
    public function nfAddMetabox(array $metaboxHandlers): array
    {
        $metaboxHandlers['calculations']='NinjaForms\Includes\Admin\Metaboxes\CalculationsReact';

        return $metaboxHandlers;
    }
}
