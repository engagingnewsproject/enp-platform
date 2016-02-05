<h2>Detailed responses</h2>
<div class="bootstrap">
  <?php
  
  $quiz_responses = $wpdb->get_results( 
    "
    SELECT * 
    FROM enp_quiz_responses
    WHERE correct_option_value != '-1' AND quiz_id = " . $quiz->ID
  );
      
  if ( $quiz_responses )
  {
    echo "<div class='table-responsive'>";
    echo "<table class='table'>";
    echo "<thead><tr>
            <th>Datetime</th>
            <th>Correct</th>
            <th>User Response</th>
            <th>Correct Response</th>
            <th>IP Address</th>
          </tr></thead>";
    foreach ( $quiz_responses as $quiz_response )
    {
      ?>
      <tr>
        <td><?php echo $quiz_response->datetime; ?></td>
        <td><?php echo $quiz_response->is_correct ? "Yes": "No"; ?></td>
        <td><?php echo $quiz_response->quiz_option_value ?></td>
        <td><?php echo $quiz_response->correct_option_value ?></td>
        <td><?php echo $quiz_response->ip_address ?></td>
      </tr>
      <?php
    }  
    
    echo "</table>";
    echo "</div>";
  }
  else
  {
    ?>
    <p>No responses for this quiz just yet!</p>
    <?php
  }
  ?>
</div>