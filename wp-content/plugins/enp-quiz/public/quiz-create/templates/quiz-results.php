<?php
/**
 * The template for viewing the results of a quiz
 *
 * @since             0.0.1
 * @package           Enp_quiz
 */
?>
<?php echo $this->dashboard_breadcrumb_link();?>
<h1 class="enp-page-title enp-results-title"><?php echo $quiz->get_quiz_title();?></h1>
<section class="enp-container enp-results__container">
    <section class="enp-container enp-results-flow__container">
        <?php include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/quiz-results-flow.php');?>
    </section>

    <section class="enp-container enp-quiz-scores__container">
        <div class="enp-quiz-scores">
            <div class="enp-quiz-score__line-chart"></div>
        </div>

        <table class="enp-quiz-scores-table">
            <tr>
                <th class="enp-quiz-scores-table__label">Score</th>
                <th class="enp-quiz-scores-table__score"># of People</th>
            </tr>
            <?php foreach($quiz->quiz_score_chart_data['quiz_scores'] as $key => $val) {?>
                <tr>
                    <td class="enp-quiz-scores-table__label"><?php echo $quiz->quiz_score_chart_data['quiz_scores_labels'][$key];?></td>
                    <td class="enp-quiz-scores-table__score"><?php echo $val;?></td>
                </tr>
            <?php } ?>

        </table>
    </section>
</section>

<?php include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/question-results-section.php');?>

<section class="enp-container enp-aside__container">
    <aside class="enp-aside enp-embed__container">
        <h3 class="enp-aside-title enp-embed__title">Embed</h3>
        <?php include (ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/quiz-embed-code.php');?>
    </aside>
    <?php include (ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/quiz-share.php');?>
</section>
