<?php
/*
Template Name: Poll Report
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<div id="left_area">
    <h1>Poll Report</h1>
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <h2>Detailed responses</h2>
    <div class="bootstrap">
      <?php
      $poll = $wpdb->get_row("
        SELECT * FROM enp_poll 
        WHERE guid = '" . $_GET["guid"] . "' ");
      
      $poll_responses = $wpdb->get_results( 
        "
        SELECT * 
        FROM enp_poll_responses
        WHERE correct_option_value != '-1' AND poll_id = " . $poll->ID
      );
          
      if ( $poll_responses )
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
        foreach ( $poll_responses as $poll_response )
        {
          ?>
          <tr>
            <td><?php echo $poll_response->datetime; ?></td>
            <td><?php echo $poll_response->is_correct ? "Yes": "No"; ?></td>
            <td><?php echo $poll_response->poll_option_value ?></td>
            <td><?php echo $poll_response->correct_option_value ?></td>
            <td><?php echo $poll_response->ip_address ?></td>
          </tr>
          <?php
        }  
        
        echo "</table>";
        echo "</div>";
      }
      else
      {
        ?>
        <p>No responses for this poll just yet!</p>
        <?php
      }
      ?>
    </div>
    
    <h2>Chart of responses</h2>
    <?php
    $poll_responses_option_1 = $wpdb->get_var( 
      "SELECT COUNT(*) 
      FROM enp_poll_responses
      WHERE poll_option_id = 1
      AND poll_id = " . $poll->ID
    );
    
    $poll_responses_option_2 = $wpdb->get_var( 
      "SELECT COUNT(*) 
      FROM enp_poll_responses
      WHERE poll_option_id = 2
      AND poll_id = " . $poll->ID
    );
    $poll_responses_option_3 = $wpdb->get_var( 
      "SELECT COUNT(*) 
      FROM enp_poll_responses
      WHERE poll_option_id = 3
      AND poll_id = " . $poll->ID
    );
    $poll_responses_option_4 = $wpdb->get_var( 
      "SELECT COUNT(*) 
      FROM enp_poll_responses
      WHERE poll_option_id = 4
      AND poll_id = " . $poll->ID
    );
    ?>
    <input type="hidden" id="poll-responses-option-1" value="<?php echo $poll_responses_option_1 ?>">
    <input type="hidden" id="poll-responses-option-2" value="<?php echo $poll_responses_option_2 ?>">
    <input type="hidden" id="poll-responses-option-3" value="<?php echo $poll_responses_option_3 ?>">
    <input type="hidden" id="poll-responses-option-4" value="<?php echo $poll_responses_option_4 ?>">
    <div id="poll-answer-pie-graph"></div>
    <div class="bootstrap"><p><a href="list-polls/" class="btn btn-primary btn-xs active" role="button">Back to polls</a></p></div>
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>