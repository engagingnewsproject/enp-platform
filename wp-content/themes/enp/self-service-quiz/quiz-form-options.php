<div class="panel panel-info">
  <div class="panel-heading">Quiz Options</div>
  <div class="panel-body">

    <!-- BEGIN QUIZ TITLE -->
    <?php //if ( !$quiz ) {
    if ( $first_question == true) {
    ?>
    <div class="form-group">
      <label for="input-title" class="col-sm-3">Title <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify a name to help you identify the quiz"></span></label>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="input-title" id="input-title" placeholder="Enter Title" value="<?php echo esc_attr($quiz->title); ?>">
      </div>
    </div>
    <?php } ?>
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

    <!-- BEGIN QUIZ IMAGE SELECTION -->
    
    <div class="form-group">
      <label for="quiz-image-url" class="col-sm-3">Image <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Upload or select image to be placed beneath the quiz quesiton."></span></label>
        <?php $img_post_id = get_quiz_option( $quiz->ID, 'quiz_image_wp_post_id' );?>
        <div id="quiz_image_wrapper" class="col-sm-9">
          <span id="quiz_image_preview"><?php echo !empty($img_post_id) ? wp_get_attachment_image($img_post_id,"thumbnail") : ''; ?></span>&nbsp;
          <input type="hidden" name="quiz-image-url" id="quiz-image-url" class="regular-text" value="<?php echo !empty($img_post_id) ? wp_get_attachment_image_src($img_post_id,"thumbnail") : ''; ?>">
          <button name="upload-btn" id="upload-btn" class="btn btn-warning"><i class="fa fa-picture-o"></i> Select Image</button>
          <input type="hidden" class="form-control" rows="3" name="quiz-image-wp-post-id" id="quiz-image-wp-post-id" value="<?php echo !empty($img_post_id) ? $img_post_id : ''; ?>">
        </div>
      
      
      <script type="text/javascript">
        jQuery(document).ready(function($){
          $('#upload-btn').click(function(e) {
                e.preventDefault();
                var image = wp.media({ 
                    title: 'Upload Image',
                    // mutiple: true if you want to upload multiple files at once
                    multiple: false
                }).open()
                .on('select', function(e){
                    // This will return the selected image from the Media Uploader, the result is an object
                    var uploaded_image = image.state().get('selection').first();
                    // We convert uploaded_image to a JSON object to make accessing it easier
                    // Output to the console uploaded_image
                    var img = uploaded_image.toJSON();
                    console.log(img);
                    // Let's assign the url value to the input field
                    $('#quiz-image-url').val(img.url);
                    $('#quiz-image-wp-post-id').val(img.id);

                    $('#quiz_image_preview').html('<img src="'+img.url+'" alt="'+img.alt+'" style="max-height: 72px; margin: 0 1em 0 0;">');
                });
            });
        });
      </script>
    </div>


    <!-- END QUIZ IMAGE SELECTION -->

    <!-- BEGIN QUIZ TYPE -->
    <?php if ( !$quiz ) { ?>
    <div class="form-group quiz-type">
      <label class="col-sm-3">Question Type <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="top" title="Specify how to capture quiz responses"></span></label>
      <div class="col-sm-9">
        <div class="input-group">
          <span class="input-group-addon">
            <input type="radio" name="quiz-type" id="qt-multiple-choice" value="multiple-choice">
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
<!--        <label for="input-title" class="col-sm-3">Quiz Type</label>-->
          <label for="input-title" class="col-sm-3">Question Type</label>
        <div class="col-sm-9">
          <input type="hidden" name="quiz-type" id="quiz-type" value="<?php echo $quiz->quiz_type == "slider" ? "slider" : "multiple-choice"; ?>">
          <p><b><?php echo $quiz->quiz_type == "slider" ? "Slider" : "Multiple Choice"; ?></b></p>
        </div>
      </div>
    <?php } ?>
    <!-- END QUIZ TYPE -->
  </div>
</div>
