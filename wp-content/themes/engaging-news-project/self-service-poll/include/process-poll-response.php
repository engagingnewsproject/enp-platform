<?php
include('../../../../../wp-config.php');
global $wpdb;

if(isset($_POST['input-id'])) {
  
  $date = date('Y-m-d H:i:s');
  $guid = $_POST['input-guid'];
  $poll_type = $_POST['poll-type'];
  $response_id = 0;
  
  $poll_id = $wpdb->get_var( "
      SELECT ID FROM enp_poll 
      WHERE guid = '" . $guid . "' " );

  if ( $poll_type == 'multiple-choice' ) {
    $response_id = processMCResponse($date, $poll_id, $wpdb);
  } else {
    $response_id = processSliderResponse($date, $poll_id, $wpdb);
  }
  
  header("Location: " . get_site_url() . "/poll-answer/?response_id=" . $response_id . "&guid=" . $guid);
}

function processMCResponse($date, $poll_id, $wpdb) {
  $poll_answer_id = $_POST['pollRadios'];
  $poll_answer_value = $_POST['option-radio-id-' . $poll_answer_id];
  $is_correct = 0;
      
  $correct_option_id = $wpdb->get_var("
    SELECT value FROM enp_poll_options
    WHERE field = 'correct_option' AND
    poll_id = " . $poll_id);
    
  $correct_option_value = $wpdb->get_var("
    SELECT value FROM enp_poll_options
    WHERE field = 'answer_option' AND
    id = " . $correct_option_id);
  
  if ( $correct_option_id == $poll_answer_id ) {
    $is_correct = 1;
  }

  $wpdb->insert( 'enp_poll_responses', 
  array( 'poll_id' => $poll_id , 'poll_option_id' => $poll_answer_id, 'poll_option_value' => $poll_answer_value, 
    'correct_option_id' => $correct_option_id, 'correct_option_value' => $correct_option_value, 
    'is_correct' => $is_correct, 'ip_address' => $_SERVER['REMOTE_ADDR'], 'response_datetime' => $date ));
    
  return $wpdb->insert_id;
}

function processSliderResponse($date, $poll_id, $wpdb) {
  $is_correct = 0;
  
  $slider_value = $_POST['slider-value'];
  $slider_high_answer = $_POST['slider-high-answer'];
  $slider_low_answer = $_POST['slider-low-answer'];
  $correct_option_value = $slider_low_answer . '-' . $slider_high_answer;
  
  //TODO more scenario's
  if ( $slider_value <= $slider_high_answer && $slider_value >= $slider_low_answer ) {
    $is_correct = 1;
  }

  $wpdb->insert( 'enp_poll_responses', 
  array( 'poll_id' => $poll_id , 'poll_option_id' => -2, 'poll_option_value' => $slider_value, 
    'correct_option_id' => -2, 'correct_option_value' => $correct_option_value, 
    'is_correct' => $is_correct, 'ip_address' => $_SERVER['REMOTE_ADDR'], 'response_datetime' => $date ));
    
  return $wpdb->insert_id;
}
?>