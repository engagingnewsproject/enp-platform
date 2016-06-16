<section class="enp-results">
    <div class="enp-results__score">
        <h2 class="enp-results__score__title"><?php echo $qt_end->get_score_percentage();?><span class="enp-results__score__title__percentage">%</span> <span class="enp-screen-reader-text">Correct</span></h2>
        <svg class="enp-results__score__circle" width="200" height="200" viewPort="0 0 100 100" version="1.1" xmlns="http://www.w3.org/2000/svg">
          <circle class="enp-results__score__circle__bg" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
          <circle id="enp-results__score__circle__path" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="<?php echo $qt_end->get_score_circle_dashoffset();?>"></circle>
        </svg>
    </div>
    <p class="enp-results__encouragement"><?php echo $qt_end->get_quiz_end_title();?></p>
    <p class="enp-results__description"><?php echo $qt_end->get_quiz_end_content();?></p>
    <h3 class="enp-results__share-title">Share Your Results</h3>
    <ul class="enp-results__share">
        <li class="enp-results__share__item"><a class="enp-results__share__link enp-results__share__item--facebook" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(ENP_QUIZ_URL.$qt_end->quiz->get_quiz_id());?>">
            <svg class="enp-icon enp-icon--facebook enp-results__share__item__icon enp-results__share__item__icon--facebook">
              <use xlink:href="#icon-facebook" />
              <span class="enp-screen-reader-text">Facebook: Link opens in new window</span>
            </svg>
        </a></li>
        <li class="enp-results__share__item"><a class="enp-results__share__link enp-results__share__item--twitter" target="_blank" href="http://twitter.com/intent/tweet?text=<?php echo 'I got '.$qt_end->get_score_percentage().'%25 right on the '.$qt_end->quiz->get_quiz_title().' Quiz. How many can you get right?';?>&url=<?php echo ENP_QUIZ_URL.$qt_end->quiz->get_quiz_id();?>">
            <svg class="enp-icon enp-icon--twitter enp-results__share__item__icon enp-results__share__item__icon--twitter">
              <use xlink:href="#icon-twitter" />
            </svg>
            <span class="enp-screen-reader-text">Twitter: Link opens in new window</span>
        </a></li>
        <li class="enp-results__share__item"><a class="enp-results__share__link enp-results__share__item--email" href="mailto:?subject=<?php echo rawurlencode( $qt_end->quiz->get_quiz_title().' Quiz');?>&body=<?php echo rawurlencode('I got ').$qt_end->get_score_percentage().rawurlencode('% right on the '.$qt_end->quiz->get_quiz_title().' Quiz. How well can you do?');?>">
            <svg class="enp-icon enp-icon--mail enp-results__share__item__icon enp-results__share__item__icon--email">
              <use xlink:href="#icon-mail" />
            </svg>
        </a></li>
    </ul>
    <div class="enp-quiz-restart__container">
        <button type="submit" class="enp-btn enp-quiz-restart" name="enp-quiz-restart" value="enp-quiz-restart">Restart Quiz</button>
    </div>
</section>
