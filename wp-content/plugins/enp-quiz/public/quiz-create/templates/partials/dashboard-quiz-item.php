

<li id="enp-dash-item--<?php echo $quiz->get_quiz_id();?>" class="enp-dash-item enp-dash-item--<?php echo $quiz->get_quiz_status();?>">
    <div class="enp-dash-item__header">
        <h3 class="enp-dash-item__title"><?php echo $this->get_quiz_dashboard_item_title($quiz);?></h3>
        <nav>
            <ul id="enp-dash-item__nav--<?php echo $quiz->get_quiz_id();?>" class="enp-dash-item__nav">
                <?php
                    $quiz_actions = $this->get_quiz_actions($quiz);
                    foreach($quiz_actions as $quiz_action) {
                        echo '<li class="enp-dash-item__nav__item"><a href="'.$quiz_action['url'].'">'.$quiz_action['title'].'</a></li>';
                    }
                ?>
                <li class="enp-dash-item__nav__item">
                    <form id="enp-delete-quiz-<?php echo $quiz->get_quiz_id()?>" method="post" action="<?php echo htmlentities(ENP_QUIZ_DASHBOARD_URL.'user/'); ?>">
                        <?php echo $nonce_input;?>
                        <input type="hidden" class="enp-dash-item__quiz-id" name="enp_quiz[quiz_id]" value="<?php echo $quiz->get_quiz_id()?>" />
                        <button name="enp-quiz-submit" class="enp-quiz-submit enp-dash-item__delete" value="delete-quiz">
                            <svg class="enp-dash-item__delete__icon enp-icon enp-icon--delete">
                              <use xlink:href="#icon-delete">
                                  <title>Delete Quiz - <?php echo $quiz->get_quiz_title();?></title>
                              </use>
                            </svg>
                        </button>
                    </form>
                </li>
            </ul>
        </nav>
    </div>
    <div class="enp-dash-item__content">
        <?php $quiz_create_date = strtotime( $quiz->get_quiz_created_at() );?>
        <div class="enp-dash-item__meta">
            <time class="enp-dash-item__created-at" datetime="<?php echo date( 'Y-m-dTH:i:s', $quiz_create_date );?>"><?php echo date( 'M d, Y', $quiz_create_date );?></time><?php if(current_user_can('manage_options') && isset($_GET['include']) && $_GET['include'] === 'all_users') {
                $created_by = $quiz->get_quiz_created_by();
                $user_info = get_userdata($created_by);
                echo ' <span class="enp-dash-item__username">'.($user_info !== false ? "$user_info->user_email" : "user has been deleted").'</span>';
            }?>
        </div>
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
