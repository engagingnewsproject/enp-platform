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

<div class="panel panel-info style-options">
  <div class="panel-heading style-panel">
    Styling options - Optional <!-- <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Optional styling configuration for the quiz"></span> -->
    <button type="button" class="btn btn-warning btn-sm reset-styling">Reset Style Settings</button>
  </div>
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
      <label for="quiz-display-width" class="col-sm-4">Display Width <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the width in px"></span></label>
      <div class="col-sm-8">
        <input type="text" class="form-control" name="quiz-display-width" id="quiz-display-width" placeholder="Enter Display Width" value="<?php echo $quiz_display_width ? esc_attr($quiz_display_width) : "336px"; ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="quiz-display-height" class="col-sm-4">Display Height <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify the height in px"></span></label>
      <div class="col-sm-8">
        <input type="text" class="form-control" name="quiz-display-height" id="quiz-display-height" placeholder="Enter Display Height" value="<?php echo $quiz_display_height ? esc_attr($quiz_display_height) : "280px"; ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="quiz-display-css" class="col-sm-4">Custom CSS <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify CSS to be applied to the quiz.  Note that this will override the settings above."></span></label>
      <div class="col-sm-8">
        <textarea class="form-control" rows="5" name="quiz-display-css" id="quiz-display-css" placeholder="Enter Custom CSS (eg. border: 1px black solid;color:#00000;)"><?php echo $quiz_display_css ? esc_attr($quiz_display_css) : "" ; ?></textarea>
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
          <label for="quiz-show-title" class="form-control quiz-type-label">Show Title</label>
        </div><!-- /input-group -->
      </div>
    </div>
  </div>
</div>