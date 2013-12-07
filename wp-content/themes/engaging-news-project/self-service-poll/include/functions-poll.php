<?php
define('child_template_directory', get_stylesheet_directory_uri() );

// BUILT WITH LESS, so add bootstrap to a wrapper to apply styles
wp_enqueue_style( 'main-css', child_template_directory . '/self-service-poll/css/main.css');
wp_enqueue_style( 'bootstrap', child_template_directory . '/self-service-poll/css/bootstrap-prefix.css');
wp_enqueue_style( 'slider', child_template_directory . '/self-service-poll/css/slider.css');
wp_enqueue_style( 'jqplot', child_template_directory . '/self-service-poll/css/jquery.jqplot.min.css');
// wp_enqueue_style( 'bootstrap', child_template_directory . '/self-service-poll/css/main.less');
//wp_enqueue_style( 'bootstrap', child_template_directory . '/self-service-poll/css/bootstrap.min.css');

// wp_enqueue_style( 'bootstrap-theme', child_template_directory . '/self-service-poll/css/bootstrap-theme.min.css');

wp_enqueue_script('poll-custom', child_template_directory . '/self-service-poll/js/poll-custom.js', array('jquery'), '1.0', true);
wp_enqueue_script('bootstrap-js', child_template_directory . '/self-service-poll/js/vendor/bootstrap.min.js', array('jquery'), '1.0', true);
// wp_enqueue_script('less', child_template_directory . '/self-service-poll/js/vendor/less-1.5.1.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('validate', child_template_directory . '/self-service-poll/js/vendor/jquery.validate.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('slider', child_template_directory . '/self-service-poll/js/vendor/bootstrap-slider.js', array('jquery'), '1.0', true);
wp_enqueue_script('jqplot', child_template_directory . '/self-service-poll/js/vendor/jquery.jqplot.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('excanvas', child_template_directory . '/self-service-poll/js/vendor/excanvas.js', array('jquery'), '1.0', true);
//<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="excanvas.js"></script><![endif]-->

wp_enqueue_script('jqplotpie', child_template_directory . '/self-service-poll/js/vendor/jqplot.pieRenderer.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('formhelper-number', child_template_directory . '/self-service-poll/js/vendor/bootstrap-formhelpers-number.js', array('jquery'), '1.0', true);



wp_enqueue_script( 'jquery-ui-sortable' );

// if using a custom function, you need this
//global $wpdb


// insert custom arrangment of the post-add-edit form boxes
// for every single user upon registered
function set_user_metaboxes($user_id) {

    // order
    // $meta_key = 'meta-box-order_post';
    // $meta_value = array(
    //     'side' => 'submitdiv,formatdiv,categorydiv,postimagediv',
    //     'normal' => 'postexcerpt,trackbacksdiv,tagsdiv-post_tag,postcustom,commentstatusdiv,commentsdiv,slugdiv,authordiv,revisionsdiv',
    //     'advanced' => '',
    // );
    // update_user_meta( $user_id, $meta_key, $meta_value );

    // hiddens
    $meta_key = 'metaboxhidden_poll';
    $meta_value = array('wpseo_meta', 'sharing_meta');
    update_user_meta( $user_id, $meta_key, $meta_value );

}
add_action('user_register', 'set_user_metaboxes');

function posts_for_current_author($query) {
	global $user_level;

  //TODO Check if it should be more that 5 //http://wordpress.org/support/topic/show-only-authors-posts-in-admin-panel-instead-of-all-posts
	if($query->is_admin && $user_level < 5) {
		global $user_ID;
		$query->set('author',  $user_ID);
		unset($user_ID);
	}
	unset($user_level);

	return $query;
}
add_filter('pre_get_posts', 'posts_for_current_author');

//http://wordpress.stackexchange.com/questions/3578/change-the-text-on-the-publish-button
add_action( 'admin_print_footer_scripts', 'remove_save_button' );
function remove_save_button()
{   
?>
<script>
if ( jQuery('body').hasClass('post-type-poll') ) {
  jQuery(document).ready(function($){$('#publish').val("Create Poll");});
}
</script><?php
}

function my_columns_filter( $columns ) {
   unset($columns['wpseo-score']);
   unset($columns['wpseo-title']);
   unset($columns['wpseo-metadesc']);
   unset($columns['wpseo-focuskw']);
   return $columns;
}

// Custom Post Type
add_filter( 'manage_edit-poll_columns', 'my_columns_filter',10, 1 );

function redirect_to_front_page() {
global $redirect_to;
  // if (!isset($_GET['redirect_to'])) {
  //   $redirect_to = get_option('siteurl');
  // }
  
  $redirect_to = get_permalink( get_page_by_path( 'list-polls' ) );
}
add_action('login_form', 'redirect_to_front_page');

// Only admins see admin bar
if ( ! current_user_can( 'manage_options' ) ) {
    show_admin_bar( false );
}


