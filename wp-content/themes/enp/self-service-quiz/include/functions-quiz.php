<?php
// include("include/quiz-shortcodes.php");

define('child_template_directory', get_stylesheet_directory_uri() );

function enqueue_self_service_quiz_scripts () {


  require_once(TEMPLATEPATH."/self-service-quiz/include/quiz-shortcodes.php");

  // BUILT WITH LESS, so add bootstrap to a wrapper to apply styles
  wp_enqueue_style( 'main-css', child_template_directory . '/self-service-quiz/css/main.css');
  wp_enqueue_style( 'bootstrap', child_template_directory . '/self-service-quiz/css/bootstrap-prefix.css');
  wp_enqueue_style( 'slider', child_template_directory . '/self-service-quiz/css/slider.css');
  wp_enqueue_script('quiz-custom', child_template_directory . '/self-service-quiz/js/quiz-custom.js', array('jquery'), '1.0', true);
  //wp_enqueue_script('bootstrap-js', child_template_directory . '/self-service-quiz/js/vendor/bootstrap.min.js', array('jquery'), '1.0', true);
  wp_enqueue_script('validate', child_template_directory . '/self-service-quiz/js/vendor/jquery.validate.min.js', array('jquery'), '1.0', true);
  wp_enqueue_script('slider', child_template_directory . '/self-service-quiz/js/vendor/bootstrap-slider.js', array('jquery'), '1.0', true);

  // scripts we don't need for the iframe
  if(!is_page_template('self-service-quiz/page-iframe-quiz.php' )) {
    // jqplot scripts
    wp_enqueue_style( 'jqplot', child_template_directory . '/self-service-quiz/css/jquery.jqplot.min.css');
    wp_enqueue_script('jqplot', child_template_directory . '/self-service-quiz/js/vendor/jquery.jqplot.min.js', array('jquery'), '1.0', true);
    wp_enqueue_script('excanvas', child_template_directory . '/self-service-quiz/js/vendor/excanvas.min.js', array('jquery'), '1.0', true);
    //<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="excanvas.js"></script><![endif]-->
    wp_enqueue_script('jqplotpie', child_template_directory . '/self-service-quiz/js/vendor/jqplot.pieRenderer.min.js', array('jquery'), '1.0', true);

    wp_enqueue_script('formhelper-number', child_template_directory . '/self-service-quiz/js/vendor/bootstrap-formhelpers-number.js', array('jquery'), '1.0', true);
    wp_enqueue_script( 'jquery-ui-sortable' );

  }

  wp_enqueue_script('placeholder', child_template_directory . '/self-service-quiz/js/vendor/jquery.placeholder.js', array('jquery'), '1.0', true);

  wp_enqueue_script('jquery-ui-touch-punch' , child_template_directory . '/self-service-quiz/js/vendor/jquery.ui.touch-punch.js', Array('jquery'), '', true);

}
add_action('wp_enqueue_scripts', 'enqueue_self_service_quiz_scripts');

//add_action('wp_print_scripts','include_jquery_form_plugin');
function include_jquery_form_plugin(){
    if (is_page('configure-quiz')){ // only add this on the page that allows the upload
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-form',array('jquery'),false,true );
    }
}
function add_media_upload_scripts() {
    if ( is_admin() ) {
         return;
       }
    wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'add_media_upload_scripts');

function iframe_quiz_hide_admin_bar () {
  global $post;
  if( is_page('iframe-quiz') ) {
    show_admin_bar( false );
  }
}
add_action( 'wp', 'iframe_quiz_hide_admin_bar' );

//add_action('get_template_part_self-service-quiz/quiz-form','enqueue_admin_self_service_quiz_scripts');

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

$generate_split_test = array(
  // 'page_template' => 'self-service-quiz/page-create-a-quiz.php',
  // 'page_template' => 'template-full-width.php',
  'post_type'     => 'page',
  'post_title'    => 'Generate A/B Test Code',
  'post_content'  => '[generate-split-test]',
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

if( !get_page_by_title('Generate A/B Test Code')) {
  wp_insert_post($generate_split_test);
}


// if using a custom function, you need this
//global $wpdb

/* enter the full name you want displayed alongside the email address */
/* from http://miloguide.com/filter-hooks/wp_mail_from_name/ */
function enp_filter_wp_mail_from_name($from_name){
    return "Engaging News Project";
}
add_filter("wp_mail_from_name", "enp_filter_wp_mail_from_name");

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
	add_options_page('Global Custom Fields', 'Global Custom Fields', 'edit_pages', 'functions', 'editglobalcustomfields');
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
      <div class="social-login-custom">' . oa_social_login_html() . '</div>
    </div>';

	return  $login_html;
}

function hioweb_add_shortcodes() {
	add_shortcode( 'display-login-form', 'display_login_form_shortcode' );
}

add_action( 'init', 'hioweb_add_shortcodes' );

add_filter( 'wp_mail_from_name', 'custom_wp_mail_from_name' );
function custom_wp_mail_from_name( $original_email_from )
{
	return 'Engaging News Project';
}

add_filter( 'wp_mail_from', 'custom_wp_mail_from' );
function custom_wp_mail_from( $original_email_address )
{
	//Make sure the email is from the same domain
	//as your website to avoid being marked as spam.
  // return 'donotreply@engagingnewsproject.org';
	return 'donotreply@engagingnewsproject.org';

}

function get_user_ip() {
  //Just get the headers if we can or else use the SERVER global
  if ( function_exists( 'apache_request_headers' ) ) {
    $headers = apache_request_headers();
  } else {
    $headers = $_SERVER;
  }
  //Get the forwarded IP if it exists
  if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
    $the_ip = $headers['X-Forwarded-For'];
  } elseif ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )
  ) {
    $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
  } else {

    $the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
  }
  if(!empty($the_ip)){
    return $the_ip;
  } else {
    return false;
  }

}

function add_capability() {
    // gets the author role
    $role = get_role( 'administrator' );

    // This only works, because it accesses the class instance.
    $role->add_cap( 'read_all_quizzes' );

    //echo '<h1>ADDING CAP</h1>';
}
add_action( 'admin_init', 'add_capability');

function get_quiz_response ( $response_id ) {

  global $wpdb;

  $quiz_response = $wpdb->get_row(
    $wpdb->prepare(
      "SELECT * FROM enp_quiz_responses WHERE ID = %d",
      $response_id
    )
  );

  if( strpos($quiz_response->correct_option_value,' to ') !== false ) {
    $answer_array = explode(' to ', $quiz_response->correct_option_value);
    $quiz_response->correct_value = $answer_array[0];
  } else {
    $quiz_response->correct_value = $quiz_response->correct_option_value;
  }

  return $quiz_response;

}

function render_answer_response_message ( $quiz_type, $q_response, $q_options ) {

  /* $vars = array(
    '[user_answer]',
    '[correct_value]',
    '[slider_label]',
    '[lower_range]',
    '[upper_range]',
  ); */

  // determine which message template
  if( $q_response->is_correct )
    $msg = $q_options->correct_answer_message;
  else
    $msg = $q_options->incorrect_answer_message;

  // replace message variables

  $user_answer = render_label( $q_response->quiz_option_value, $q_options->slider_label );
  $correct_value = render_label( $q_response->correct_value, $q_options->slider_label );

  $msg = str_replace(
    '[user_answer]',
    $user_answer,
    $msg
  );

  $msg = str_replace(
    '[correct_value]',
    $correct_value,
    $msg
  );

  // '[slider_label]' template variable is deprecated, so remove vestiges
  $msg = remove_label_variable( $msg );

  // slider ranges
  $msg = str_replace('[lower_range]', $q_options->slider_low_answer, $msg);
  $msg = str_replace('[upper_range]', $q_options->slider_high_answer, $msg);

  return $msg;

}

function render_label ( $value = '', $label = '' ) {
  if( strpos($label,'{%V%}') !== false )
    return str_replace('{%V%}', $value, $label);
  if( strpos($label,'%') !== false )
    return $value . $label;
  if( !empty($label) )
    return $value . ' ' . $label;
  return $value;
}

// [slider_label] template variable is deprecated. This function removes the label variable
function remove_label_variable ( $msg = '' ) {
  $msg = str_replace(' [slider_label]', '', $msg);
  $msg = str_replace('[slider_label]', '', $msg);
  return $msg;
}

function get_quiz_option ( $quiz_id, $option ) {
  global $wpdb;
  return $wpdb->get_var(
    $wpdb->prepare("
        SELECT value FROM enp_quiz_options
        WHERE field = %s AND quiz_id = %d LIMIT 1", $option, $quiz_id
    )
  );
}

add_action('init', 'allow_subscriber_uploads');
function allow_subscriber_uploads() {
    $subscriber = get_role('subscriber');
    $subscriber->add_cap('upload_files');
}

//add_action('init', 'no_mo_dashboard');
function no_mo_dashboard() {
  if (!current_user_can('manage_options') && $_SERVER['DOING_AJAX'] != '/wp-admin/admin-ajax.php') {
  wp_redirect(home_url()); exit;
  }
}

// get custom quiz styles
function get_quiz_styles($quiz_style_ID) {
  global $wpdb;

  $quiz_background_color = $wpdb->get_var( "
      SELECT value FROM enp_quiz_options
      WHERE field = 'quiz_background_color' AND quiz_id = " . $quiz_style_ID );

  $quiz_text_color = $wpdb->get_var( "
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_text_color' AND quiz_id = " . $quiz_style_ID );

  $quiz_display_width = $wpdb->get_var( "
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_width' AND quiz_id = " . $quiz_style_ID );

  $quiz_display_height = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_height' AND quiz_id = " . $quiz_style_ID);

  $quiz_display_padding = $wpdb->get_var( "
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_padding' AND quiz_id = " . $quiz_style_ID );

  $quiz_display_css = $wpdb->get_var("
    SELECT value FROM enp_quiz_options
    WHERE field = 'quiz_display_css' AND quiz_id = " . $quiz_style_ID);

  // Compile quiz styles
  $quiz_styles = 'box-sizing: border-box;
                  background: '.$quiz_background_color.';
                  color: '.$quiz_text_color.';
                  width: '.$quiz_display_width.';
                  height: '.$quiz_display_height.';
                  padding: 10px 0;';
  // append custom styles
  $quiz_styles .= (!empty($quiz_display_css) ? $quiz_display_css : '');

  return $quiz_styles;
}


// Remove admin bar for logged in users if its a quiz iframe template
function remove_iframe_admin_bar(){
  if(is_user_logged_in()) {
    // check if we're displaying an iframe template
    if(is_page_template( 'self-service-quiz/page-quiz-answer.php' ) || is_page_template( 'self-service-quiz/page-iframe-quiz.php' )) {
      return false;
    } else {
      // logged in and no iframe template, so show the admin bar
      return true;
    }
  }
}
add_filter( 'show_admin_bar' , 'remove_iframe_admin_bar');


// get the parent question row by guid
function get_quiz_parent_by_guid($guid) {
  global $wpdb;
  $quiz_id = $wpdb->get_var(
    $wpdb->prepare(
      "SELECT ID FROM enp_quiz
      WHERE guid = '%s' LIMIT 1",
      $guid
    )
  );

  return get_quiz_parent_by_id($quiz_id);
}

// return the parent question row from enp_quiz by quiz id
function get_quiz_parent_by_id($quiz_id) {
  global $wpdb;
  // get the parent_guid from enp_quiz_next
  $quiz_parent_guid = $wpdb->get_var(
    $wpdb->prepare(
      "SELECT parent_guid FROM enp_quiz_next
      WHERE curr_quiz_id = '%s' LIMIT 1",
      $quiz_id
    )
  );

  // query the row that matches the guid on the parent guid
  $quiz_parent = $wpdb->get_row(
    $wpdb->prepare(
      "SELECT * FROM enp_quiz
      WHERE guid = '%s' LIMIT 1",
      $quiz_parent_guid
    )
  );

  return $quiz_parent;
}

// return an array of all quiz questions for looping through
function get_all_quiz_questions($parent_guid) {
  global $wpdb;

  // setup next_q_id as the parent ID to start the loop
  $next_q_id = $wpdb->get_var(
    $wpdb->prepare(
      "SELECT ID FROM enp_quiz
      WHERE guid = '%s' LIMIT 1",
      $parent_guid
    )
  );


  while($next_q_id != 0) {

    // get the quiz where curr_quiz_id (or, if first one, it'll be the id of our parent)
    $quiz = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT * FROM enp_quiz
        WHERE ID = '%s' LIMIT 1",
        $next_q_id
      )
    );

    // check if it's active
    $is_q_active = is_quiz_active($quiz->ID);
    if($is_q_active === true) {
      //push to array
      $all_active_questions[] = $quiz;
    }

    // setup next id to get
    $next_q_id = $wpdb->get_var(
      $wpdb->prepare(
        "SELECT next_quiz_id FROM enp_quiz_next
        WHERE curr_quiz_id = '%s' LIMIT 1",
        $quiz->ID
      )
    );
  }

  return $all_active_questions;
}


// check if a question is active
function is_quiz_active($quiz_id) {
  global $wpdb;
  $q_active_SQL = $wpdb->prepare("SELECT active FROM enp_quiz WHERE ID= '%s' LIMIT 1", $quiz_id);
  $q_active = $wpdb->get_var($q_active_SQL);

  $active = false;
  if($q_active == 1) {
    $active = true;
  }

  return $active;
}


// build dropdown of all quizzes with guid values
function quiz_display_width($quiz) {
  global $wpdb;
  $quiz_display_width = $wpdb->get_var("
    SELECT `value` FROM enp_quiz_options
    WHERE field = 'quiz_display_width' AND quiz_id = " . $quiz->ID);

  return $quiz_display_width;
}

function quiz_display_height($quiz) {
  global $wpdb;
  $quiz_display_height = $wpdb->get_var("
    SELECT `value` FROM enp_quiz_options
    WHERE field = 'quiz_display_height' AND quiz_id = " . $quiz->ID);

  return $quiz_display_height;
}


?>
