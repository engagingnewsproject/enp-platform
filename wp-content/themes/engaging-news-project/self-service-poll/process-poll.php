<?php

if(isset($_POST['input-id'])) {
  include('../../../../wp-config.php');
  global $wpdb;
  
  // echo "<h1>Title: " . $_POST['input-title'] . "</h1>";
  // echo "<h1>Question: " . $_POST['input-question'] . "</h1>";
  // echo "<h1>Poll Type: " . $_POST['poll-type'] . "</h1>";
  $date = date('Y-m-d H:i:s');
  
  $id = $_POST['input-id'];

  $wpdb->insert( 'enp_poll_responses', array( 'poll_id' => $id , 'is_correct' => 1, 'ip_address' => $_SERVER['REMOTE_ADDR'], 'datetime' => $date ));
  
  header("Location: /enp/poll-answer/?id=" . $id);
  
}
?>