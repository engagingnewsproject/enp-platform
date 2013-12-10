<?php
/*
Template Name: View Quiz
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<div id="left_area">
		<?php 
    $user_ID = get_current_user_id(); 
    
    if ( $user_ID ) {
      
    get_template_part('includes/breadcrumbs', 'page');
    if ( $_GET["quiz_updated"] ) {
      if ( $_GET["quiz_updated"] == 1 ) {
        $quiz_notifications =  "<span class='quiz-notification success'><span class='glyphicon glyphicon-info-sign'></span> Quiz successfully updated.</span><div class='clear'></div>";
      }
    }
    
    if ( $_GET["guid"] ) {
      $poll_info = $wpdb->get_row("
        SELECT p.create_datetime, poa.value 'correct_answer'
        FROM enp_quiz_options poa
        INNER JOIN enp_quiz_options po ON po.value = poa.ID
        INNER JOIN enp_quiz p ON p.ID = po.quiz_id
        WHERE po.field = 'correct_option' AND p.guid = '" . $_GET["guid"] . "' ");
      
      $poll_created_date = $poll_info->create_datetime;
      $correct_answer = $poll_info->correct_answer;
    }
    
    echo $quiz_notifications;
    ?>

    <h1>Quiz</h1>
    <span class="bootstrap top-edit-button"><a href="configure-quiz/?edit_guid=<?php echo $_GET["guid"] ?>" class="btn btn-info active" role="button">Edit Quiz</a></span>
    <h4>Created <?php echo $poll_created_date; ?></h4>
    <span class="bootstrap"><hr></span>
    <h3>Preview Quiz</h3>
    <span class="bootstrap"><hr></span>
    <?php get_template_part('self-service-quiz/quiz-display', 'page'); ?>
    <div class="clear"></div>
    <?php 
      
    if ( $correct_answer ) {
    ?>
    <p><b>Correct Answer</b>: <i><?php echo $correct_answer ?></i></p>
    <?php } ?>
    <span class="bootstrap"><hr></span>
		<h3>iframe Code</h3>
		<div class="bootstrap">
      <?php $iframe_url = get_site_url() . '/iframe-quiz/?guid=' . $_GET["guid"]; ?>
      <p>Copy and paste this code into your target website.  <a href="<?php echo $iframe_url ?>" target="_blank">Preview iframe</a>.</p>
	    <div class="form-group">
        <textarea class="form-control" id="quiz-iframe-code" rows="5"><?php echo '<iframe height="450" width="475" frameborder="0" hspace="0" src="' . $iframe_url . '"></iframe>' ?></textarea>
      </div>
      <div class="clear"></div>
	    <div class="form-group">
        <p>
          <a href="configure-quiz/?edit_guid=<?php echo $_GET["guid"] ?>" class="btn btn-info btn-xs active" role="button">Edit Quiz</a> | <a href="list-quizzes/?delete_guid=<?php echo $_GET["guid"] ?>" onclick="return confirm('Are you sure you want to delete this quiz?')" class="btn btn-danger btn-xs active" role="button">Delete Quiz</a>  | <a href="quiz-report/?guid=<?php echo $_GET["guid"] ?>" class="btn btn-primary btn-xs active" role="button">Quiz Reports</a></p>
        <p><a href="configure-quiz" class="btn btn-info btn-xs active" role="button">New Quiz</a> | <a href="list-quizzes/" class="btn btn-primary btn-xs active" role="button">Back to Quizzes</a></p></div>
    </div>
    
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