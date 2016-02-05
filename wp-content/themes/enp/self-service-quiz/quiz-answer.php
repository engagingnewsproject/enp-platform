<?php if ( $quiz_response->is_correct == 1 ) { ?>
  <h3 style="margin-top:0;"><span class="glyphicon glyphicon-check"></span> Congratulations!</h3><!-- ||KVB -->
  <div class="alert alert-success" style="width:80%;">
    <p><b>Question:</b> <span class="quiz-question-preview"><?php echo $question_text; ?></span></p>
    <p><b>Answer:</b> <span class="correct-answer-message" id="correct-answer"><?php echo stripslashes($answer_message); ?></span></p>
  </div>
<?php } else { ?>
  <h3 style="margin-top:0;"><span class="glyphicon glyphicon-info-sign"></span> Sorry!</h3><!-- ||KVB -->
  <div class="alert alert-info" style="width:80%;">
    <p><b>Question:</b> <span class="quiz-question-preview"><?php echo $question_text; ?></span></p>
    <p><b>Answer:</b> <span class="incorrect-answer-message"><?php echo stripslashes($answer_message); ?></span></p>
  </div>
<?php } ?>

<script>

  // We only want this code to run on the iframe page
  if(document.querySelector('.quiz-iframe')) {
  	var answer = '';

  	if (document.getElementById('correct-answer')) {
  		answer = 'correct';
  	} else {
  		answer = 'incorrect';
  	}

  	var key = (parseInt(localStorage.length) + 1).toString();
  	localStorage.setItem(key, answer);
  }

</script>
