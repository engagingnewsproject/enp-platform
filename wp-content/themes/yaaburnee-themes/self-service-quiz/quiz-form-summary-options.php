<?php

//$summary_message = get_option('summary_message');
$getOptionRow = $wpdb->get_row("SELECT * FROM enp_quiz_options WHERE quiz_id = '" . $curr_quiz_id . "' && field = 'summary_message'");
$summary_message = $getOptionRow->value;
if ('' == $summary_message) {
    $summary_message = 'Thanks for taking our quiz!';
}
$summary_message_top = 'You got [x-correct] out of [x-total] correct!';
?>

<!-- <h3 class="bootstrap">Summary Message Settings - Optional</h3> -->

<!-- BEGIN Summary MESSAGE -->
<div class="form-group">
  <div class="col-sm-3">
    <label for="input-summary-message">Summary Message <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the Summary to display"></span></label>
  </div>
  <div class="col-sm-9">
    <input type="hidden" name="default-summary-message" id="default-summary-message" value="<?php echo $summary_message; ?>">
    <textarea class="form-control" rows="4" name="input-summary-message" id="input-summary-message" placeholder="Enter Summary Message"><?php echo $summary_message; ?></textarea>
  </div>
</div>

<div class="form-group">
  <span class="col-sm-3">Summary Message Preview</span>
  <div class="col-sm-9">
    <?php
    
    include(locate_template('self-service-quiz/quiz-summary.php'));
    
    ?>
  </div>
</div>
<!-- END Summary MESSAGE -->
