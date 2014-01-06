<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
	<article class="entry post clearfix">
		<h1 class="main_title"><?php the_title(); ?></h1>

		<div class="post-content clearfix">
      <? 
      if ( $_GET["edit_guid"] ) {
        $quiz = $wpdb->get_row("SELECT * FROM enp_quiz WHERE guid = '" . $_GET["edit_guid"] . "'" ); 
      }

      if ( !$quiz->locked ) {
      ?>
			<div class="entry_content bootstrap <?php echo $quiz ? "edit_quiz" : "new_quiz"?>">
		        <form id="quiz-form" class="form-horizontal" role="form" method="post" action="<?php echo get_stylesheet_directory_uri(); ?>/self-service-quiz/include/process-quiz-form.php">
		          <input type="hidden" name="input-id" id="input-id" value="<?php echo $quiz->ID; ?>">
				      <input type="hidden" name="input-guid" id="input-guid" value="<?php echo $quiz->guid; ?>">
              <div class="panel panel-info">
                <div class="panel-heading">Quiz Options</div>
                <div class="panel-body">
              
                  <!-- BEGIN QUIZ TITLE -->
    		          <div class="form-group">
    		            <label for="input-title" class="col-sm-3">Title <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify a name to help you identify the quiz"></span></label>
    		            <div class="col-sm-9">
    		              <input type="text" class="form-control" name="input-title" id="input-title" placeholder="Enter Title" value="<?php echo esc_attr($quiz->title); ?>">
    		            </div>
    		          </div>
                  <!-- END QUIZ TITLE -->
              
                  <!-- BEGIN QUIZ QUESTION -->
    		          <div class="form-group">
    		            <label for="input-question" class="col-sm-3">Question <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the question the quiz will ask"></span></label>
    		            <div class="col-sm-9">
    		              <input type="text" class="form-control" name="input-question" id="input-question" placeholder="Enter Quiz Question" value="<?php echo esc_attr($quiz->question); ?>">
    		            </div>
    		          </div>
                  <!-- END QUIZ QUESTION -->
        
                  <!-- BEGIN QUIZ TYPE -->
                  <?php if ( !$quiz ) { ?>
    		          <div class="form-group quiz-type">
    		            <label for="quiz-type" class="col-sm-3">Quiz Type <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify how to capture quiz responses"></span></label>
    		            <div class="col-sm-9">
                      <div class="input-group">
                        <span class="input-group-addon">
                          <input type="radio" name="quiz-type" id="qt-multiple-choice" value="multiple-choice" checked>
                        </span>
                        <label for="quiz-type" class="form-control quiz-type-label" id="quiz-type-label-mc">Multiple Choice</label>
                      </div><!-- /input-group -->
    		            </div>
    		          </div>
    		          <div class="form-group quiz-type">
                    <label for="quiz-type" class="col-sm-3"></label>
    		            <div class="col-sm-9">
                      <div class="input-group">
                        <span class="input-group-addon">
                          <input type="radio" name="quiz-type" id="qt-slider" value="slider">
                        </span>
                        <label for="quiz-type" class="form-control quiz-type-label" id="quiz-type-label-slider">Slider</label>
                      </div><!-- /input-group -->
    		            </div>
    		          </div>
                  <?php } else { ?>
      		          <div class="form-group">
      		            <label for="input-title" class="col-sm-3">Quiz Type</label>
      		            <div class="col-sm-9">
                        <input type="hidden" name="quiz-type" id="quiz-type" value="<?php echo $quiz->quiz_type == "slider" ? "slider" : "multiple-choice"; ?>">
      		              <p><b><?php echo $quiz->quiz_type == "slider" ? "Slider" : "Multiple Choice"; ?></b></p>
      		            </div>
      		          </div>
                  <?php } ?>
                  <!-- END QUIZ TYPE -->
		            </div>
		          </div>
              
              <div class="panel panel-info">
                <div class="panel-heading">Quiz Answers</div>
                <div class="panel-body">
      
                  <?php if ( !$quiz || $quiz->quiz_type == "multiple-choice" ) { ?>
    		          <div class="form-group multiple-choice-answers">
    		            <label for="mc-answer-1" class="col-sm-3">Answers <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Enter one or more answers "></span></label>
    		            <div class="col-sm-9">
                      <?php 
                      $mc_correct_answer;
                  
                      if ( $quiz->ID ) {
                        $mc_correct_answer = $wpdb->get_var("
                          SELECT value FROM enp_quiz_options
                          WHERE field = 'correct_option' AND quiz_id = " . $quiz->ID);
                  
                        $mc_answers = $wpdb->get_results("
                          SELECT * FROM enp_quiz_options
                          WHERE field = 'answer_option' AND quiz_id = " . $quiz->ID . 
                          " ORDER BY `display_order`");
                      }
                    
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
                            <input type="text" class="form-control <?php echo $currect_answer_id == $mc_correct_answer ? "correct-option" : $mc_correct_answer; ?>" name="mc-answer-<?php echo $key; ?>" id="mc-answer-<?php echo $key; ?>" placeholder="Enter Answer" value="<?php echo esc_attr($mc_answer->value); ?>">
                            <span class="glyphicon glyphicon-remove remove-answer" <?php echo $key == "1" ? 'data-toggle="tooltip" title="Click to remove the answer."' : ''; ?>></span>
                          </li>
                        <?php 
                        }
                        ?>
                      </ul>
                      <ul class="mc-answers additional-answer-wrapper">
                        <li class="ui-state-default additional-answer">
                          <input type="text" class="form-control" placeholder="Click to add additional answer" value="">
                      </li>
                    </ul>
    		            </div>
    		          </div>
              
                  <?php } ?>
                

                  <?php 
                  if ( !$quiz || $quiz->quiz_type == "slider" ) { 
                    if ( $quiz ) {
                      $wpdb->query('SET OPTION SQL_BIG_SELECTS = 1');
                      $slider_options = $wpdb->get_row("
                        SELECT po_high.value 'slider_high', po_low.value 'slider_low', po_start.value 'slider_start', po_increment.value 'slider_increment', po_high_answer.value 'slider_high_answer', po_low_answer.value 'slider_low_answer', po_label.value 'slider_label'
                        FROM enp_quiz_options po
                        LEFT OUTER JOIN enp_quiz_options po_high ON po_high.field = 'slider_high' AND po.quiz_id = po_high.quiz_id
                        LEFT OUTER JOIN enp_quiz_options po_low ON po_low.field = 'slider_low' AND po.quiz_id = po_low.quiz_id
                        LEFT OUTER JOIN enp_quiz_options po_start ON po_start.field = 'slider_start' AND po.quiz_id = po_start.quiz_id
                        LEFT OUTER JOIN enp_quiz_options po_increment ON po_increment.field = 'slider_increment' AND po.quiz_id = po_increment.quiz_id
                        LEFT OUTER JOIN enp_quiz_options po_high_answer ON po_high_answer.field = 'slider_high_answer' AND po.quiz_id = po_high_answer.quiz_id
                        LEFT OUTER JOIN enp_quiz_options po_low_answer ON po_low_answer.field = 'slider_low_answer' AND po.quiz_id = po_low_answer.quiz_id
                        LEFT OUTER JOIN enp_quiz_options po_label ON po_label.field = 'slider_label' AND po.quiz_id = po_label.quiz_id
                        WHERE po.quiz_id = " . $quiz->ID . "
                        GROUP BY po.quiz_id;");
                    } else {
                      $slider_options = [];
                    }
                  ?>
              
    		          <div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
    		            <label for="slider-range-values" class="col-sm-3">Range of Values for Slider <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Define the upper and lower selectable values for the slider."></span></label>
    		            <div class="col-sm-4">
                      <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-low" id="slider-low" placeholder="Enter low slider value" value="<?php echo $slider_options ? $slider_options->slider_low : 0; ?>">
    		            </div>
                    <label for="slider-range-to" class="col-sm-1">to</label>
    		            <div class="col-sm-4">
                      <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-high" id="slider-high" placeholder="Enter top slider value" value="<?php echo $slider_options ? $slider_options->slider_high : 10; ?>">
    		            </div>
    		          </div>
              
    		          <div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
    		            <label for="slider-answer-range" class="col-sm-3">Range of Correct Values for Slider <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Define the upper and lower limits for the slider.  For an exact value, make these values match."></span></label>
    		            <div class="col-sm-4">
                      <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-low-answer" id="slider-low-answer" placeholder="Enter low slider value" value="<?php echo $slider_options ? $slider_options->slider_low_answer : 0; ?>">
    		            </div>
                    <label for="slider-range-to" class="col-sm-1">to</label>
    		            <div class="col-sm-4">
                      <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-high-answer" id="slider-high-answer" placeholder="Enter top slider value" value="<?php echo $slider_options ? $slider_options->slider_high_answer : 0; ?>">
    		            </div>
    		          </div>  
              
    		          <div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
    		            <label for="slider-start" class="col-sm-4">Slider Start Value <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify what value the slider selector should start on."></span></label>
    		            <div class="col-sm-8">
                      <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-start" id="slider-start" placeholder="Enter start value" value="<?php echo $slider_options ? $slider_options->slider_start : 5; ?>">
    		            </div>
    		          </div>
              
    		          <div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
    		            <label for="slider-increment" class="col-sm-4">Slider Increment Value <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify how much the values should change when using the slider."></span></label>
    		            <div class="col-sm-8">
                      <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-increment" id="slider-increment" placeholder="Enter increment value" value="<?php echo $slider_options ? $slider_options->slider_increment : 1; ?>">
    		            </div>
    		          </div>
              
    		          <div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
    		            <label for="slider-label" class="col-sm-4">Slider Label <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the label that will appear behind the numbers on the slider."></span></label>
    		            <div class="col-sm-8">
    		              <input type="text" class="form-control" name="slider-label" id="slider-label" placeholder="Enter Slider Label" value="<?php echo $slider_options ? esc_attr($slider_options->slider_label) : '%'; ?>">
    		            </div>
    		          </div>
              
                  <span class="bootstrap slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>"><hr></span>
                  <h3 class="slider-answers slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">Slider preview</h3>
              
                  <div class="form-group slider-answers quiz-display" style="<?php echo !$quiz ? "display:none" : ""; ?>"> 
                    <div class="col-xs-2 slider-value">
                      <span class="badge" id="slider-value-label"><?php echo $slider_options->slider_start . $slider_options->slider_label; ?></span>
                    </div>
                    <div class="col-xs-10">
              	      <?php include(locate_template('self-service-quiz/slider-display.php'));  ?>
                    </div>
                  </div>
                  <span class="bootstrap"><hr></span>
              
                  <?php 
                  } 
                  ?>
                </div>
              </div>
              
              <?php
              if ( $quiz->ID ) {
                $quiz_background_color = $wpdb->get_var("
                  SELECT value FROM enp_quiz_options
                  WHERE field = 'quiz_background_color' AND quiz_id = " . $quiz->ID);
              
                $quiz_text_color = $wpdb->get_var("
                  SELECT value FROM enp_quiz_options
                  WHERE field = 'quiz_text_color' AND quiz_id = " . $quiz->ID);
              
                $quiz_display_width = $wpdb->get_var("
                  SELECT value FROM enp_quiz_options
                  WHERE field = 'quiz_display_width' AND quiz_id = " . $quiz->ID);
              
                $quiz_display_height = $wpdb->get_var("
                  SELECT value FROM enp_quiz_options
                  WHERE field = 'quiz_display_height' AND quiz_id = " . $quiz->ID);
                
                // $quiz_display_padding = $wpdb->get_var("
                //   SELECT value FROM enp_quiz_options
                //   WHERE field = 'quiz_display_padding' AND quiz_id = " . $quiz->ID);
                
                // $quiz_display_border = $wpdb->get_var("
                //   SELECT value FROM enp_quiz_options
                //   WHERE field = 'quiz_display_border' AND quiz_id = " . $quiz->ID);
                
                $quiz_show_title = $wpdb->get_var("
                  SELECT value FROM enp_quiz_options
                  WHERE field = 'quiz_show_title' AND quiz_id = " . $quiz->ID);

                $quiz_display_css = $wpdb->get_var("
                  SELECT value FROM enp_quiz_options
                  WHERE field = 'quiz_display_css' AND quiz_id = " . $quiz->ID);
              }
              
              ?>
              
              <div class="panel panel-info">
                <div class="panel-heading">Styling options <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Optional styling configuration for the quiz"></span></div>
                <div class="panel-body">
    		          <div class="form-group">
    		            <label for="quiz-background-color" class="col-sm-4">Background Color <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify a web color hex code"></span></label>
    		            <div class="col-sm-8">
    		              <input type="text" class="form-control" name="quiz-background-color" id="quiz-background-color" placeholder="Enter Background Color" value="<?php echo $quiz_background_color ? esc_attr($quiz_background_color) : "#ffffff" ; ?>">
    		            </div>
    		          </div>
    		          <div class="form-group">
    		            <label for="quiz-text-color" class="col-sm-4">Text Color <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify a web color hex code"></span></label>
    		            <div class="col-sm-8">
    		              <input type="text" class="form-control" name="quiz-text-color" id="quiz-text-color" placeholder="Enter Text Color" value="<?php echo $quiz_text_color ? esc_attr($quiz_text_color) : "#000000" ; ?>">
    		            </div>
    		          </div>
    		          <!-- <div class="form-group">
                    <label for="quiz-display-border" class="col-sm-4">Border <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify CSS style border"></span></label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" name="quiz-display-border" id="quiz-display-border" placeholder="Enter CSS Border" value="<?php //echo $quiz_display_border ? $quiz_display_border : "1px black solid" ; ?>">
                    </div>
                  </div> -->
    		          <div class="form-group">
    		            <label for="quiz-display-width" class="col-sm-4">Display Width <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the width in px or %"></span></label>
    		            <div class="col-sm-8">
    		              <input type="text" class="form-control" name="quiz-display-width" id="quiz-display-width" placeholder="Enter Display Width" value="<?php echo $quiz_display_width ? esc_attr($quiz_display_width) : "336px" ; ?>">
    		            </div>
    		          </div>
    		          <div class="form-group">
    		            <label for="quiz-display-height" class="col-sm-4">Display Height <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the height in px or %"></span></label>
    		            <div class="col-sm-8">
    		              <input type="text" class="form-control" name="quiz-display-height" id="quiz-display-height" placeholder="Enter Display Height" value="<?php echo $quiz_display_height ? esc_attr($quiz_display_height) : "280px" ; ?>">
    		            </div>
    		          </div>
    		          <div class="form-group">
    		            <label for="quiz-display-css" class="col-sm-4">Custom CSS <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify CSS to be applied to the quiz.  Note that this will override the settings above."></span></label>
    		            <div class="col-sm-8">
    		              <textarea class="form-control" rows="5" name="quiz-display-css" placeholder="Enter Custom CSS (eg. border: 1px black solid;color:#00000;)"><?php echo $quiz_display_css ? esc_attr($quiz_display_css) : "" ; ?></textarea>
    		            </div>
    		          </div>
    		          <!-- <div class="form-group">
                    <label for="quiz-display-padding" class="col-sm-4">Display Padding <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the padding in pixels"></span></label>
                    <div class="col-sm-8">
                      <input type="text" class="form-control" name="quiz-display-padding" id="quiz-display-padding" placeholder="Enter Display Padding" value="<?php //echo $quiz_display_padding ? $quiz_display_padding : "15px" ; ?>">
                    </div>
                  </div> -->
    		          <div class="form-group">
    		            <label for="quiz-show-title" class="col-sm-4">Display Title <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Tick the box to show the title with the quiz"></span></label>
    		            <div class="col-sm-8">
                      <div class="input-group">
                        <span class="input-group-addon">
                          <input type="checkbox" name="quiz-show-title" id="quiz-show-title" <?php echo $quiz_show_title ? "checked": ""; ?>>
                        </span>
                        <label for="quiz-show-title" class="form-control quiz-type-label" id="quiz-show-title">Show Title</label>
                      </div><!-- /input-group -->
    		            </div>
    		          </div>
                </div>
              </div>
              
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
        <p>This quiz is locked for editing, as responses have been recieved.</p>
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