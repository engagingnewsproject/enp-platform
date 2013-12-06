<?php
include('../../../../../wp-config.php');
global $wpdb;

if( $_POST['input-question'] ) {
  $date = date('Y-m-d H:i:s');
  $guid = $_POST['input-guid'] ? $_POST['input-guid'] : uniqid('', true) . '_' . md5(mt_rand());
  $poll_type = $_POST['poll-type'];
  
  $poll_id = processPoll($poll_type, $guid, $date, $wpdb);

  processAnswers($poll_id, $poll_type, $date, $wpdb);
  
  header("Location: " . get_site_url() . "/view-poll?guid=" . $guid);
}

function processPoll($poll_type, $guid, $date, $wpdb) {
  $user_ID = get_current_user_id(); 
  $title = $_POST['input-title'];
  $question = $_POST['input-question'];
  $poll_id = $_POST['input-id'];
  
  if ( $_POST['input-guid'] ) {
    updatePoll($title, $question, $user_ID, $guid, $date, $wpdb);
  } else {
    $poll_id = createPoll($title, $question, $poll_type, $user_ID, $guid, $date, $wpdb);
  }
  
  return $poll_id; 
}

function updatePoll($title, $question, $user_ID, $guid, $date, $wpdb) {
  $wpdb->update( 
  	'enp_poll', 
  	array( 
  		'title' => $title,	
  		'question' => $question,	
      'last_modified_user_id' => $user_ID,
      'last_modified_datetime' => $date
  	), 
  	array( 'guid' => $guid )
  );
}

function createPoll($title, $question, $poll_type, $user_ID, $guid, $date, $wpdb) {  
  $wpdb->insert( 'enp_poll', array( 'guid' => $guid, 'user_id' => $user_ID , 'title' => $title, 'question' => $question, 'poll_type' => $poll_type, 'create_datetime' => $date, 'last_modified_datetime' => $date, 'last_modified_user_id' => $user_ID, 'active' => 1 ));
  
  return $wpdb->insert_id;
}

function processAnswers($poll_id, $poll_type, $date, $wpdb) {
  // Delete all options
  $wpdb->delete( 'enp_poll_options', array( 'poll_id' => $poll_id ), array( '%d' ) );
  
  if ( $poll_type == "multiple-choice") {
    processMCAnswers($poll_id, $date, $wpdb);
  } else {
    processSliderOptions($poll_id, $date, $wpdb);
  }
}

function processMCAnswers($poll_id, $date, $wpdb) {
  $mc_answer_count = $_POST['mc-answer-count'];
  $correct_option = $_POST['correct-option'];

  for ($i = 1; $i <= $mc_answer_count; $i++) {
    if ( !empty($_POST['mc-answer-' . $i]) ) {
      $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $poll_id, 'field' => 'answer_option', 'value' => $_POST['mc-answer-' . $i], 'create_datetime' => $date, 'display_order' => $_POST['mc-answer-order-' . $i] ));
      
      if ( $correct_option == 'mc-answer-' . $i) {
        $correct_option_id = $wpdb->insert_id;
        $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $poll_id, 'field' => 'correct_option', 'value' => $correct_option_id, 'create_datetime' => $date, 'display_order' => 0 ));
      }
    }
  }
}

function processSliderOptions($poll_id, $date, $wpdb) {
  $slider_high = $_POST['slider-high'];
  $slider_low = $_POST['slider-low'];
  $slider_start = $_POST['slider-start'];
  $slider_increment = $_POST['slider-increment'];
  $slider_high_answer = $_POST['slider-high-answer'];
  $slider_low_answer = $_POST['slider-low-answer'];
  $slider_label = $_POST['slider-label'];
  
  // Add new options
  $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $poll_id, 'field' => 'slider_high', 
    'value' => $slider_high, 'create_datetime' => $date, 'display_order' => 0));
  $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $poll_id, 'field' => 'slider_low', 
    'value' => $slider_low, 'create_datetime' => $date, 'display_order' => 0));
  $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $poll_id, 'field' => 'slider_start', 
    'value' => $slider_start, 'create_datetime' => $date, 'display_order' => 0));
  $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $poll_id, 'field' => 'slider_increment', 
    'value' => $slider_increment, 'create_datetime' => $date, 'display_order' => 0));
  $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $poll_id, 'field' => 'slider_high_answer', 
    'value' => $slider_high_answer, 'create_datetime' => $date, 'display_order' => 0));
  $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $poll_id, 'field' => 'slider_low_answer', 
    'value' => $slider_low_answer, 'create_datetime' => $date, 'display_order' => 0));
  $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $poll_id, 'field' => 'slider_label', 
    'value' => $slider_label, 'create_datetime' => $date, 'display_order' => 0));
  
}
?>