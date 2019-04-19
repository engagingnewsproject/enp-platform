<div class="enp-results-flow">
    <h2 class="enp-screen-reader-text">Quiz User Flow</h2>
    <div class="enp-results-flow__item enp-results-flow__item--total-views">
        <h3 class="enp-results-flow__title enp-results-flow__title--total-views">Total Views</h3>
        <div class="enp-results-flow__number enp-results-flow__number--total-views"><?php echo $quiz->get_quiz_views();?></div>
    </div>
    <div class="enp-results-flow__item enp-results-flow__item--quiz-starts">
        <h3 class="enp-results-flow__title enp-results-flow__title--quiz-starts">Quiz Starts</h3>
        <div class="enp-results-flow__number enp-results-flow__number--quiz-starts"><?php echo $quiz->get_quiz_starts();?></div>
        <div class="enp-results-flow__percentage enp-results-flow__percentage--quiz-starts"><?php echo $this->percentagize($quiz->get_quiz_starts(), $quiz->get_quiz_views(), 1);?></div>
    </div>
    <div class="enp-results-flow__item enp-results-flow__item--quiz-finishes">
        <h3 class="enp-results-flow__title enp-results-flow__title--quiz-finishes">Finishes</h3>
        <div class="enp-results-flow__number enp-results-flow__number--quiz-finishes"><?php echo $quiz->get_quiz_finishes();?></div>
        <div class="enp-results-flow__percentage enp-results-flow__percentage--quiz-finishes"><?php echo $this->percentagize($quiz->get_quiz_finishes(), $quiz->get_quiz_views(), 1);?></div>
    </div>
</div>
