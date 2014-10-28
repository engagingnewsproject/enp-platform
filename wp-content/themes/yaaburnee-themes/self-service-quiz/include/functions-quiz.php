<?php
// include("include/quiz-shortcodes.php");
require_once(TEMPLATEPATH."/self-service-quiz/include/quiz-shortcodes.php");

define('child_template_directory', get_stylesheet_directory_uri() );

// BUILT WITH LESS, so add bootstrap to a wrapper to apply styles
wp_enqueue_style( 'main-css', child_template_directory . '/self-service-quiz/css/main.css');
wp_enqueue_style( 'bootstrap', child_template_directory . '/self-service-quiz/css/bootstrap-prefix.css');
wp_enqueue_style( 'slider', child_template_directory . '/self-service-quiz/css/slider.css');
wp_enqueue_style( 'jqplot', child_template_directory . '/self-service-quiz/css/jquery.jqplot.min.css');
wp_enqueue_script('quiz-custom', child_template_directory . '/self-service-quiz/js/quiz-custom.js', array('jquery'), '1.0', true);
wp_enqueue_script('bootstrap-js', child_template_directory . '/self-service-quiz/js/vendor/bootstrap.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('validate', child_template_directory . '/self-service-quiz/js/vendor/jquery.validate.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('slider', child_template_directory . '/self-service-quiz/js/vendor/bootstrap-slider.js', array('jquery'), '1.0', true);
wp_enqueue_script('jqplot', child_template_directory . '/self-service-quiz/js/vendor/jquery.jqplot.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('excanvas', child_template_directory . '/self-service-quiz/js/vendor/excanvas.min.js', array('jquery'), '1.0', true);
//<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="excanvas.js"></script><![endif]-->

wp_enqueue_script('jqplotpie', child_template_directory . '/self-service-quiz/js/vendor/jqplot.pieRenderer.min.js', array('jquery'), '1.0', true);
wp_enqueue_script('formhelper-number', child_template_directory . '/self-service-quiz/js/vendor/bootstrap-formhelpers-number.js', array('jquery'), '1.0', true);
wp_enqueue_script('placeholder', child_template_directory . '/self-service-quiz/js/vendor/jquery.placeholder.js', array('jquery'), '1.0', true);

wp_enqueue_script( 'jquery-ui-sortable' );
wp_enqueue_script('jquery-ui-touch-punch' , child_template_directory . '/self-service-quiz/js/vendor/jquery.ui.touch-punch.js', Array('jquery'), '', true);


global $wpdb;
$sql_enp_quiz = "CREATE TABLE `enp_quiz` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `guid` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `quiz_type` varchar(25) NOT NULL,
  `question` varchar(255) NOT NULL,
  `create_datetime` datetime NOT NULL,
  `last_modified_datetime` datetime NOT NULL,
  `last_modified_user_id` bigint(20) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `locked` tinyint(4) NOT NULL,
  PRIMARY KEY (`ID`)
);";

$sql_enp_quiz_next = "CREATE TABLE `enp_quiz_next` (
  `enp_quiz_next` bigint(20) NOT NULL AUTO_INCREMENT,
  `curr_quiz_id` bigint(20) NOT NULL,
  `next_quiz_id` bigint(20) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`enp_quiz_next`)
);"; // ||KVB

$sql_enp_quiz_options = "CREATE TABLE `enp_quiz_options` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint(20) NOT NULL,
  `field` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `create_datetime` datetime NOT NULL,
  `display_order` int(10) NOT NULL,
  PRIMARY KEY (`ID`)
);";



$sql_enp_quiz_responses = "CREATE TABLE `enp_quiz_responses` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint(20) NOT NULL,
  `quiz_option_id` bigint(20) NOT NULL,
  `quiz_option_value` varchar(255) NOT NULL,
  `correct_option_id` bigint(20) NOT NULL,
  `correct_option_value` varchar(255) NOT NULL,
  `is_correct` tinyint(4) NOT NULL,
  `ip_address` varchar(50) NOT NULL,
  `response_datetime` datetime NOT NULL,
  `preview_response` tinyint(4) NOT NULL,
  PRIMARY KEY (`ID`)
);";

// require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
// dbDelta( $sql_enp_quiz );
// require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
// dbDelta( $sql_enp_quiz_options );
// require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
// dbDelta( $sql_enp_quiz_responses );

$configure_quiz = array(
  // 'page_template' => 'self-service-quiz/page-configure-quiz.php',
  // 'page_template' => 'template-full-width.php',
  'post_type'     => 'page',
  'post_title'    => 'Configure Quiz',
  'post_content'  => '[configure_quiz]',
  'post_status'   => 'publish',
  'post_author'   => 4
);

$quiz_report = array(
  // 'page_template' => 'self-service-quiz/page-quiz-view.php',
  // 'page_template' => 'template-full-width.php',
  'post_type'     => 'page',
  'post_title'    => 'Quiz Report',
  'post_content'  => '[quiz_report]',
  'post_status'   => 'publish',
  'post_author'   => 4
);

$quiz_answer = array(
  'page_template' => 'self-service-quiz/page-quiz-answer.php',
  // 'page_template' => 'template-full-width.php',
  'post_type'     => 'page',
  'post_title'    => 'Quiz Answer',
  'post_content'  => '',
  'post_status'   => 'publish',
  'post_author'   => 4
);

$iframe_quiz = array(
  'page_template' => 'self-service-quiz/page-iframe-quiz.php',
  // 'page_template' => 'template-full-width.php',
  'post_type'     => 'page',
  'post_title'    => 'iframe quiz',
  'post_content'  => '',
  'post_status'   => 'publish',
  'post_author'   => 4
);

$create_a_quiz = array(
  // 'page_template' => 'self-service-quiz/page-create-a-quiz.php',
  // 'page_template' => 'template-full-width.php',
  'post_type'     => 'page',
  'post_title'    => 'Create a Quizz',
  'post_content'  => '[create-a-quiz]',
  'post_status'   => 'publish',
  'post_author'   => 4
);

$view_quiz = array(
  // 'page_template' => 'self-service-quiz/page-quiz-view.php',
  // 'page_template' => 'template-full-width.php',
  'post_type'     => 'page',
  'post_title'    => 'View Quiz',
  'post_content'  => '[view_quiz]',
  'post_status'   => 'publish',
  'post_author'   => 4
);

if( !get_page_by_title('Configure Quiz') ) {
  wp_insert_post( $configure_quiz );
  wp_insert_post( $quiz_report );
  wp_insert_post( $quiz_answer );
  wp_insert_post( $iframe_quiz );
  wp_insert_post( $create_a_quiz );
  wp_insert_post( $view_quiz );
}


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
  
  $redirect_to = get_permalink( get_page_by_path( 'create-a-quiz' ) );
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
    $terms_conditions_url = 'http://' . $_SERVER['SERVER_NAME'] . '/terms-and-conditions';
    ?>
    <p>Please note that this software is a free service and should be taken as it comes.  Thanks!</p>
    <br>
    <input type="checkbox" name="login_accept" id="login_accept" />I agree to the <a href="<?php echo $terms_conditions_url; ?>" target="_blank">terms and conditions</a>.
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

// JS hack to require Terms and Conditions acceptance on OA Social login usage

function enp_require_tac_script () {
  ?>
  <script>
  
  jQuery('.oneall_social_login').on('click', function() {
    // if checkbox is selected
    if( jQuery('#login_accept').is(':checked') ) {
      jQuery(this).addClass('active');
      jQuery('#login_error').hide();
    } else {
    // else
      jQuery(this).removeClass('active');
      var log_err = jQuery('#login_error');
      if( log_err.length !== 0 ) {
        log_err.show();
      } else {
        var html = '<div id="login_error"><strong>ERROR</strong>: Terms and conditions must be accepted to proceed.</div>';
        jQuery(html).insertAfter('p.message.register');
      }
    }
    
  });

  jQuery('#login_accept').on('click', function() {
    jQuery('.oneall_social_login').trigger('click');
  });
  //console.log('enp_require_tac_script!');

  </script>
  <?php
}

add_action('register_form', 'enp_require_tac_script');


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

function oa_social_login_html() {
  ob_start();
  do_action('oa_social_login');
  $social_login_html = ob_get_contents();
  ob_end_clean();
  return $social_login_html;
}

function display_login_form_shortcode() {
	if ( is_user_logged_in() )
		return '';
  
  //$social_login_html = oa_social_login_html();
  
  $login_html  = 
  '<div class="enp-login bootstrap">
    <h2 class="widget_title">Log In</h2>
    <p><b>Please Login or <a href="' . get_site_url() . '/wp-login.php?action=register">Register</a> to Create your Quiz!</b></p>
    <div class="members-login-form">
  	  <form name="loginform" id="loginform" action="' . get_site_url() . '/wp-login.php" method="post">
			
  			<p class="login-username">
  				<label for="user_login">Username</label>
  				<input type="text" name="log" id="user_login" class="form-control" value="">
  			</p>
  			<p class="login-password">
  				<label for="user_pass">Password</label>
  				<input type="password" name="pwd" id="user_pass" class="form-control" value="">
  			</p>
			
  			<p class="login-remember"><label><input name="rememberme" type="checkbox" id="wp-submit" value="forever"> Remember Me</label></p>
  			<p class="login-submit">
  				<input type="submit" name="wp-submit" id="1" class="btn btn-primary form-control" value="Login Now">
  				<input type="hidden" name="redirect_to" value="' . get_site_url() . '/create-a-quiz/">
  			</p>
			
  		  </form>
      </div>
      <!-- <div class="social-login-custom">' . $social_login_html . '</div> -->
    </div>';

	return  $login_html;
}

function hioweb_add_shortcodes() {
	add_shortcode( 'display-login-form', 'display_login_form_shortcode' );
}

add_action( 'init', 'hioweb_add_shortcodes' );
