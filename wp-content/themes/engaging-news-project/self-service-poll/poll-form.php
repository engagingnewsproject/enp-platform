<?php

if(isset($_POST['input-title'])) {
  include('../../../../wp-config.php');
  global $wpdb;
  
  // echo "<h1>Title: " . $_POST['input-title'] . "</h1>";
  // echo "<h1>Question: " . $_POST['input-question'] . "</h1>";
  // echo "<h1>Poll Type: " . $_POST['poll-type'] . "</h1>";
  $user_ID = get_current_user_id(); 
  $date = date('Y-m-d H:i:s');
  
  $id = $_POST['input-id'];
  $title = $_POST['input-title'];
  $question = $_POST['input-question'];
  $poll_type = $_POST['poll-type'];
  
  if ( $id ) {
    $wpdb->update( 
    	'enp_poll', 
    	array( 
    		'title' => $title,	
    		'question' => $question,	
        'last_modified_user_id' => $user_ID
    	), 
    	array( 'ID' => $id )
    );
  } else {
    $wpdb->insert( 'enp_poll', array( 'user_id' => $user_ID , 'title' => $title, 'question' => $question, 'poll_type' => $poll_type, 'create_datetime' => $date, 'last_modified_datetime' => $date, 'last_modified_user_id' => $user_ID, 'active' => 1 ));
    $id = $wpdb->insert_id;
  }
  
  header("Location: /enp/view-poll?id=" . $id);
  
}
?>