<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
add_action('wp_ajax_userValidate','viwptf_do_user_validation');
function viwptf_do_user_validation()
{
header('Content-type: application/json');

$username = sanitize_text_field($_GET['screen_name']);

$url="https://twitter.com/intent/user?screen_name=".$username;
if(!@file_get_contents($url)) {
    $data = array( 
    	'data' => "Invalid screen name",
    	'class' => 'user-validator-invalid'
    );
}
else {
	$data = array( 
    	'data' => "Valid screen name",
    	'class' => 'user-validator-valid'
    );
}
echo json_encode( $data );
exit();
}
?>