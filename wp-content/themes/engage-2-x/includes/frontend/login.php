<?php
/**
 * Login-related functionality
 * 
 * This file contains all hooks and functions related to the login system,
 * including custom redirects and login page modifications.
 */

/**
 * Redirect users to a specific URL after login
 * 
 * @param string $redirect_to The redirect destination URL
 * @param string $request The requested URL
 * @param WP_User $user The logged-in user object
 * @return string The modified redirect URL
 */
function custom_login_redirect($redirect_to, $request, $user) {
    // Check if the user is an administrator
    if (isset($user->roles) && is_array($user->roles) && in_array('administrator', $user->roles)) {
        // Redirect administrators to the dashboard
        return admin_url();
    } else {
        // Redirect other users to a different URL
        return home_url('/enp-quiz/dashboard/user/');
    }
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);

/**
 * Add a custom link to the registration page
 */
function add_custom_link_to_registration_page() {
    // Check if we are on the registration page
    if (isset($_GET['action']) && $_GET['action'] === 'register') {
        echo '
        <div class="terms-page-link" style="text-align: center">
            <a href="/terms-and-conditions/" rel="custom-link">Terms and Conditions</a>
        </div>';
    }
}
add_action('login_footer', 'add_custom_link_to_registration_page');

/**
 * Add a custom link to the login page
 */
function add_custom_link_to_login_page() {
    // Check if we are on the login page
    if (!isset($_GET['action']) || $_GET['action'] === 'login') {
        echo '
        <div class="terms-page-link" style="text-align: center">
            <a href="/terms-and-conditions/" rel="custom-link">Terms and Conditions</a>
        </div>';
    }
}
add_action('login_footer', 'add_custom_link_to_login_page'); 