<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
	<article class="entry post clearfix">
		<h1 class="main_title"><?php the_title(); ?></h1>

		<div class="post-content clearfix">
      <? 
      if ( $_GET["edit_guid"] ) {
        $quiz = $wpdb->get_row("SELECT * FROM enp_quiz WHERE guid = '" . $_GET["edit_guid"] . "'" ); 
      }
      
      if ( $quiz ) {
        $question_text = esc_attr($quiz->question);
      } else {
        $question_text = "Enter Quiz Question";
      }
      
      // Removing lock feature...remove permanently after more feedback
      //if ( !$quiz->locked ) {
      if ( true ) {
      ?>
			<div class="entry_content bootstrap <?php echo $quiz ? "edit_quiz" : "new_quiz"?>">
            <?php if ( $quiz->locked ) { ?>
              <div class='bootstrap'>
                <div class='alert alert-warning alert-dismissable'>
                  <span class='glyphicon glyphicon-warning-sign'></span> Quiz has received responses.  Editing could cause inconsistencies in reporting.<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                </div>
                <div class='clear'></div>
              </div>
            <?php } ?> 
		        <form id="quiz-form" class="form-horizontal" role="form" method="post" action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-quiz/include/process-quiz-form.php">
		          <input type="hidden" name="input-id" id="input-id" value="<?php echo $quiz->ID; ?>">
				      <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $quiz->guid; ?>">
              
              <?php include(locate_template('self-service-quiz/quiz-form-options.php')); ?>    
                        
              <div class="panel panel-info">
                <div class="panel-heading">Quiz Answers</div>
                <div class="panel-body">
      
                  <?php include(locate_template('self-service-quiz/quiz-form-mc-options.php')); ?>    

                  <?php include(locate_template('self-service-quiz/quiz-form-slider-options.php')); ?>    
                
                  <?php include(locate_template('self-service-quiz/quiz-form-aanswer-options.php')); ?>    
                  
                </div>
              </div>
              
              <?php include(locate_template('self-service-quiz/quiz-form-styling-options.php')); ?>
              
		          <div class="form-group">
		            <div class="col-sm-12">
		              <button type="submit" class="btn btn-primary"><?php echo $quiz ? "Update Quiz" : "Create Quiz"; ?></button>
                  <?php if ($quiz) { ?>
                    <a href="view-quiz?guid=<?php echo $quiz->guid ?>" class="btn btn-warning" role="button">Cancel</a>
                  <?php } ?>
		            </div>
		          </div>
		        </form>
		        <a href="list-quizzes/" class="btn btn-primary btn-xs active" role="button">Back to Quizzes</a>
						<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Trim').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
			</div> <!-- end .entry_content -->
      
      <?php } else { ?>
        <p>This quiz is locked for editing, as responses have been received.</p>
        <div class="bootstrap">
    	    <div class="form-group">
            <p>
              <a href="view-quiz?guid=<?php echo $quiz->guid ?>" class="btn btn-info btn-sm active" role="button">View Quiz</a> 
            <p><a href="configure-quiz" class="btn btn-info btn-xs active" role="button">New Quiz</a> | <a href="list-quizzes/" class="btn btn-primary btn-xs active" role="button">Back to Quizzes</a></p>
          </div>
        </div>
      <?php } ?>
		</div> <!-- end .post-content -->
	</article> <!-- end .post -->
<?php endwhile; // end of the loop. ?>