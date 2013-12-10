<?php
/*
Template Name: Quiz Report
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<div id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <h1>Quiz Report</h1>
    <h2>Chart of responses</h2>
    <?php
    $quiz = $wpdb->get_row("
      SELECT * FROM enp_quiz 
      WHERE guid = '" . $_GET["guid"] . "' ");
      
    $quiz_responses_option_1 = $wpdb->get_var( 
      "SELECT COUNT(*) 
      FROM enp_quiz_responses
      WHERE quiz_option_id = 1
      AND quiz_id = " . $quiz->ID
    );
    
    $quiz_responses_option_2 = $wpdb->get_var( 
      "SELECT COUNT(*) 
      FROM enp_quiz_responses
      WHERE quiz_option_id = 2
      AND quiz_id = " . $quiz->ID
    );
    $quiz_responses_option_3 = $wpdb->get_var( 
      "SELECT COUNT(*) 
      FROM enp_quiz_responses
      WHERE quiz_option_id = 3
      AND quiz_id = " . $quiz->ID
    );
    $quiz_responses_option_4 = $wpdb->get_var( 
      "SELECT COUNT(*) 
      FROM enp_quiz_responses
      WHERE quiz_option_id = 4
      AND quiz_id = " . $quiz->ID
    );
    ?>
    <input type="hidden" id="quiz-responses-option-1" value="<?php echo $quiz_responses_option_1 ?>">
    <input type="hidden" id="quiz-responses-option-2" value="<?php echo $quiz_responses_option_2 ?>">
    <input type="hidden" id="quiz-responses-option-3" value="<?php echo $quiz_responses_option_3 ?>">
    <input type="hidden" id="quiz-responses-option-4" value="<?php echo $quiz_responses_option_4 ?>">
    <div id="quiz-answer-pie-graph"></div>
    <?php //include(locate_template('self-service-quiz/quiz-detailed-responses.php')); ?>
    
    <div class="bootstrap"><p><a href="list-quizzes/" class="btn btn-primary btn-xs active" role="button">Back to quizzes</a></p></div>
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>