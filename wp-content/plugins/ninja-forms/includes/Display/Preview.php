<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Display_Preview
 */
final class NF_Display_Preview
{
    protected $form_id = '';
    protected $_form_id = '';

    public function __construct()
    {
        if ( ! isset( $_GET['nf_preview_form'] ) ) return;

        $this->_form_id = WPN_Helper::sanitize_text_field($_GET['nf_preview_form']);

        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

        add_filter('the_title', array( $this, 'the_title' ) );
        remove_filter( 'the_content', 'wpautop' );
        remove_filter( 'the_excerpt', 'wpautop' );
        add_filter('the_content', array( $this, 'the_content' ), 9001 );
        add_filter('get_the_excerpt', array( $this, 'the_content' ) );
        //switched from template_include to template redirect filter hook to work with block-based (FSE) themes
        add_filter('template_redirect', array( $this, 'template_include' ) );

        add_filter('post_thumbnail_html', array( $this, 'post_thumbnail_html' ) );
    }

    public function pre_get_posts( $query )
    {
		$query->set( 'posts_per_page', 1 );
    }

    /**
     * @return string
     */
    function the_title( $title )
    {
        if( ! in_the_loop() ) return $title;

        $form_title = Ninja_Forms()->form( $this->_form_id )->get()->get_setting( 'title' );

        return esc_html( $form_title ) . " " . esc_html__( 'Preview', 'ninja-forms' );
    }

    /**
     * @return string
     */
    function the_content()
    {
        if ( ! is_user_logged_in() ) return esc_html__( 'You must be logged in to preview a form.', 'ninja-forms' );

        // takes into account if we are trying to preview a non-published form
        $tmp_id_test = explode( '-', $this->_form_id );

        // if only 1 element, then is it numeric
	    if( 1 === count( $tmp_id_test) && ! is_numeric( $tmp_id_test[ 0 ] ) ) {
		    return esc_html__( 'You must provide a valid form ID.', 'ninja-forms' );
	    }
	    // if 2 array elements, is the first equal to 'tmp' and the second numeric
	    elseif ( ( 2 === count( $tmp_id_test )
	                 && ('tmp' != $tmp_id_test[ 0 ]
                     || ! is_numeric( $tmp_id_test[ 1 ] ) ) ) ) {
		    return esc_html__( 'You must provide a valid form ID.', 'ninja-forms' );
	    }

        return do_shortcode( "[nf_preview id='{$this->_form_id}']" );
    }

    /**
     * Locate_template will be loaded using second argument of the get_query_templates() function
     * First argument will be prefixed with _template to create a hook
     * @return void
     */
    function template_include()
    {
      $templates = array( 'page.php', 'single.php', 'index.php');
      include( get_query_template('ninja-forms', $templates) );

      exit;
    }

    function post_thumbnail_html() {
    	return '';
    }

} // END CLASS NF_Display_Preview
