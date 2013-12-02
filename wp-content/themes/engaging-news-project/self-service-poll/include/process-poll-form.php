<?php

if(isset($_POST['input-title'])) {
  include('../../../../../wp-config.php');
  global $wpdb;
  
  // echo "<h1>Title: " . $_POST['input-title'] . "</h1>";
  // echo "<h1>Question: " . $_POST['input-question'] . "</h1>";
  // echo "<h1>Poll Type: " . $_POST['poll-type'] . "</h1>";
  $user_ID = get_current_user_id(); 
  $date = date('Y-m-d H:i:s');
  
  $id = $_POST['input-id'];
  $guid = $_POST['input-guid'];
  $title = $_POST['input-title'];
  $question = $_POST['input-question'];
  $poll_type = $_POST['poll-type'];
  $mc_answer_count = $_POST['mc-answer-count'];
  $correct_option = $_POST['correct-option'];
  
  if ( $guid ) {
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
  } else {
	  $guid = uniqid('', true) . '_' . md5(mt_rand());
    $wpdb->insert( 'enp_poll', array( 'guid' => $guid, 'user_id' => $user_ID , 'title' => $title, 'question' => $question, 'poll_type' => $poll_type, 'create_datetime' => $date, 'last_modified_datetime' => $date, 'last_modified_user_id' => $user_ID, 'active' => 1 ));
    $id = $wpdb->insert_id;
    
    for ($i = 1; $i <= $mc_answer_count; $i++) {
      if ( !empty($_POST['mc-answer-' . $i]) ) {
        $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $id, 'field' => 'answer_option', 'value' => $_POST['mc-answer-' . $i], 'create_datetime' => $date, 'display_order' => $_POST['mc-answer-order-' . $i] ));
        
        if ( $correct_option == 'mc-answer-' . $i) {
          $correct_option_id = $wpdb->insert_id;
          $wpdb->insert( 'enp_poll_options', array( 'poll_id' => $id, 'field' => 'correct_option', 'value' => $correct_option_id, 'create_datetime' => $date, 'display_order' => 0 ));
        }
      }
    }
  }
  
  header("Location: " . get_site_url() . "/view-poll?guid=" . $guid);
  
}
?>