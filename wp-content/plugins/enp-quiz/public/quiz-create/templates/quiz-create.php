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
 * for reference,
 * $this = $Quiz_create = Enp_quiz_Quiz_create class
 *
 */
?>
<?php echo $Quiz_create->dashboard_breadcrumb_link();?>
<section class="enp-container enp-quiz-form-container js-enp-quiz-create-form-container">
    <?php include_once(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-breadcrumbs.php');?>

    <?php do_action('enp_quiz_display_messages'); ?>

    <form id="enp-quiz-create-form" class="enp-form enp-quiz-form" enctype="multipart/form-data" method="post" action="<?php echo $Quiz_create->get_quiz_action_url(); ?>" novalidate>
        <?php
        $enp_quiz_nonce->outputKey();
        echo $Quiz_create->hidden_fields();?>

        <fieldset class="enp-fieldset enp-quiz-title">
            <label class="enp-label enp-quiz-title__label" for="quiz-title">
                Quiz Title
            </label>
            <textarea id="quiz-title" class="enp-textarea enp-quiz-title__textarea" type="text" name="enp_quiz[quiz_title]" maxlength="255" placeholder="My Engaging Quiz Title"/><?php echo $quiz->get_value('quiz_title') ?></textarea>
        </fieldset>

        <section class="enp-quiz-create__questions">
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
        </section>

        <?php echo $Quiz_create->get_add_question_button();?>


        <button type="submit" class="enp-btn--save enp-quiz-submit enp-quiz-form__save" name="enp-quiz-submit" value="save">Save</button>

        <?php echo $Quiz_create->get_next_step_button();?>

    </form>
</section>
