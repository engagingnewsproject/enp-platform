<?php

/*
Plugin Name: ENP Registration
Plugin URI: https://engagingnewsproject.org
Description: Disables auto-generated registration password, allows users to create own password on registration page, and customizes new registration email.
Author: Jeremy Jones (Based on a Burak Aydin's https://wordpress.org/plugins/custom-registration/)
Version: 1.0
License: GPLv2 or later
*/


// Disable new user notification and auto-generated password emails.
if(!function_exists('wp_new_user_notification')){

	/**
	 * From pluggable.php, an overrideable function in WordPress Core
	 * Email login credentials to a newly-registered user.
	 *
	 */
	function wp_new_user_notification( $user_id ) {
		global $wpdb, $wp_hasher;
		$user = get_userdata( $user_id );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$message = __('Thanks for signing up at the Engaging News Project! To create your first quiz, go to: ') . site_url('quiz-creator') . "\r\n\r\n";
		$message .= __('This tool will let you create quizzes that you can embed on your website using a simple iframe code. The quizzes can be created for any kind of information to increase your engagement, such as:'). "\r\n\r\n";

		$message .= __('- Poll results (What percentage of the public has a smart phone?)'). "\r\n";
		$message .= __('- Health data (How many Americans have asthma?)'). "\r\n";
		$message .= __('- Government information (How much has the budget for Social Security increased this past year?)'). "\r\n";
		$message .= __('- Crime statistics (How many burglaries were reported in New York last month?)'). "\r\n";
		$message .= __('- Public actions (How many people voted in the election last night?)'). "\r\n\r\n";
		$message .= __('These quizzes have been tested by the Engaging News Project. Our research shows that these quizzes help people learn more than presenting information without a quiz. Even more, they increase time on page and site visitors rate them as enjoyable. We also provide data on how frequently the feature is used and how people responded.'). "\r\n\r\n";
		$message .= __('If you have questions or would like to provide feedback on the Quiz Creator, please email us at katie.steiner@austin.utexas.edu.'). "\r\n\r\n";

		$message .= __('Best,') . "\r\n";
		$message .= __('The Engaging News Project Team') . "\r\n";
		$message .= site_url() . "\r\n";

		wp_mail($user->user_email, __('Welcome to the Engaging News Project!'), $message);
	}

}


// Adding new element for register form
function enp_register_form(){

		$user_pass=(!empty($_POST['user_pass'])) ? sanitize_text_field($_POST['user_pass']) : '';

		$confirm_pass=(!empty($_POST['confirm_pass'])) ? sanitize_text_field($_POST['confirm_pass']) : '';

	?>

	<p>
		<label for="user_pass">Password</label>
		<input type="password" class="input" name="user_pass" value="<?php echo esc_attr($user_pass); ?>">
	</p>

	<p>
		<label for="confirm_pass">Confirm Password</label>
		<input type="password" class="input" name="confirm_pass" value="<?php echo esc_attr($confirm_pass); ?>">
	</p>

<?php }

add_action('register_form','enp_register_form');



// Adding validation
function enp_registration_errors($error){

	if(empty($_POST['user_pass'])){
		$error->add('user_pass_error','<strong>ERROR:</strong> You should fill out the password field.');
	}

	if(empty($_POST['confirm_pass'])){
		$error->add('confirm_pass_error','<strong>ERROR:</strong> You should fill out the confirm password field.');
	}

	if($_POST['user_pass'] !== $_POST['confirm_pass']){
		$error->add('confirm_pass_error','<strong>ERROR:</strong> The passwords you entered don\'t match. Make sure each password field matches.');
	}

	return $error;

}

add_action('registration_errors','enp_registration_errors');



// Saving user password and log them in
function enp_saving_password($user_id){

	// update the user with their new password
	wp_update_user(array(
			'ID' 		=> $user_id,
			'user_pass' => $_POST['user_pass']
		));

	// Set the global user object
	$current_user = get_user_by( 'id', $user_id );

	// set the WP login cookie
	$secure_cookie = is_ssl() ? true : false;
	wp_set_auth_cookie( $user_id, true, $secure_cookie );

}
add_action('user_register','enp_saving_password');


// disable "Notice of Password Change" email. It's not very helpful anyways, and it
// gets triggered when we update the user's password immediately after they're created in enp_saving_password()
add_filter('send_password_change_email', '__return_false');
