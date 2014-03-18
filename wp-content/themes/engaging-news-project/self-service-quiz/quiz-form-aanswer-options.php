<h3 class="bootstrap">Advanced Answer Settings - Optional</h3>

<!-- BEGIN CORRECT ANSWER MESSAGE -->
<div class="form-group">
  <div class="col-sm-3">
    <label for="input-correct-answer-message">Correct Answer Message <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the message to display for correct answers"></span></label>
  </div>
  <div class="col-sm-9">
    <textarea class="form-control" rows="4" name="input-correct-answer-message" id="input-correct-answer-message" placeholder="Enter Correct Answer Message"><?php echo $slider_options->correct_answer_message ? esc_attr($slider_options->correct_answer_message) : "Your answer of [user_answer] is within the acceptable range of [lower_range] to [upper_range], with the exact answer being [correct_value]."; ?></textarea>
    <button id="correct-answer-message-user-answer" class="btn btn-info btn-xs answer-message-button">User Answer</button>
    <button id="correct-answer-message-lower-range" class="btn btn-info btn-xs answer-message-button">Lower Range</button>
    <button id="correct-answer-message-upper-range" class="btn btn-info btn-xs answer-message-button">Upper Range</button>
    <button id="correct-answer-message-correct-value" class="btn btn-info btn-xs answer-message-button">Correct Value</button>
    <button id="correct-answer-message-reset" class="btn btn-warning btn-xs answer-message-button">Reset Default Value</button>
  </div>
</div>

<div class="form-group">
  <span class="col-sm-3">Correct Answer Message Preview</span>
  <div class="col-sm-9">
    <?php 
    $quiz_response_correct_option_id = 0; //TODO what should this be?
    $quiz_response_option_value = $slider_options->slider_correct_answer; 
    if ( $use_slider_range ) {
      $display_answer = $slider_options->slider_low_answer . ' to ' . $slider_options->slider_high_answer;
    } else {
      $display_answer = $slider_options->slider_correct_answer;
    }
    $exact_value = $use_slider_range;
    $is_correct = true;
    $correct_answer_message = $slider_options->correct_answer_message ?
      $slider_options->correct_answer_message : 
      "Your answer of [user_answer] is within the acceptable range of [lower_range] to [upper_range], with the exact answer being [correct_value].";
    
    $correct_answer_message = str_replace('[user_answer]',$quiz_response_option_value, $correct_answer_message);
    $correct_answer_message = str_replace('[lower_range]', $slider_options->slider_low_answer, $correct_answer_message);
    $correct_answer_message = str_replace('[upper_range]', $slider_options->slider_high_answer, $correct_answer_message);
    $correct_answer_message = str_replace('[correct_value]', $slider_options->slider_correct_answer, $correct_answer_message);
    
    include(locate_template('self-service-quiz/quiz-answer.php')); 
    
    ?>
  </div>
</div>
<!-- END CORRECT QUIZ ANSWER MESSAGE -->

<!-- BEGIN INCORRECT ANSWER MESSAGE -->
<div class="form-group">
  <label for="input-incorrect-answer-message" class="col-sm-3">Incorrect Answer Message <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the message to display for incorrect answers"></span></label>
  <div class="col-sm-9">
    <textarea class="form-control" rows="4" name="input-incorrect-answer-message" id="input-incorrect-answer-message" placeholder="Enter Incorrect Answer Message"><?php echo $slider_options->incorrect_answer_message ? esc_attr($slider_options->incorrect_answer_message) : "Your answer is [user_answer], but the correct answer is within the range of [lower_range] to [upper_range].  The exact answer is [correct_value]."; ?></textarea>
    <button id="incorrect-answer-message-user-answer" class="btn btn-info btn-xs answer-message-button">User Answer</button>
    <button id="incorrect-answer-message-lower-range" class="btn btn-info btn-xs answer-message-button">Lower Range</button>
    <button id="incorrect-answer-message-upper-range" class="btn btn-info btn-xs answer-message-button">Upper Range</button>
    <button id="incorrect-answer-message-correct-value" class="btn btn-info btn-xs answer-message-button">Correct Value</button>
    <button id="incorrect-answer-message-reset" class="btn btn-warning btn-xs answer-message-button">Reset Default Value</button>
  </div>
</div>

<div class="form-group">
  <span class="col-sm-3">Incorrect Answer Message Preview</span>
  <div class="col-sm-9">
    <?php 
    $quiz_response_correct_option_id = 0; //TODO what should this be?
    // TODO what if its not a range
    $quiz_response_option_value = $slider_options->slider_high_answer + 1; 
    if ( $use_slider_range ) {
      $display_answer = $slider_options->slider_low_answer . ' to ' . $slider_options->slider_high_answer;
    } else {
      $display_answer = $slider_options->slider_correct_answer;
    }
    $exact_value = $use_slider_range;
    $is_correct = false;
    $incorrect_answer_message = $slider_options->incorrect_answer_message ?
      $slider_options->incorrect_answer_message : 
      "Your answer is [user_answer], but the correct answer is within the range of [lower_range] to [upper_range].  The exact answer is [correct_value].";
    
    $incorrect_answer_message = str_replace('[user_answer]', $quiz_response_option_value, $incorrect_answer_message);
    $incorrect_answer_message = str_replace('[lower_range]', $slider_options->slider_low_answer, $incorrect_answer_message);
    $incorrect_answer_message = str_replace('[upper_range]', $slider_options->slider_high_answer, $incorrect_answer_message);
    $incorrect_answer_message = str_replace('[correct_value]', $slider_options->slider_correct_answer, $incorrect_answer_message);
    
    include(locate_template('self-service-quiz/quiz-answer.php')); 
    ?>
  </div>
</div>
<!-- END INCORRECT ANSWER MESSAGE -->