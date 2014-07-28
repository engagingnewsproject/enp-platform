<?php
include('../../../../../wp-config.php');
global $wpdb;

if(isset($_GET['guid'])) {
  
  $guid = $_GET['guid'];
  
  $quiz = $wpdb->get_row("
    SELECT * FROM enp_quiz 
    WHERE guid = '" . $guid . "' ");
  
  deleteQuizResponses($quiz->ID, $wpdb);

  header("Location: " . get_site_url() . "/quiz-report/?guid=" . $guid . '&message=responses_deleted');

}

function deleteQuizResponses($quiz_id, $wpdb) {
  return $wpdb->delete( 'enp_quiz_responses', array( 'quiz_id' => $quiz_id ), array( '%d' ) );
}


?>