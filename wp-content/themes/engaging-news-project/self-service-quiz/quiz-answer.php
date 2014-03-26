<?php if ( $is_correct == 1) { ?>
  <h3><span class="glyphicon glyphicon-check"></span> Congratulations!</h3>
  <div class="alert alert-success">
    <p><b>Question:</b> <span class="quiz-question-preview"><?php echo $question_text; ?></span></p>
    <p><b>Answer:</b> <span class="correct-answer-message"><?php echo $correct_answer_message; ?></span></p>
  </div>
<?php } else { ?>
  <h3><span class="glyphicon glyphicon-info-sign"></span> Sorry!</h3>
  <div class="alert alert-info">
    <p><b>Question:</b> <span class="quiz-question-preview"><?php echo $question_text; ?></span></p>
    <p><b>Answer:</b> <span class="incorrect-answer-message"><?php echo $incorrect_answer_message; ?></span></p>
  </div>
<?php } ?>