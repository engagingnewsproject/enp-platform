<fieldset id="question_<?php echo $qt_question->question->get_question_id();?>" class="enp-question__fieldset <?php echo $qt_question->get_question_classes();?>"  tabindex="0">
    <input id="enp-question-id" type="hidden" name="enp-question-id" value="<? echo $qt_question->question->get_question_id();?>"/>
    <input id="enp-question-type" type="hidden" name="enp-question-type" value="<? echo $qt_question->question->get_question_type();?>"/>

    <legend class="enp-question__legend enp-question__question"><? echo $qt_question->question->get_question_title();?></legend>

    <?php
    $question_image = $qt_question->question->get_question_image();
    if(!empty($question_image)) {
        include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/question-image.php');
    }
    $question_state = $qt_question->qt->get_state();
    if($question_state === 'question_explanation') {
        include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/question-explanation.php');
    }
    $question_type = $qt_question->question->get_question_type();
    if($question_type === 'mc' && $question_state === 'question') {?>
        <p id="enp-question__helper--<?php echo $qt_question->question->get_question_id();?>" class="enp-question__helper">Select one option.</p>
        <?php
        $mc_option_ids = $qt_question->question->get_mc_options();
        // randomize the order
        shuffle($mc_option_ids);
        // loop through mc option ids and output them
        foreach($mc_option_ids as $mc_option_id) {
            $mc_option = new Enp_quiz_MC_option($mc_option_id);
            include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/mc-option.php');
        }
    } elseif($question_type === 'slider' && $question_state === 'question') {
        $slider_id = $qt_question->question->get_slider();
        $slider = new Enp_quiz_Slider($slider_id);
        include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/slider.php');
    }?>

    <button type="submit" class="enp-btn enp-options__submit enp-question__submit" name="enp-question-submit" value="enp-question-submit"><span class="enp-question__submit__text">Submit Answer</span> <svg class="enp-icon enp-icon--chevron-right enp-options__submit__icon enp-question__submit__icon">
      <use xlink:href="#icon-chevron-right" />
    </svg></button>


</fieldset>
