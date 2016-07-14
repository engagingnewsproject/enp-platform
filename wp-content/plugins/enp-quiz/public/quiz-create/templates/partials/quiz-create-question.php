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

    <?php if($quiz_status === 'draft') {?>
        <button class="enp-question__button enp-quiz-submit enp-question__button--delete" name="enp-quiz-submit" value="question--delete-<?php echo $question_id;?>">
            <svg class="enp-icon enp-icon--delete enp-question__icon--question-delete"><use xlink:href="#icon-delete"><title>Delete Question</title></use></svg>
        </button>
    <?php } ?>


    <div class="enp-question-inner enp-question">
        <label class="enp-label enp-question-title__label" for="question-title-<?php echo $question_i;?>">
            Question
        </label>
        <textarea id="question-title-<?php echo $question_i;?>" class="enp-textarea enp-question-title__textarea" name="enp_question[<?php echo $question_i;?>][question_title]" maxlength="255" placeholder="Why can't we tickle ourselves?"/><?php echo $question->get_question_title();?></textarea>

        <input type="hidden" id="enp-question-image-<?echo $question_id;?>" class="enp-question-image__input" name="enp_question[<?echo $question_i;?>][question_image]" value="<?php echo $question_image;?>">

        <?php
            if(!empty($question_image)) {
                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question-image.php');
            } elseif($question_id !== '{{question_id}}') {
                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question-image-upload.php');
            }
        ?>

        <h4 class="enp-legend enp-question-type__legend">Question Type</h4>

        <?php if(($quiz_status === 'published' && $question->get_question_type() === 'mc') || $quiz_status === 'draft') { ?>
            <input type="radio" id="enp-question-type__mc--<?php echo $question_id;?>" class="enp-radio enp-question-type__input enp-question-type__input--mc" name="enp_question[<?echo $question_i;?>][question_type]" value="mc" <?php checked( $question->get_question_type(), 'mc' );?>/>
            <label class="enp-label enp-question-type__label enp-question-type__label--mc" for="enp-question-type__mc--<?php echo $question_id;?>"><span class="enp-screen-reader-text">Question Type: </span>Multiple Choice</label>
        <?php
        }

        if(($quiz_status === 'published' && $question->get_question_type() === 'slider') || $quiz_status === 'draft') { ?>
            <input type="radio" id="enp-question-type__slider--<?php echo $question_id;?>" class="enp-radio enp-question-type__input enp-question-type__input--slider" name="enp_question[<?echo $question_i;?>][question_type]" value="slider" <?php checked( $question->get_question_type(), 'slider' );?>/>
            <label class="enp-label enp-question-type__label enp-question-type__label--slider" for="enp-question-type__slider--<?php echo $question_id;?>"><span class="enp-screen-reader-text">Question Type: </span>Slider</label>
        <?php
        }

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
            <textarea id="enp-question-explanation__<?php echo $question_i;?>" class="enp-textarea enp-answer-explanation__textarea" name="enp_question[<?php echo $question_i;?>][question_explanation]" maxlength="255" placeholder="Your cerebellum can predict your own actions, so you're unable to 'surprise' yourself with a tickle."><?php echo $question->get_question_explanation();?></textarea>
        </fieldset>
    </div>
</section>
