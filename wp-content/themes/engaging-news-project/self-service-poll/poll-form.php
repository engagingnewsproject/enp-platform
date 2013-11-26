<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
<? 
if ( $_GET["edit_guid"] ) {
  $poll = $wpdb->get_row("SELECT * FROM enp_poll WHERE guid = '" . $_GET["edit_guid"] . "'" ); 
}
?>
	<article class="entry post clearfix">
		<h1 class="main_title"><?php the_title(); ?></h1>

		<div class="post-content clearfix">

			<div class="entry_content bootstrap">
		        <form id="poll-form" class="form-horizontal" role="form" method="post" action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-poll/include/process-poll-form.php">
		          <input type="hidden" name="input-id" id="input-id" value="<?php echo $poll->id; ?>">
				  <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $poll->guid; ?>">
		          <div class="form-group">
		            <label for="input-title" class="col-sm-2">Title</label>
		            <div class="col-sm-10">
		              <input type="text" class="form-control" name="input-title" id="title" placeholder="Enter Title" value="<?php echo $poll->title; ?>">
		            </div>
		          </div>
        
              <?php if ( !$poll ) { ?>
		          <div class="form-group">
		            <label for="input-question" class="col-sm-2">Poll Type</label>
		            <div class="col-sm-10">
		              <div class="radio">
		                <label>
		                  <input type="radio" name="poll-type" id="optionsRadios1" value="multiple-choice" checked>
		                  Multiple Choice
		                </label>
		              </div>
		              <div class="radio">
		                <label>
		                  <input type="radio" name="poll-type" id="optionsRadios2" value="slider">
		                  Slider
		                  </label>
		              </div>
		            </div>
		          </div>
              <?php } else { ?>
  		          <div class="form-group">
  		            <label for="input-title" class="col-sm-2">Poll Type</label>
  		            <div class="col-sm-10">
  		              <p><?php echo $poll->poll_type == "slider" ? "Slider" : "Multiple Choice"; ?></p>
  		            </div>
  		          </div>
              <?php } ?>
      
		          <div class="form-group">
		            <label for="input-question" class="col-sm-2">Question</label>
		            <div class="col-sm-10">
		              <input type="text" class="form-control" name="input-question" id="question" placeholder="Enter Poll Question" value="<?php echo $poll->question; ?>">
		            </div>
		          </div>
              
		          <div class="form-group">
		            <label for="input-answer-1" class="col-sm-2">Answers</label>
		            <div class="col-sm-10">
		              <p>Coming soon!</p>
		            </div>
		          </div>
        
		          <div class="form-group">
		            <div class="col-sm-offset-2 col-sm-10">
		              <button type="submit" class="btn btn-primary"><?php echo $poll ? "Update Poll" : "Create Poll"; ?></button>
		            </div>
		          </div>
		        </form>
		        <a href="list-polls/" class="btn btn-primary btn-xs active" role="button">Back to polls</a>
						<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Trim').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
			</div> <!-- end .entry_content -->
		</div> <!-- end .post-content -->
	</article> <!-- end .post -->
<?php endwhile; // end of the loop. ?>