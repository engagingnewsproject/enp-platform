<?php 
if ( !$quiz || $quiz->quiz_type == "slider" ) { 
  if ( $quiz ) {
    $wpdb->query('SET OPTION SQL_BIG_SELECTS = 1');
    $slider_options = $wpdb->get_row("
      SELECT po_high.value 'slider_high', po_low.value 'slider_low', po_start.value 'slider_start', po_increment.value 'slider_increment', po_high_answer.value 'slider_high_answer', po_low_answer.value 'slider_low_answer', po_correct_answer.value 'slider_correct_answer', po_label.value 'slider_label', po_correct_message.value 'correct_answer_message', po_incorrect_message.value 'incorrect_answer_message'
      FROM enp_quiz_options po
      LEFT OUTER JOIN enp_quiz_options po_high ON po_high.field = 'slider_high' AND po.quiz_id = po_high.quiz_id
      LEFT OUTER JOIN enp_quiz_options po_low ON po_low.field = 'slider_low' AND po.quiz_id = po_low.quiz_id
      LEFT OUTER JOIN enp_quiz_options po_start ON po_start.field = 'slider_start' AND po.quiz_id = po_start.quiz_id
      LEFT OUTER JOIN enp_quiz_options po_increment ON po_increment.field = 'slider_increment' AND po.quiz_id = po_increment.quiz_id
      LEFT OUTER JOIN enp_quiz_options po_high_answer ON po_high_answer.field = 'slider_high_answer' AND po.quiz_id = po_high_answer.quiz_id
      LEFT OUTER JOIN enp_quiz_options po_low_answer ON po_low_answer.field = 'slider_low_answer' AND po.quiz_id = po_low_answer.quiz_id
      LEFT OUTER JOIN enp_quiz_options po_correct_answer ON po_correct_answer.field = 'slider_correct_answer' AND po.quiz_id = po_correct_answer.quiz_id
      LEFT OUTER JOIN enp_quiz_options po_label ON po_label.field = 'slider_label' AND po.quiz_id = po_label.quiz_id
      LEFT OUTER JOIN enp_quiz_options po_correct_message ON po_correct_message.field = 'correct_answer_message' AND po.quiz_id = po_correct_message.quiz_id
      LEFT OUTER JOIN enp_quiz_options po_incorrect_message ON po_incorrect_message.field = 'incorrect_answer_message' AND po.quiz_id = po_incorrect_message.quiz_id
      WHERE po.quiz_id = " . $quiz->ID . "
      GROUP BY po.quiz_id;");
  } else {
    $slider_options = array();
  }
?>
<div class="slider-usability-note alert alert-warning"><span class="glyphicon glyphicon-warning-sign"></span><span><b> Usability Note</b>: The quiz now has <span id="slider-selectable-values"></span> selectable values.  Please consider increasing the increment value or decreasing the slider range to allow for easier selection of values.  The max suggested is 100.</span></div>
<div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
  <label for="slider-low" class="col-sm-3">Range of<br>Slider Display <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Define the upper and lower selectable values for the slider. Max range is -9999 to 9999.  Please use a label for larger values (ie. million)."></span></label>
  <div class="col-sm-4">
    <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-low" id="slider-low" placeholder="Enter low slider value" value="<?php echo $slider_options ? $slider_options->slider_low : 0; ?>">
  </div>
  <label for="slider-high" class="col-sm-1">to</label>
  <div class="col-sm-4">
    <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-high" id="slider-high" placeholder="Enter top slider value" value="<?php echo $slider_options ? $slider_options->slider_high : 10; ?>">
  </div>
</div>

<div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
  <label for="slider-correct-answer" class="col-sm-3">Correct Value<br>for Slider <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Define the exact answer value for the slider."></span></label>
  <div class="col-sm-4">
    <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-correct-answer" id="slider-correct-answer" placeholder="Enter correct slider value" value="<?php echo $slider_options ? $slider_options->slider_correct_answer : 0; ?>">
  </div>
</div>  

<?php 
$use_slider_range = false;

if ( $slider_options &&
   ( $slider_options->slider_correct_answer != $slider_options->slider_low_answer ||
     $slider_options->slider_correct_answer != $slider_options->slider_high_answer) ) {
       $use_slider_range = true;
} 
?>
<div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
  <label for="use-slider-range" class="col-sm-3">Count a range<br>of values<br>as correct <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="To allow a range of values to count as correct, check the box"></span></label>
  <div class="col-sm-9">
    <input type="checkbox" name="use-slider-range" id="use-slider-range" value="use-slider-range" <?php echo $use_slider_range ? "checked" : ""; ?>>
  </div>
</div>

<div class="form-group slider-high-answer-element" <?php 
  echo $use_slider_range || !$quiz ? "style='display:none'" : ""; ?>>
  <label for="slider-low-answer" class="col-sm-3">Range of <br>Correct Values <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Define the upper and lower limits for the slider.  For an exact value, make these values match."></span></label>
  <div class="col-sm-4">
    <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-low-answer" id="slider-low-answer" placeholder="Enter low slider value" value="<?php echo $slider_options ? $slider_options->slider_low_answer : 0; ?>">
  </div>
  <label for="slider-high-answer" class="col-sm-1">to</label>
  <div class="col-sm-4">
    <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-high-answer" id="slider-high-answer" placeholder="Enter top slider value" value="<?php echo $slider_options ? $slider_options->slider_high_answer : 0; ?>">
  </div>
</div>

<div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
  <label for="slider-start" class="col-sm-3">Default Slider<br/>Start Value <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify what value the slider selector should start on."></span></label>
  <div class="col-sm-9">
    <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-start" id="slider-start" placeholder="Enter start value" value="<?php echo $slider_options ? $slider_options->slider_start : 5; ?>">
  </div>
</div>

<div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
  <label for="slider-increment" class="col-sm-3">Slider Increment<br>Value <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify how much the values should change when using the slider."></span></label>
  <div class="col-sm-9">
    <input type="text" class="form-control bfh-number" data-min="-9999" name="slider-increment" id="slider-increment" placeholder="Enter increment value" value="<?php echo $slider_options ? $slider_options->slider_increment : 1; ?>">
  </div>
</div>

<div class="form-group slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
  <label for="slider-label" class="col-sm-3">Slider Label <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the label that will appear behind the numbers on the slider.  Prevent overlap with longer labels by increasing the quiz width."></span></label>
  <div class="col-sm-9">
    <input type="text" class="form-control" name="slider-label" id="slider-label" placeholder="Enter Slider Label" value="<?php echo $slider_options ? esc_attr($slider_options->slider_label) : '%'; ?>">
  </div>
</div>

<hr class="bootstrap slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">
<h3 class="slider-answers slider-answers" style="<?php echo !$quiz ? "display:none" : ""; ?>">Slider preview</h3>

<div class="form-group slider-answers quiz-display" style="<?php echo !$quiz ? "display:none" : ""; ?>"> 
  <div class="col-xs-2 slider-value">
    <span class="badge" id="slider-value-label"><?php echo $slider_options->slider_start . $slider_options->slider_label; ?></span>
  </div>
  <div class="col-xs-10">
    <?php include(locate_template('self-service-quiz/slider-display.php'));  ?>
  </div>
</div>
<hr class="bootstrap">

<?php } ?>