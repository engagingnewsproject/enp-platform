<?php 
/*
 * Add Spam User role
*/
add_action('init', function () {
    if (!get_role('spam_user')) {
        add_role('spam_user', 'Spam User', []);
    }
});

// To block spam_user from logging in entirely:
add_action('wp_login', function ($user_login, $user) {
    if (in_array('spam_user', (array) $user->roles)) {
        wp_logout();
        wp_die('Access denied.');
    }
}, 10, 2);
