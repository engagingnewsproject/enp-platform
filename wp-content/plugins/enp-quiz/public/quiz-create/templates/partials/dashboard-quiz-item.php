

<li class="enp-dash-item enp-dash-item--<?php echo $quiz->get_quiz_status();?>">
    <div class="enp-dash-item__header">
        <h3 class="enp-dash-item__title"><?php echo $this->get_quiz_dashboard_item_title($quiz);?></h3>
        <ul class="enp-dash-item__nav">
            <?php
                $quiz_actions = $this->get_quiz_actions($quiz);
                foreach($quiz_actions as $quiz_action) {
                    echo '<li class="enp-dash-item__nav__item"><a href="'.$quiz_action['url'].'">'.$quiz_action['title'].'</a></li>';
                }
            ?>
            <!--<li class="enp-dash-item__nav__item">
                <form>
                    <button class="enp-dash-item__delete">
                        <svg class="enp-dash-item__delete__icon enp-icon enp-icon--delete">
                          <use xlink:href="#icon-delete">
                              <title>Delete Quiz - <?php echo $quiz->get_quiz_title();?></title>
                          </use>
                        </svg>
                    </button>
                </form>
            </li>-->
        </ul>
    </div>
    <div class="enp-dash-item__content">
        <ul class="enp-quiz-results">
            <li class="enp-quiz-results__item enp-quiz-results__item--views">
                <span class="enp-quiz-results__number enp-quiz-results__number--views"><?php echo $this->get_dashboard_quiz_views($quiz);?></span>
                <div class="enp-quiz-results__label">Views</div>
            </li>
            <li class="enp-quiz-results__item enp-quiz-results__item--finishes">
                <span class="enp-quiz-results__number enp-quiz-results__number--finishes"><?php echo $this->get_dashboard_quiz_finishes($quiz);?></span>
                <div class="enp-quiz-results__label">Finishes</div>
            </li>
            <li class="enp-quiz-results__item enp-quiz-results__item--average-score">
                <span class="enp-quiz-results__number enp-quiz-results__number--average-score"><?php echo $this->get_dashboard_quiz_score_average($quiz);?></span>
                <div class="enp-quiz-results__label">Average</div>
            </li>
        </ul>
    </div>
</li>
