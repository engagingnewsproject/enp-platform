<?php
/*
Template Name: Quiz Report
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<div id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <?php
    $user_ID = get_current_user_id(); 
    
    if ( $user_ID ) {
    ?>
    <h1>Quiz Report</h1>
    <?php
    $quiz = $wpdb->get_row("
      SELECT * FROM enp_quiz 
      WHERE guid = '" . $_GET["guid"] . "' ");
      
    $mc_answers = $wpdb->get_results("
      SELECT * FROM enp_quiz_options
      WHERE field = 'answer_option' AND quiz_id = " . $quiz->ID . 
      " ORDER BY `display_order`");
      
    $correct_option_id = $wpdb->get_var("
      SELECT value FROM enp_quiz_options
      WHERE field = 'correct_option' AND
      quiz_id = " . $quiz->ID);
      
    $quiz_response_count = $wpdb->get_var( 
      "
      SELECT COUNT(*) 
      FROM enp_quiz_responses
      WHERE correct_option_value != '-1' AND quiz_id = " . $quiz->ID
    );
      
    if ( $quiz_response_count > 0 ) {
    ?>
    <div id="quiz-answer-pie-graph"></div>
    <div class="bootstrap">
      <div class="panel panel-primary">
        <!-- Default panel contents -->
        <div class="panel-heading">Response Detail</div>
          <div class='table-responsive'>
            <table class='table'>
              <thead><tr>
                <th>ID</th>
                <th>Answer</th>
                <th>Selected Count</th>
                <th>Display Order</th>
                <!-- <th>% Selected</th> -->
              </tr></thead>
              <?php
              foreach ( $mc_answers as $mc_answer ) { 
                $quiz_responses[$mc_answer->ID] = $wpdb->get_var( 
                  "SELECT COUNT(*) 
                  FROM enp_quiz_responses
                  WHERE quiz_option_id = " . $mc_answer->ID . "
                  AND quiz_id = " . $quiz->ID
                );
                ?>
                <tr class="<?php echo $correct_option_id == $mc_answer->ID ? "correct" : ""; ?>">
                  <td><?php echo $mc_answer->ID ?></td>
                  <td><input type="hidden" class="form-control quiz-responses-option" id="<?php echo $mc_answer->ID ?>" value="<?php echo $mc_answer->value ?>"><?php echo $mc_answer->value ?></td>
                  <td><input type="hidden" class="form-control quiz-responses-option-count" id="quiz-responses-option-count-<?php echo $mc_answer->ID ?>" value="<?php echo $quiz_responses[$mc_answer->ID] ?>"><?php echo $quiz_responses[$mc_answer->ID] ?></td>
                  <td><?php echo $mc_answer->display_order ?></td>
                  <!-- <td><?php// echo ROUND($quiz_responses[$mc_answer->ID]/$quiz_response_count*100, 2) ?>%</td> -->
                </tr>
                <?php
              }
              ?>
            </table>
          </div>
        </div>
    </div>
    <?php //include(locate_template('self-service-quiz/quiz-detailed-responses.php')); ?>
    <div class="bootstrap">
      <div class="panel panel-primary">
        <!-- Default panel contents -->
        <div class="panel-heading">Quiz statistics</div>
        <div class="input-group">
          <span class="input-group-addon" name="correct-responses">Correct responses: </span>
          <label class="form-control">100</label>
        </div>
        <div class="input-group">
          <span class="input-group-addon" name="correct-responses">% correct: </span>
          <label class="form-control">5%</label>
        </div>
      Incorrect responses
      % incorrect
      % Answering: 
      Unique views: 
      Total views: 
      Slider answer values (configured)
      Slider % below correct value
      Slider % above correct value
      </div>
    </div>
    <?php } else { ?>
      <p>No responses for this quiz just yet!</p>
    <?php } ?>
    <div class="bootstrap"><p><a href="list-quizzes/" class="btn btn-primary btn-xs active" role="button">Back to Quizzes</a></p></div>
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
    <?php
    } else {
    ?>
      <p>Please login to start creating quizzes!</p>
    <?php } ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>