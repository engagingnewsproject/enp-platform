<?php if ( $is_correct == 1) { ?>
  <h3><span class="glyphicon glyphicon-check"></span> Congratulations!</h3>
  <div class="alert alert-success">
    <p><b>Question:</b> <?php echo $question_text; ?></p>
    <span class="correct-answer-message"><?php echo $correct_answer_message; ?></span>
    <br/>
    
    <?php if ( $quiz_response_correct_option_id == -2 && !$exact_value ) { ?>
      Your answer of <i><?php echo $quiz_response_option_value ?></i> is within the correct range of <i><?php echo $display_answer ?></i>.
    <?php } else { ?>
      <i><?php echo $display_answer ?></i> is the correct answer!
    <?php } ?>
  </div>
<?php } else { ?>
  <h3><span class="glyphicon glyphicon-info-sign"></span> Sorry!</h3>
  <div class="alert alert-info">
    <p><b>Question:</b> <?php echo $question_text; ?></p>
    <span class="incorrect-answer-message"><?php echo $incorrect_answer_message; ?></span>
    <br/>
    <p>Your answer is <i><?php echo $quiz_response_option_value ?></i>, but the correct answer is <?php echo $exact_value ? "" : "within the range of "; ?><i><?php echo $display_answer ?></i>.</p>
  </div>
<?php } ?>