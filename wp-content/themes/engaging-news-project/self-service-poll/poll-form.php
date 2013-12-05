<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
<? 
if ( $_GET["edit_guid"] ) {
  $poll = $wpdb->get_row("SELECT * FROM enp_poll WHERE guid = '" . $_GET["edit_guid"] . "'" ); 
}
?>
	<article class="entry post clearfix">
		<h1 class="main_title"><?php the_title(); ?></h1>

		<div class="post-content clearfix">

			<div class="entry_content bootstrap <?php echo $poll ? "edit_poll" : "new_poll"?>">
		        <form id="poll-form" class="form-horizontal" role="form" method="post" action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-poll/include/process-poll-form.php">
		          <input type="hidden" name="input-id" id="input-id" value="<?php echo $poll->ID; ?>">
				      <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $poll->guid; ?>">
		          <div class="form-group">
		            <label for="input-title" class="col-sm-2">Title</label>
		            <div class="col-sm-10">
		              <input type="text" class="form-control" name="input-title" id="input-title" placeholder="Enter Title" value="<?php echo $poll->title; ?>">
		            </div>
		          </div>
              
		          <div class="form-group">
		            <label for="input-question" class="col-sm-2">Question</label>
		            <div class="col-sm-10">
		              <input type="text" class="form-control" name="input-question" id="input-question" placeholder="Enter Poll Question" value="<?php echo $poll->question; ?>">
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
      
              <?php if ( !$poll || $poll->poll_type == "multiple-choice" ) { ?>
		          <div class="form-group multiple-choice-answers">
		            <label for="input-answer-1" class="col-sm-2">Answers</label>
		            <div class="col-sm-10">
                  <?php 
                  $mc_correct_answer = $wpdb->get_var("
                    SELECT value FROM enp_poll_options
                    WHERE field = 'correct_option' AND poll_id = " . $poll->ID);
                  
                  $mc_answers = $wpdb->get_results("
                    SELECT * FROM enp_poll_options
                    WHERE field = 'answer_option' AND poll_id = " . $poll->ID . 
                    " ORDER BY `display_order`");
                    
                  $mc_answers = $mc_answers ? $mc_answers : ["1", "2", "3", "4"];
                  
                  $mc_answer_count = count($mc_answers);
                  ?>
                  <input type="hidden" name="mc-answer-count" id="mc-answer-count" value="<?php echo $mc_answer_count; ?>">
                  <input type="hidden" name="correct-option" id="correct-option" value="">
                  <ul id="mc-answers" class="mc-answers">
                    <?php 
                    foreach ( $mc_answers as $key=>$mc_answer ) { 
                      $key++;
                      $currect_answer_id = $mc_answer->ID ? $mc_answer->ID : -1;
                    ?>
                      <li class="ui-state-default">
                        <span class="glyphicon glyphicon-check select-answer" <?php echo $key == "1" ? 'data-toggle="tooltip" title="Click to select the correct answer."' : ''; ?>></span>
                        <span class="glyphicon glyphicon-move move-answer" <?php echo $key == "1" ? 'data-toggle="tooltip" data-placement="bottom" title="Click, hold, and drag to change the order."' : ''; ?>></span>
                        <input type="hidden" class="mc-answer-order" name="mc-answer-order-<?php echo $key; ?>" id="mc-answer-order-<?php echo $key; ?>" value="<?php echo $key; ?>">
                        <input type="hidden" class="mc-answer-id" name="mc-answer-id-<?php echo $key; ?>" id="mc-answer-id-<?php echo $key; ?>" value="<?php echo $mc_answer->ID; ?>">
                        <input type="text" class="form-control <?php echo $currect_answer_id == $mc_correct_answer ? "correct-option" : $mc_correct_answer; ?>" name="mc-answer-<?php echo $key; ?>" id="mc-answer-<?php echo $key; ?>" placeholder="Enter Answer" value="<?php echo $mc_answer->value; ?>">
                        <span class="glyphicon glyphicon-remove remove-answer" <?php echo $key == "1" ? 'data-toggle="tooltip" title="Click to remove the answer."' : ''; ?>></span>
                      </li>
                    <?php 
                    }
                    ?>
                  </ul>
                  <ul class="mc-answers additional-answer-wrapper">
                    <li class="ui-state-default additional-answer">
                      <input type="text" class="form-control" placeholder="Click to add answer" value="">
                  </li>
                </ul>
		            </div>
		          </div>
              
              <?php } ?>
                

              <?php 
              if ( !$poll || $poll->poll_type == "slider" ) { 
                if ( $poll ) {
                  $slider_options = $wpdb->get_row("
                    SELECT po_high.value 'slider_high', po_low.value 'slider_low', po_start.value 'slider_start', po_increment.value 'slider_increment', po_high_answer.value 'slider_high_answer', po_low_answer.value 'slider_low_answer'
                    FROM enp_poll_options po
                    LEFT OUTER JOIN enp_poll_options po_high ON po_high.field = 'slider_high' AND po.poll_id = po_high.poll_id
                    LEFT OUTER JOIN enp_poll_options po_low ON po_low.field = 'slider_low' AND po.poll_id = po_low.poll_id
                    LEFT OUTER JOIN enp_poll_options po_start ON po_start.field = 'slider_start' AND po.poll_id = po_start.poll_id
                    LEFT OUTER JOIN enp_poll_options po_increment ON po_increment.field = 'slider_increment' AND po.poll_id = po_increment.poll_id
                    LEFT OUTER JOIN enp_poll_options po_high_answer ON po_high_answer.field = 'slider_high_answer' AND po.poll_id = po_high_answer.poll_id
                    LEFT OUTER JOIN enp_poll_options po_low_answer ON po_low_answer.field = 'slider_low_answer' AND po.poll_id = po_low_answer.poll_id
                    WHERE po.poll_id = " . $poll->ID . "
                    GROUP BY po.poll_id");
                } else {
                  $slider_options = [];
                }
              ?>
              
		          <div class="form-group slider-answers" style="<?php echo !$poll ? "display:none" : ""; ?>">
		            <label for="slider-high" class="col-sm-4">Slider High</label>
		            <div class="col-sm-8">
                  <input type="text" class="form-control bfh-number" name="slider-high" id="slider-high" placeholder="Enter top slider value" value="<?php echo $slider_options ? $slider_options->slider_high : 10; ?>">
		            </div>
		          </div>
              
		          <div class="form-group slider-answers" style="<?php echo !$poll ? "display:none" : ""; ?>">
		            <label for="slider-low" class="col-sm-4">Slider Low</label>
		            <div class="col-sm-8">
                  <input type="text" class="form-control bfh-number" name="slider-low" id="slider-low" placeholder="Enter low slider value" value="<?php echo $slider_options ? $slider_options->slider_low : 0; ?>">
		            </div>
		          </div>
              
		          <div class="form-group slider-answers" style="<?php echo !$poll ? "display:none" : ""; ?>">
		            <label for="slider-start" class="col-sm-4">Slider Start Value</label>
		            <div class="col-sm-8">
                  <input type="text" class="form-control bfh-number" name="slider-start" id="slider-start" placeholder="Enter start value" value="<?php echo $slider_options ? $slider_options->slider_start : 5; ?>">
		            </div>
		          </div>
              
		          <div class="form-group slider-answers" style="<?php echo !$poll ? "display:none" : ""; ?>">
		            <label for="slider-increment" class="col-sm-4">Slider Increment Value</label>
		            <div class="col-sm-8">
                  <input type="text" class="form-control bfh-number" name="slider-increment" id="slider-increment" placeholder="Enter increment value" value="<?php echo $slider_options ? $slider_options->slider_increment : 1; ?>">
		            </div>
		          </div>
              
		          <div class="form-group slider-answers" style="<?php echo !$poll ? "display:none" : ""; ?>">
		            <label for="slider-high-answer" class="col-sm-4">Slider High Answer</label>
		            <div class="col-sm-8">
                  <input type="text" class="form-control bfh-number" name="slider-high-answer" id="slider-high-answer" placeholder="Enter top slider value" value="<?php echo $slider_options ? $slider_options->slider_high_answer : 0; ?>">
		            </div>
		          </div>
              
		          <div class="form-group slider-answers" style="<?php echo !$poll ? "display:none" : ""; ?>">
		            <label for="slider-low-answer" class="col-sm-4">Slider Low Answer</label>
		            <div class="col-sm-8">
                  <input type="text" class="form-control bfh-number" name="slider-low-answer" id="slider-low-answer" placeholder="Enter low slider value" value="<?php echo $slider_options ? $slider_options->slider_low_answer : 0; ?>">
		            </div>
		          </div>
              <div class="form-group slider-answers" style="<?php echo !$poll ? "display:none" : ""; ?>">
                <div class="col-xs-2">
          	      <input class="form-control" type="text" id="slider-value" value="<?php echo $slider_options ? $slider_options->slider_start : 5; ?>" />
                </div>
                <div class="col-xs-4">
          	      <div id="slider-wapper">
                    <input type="text" id="preview-slider" value="" data-slider-min="<?php echo $slider_options ? $slider_options->slider_low : 0; ?>" data-slider-max="<?php echo $slider_options ? $slider_options->slider_high : 10; ?>" data-slider-step="<?php echo $slider_options ? $slider_options->slider_increment : 1; ?>" data-slider-value="<?php echo $slider_options ? $slider_options->slider_start : 5; ?>" data-slider-orientation="horizontal" data-slider-tooltip="show" >
                  </div>
                </div>
              </div>
              
              <?php 
              } 
              ?>
        
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