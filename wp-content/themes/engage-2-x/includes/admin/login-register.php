<?php

/**
 * Custom Login and Registration Masking for WordPress
 *
 * This file provides rewrite rules, query vars, and template redirects to mask all default WordPress login, registration, password reset, and logout URLs.
 * It also updates all relevant email links and form actions to use the masked URLs, improving security and branding.
 *
 * Features:
 * - Masks login, register, lost password, reset password, and logout URLs
 * - Updates all system emails to use masked URLs
 * - Ensures all forms post to the correct masked endpoints
 * - Blocks direct access to wp-login.php and wp-admin for non-logged-in users
 *
 * @package Engage\Admin
 */

// Add custom rewrite rules for login, register, lost password, reset password, and logout
/**
 * Registers custom rewrite rules for all masked authentication endpoints.
 *
 * @return void
 */
function custom_login_rewrite_rules()
{
	add_rewrite_rule('^user-login/?$', 'index.php?user_login=1', 'top');
	// add_rewrite_rule('^user-login/?$', 'index.php?user_login=1&login=resetpass', 'top');
	add_rewrite_rule('^user-register/?$', 'index.php?user_register=1', 'top');
	add_rewrite_rule('^user-postpass/?$', 'wp-login.php?action=postpass', 'top');
	add_rewrite_rule('^user-logout/?$', 'index.php?user_logout=1', 'top');
	add_rewrite_rule('^user-reset/?$', 'index.php?user_reset=1', 'top');
	add_rewrite_rule('^user-lostpassword/?$', 'index.php?user_lostpassword=1', 'top');
	// error_log('custom_login_rewrite_rules ran');
}
add_action('init', 'custom_login_rewrite_rules', 9999); // Register rewrite rules on init

/**
 * Flushes rewrite rules on theme activation to ensure custom rules are registered.
 *
 * @return void
 */
function custom_flush_rewrite_rules()
{
	custom_login_rewrite_rules();
	flush_rewrite_rules();
}
add_action('after_switch_theme', 'custom_flush_rewrite_rules'); // Flush rules after theme switch

/**
 * Blocks direct access to wp-login.php and wp-admin for non-logged-in users, except for allowed actions.
 *
 * @return void
 */
function block_default_login()
{
	$request_uri = $_SERVER['REQUEST_URI'];
	$action = isset($_GET['action']) ? $_GET['action'] : '';
	$login_status = isset($_GET['login']) ? $_GET['login'] : '';
	// Allow logout, register, and resetpass login status to go through
	if (
		(strpos($request_uri, 'wp-login.php') !== false && !in_array($action, ['logout', 'register', 'resetpass']) && $login_status !== 'resetpass')
		||
		(strpos($request_uri, 'wp-admin') !== false && !is_user_logged_in())
	) {
		wp_redirect(home_url('/user-login'));
		exit;
	}
}
add_action('init', 'block_default_login'); // Block default login endpoints

/**
 * Registers custom query vars for masked authentication endpoints.
 *
 * @param array $vars Existing query vars.
 * @return array Modified query vars.
 */
add_filter('query_vars', function($vars) {
    $vars[] = 'user_login';
    $vars[] = 'user_logout';
    $vars[] = 'user_register';
    $vars[] = 'user_reset';
    $vars[] = 'user_lostpassword';
    return $vars;
});

/**
 * Handles template redirects for all custom authentication endpoints, including login, logout, register, reset, and lost password.
 *
 * @return void
 */
add_action('template_redirect', function() {
    if (get_query_var('user_login')) {
        if (!isset($user_login)) $user_login = '';
        if (!isset($error)) $error = '';
        require(ABSPATH . 'wp-login.php');
        exit;
    }
    if (get_query_var('user_logout')) {
        wp_logout();
        wp_redirect(home_url('/user-login'));
        exit;
    }
    if (get_query_var('user_register')) {
        $_GET['action'] = 'register';
        $_POST['action'] = 'register';
        $_REQUEST['action'] = 'register';
        if (!isset($user_login)) $user_login = '';
        if (!isset($error)) $error = '';
        require(ABSPATH . 'wp-login.php');
        exit;
    }
    if (get_query_var('user_reset')) {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        if (in_array($action, ['rp', 'resetpass'])) {
            $_GET['action'] = $action;
            $_POST['action'] = $action;
            $_REQUEST['action'] = $action;
        } else {
            $_GET['action'] = 'rp';
            $_POST['action'] = 'rp';
            $_REQUEST['action'] = 'rp';
        }
        if (!isset($user_login)) $user_login = '';
        if (!isset($error)) $error = '';
        require(ABSPATH . 'wp-login.php');
        exit;
    }
    if (get_query_var('user_lostpassword')) {
        // error_log('Handling user_lostpassword');
        $_GET['action'] = 'lostpassword';
        $_POST['action'] = 'lostpassword';
        $_REQUEST['action'] = 'lostpassword';
        if (!isset($user_login)) $user_login = '';
        if (!isset($error)) $error = '';
        require(ABSPATH . 'wp-login.php');
        exit;
    }
});

/**
 * Filters the register URL to use the masked endpoint.
 *
 * @param string $url The original register URL.
 * @return string The masked register URL.
 */
add_filter('register_url', function($url) {
    return home_url('/user-register');
});

/**
 * Filters the password reset email message to use the masked reset URL.
 *
 * @param string $message The original message.
 * @param string $key The password reset key.
 * @param string $user_login The user login.
 * @param WP_User $user_data The user data object.
 * @return string The modified message.
 */
add_filter('retrieve_password_message', function($message, $key, $user_login, $user_data) {
    $site_url = home_url('/user-reset/');
    $reset_url = add_query_arg([
        'action' => 'rp',
        'key'    => $key,
        'login'  => rawurlencode($user_login),
    ], $site_url);

    // Compose a new message, ignoring the default
    $message = sprintf(
        "Username: %s\n\nTo set your password, visit the following address:\n\n%s\n\nIf you did not request this, please ignore this email.",
        $user_login,
        $reset_url
    );
    return $message;
}, 100, 4); // Use a high priority to override others

/**
 * Filters the new user notification email to use the masked reset URL.
 *
 * @param array $wp_new_user_notification_email The original email array.
 * @param WP_User $user The user object.
 * @param string $blogname The site name.
 * @return array The modified email array.
 */
add_filter('wp_new_user_notification_email', function($wp_new_user_notification_email, $user, $blogname) {
    $site_url = home_url('/user-reset/');
    $key = get_password_reset_key($user);
    $reset_url = add_query_arg([
        'action' => 'rp',
        'key'    => $key,
        'login'  => rawurlencode($user->user_login),
    ], $site_url);

    $wp_new_user_notification_email['message'] = sprintf(
        "Username: %s\n\nTo set your password, visit the following address:\n\n%s\n\nIf you did not request this, please ignore this email.",
        $user->user_login,
        $reset_url
    );
    return $wp_new_user_notification_email;
}, 10, 3);

// Auto-Login After Password Reset (optional, currently disabled)
// add_action('after_password_reset', function($user, $new_pass) {
//     wp_set_auth_cookie($user->ID, true); // true = remember me
//     wp_set_current_user($user->ID);
// }, 10, 2);

/**
 * Filters the lost password URL to use the masked endpoint.
 *
 * @param string $url The original lost password URL.
 * @param string $redirect The redirect URL.
 * @return string The masked lost password URL.
 */
add_filter('lostpassword_url', function($url, $redirect) {
    $lostpassword_url = home_url('/user-lostpassword/');
    if (!empty($redirect)) {
        $lostpassword_url = add_query_arg('redirect_to', urlencode($redirect), $lostpassword_url);
    }
    return $lostpassword_url;
}, 100, 2);

/**
 * Filters the login URL to use the masked endpoint.
 *
 * @param string $login_url The original login URL.
 * @param string $redirect The redirect URL.
 * @param bool $force_reauth Whether to force reauthentication.
 * @return string The masked login URL.
 */
add_filter('login_url', function($login_url, $redirect, $force_reauth) {
    $url = home_url('/user-login/');
    if (!empty($redirect)) {
        $url = add_query_arg('redirect_to', urlencode($redirect), $url);
    }
    if ($force_reauth) {
        $url = add_query_arg('reauth', '1', $url);
    }
    return $url;
}, 100, 3);

/**
 * Forces the login form action to use the masked login endpoint via JavaScript.
 */
add_action('login_form_login', function() {
    ?>
    <script>
    // Force the login form action to /user-login/ on the login page
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('loginform');
        if (form) {
            form.action = '<?php echo home_url('/user-login/'); ?>';
        }
    });
    </script>
    <?php
});

/**
 * Forces the lost password form action to use the masked lost password endpoint via JavaScript.
 */
add_action('login_form_lostpassword', function() {
    ?>
    <script>
    // Force the lost password form action to /user-lostpassword/ on the lost password page
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('lostpasswordform');
        if (form) {
            form.action = '<?php echo home_url('/user-lostpassword?action=lostpassword'); ?>';
        }
    });
    </script>
    <?php
});

/**
 * Forces the password reset form action to use the masked reset endpoint via JavaScript (for both rp and resetpass actions).
 */
add_action('login_form_rp', function() {
    ?>
    <script>
    // Force the password reset form action to /user-reset/?action=resetpass
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('resetpassform');
        if (form) {
            form.action = '<?php echo home_url('/user-reset/?action=resetpass'); ?>';
        }
    });
    </script>
    <?php
});
add_action('login_form_resetpass', function() {
    ?>
    <script>
    // Force the password reset form action to /user-reset/?action=resetpass
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('resetpassform');
        if (form) {
            form.action = '<?php echo home_url('/user-reset/?action=resetpass'); ?>';
        }
    });
    </script>
    <?php
});