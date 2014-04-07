<?php
define('child_template_directory', get_stylesheet_directory_uri() );

// BUILT WITH LESS, so add bootstrap to a wrapper to apply styles
wp_enqueue_style( 'main-css', child_template_directory . '/self-service-quiz/css/main.css');
wp_enqueue_style( 'bootstrap', child_template_directory . '/self-service-quiz/css/bootstrap-prefix.css');
wp_enqueue_style( 'slider', child_template_directory . '/self-service-quiz/css/slider.css');
wp_enqueue_style( 'jqplot', child_template_directory . '/self-service-quiz/css/jquery.jqplot.min.css');
// wp_enqueue_style( 'bootstrap', child_template_directory . '/self-service-quiz/css/main.less');
//wp_enqueue_style( 'bootstrap', child_template_directory . '/self-service-quiz/css/bootstrap.min.css');

// wp_enqueue_style( 'bootstrap-theme', child_template_directory . '/self-service-quiz/css/bootstrap-theme.min.css');

wp_enqueue_script('quiz-custom', child_template_directory . '/self-service-quiz/js/quiz-custom.js', array('jquery'), '1.0', true);
wp_enqueue_script('bootstrap-js', child_template_directory . '/self-service-quiz/js/vendor/bootstrap.min.js', array('jquery'), '1.0', true);
// wp_enqueue_script('less', child_template_directory . '/self-service-quiz/js/vendor/less-1.5.1.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('validate', child_template_directory . '/self-service-quiz/js/vendor/jquery.validate.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('slider', child_template_directory . '/self-service-quiz/js/vendor/bootstrap-slider.js', array('jquery'), '1.0', true);
wp_enqueue_script('jqplot', child_template_directory . '/self-service-quiz/js/vendor/jquery.jqplot.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('excanvas', child_template_directory . '/self-service-quiz/js/vendor/excanvas.min.js', array('jquery'), '1.0', true);
//<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="excanvas.js"></script><![endif]-->

wp_enqueue_script('jqplotpie', child_template_directory . '/self-service-quiz/js/vendor/jqplot.pieRenderer.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('formhelper-number', child_template_directory . '/self-service-quiz/js/vendor/bootstrap-formhelpers-number.js', array('jquery'), '1.0', true);
wp_enqueue_script('placeholder', child_template_directory . '/self-service-quiz/js/vendor/jquery.placeholder.js', array('jquery'), '1.0', true);



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
    $meta_key = 'metaboxhidden_quiz';
    $meta_value = array('wpseo_meta', 'sharing_meta');
    update_user_meta( $user_id, $meta_key, $meta_value );

}
add_action('user_register', 'set_user_metaboxes');

function posts_for_current_author($query) {
	global $user_level;

    // Editor roles equates to levels 5 through 7, so anything lower then 5 is lower then an editor role... 
    //http://codex.wordpress.org/Roles_and_Capabilities#User_Level_to_Role_Conversion
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
if ( jQuery('body').hasClass('post-type-quiz') ) {
  jQuery(document).ready(function($){$('#publish').val("Create Quiz");});
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
add_filter( 'manage_edit-quiz_columns', 'my_columns_filter',10, 1 );

function redirect_to_front_page() {
global $redirect_to;
  // if (!isset($_GET['redirect_to'])) {
  //   $redirect_to = get_option('siteurl');
  // }
  
  $redirect_to = get_permalink( get_page_by_path( 'list-quizzes' ) );
}
add_action('login_form', 'redirect_to_front_page');

// Only admins see admin bar
if ( ! current_user_can( 'manage_options' ) ) {
    show_admin_bar( false );
}

// Add text to the registration page
// https://codex.wordpress.org/Customizing_the_Registration_Form
add_action('register_form','myplugin_register_form');
function myplugin_register_form (){
    $first_name = ( isset( $_POST['first_name'] ) ) ? $_POST['first_name']: '';
    ?>
    <p>Please note that this software is a free service and should be taken as is comes.  Thanks!</p>
    <br>
    <input type="checkbox" name="login_accept" id="login_accept" />I agree to the <a href="<?php echo get_site_url(); ?>/terms-conditions/" target="_blank">terms and conditions</a>.
    <br><br>
    <?php
}

function myplugin_check_fields($errors, $sanitized_user_login, $user_email) {

  // See if the checkbox #login_accept was checked
  if ( isset( $_REQUEST['login_accept'] ) && $_REQUEST['login_accept'] == 'on' ) {
      // Checkbox on, allow login
      // return $user;
  } else {
      // Did NOT check the box, do not allow login
      $errors->add( 'login_accept', __('<strong>ERROR</strong>: Terms and conditions must be accepted to proceed.', get_site_url()) );
  }

    return $errors;

}

add_filter('registration_errors', 'myplugin_check_fields', 10, 3);


//Custom Theme Settings
add_action('admin_menu', 'add_gcf_interface');

function add_gcf_interface() {
	add_options_page('Global Custom Fields', 'Global Custom Fields', '8', 'functions', 'editglobalcustomfields');
}

function editglobalcustomfields() {
	?>
	<div class='wrap'>
	<h2>Global Custom Fields</h2>
	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options') ?>

	<p><strong>Multiple Choice Correct Answer Message</strong><br />
	<textarea class="form-control" rows="4" cols="50" name="mc_correct_answer_message" id="mc-correct-answer-message" placeholder="Enter Correct Answer Message"><?php echo get_option('mc_correct_answer_message') ? get_option('mc_correct_answer_message') : "Your answer of [user_answer] is correct!"; ?></textarea></p>
  
	<p><strong>Multiple Choice Incorrect Answer Message</strong><br />
	<textarea class="form-control" rows="4" cols="50" name="mc_incorrect_answer_message" id="mc-correct-answer-message" placeholder="Enter Correct Answer Message"><?php echo get_option('mc_incorrect_answer_message') ? get_option('mc_incorrect_answer_message') : "Your answer is [user_answer], but the correct answer is [correct_value]."; ?></textarea></p>
  
	<p><strong>Slider Correct Answer Message</strong><br />
	<textarea class="form-control" rows="4" cols="50" name="slider_correct_answer_message" id="slider-correct-answer-message" placeholder="Enter Correct Answer Message"><?php echo get_option('slider_correct_answer_message') ? get_option('slider_correct_answer_message') : "Your answer of [user_answer] is correct!"; ?></textarea></p>
  
	<p><strong>Slider Incorrect Answer Message</strong><br />
	<textarea class="form-control" rows="4" cols="50" name="slider_incorrect_answer_message" id="slider-correct-answer-message" placeholder="Enter Correct Answer Message"><?php echo get_option('slider_incorrect_answer_message') ? get_option('slider_incorrect_answer_message') : "Your answer is [user_answer], but the correct answer is [correct_value]."; ?></textarea></p>
  
	<p><strong>Slider Range Correct Answer Message</strong><br />
	<textarea class="form-control" rows="4" cols="50" name="slider_range_correct_answer_message" id="slider-range-correct-answer-message" placeholder="Enter Correct Answer Message"><?php echo get_option('slider_range_correct_answer_message') ? get_option('slider_range_correct_answer_message') : "Your answer of [user_answer] is correct!"; ?></textarea></p>
  
	<p><strong>Slider Range Incorrect Answer Message</strong><br />
	<textarea class="form-control" rows="4" cols="50" name="slider_range_incorrect_answer_message" id="slider-range-correct-answer-message" placeholder="Enter Correct Answer Message"><?php echo get_option('slider_range_incorrect_answer_message') ? get_option('slider_range_incorrect_answer_message') : "Your answer is [user_answer], but the correct answer is [correct_value]."; ?></textarea></p>

	<p><input type="submit" name="Submit" value="Update Options" /></p>

	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="mc_correct_answer_message,mc_incorrect_answer_message,slider_correct_answer_message,slider_incorrect_answer_message,slider_range_correct_answer_message,slider_range_incorrect_answer_message" />

	</form>
	</div>
	<?php
}
