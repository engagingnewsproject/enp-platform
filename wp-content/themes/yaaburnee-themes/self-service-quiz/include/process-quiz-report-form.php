<?php
include('../../../../../wp-config.php');
global $wpdb;

$quiz_id = $_POST['input-id'];

if( $quiz_id ) {
  $guid = $_POST['input-guid'];
  $date = date('Y-m-d H:i:s');

  processQuizReport($quiz_id, $date, $wpdb);

  $report_all = '&report=single';
  if(isset($_POST['report-view'])) {
    $report = $_POST['report-view'];
    if($report == 'all') {
      $report_all = '&report=all';
    }
  }

  //NTH Check for update errors in DB and show gracefully to the user
  header("Location: " . get_site_url() . "/quiz-report?guid=" . $guid . "&quiz_report_updated=1".$report_all );
}

function processQuizReport($quiz_id, $date, $wpdb) {
  $wpdb->delete( 'enp_quiz_options', array( 'quiz_id' => $quiz_id, 'field' => 'report_ignored_ip_addresses'), array( '%d', '%s' ) );

  $report_ignored_ip_addresses = $_POST['input-report-ip-addresses'];

  // Add new options
  $wpdb->insert( 'enp_quiz_options', array( 'quiz_id' => $quiz_id, 'field' => 'report_ignored_ip_addresses',
    'value' => $report_ignored_ip_addresses, 'create_datetime' => $date, 'display_order' => 0),
      array(
          '%d',
          '%s',
          '%s',
          '%s',
          '%d'));

}
?>
