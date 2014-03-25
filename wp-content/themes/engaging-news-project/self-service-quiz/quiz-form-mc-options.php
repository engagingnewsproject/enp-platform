<?php if ( !$quiz || $quiz->quiz_type == "multiple-choice" ) { ?>
<div class="form-group multiple-choice-answers">
  <div class="col-sm-3">
          <label for="mc-answer-1">Answers <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Enter one or more answers "></span></label>
          <br/><br/>
          <span>Click <span class="glyphicon glyphicon-check select-answer"></span> to indicate the right answer.</span>
      </div>
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
  
    $mc_answers = $mc_answers ? $mc_answers : array("1", "2", "3");

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
          <input type="text" class="form-control mc-answer <?php echo $currect_answer_id == $mc_correct_answer ? "correct-option" : $mc_correct_answer; ?>" name="mc-answer-<?php echo $key; ?>" id="mc-answer-<?php echo $key; ?>" placeholder="Enter Answer" value="<?php echo esc_attr($mc_answer->value); ?>">
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