<?php

if(isset($_POST['input-id'])) {
  include('../../../../../wp-config.php');
  global $wpdb;
  $date = date('Y-m-d H:i:s');
  
  $guid = $_POST['input-guid'];

  $poll_answer_id = $_POST['pollRadios'];
  $poll_answer_value = $_POST['option-radio-id-' . $poll_answer_id];
  $is_correct = 0;
  
  $id = $wpdb->get_var( "
      SELECT ID FROM enp_poll 
      WHERE guid = '" . $guid . "' " );
      
  $correct_option_id = $wpdb->get_var("
    SELECT value FROM enp_poll_options
    WHERE field = 'correct_option' AND
    poll_id = " . $id);
    
  $correct_option_value = $wpdb->get_var("
    SELECT value FROM enp_poll_options
    WHERE field = 'answer_option' AND
    id = " . $correct_option_id);
  
  if ( $correct_option_id == $poll_answer_id ) {
    $is_correct = 1;
  }

  $wpdb->insert( 'enp_poll_responses', 
  array( 'poll_id' => $id , 'poll_option_id' => $poll_answer_id, 'poll_option_value' => $poll_answer_value, 
    'correct_option_id' => $correct_option_id, 'correct_option_value' => $correct_option_value, 
    'is_correct' => $is_correct, 'ip_address' => $_SERVER['REMOTE_ADDR'], 'response_datetime' => $date ));
    
  $response_id = $wpdb->insert_id;
  
  header("Location: " . get_site_url() . "/poll-answer/?response_id=" . $response_id . "&guid=" . $guid);
  
}
?>