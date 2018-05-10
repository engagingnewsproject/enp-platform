<?php
    // get our question object
    $question = new Enp_quiz_Question($question_id);
    if($question_id === '{{question_id}}') {
        $question_i = '{{question_position}}';
    } else {
        $question_i = $question_i;
    }

    $question_image = $question->get_question_image();
?>

<section id="enp-question--<?php echo $question_id;?>" class="enp-question-content">
    <input class="enp-question-id" type="hidden" name="enp_question[<?php echo $question_i;?>][question_id]" value="<?php echo $question_id;?>" />

    <?php echo $Quiz_create->get_question_delete_button($question_id); ?>


    <div class="enp-question-inner enp-question">
        <label class="enp-label enp-question-title__label" for="question-title-<?php echo $question_i;?>">
            Question
        </label>
        <textarea id="question-title-<?php echo $question_i;?>" class="enp-textarea enp-question-title__textarea" name="enp_question[<?php echo $question_i;?>][question_title]" maxlength="6120" placeholder="Why can't we tickle ourselves?"/><?php echo $question->get_question_title();?></textarea>

        <input type="hidden" id="enp-question-image-<?echo $question_id;?>" class="enp-question-image__input" name="enp_question[<?echo $question_i;?>][question_image]" value="<?php echo $question_image;?>" />

        <?php
            echo $Quiz_create->get_question_image_template($question, $question_id, $question_i, $question_image);
        ?>

        <h4 class="enp-legend enp-question-type__legend">Question Type</h4>

        <?php echo $Quiz_create->get_question_type_input($question, $question_id, $question_i);


        include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-mc.php');

        $slider_id = $question->get_slider();
        $slider = new Enp_quiz_Slider($slider_id);
        // don't add slider in for our js question_template
        if($slider_id !== '') {
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-slider.php');
        }

        ?>
    </div>

    <div class="enp-question-inner enp-answer-explanation">
        <fieldset class="enp-fieldset enp-answer-explanation__fieldset">
            <label class="enp-label enp-answer-explanation__label" for="enp-question-explanation__<?php echo $question_i;?>">Answer Explanation</label>
            <textarea id="enp-question-explanation__<?php echo $question_i;?>" class="enp-textarea enp-answer-explanation__textarea" name="enp_question[<?php echo $question_i;?>][question_explanation]" maxlength="6120" placeholder="Your cerebellum can predict your own actions, so you're unable to 'surprise' yourself with a tickle."><?php echo $question->get_question_explanation();?></textarea>
        </fieldset>
    </div>
</section>
