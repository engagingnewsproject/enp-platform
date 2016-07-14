<?php
/**
 * The template for a user to create their quiz
 * and add questions to the quiz.
 *
 * @since             0.0.1
 * @package           Enp_quiz
 *
 * Data available to this view:
 * $quiz = quiz object (if exits), false if new quiz
 *  // example user actions
 *  $user_action = array(
 *                      'action' =>'add',
 *                      'element' => 'mc_option',
 *                      'details' => array(
 *                        'question' => '1',
 *                      ),
 *                  );
 *
 */
// var_dump($quiz);
// var_dump($user_action);
//

$quiz_id = $quiz->get_quiz_id();

 if(is_numeric($quiz_id) || is_int($quiz_id)) {
     $quiz_action_url = ENP_QUIZ_CREATE_URL.$quiz_id.'/';
 } else {
     $quiz_action_url = ENP_QUIZ_CREATE_URL.'new/';
 }
 if(empty($quiz_id))
 { $new_quiz_flag= '1'; } else { $new_quiz_flag= '0'; }
 $quiz_status = $quiz->get_quiz_status();
?>
<?php echo $this->dashboard_breadcrumb_link();?>
<section class="enp-container enp-quiz-form-container js-enp-quiz-create-form-container">
    <?php

    include_once(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-breadcrumbs.php');

    ?>

    <?php do_action('enp_quiz_display_messages'); ?>

    <form id="enp-quiz-create-form" class="enp-form enp-quiz-form" enctype="multipart/form-data" method="post" action="<?php echo htmlentities($quiz_action_url); ?>" novalidate>
        <?php $enp_quiz_nonce->outputKey();?>
        <input id="enp-quiz-id" type="hidden" name="enp_quiz[quiz_id]" value="<?php echo $quiz_id; ?>" />

        <input id="enp-quiz-new" type="hidden" name="enp_quiz[new_quiz]" value="<?php echo $new_quiz_flag;?>" />

        <fieldset class="enp-fieldset enp-quiz-title">
            <label class="enp-label enp-quiz-title__label" for="quiz-title">
                Quiz Title
            </label>
            <textarea id="quiz-title" class="enp-textarea enp-quiz-title__textarea" type="text" name="enp_quiz[quiz_title]" maxlength="255" placeholder="My Engaging Quiz Title"/><? echo $quiz->get_value('quiz_title') ?></textarea>
        </fieldset>

        <?php
            $question_i = 0;
            // count the number of questions
            $question_ids = $quiz->get_questions();
            if(!empty($question_ids)){
                foreach($question_ids as $question_id) {
                    include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question.php');
                    $question_i++;
                }
            }
        ?>

        <?php if($quiz_status !== 'published') {?>
            <button type="submit" class="enp-btn--add enp-quiz-submit enp-quiz-form__add-question" name="enp-quiz-submit" value="add-question"><svg class="enp-icon enp-icon--add enp-add-question__icon" role="presentation" aria-hidden="true">
              <use xlink:href="#icon-add" />
            </svg> Add Question</button>
        <?php } ?>


        <button type="submit" class="enp-btn--save enp-quiz-submit enp-quiz-form__save" name="enp-quiz-submit" value="save">Save</button>

        <button type="submit" id="enp-btn--next-step" class="enp-btn--submit enp-quiz-submit enp-btn--next-step enp-quiz-form__submit" name="enp-quiz-submit" value="quiz-preview"><?php echo ($quiz_status !== 'publish' ? 'Preview' : 'Settings');?> <svg class="enp-icon enp-icon--chevron-right enp-btn--next-step__icon enp-quiz-form__submit__icon">
          <use xlink:href="#icon-chevron-right" />
        </svg></button>


        <?php // set-up javascript templates

            $question_id = '{{question_id}}';
            $question_i = '{{question_position}}';
            // set-up our template
            echo '<script type="text/template" id="question_template">';
                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question.php');
            // end our template
            echo '</script>';?>

            <script type="text/template" id="question_image_upload_button_template">
                <button type="button" class="enp-btn--add enp-question-image-upload"><svg class="enp-icon enp-icon--photo enp-question-image-upload__icon--photo" role="presentation" aria-hidden="true">
                    <use xlink:href="#icon-photo" />
                </svg>
                <svg class="enp-icon enp-icon--add enp-question-image-upload__icon--add" role="presentation" aria-hidden="true">
                    <use xlink:href="#icon-add" />
                </svg> Add Image</button>
            </script>

            <?php
            echo '<script type="text/template" id="question_image_template">';
                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question-image.php');
            echo '</script>';

            echo '<script type="text/template" id="question_image_upload_template">';
                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-question-image-upload.php');
            echo '</script>';

            $mc_option_id = '{{mc_option_id}}';
            $mc_option_i = '{{mc_option_position}}';
            // set-up our template
            echo '<script type="text/template" id="mc_option_template">';
                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-mc-option.php');
            // end our template
            echo '</script>';

            // clone the object so we don't reset its own values
            $original_slider = $slider;
    		$slider = clone $slider;
            // foreach key, set it as a js template var
    		foreach($slider as $key => $value) {
    			// we don't want to unset our question object
    			$slider->$key = '{{'.$key.'}}';
    		}
            // set-up our template
            echo '<script type="text/template" id="slider_template">';
                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-slider.php');
            // end our template
            echo '</script>';

            // set-up our template
            echo '<script type="text/template" id="slider_take_template">';
                include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/slider.php');
            // end our template
            echo '</script>';

            // set-up our template
            echo '<script type="text/template" id="slider_take_range_helpers_template">';
                include(ENP_QUIZ_TAKE_TEMPLATES_PATH.'/partials/slider--range-helpers.php');
            // end our template
            echo '</script>';
            // reset back to slider var
            $slider = $original_slider;

        ?>

    </form>
</section>
