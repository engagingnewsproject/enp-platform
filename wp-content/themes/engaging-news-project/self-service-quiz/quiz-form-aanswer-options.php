<?php

$default_mc_correct_answer_message = "Your answer of [user_answer] is correct!"; 
$default_mc_incorrect_answer_message = "Your answer is [user_answer], but the correct answer is [correct_value]."; 

$default_slider_correct_answer_message = "Your answer of [user_answer] is correct!";   
$default_slider_incorrect_answer_message = "Your answer is [user_answer], but the correct answer is [correct_value]."; 

$default_slider_range_correct_answer_message = "Your answer of [user_answer] is within the acceptable range of [lower_range] to [upper_range], with the exact answer being [correct_value].";
$default_slider_range_incorrect_answer_message = "Your answer is [user_answer], but the correct answer is within the range of [lower_range] to [upper_range].  The exact answer is [correct_value]."; 


if ( $quiz->quiz_type == "multiple-choice" ) {
  // Handle Multiple Choice Answer
  $correct_answer_message = $mc_options->correct_answer_message ? $mc_options->correct_answer_message : "Your answer of [user_answer] is correct!"; 
  $incorrect_answer_message = $mc_options->incorrect_answer_message ? $mc_options->incorrect_answer_message : "Your answer is [user_answer], but the correct answer is [correct_value].";

  $quiz_response_option_value = $currect_mc_answer_value; 
  
} else if ( $slider_options && $use_slider_range ) {
  // Handle Slider Range Answer
  $correct_answer_message = $slider_options->correct_answer_message ? $slider_options->correct_answer_message : "Your answer of [user_answer] is within the acceptable range of [lower_range] to [upper_range], with the exact answer being [correct_value]."; 
  

  $incorrect_answer_message = $slider_options->incorrect_answer_message ? $slider_options->incorrect_answer_message : "Your answer is [user_answer], but the correct answer is within the range of [lower_range] to [upper_range].  The exact answer is [correct_value].";

  $quiz_response_option_value = $slider_options->slider_correct_answer; 
  
} else if ( $slider_options ) {
  // Handle Slider Exact Answer
  $correct_answer_message = $slider_options->correct_answer_message ? $slider_options->correct_answer_message : "Your answer of [user_answer] is correct!"; 
  
  $default_incorrect_answer_message = "Your answer is [user_answer], but the correct answer is [correct_value]."; 
  $incorrect_answer_message = $slider_options->incorrect_answer_message ? $slider_options->incorrect_answer_message : "Your answer is [user_answer], but the correct answer is [correct_value].";
  
  $quiz_response_option_value = $slider_options->slider_correct_answer; 
} else {
  // Handle Default
  $correct_answer_message = $slider_options->correct_answer_message ? $slider_options->correct_answer_message : "Your answer of [user_answer] is correct!"; 
  
  $incorrect_answer_message = $slider_options->incorrect_answer_message ? $slider_options->incorrect_answer_message : "Your answer is [user_answer], but the correct answer is [correct_value].";
  
  $quiz_response_option_value = "[user_answer]"; 
}

?>

<h3 class="bootstrap">Advanced Answer Settings - Optional</h3>

<!-- BEGIN CORRECT ANSWER MESSAGE -->
<div class="form-group">
  <div class="col-sm-3">
    <label for="input-correct-answer-message">Correct Answer Message <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the message to display for correct answers"></span></label>
  </div>
  <div class="col-sm-9">
    <input type="hidden" name="default-mc-correct-answer-message" id="default-mc-correct-answer-message" value="<?php echo $default_mc_correct_answer_message; ?>">
    <input type="hidden" name="default-slider-correct-answer-message" id="default-slider-correct-answer-message" value="<?php echo $default_slider_correct_answer_message; ?>">
    <input type="hidden" name="default-slider-range-correct-answer-message" id="default-slider-range-correct-answer-message" value="<?php echo $default_slider_range_correct_answer_message; ?>">
    <textarea class="form-control" rows="4" name="input-correct-answer-message" id="input-correct-answer-message" placeholder="Enter Correct Answer Message"><?php echo $correct_answer_message; ?></textarea>
    <button id="correct-answer-message-correct-value" class="btn btn-info btn-xs answer-message-button">Correct Value</button>
    <button id="correct-answer-message-user-answer" class="btn btn-info btn-xs answer-message-button">User Answer</button>
    <button id="correct-answer-message-lower-range" <?php echo $use_slider_range ? "" : "style='display:none'"; ?> class="btn btn-info btn-xs answer-message-button slider-options">Lower Range</button>
    <button id="correct-answer-message-upper-range" <?php echo $use_slider_range ? "" : "style='display:none'"; ?> class="btn btn-info btn-xs answer-message-button slider-options">Upper Range</button>
    <button id="correct-answer-message-reset" class="btn btn-warning btn-xs answer-message-button">Reset Default Value</button>
  </div>
</div>

<div class="form-group">
  <span class="col-sm-3">Correct Answer Message Preview</span>
  <div class="col-sm-9">
    <?php 
    $is_correct = true;
    
    if ( $quiz && $quiz->quiz_type == "multiple-choice" ) {
      $correct_answer_message = str_replace('[user_answer]', $currect_mc_answer_value, $correct_answer_message);
      $correct_answer_message = str_replace('[correct_value]', $currect_mc_answer_value, $correct_answer_message);
    } else if ( $quiz && $quiz->quiz_type == "slider" ) {
      $correct_answer_message = str_replace('[user_answer]', $slider_options->slider_correct_answer, $correct_answer_message);
      $correct_answer_message = str_replace('[lower_range]', $slider_options->slider_low_answer, $correct_answer_message);
      $correct_answer_message = str_replace('[upper_range]', $slider_options->slider_high_answer, $correct_answer_message);
      $correct_answer_message = str_replace('[correct_value]', $slider_options->slider_correct_answer, $correct_answer_message);
    }
    
    include(locate_template('self-service-quiz/quiz-answer.php')); 
    
    ?>
  </div>
</div>
<!-- END CORRECT QUIZ ANSWER MESSAGE -->

<!-- BEGIN INCORRECT ANSWER MESSAGE -->
<div class="form-group">
  <label for="input-incorrect-answer-message" class="col-sm-3">Incorrect Answer Message <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the message to display for incorrect answers"></span></label>
  <div class="col-sm-9">
    <input type="hidden" name="default-mc-incorrect-answer-message" id="default-mc-incorrect-answer-message" value="<?php echo $default_mc_incorrect_answer_message; ?>">
    <input type="hidden" name="default-slider-incorrect-answer-message" id="default-slider-incorrect-answer-message" value="<?php echo $default_slider_incorrect_answer_message; ?>">
    <input type="hidden" name="default-slider-range-incorrect-answer-message" id="default-slider-range-incorrect-answer-message" value="<?php echo $default_slider_range_incorrect_answer_message; ?>">
    <textarea class="form-control" rows="4" name="input-incorrect-answer-message" id="input-incorrect-answer-message" placeholder="Enter Incorrect Answer Message"><?php echo $incorrect_answer_message; ?></textarea>
    <button id="incorrect-answer-message-correct-value" class="btn btn-info btn-xs answer-message-button">Correct Value</button>
    <button id="incorrect-answer-message-user-answer" class="btn btn-info btn-xs answer-message-button">User Answer</button>
    <button id="incorrect-answer-message-lower-range" <?php echo $use_slider_range ? "" : "style='display:none'"; ?> class="btn btn-info btn-xs answer-message-button slider-options">Lower Range</button>
    <button id="incorrect-answer-message-upper-range" <?php echo $use_slider_range ? "" : "style='display:none'"; ?> class="btn btn-info btn-xs answer-message-button slider-options">Upper Range</button>
    <button id="incorrect-answer-message-reset" class="btn btn-warning btn-xs answer-message-button">Reset Default Value</button>
  </div>
</div>

<div class="form-group">
  <span class="col-sm-3">Incorrect Answer Message Preview</span>
  <div class="col-sm-9">
    <?php 
    $is_correct = false;
    
    if ( $quiz && $quiz->quiz_type == "multiple-choice" ) {
      $incorrect_answer_message = str_replace('[user_answer]', $incorrect_mc_answer_value, $incorrect_answer_message);
      $incorrect_answer_message = str_replace('[correct_value]', $currect_mc_answer_value, $incorrect_answer_message);
    } else if ( $quiz && $quiz->quiz_type == "slider" ) {
      // For exact slider, the slider high is the exact value
      $quiz_response_option_value = $slider_options->slider_high_answer + 1; 
      $incorrect_answer_message = str_replace('[user_answer]', $quiz_response_option_value, $incorrect_answer_message);
      $incorrect_answer_message = str_replace('[lower_range]', $slider_options->slider_low_answer, $incorrect_answer_message);
      $incorrect_answer_message = str_replace('[upper_range]', $slider_options->slider_high_answer, $incorrect_answer_message);
      $incorrect_answer_message = str_replace('[correct_value]', $slider_options->slider_correct_answer, $incorrect_answer_message);
    }
    
    include(locate_template('self-service-quiz/quiz-answer.php')); 
    ?>
  </div>
</div>
<!-- END INCORRECT ANSWER MESSAGE -->