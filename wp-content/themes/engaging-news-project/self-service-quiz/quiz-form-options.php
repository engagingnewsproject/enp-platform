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
        <!-- <input type="text" class="form-control" name="input-question" id="input-question" placeholder="Enter Quiz Question" value="<?php //echo esc_attr($quiz->question); ?>"> -->
        <textarea class="form-control" rows="3" name="input-question" id="input-question" placeholder="Enter Quiz Question"><?php echo $question_text == "Enter Quiz Question" ? "" : $question_text; ?></textarea>
      </div>
    </div>
    <!-- END QUIZ QUESTION -->

    <!-- BEGIN QUIZ TYPE -->
    <?php if ( !$quiz ) { ?>
    <div class="form-group quiz-type">
      <label class="col-sm-3">Quiz Type <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify how to capture quiz responses"></span></label>
      <div class="col-sm-9">
        <div class="input-group">
          <span class="input-group-addon">
            <input type="radio" name="quiz-type" id="qt-multiple-choice" value="multiple-choice" checked>
          </span>
          <label for="qt-multiple-choice" class="form-control quiz-type-label" id="quiz-type-label-mc">Multiple Choice</label>
        </div><!-- /input-group -->
      </div>
    </div>
    <div class="form-group quiz-type">
      <label class="col-sm-3"></label>
      <div class="col-sm-9">
        <div class="input-group">
          <span class="input-group-addon">
            <input type="radio" name="quiz-type" id="qt-slider" value="slider">
          </span>
          <label for="qt-slider" class="form-control quiz-type-label" id="quiz-type-label-slider">Slider</label>
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
