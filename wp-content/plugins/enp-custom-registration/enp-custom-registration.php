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
     * It doesn't get fired on Social Login, so we're totally removing
     * it and replacing it with our own email function
     *
     */
    function wp_new_user_notification( $user_id ) {

    }

}

// basd on wp_new_user_notification, but hooked into user_register
// so it fires even on social login registration or manual user create
function enp_send_welcome_email( $user_id ) {

    $user = get_userdata( $user_id );

    $message = enp_welcome_email_text();

    wp_mail($user->user_email, __('Thanks for Signing Up for the Center for Media Engagement Quiz Creator'), $message);
}


// decide if we should send the welcome email or not
function enp_welcome_email($user_id) {
    // see if the welcome email has already been sent by checking
    // for registration_email_sent, which gets added the first time
    // user_register gets run. The only reason we're checking for this is
    // that Social Login plugin calls do_action('user_register') and sends
    // this email again, and we only want people to get the email once
    $is_sent = get_user_meta($user_id, 'registration_email_sent', true);
    if($is_sent === '1') {
        // already sent. abort!
        return;
    }

    // send the email
    enp_send_welcome_email($user_id);

    // update the user_meta
    add_user_meta($user_id, 'registration_email_sent', '1', true);
}
add_action('user_register','enp_welcome_email');

// text for the welcome email
function enp_welcome_email_text() {

    $message = __('To create your first quiz, visit the quiz creator landing page on our website: https://mediaengagement.org/quiz-creator/') . "\r\n\r\n";

    $message .= __('Why Should You Add Quizzes To Your Site?') . "\r\n\r\n";

    $message .= "\t" . __('• Research shows that quizzes make sites more enjoyable — and they help people learn: https://mediaengagement.org/research/online-polls-and-quizzes/') . "\r\n";
    $message .= "\t" . __('• Research also shows that quizzes encourage people to spend more time on your site'). "\r\n";
    $message .= "\t" . __("• It's easy - our free quiz tool can help you create a quiz in minutes"). "\r\n\r\n";

    $message .= __('How Does It Work?') . "\r\n\r\n"; 

    $message .= __('Step 1: Create') . "\r\n"; 
    $message .= "\t" . __('• Name the quiz, add questions, and personalize answer responses') . "\r\n";
    $message .= __('Step 2: Customize') . "\r\n";
    $message .= "\t" . __('• Choose colors and display options') . "\r\n";
    $message .= __('Step 3: Publish') . "\r\n";
    $message .= "\t" . __('• Add to your site using the provided embed code') . "\r\n";
    $message .= __('Step 4: Analyze') . "\r\n";
    $message .= "\t" . __('• Apply A/B tests to identify which quizzes are most effective') . "\r\n\r\n";

    $message .= __('Tips for Effective Quizzes:'). "\r\n\r\n";

    $message .= "\t" . __('• Add more than one question using different quiz formats to increase engagement') . "\r\n";
    $message .= "\t" . __('• Present content that uses reliable public opinion estimates and factual, reputable data'). "\r\n";
    $message .= "\t" . __('• Be on the lookout for statistical and numerical data that can be used to create quizzes'). "\r\n\r\n";

    $message .= __("Adding quizzes to your site allows you to test your audience's knowledge and can help identify topics of interest, potential news stories, and information that needs to be shared more broadly. If you have questions or would like to provide feedback on the Quiz Creator, please email us at mediaengagement@austin.utexas.edu."). "\r\n\r\n";

    return $message;
}

// Adding new element for register form
function enp_register_form(){

        $user_pass=(!empty($_POST['user_pass'])) ? sanitize_text_field($_POST['user_pass']) : '';

        $confirm_pass=(!empty($_POST['confirm_pass'])) ? sanitize_text_field($_POST['confirm_pass']) : '';

        $honeypot=(!empty($_POST['website'])) ? sanitize_text_field($_POST['website']) : '';

    ?>
    <style>.website-input{display: none;}</style>
    <p>
        <label for="user_pass">Password</label>
        <input type="password" class="input" name="user_pass" value="<?php echo esc_attr($user_pass); ?>">
    </p>

    <p>
        <label for="confirm_pass">Confirm Password</label>
        <input type="password" class="input" name="confirm_pass" value="<?php echo esc_attr($confirm_pass); ?>">
    </p>
    <?php // honeypot! ?>
    <p class="website-input">
        <label for="website">Website</label>
        <input type="text" class="input" name="website" value="<?php echo esc_attr($honeypot); ?>">
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

    // honeypot check. If this field has a value, return a vague error
    if($_POST['website'] !== ''){
        $error->add('website_error','<strong>ERROR:</strong> Please contact us to complete your registration.');
    }

    return $error;

}

add_action('registration_errors','enp_registration_errors');



// Saving user password and log them in
function enp_saving_password($user_id){
    if(isset($_POST['user_pass']) && $_POST['user_pass'] !== '') {
        $user_pass = $_POST['user_pass'];
    } else {
        return false;
    }

    // update the user with their new password
    wp_update_user(array(
            'ID'        => $user_id,
            'user_pass' => $user_pass
        ));

    // see if there's already a user logged in
    // bc the admin user might be manually creating a user
    // if there isn't one, then log the newly created user in
    $is_current_user = wp_get_current_user();
    if($is_current_user->ID === 0) {
        // Set the global user object
        $current_user = get_user_by( 'id', $user_id );

        // set the WP login cookie
        $secure_cookie = is_ssl() ? true : false;
        wp_set_auth_cookie( $user_id, true, $secure_cookie );
    }


}
add_action('user_register','enp_saving_password');



// disable "Notice of Password Change" email. It's not very helpful anyways, and it
// gets triggered when we update the user's password immediately after they're created in enp_saving_password()
add_filter('send_password_change_email', '__return_false');
