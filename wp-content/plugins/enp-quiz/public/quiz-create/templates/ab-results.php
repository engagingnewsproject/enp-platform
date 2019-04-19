<?php
/**
 * The template for viewing the results of an A/B Test
 *
 * @since             0.0.1
 * @package           Enp_quiz
 */
?>
<?php echo $this->dashboard_breadcrumb_link();?>
<h1 class="enp-page-title enp-results-title enp-results-title--ab"><?php echo $ab_test->get_ab_test_title();?></h1>
<?php do_action('enp_quiz_display_messages');
// see if we just created the ab test or not
if(isset($_GET['enp_user_action']) && $_GET['enp_user_action'] === 'ab_test_created') {?>
    <section class="enp-ab-new-embed-code__section">
        <?php include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/ab-test-embed-code.php');?>
    </section>
<?php } ?>

<section class="enp-container enp-results__container enp-results__container--ab">
    <section class="enp-results enp-results--ab enp-results--a <?php echo  $this->ab_test_winner_loser_class($quiz_a->get_quiz_id());?>">
        <h2 class="enp-results-title enp-results-title--ab enp-results-title--a">
            <?php echo $quiz_a->get_quiz_title();?>
        </h2>
        <section class="enp-results-flow__container">
            <?php
            $quiz = $quiz_a;
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/quiz-results-flow.php');?>
        </section>

    </section>
    <section class="enp-results enp-results--ab enp-results--b <?php echo  $this->ab_test_winner_loser_class($quiz_b->get_quiz_id());?>">
        <h2 class="enp-results-title enp-results-title--ab enp-results-title--b">
            <?php echo $quiz_b->get_quiz_title();?>
        </h2>
        <section class="enp-results-flow__container">
            <?php
            $quiz = $quiz_b;
            include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/quiz-results-flow.php');?>
        </section>

    </section>

</section>

<section class="enp-container enp-ab-scores">
    <h2 class="enp-ab-scores__title">Quiz Scores</h2>
    <div class="enp-quiz-scores">
        <div class="enp-quiz-score__line-chart"></div>
    </div>
    <table class="enp-quiz-scores-table <?php echo  $this->ab_test_winner_loser_class($quiz_a->get_quiz_id());?>">
        <tr>
            <th class="enp-quiz-scores-table__label"><?php echo $quiz_a->get_quiz_title();?> Score</th>
            <th class="enp-quiz-scores-table__score"># of People</th>
        </tr>
        <?php foreach($quiz_a->quiz_score_chart_data['quiz_scores'] as $key => $val) {?>
            <tr>
                <td class="enp-quiz-scores-table__label"><?php echo  $quiz_a->quiz_score_chart_data['quiz_scores_labels'][$key];?></td>
                <td class="enp-quiz-scores-table__score"><?php echo $val;?></td>
            </tr>
        <?php } ?>
    </table>
    <table class="enp-quiz-scores-table <?php echo  $this->ab_test_winner_loser_class($quiz_b->get_quiz_id());?>">
        <tr>
            <th class="enp-quiz-scores-table__label"><?php echo $quiz_b->get_quiz_title();?> Score</th>
            <th class="enp-quiz-scores-table__score"># of People</th>
        </tr>
        <?php foreach($quiz_b->quiz_score_chart_data['quiz_scores'] as $key => $val) {?>
            <tr>
                <td class="enp-quiz-scores-table__label"><?php echo $quiz_b->quiz_score_chart_data['quiz_scores_labels'][$key];?></td>
                <td class="enp-quiz-scores-table__score"><?php echo $val;?></td>
            </tr>
        <?php } ?>
    </table>
</section>


<section class="enp-container enp-question-results__container enp-question-results__container--ab">
    <section class="enp-question-results enp--question-results__container enp-question-results--ab enp-question-results--a <?php echo  $this->ab_test_winner_loser_class($quiz_a->get_quiz_id());?>">
        <?php
        $quiz = $quiz_a; include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/question-results-section.php');?>
    </section>

    <section class="enp-question-results enp--question-results__container enp-question-results--ab enp-question-results--b <?php echo  $this->ab_test_winner_loser_class($quiz_b->get_quiz_id());?>">
        <?php
        $quiz = $quiz_b; include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/question-results-section.php');?>
    </section>
</section>

<section id="enp-ab-embed-code" class="enp-ab-embed-code__section">
    <?php include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/ab-test-embed-code.php');?>
</section>
