<?php
/**
 * The template for users to view all of the quizzes and
 * A/B Tests they have created, and begin actions on their
 * account (create new A/B Test, create new quiz, user alerts
 * etc).
 *
 * @since             0.0.1
 * @package           Enp_quiz
 */
 /*
 $user = new Enp_quiz_User();
 object containing user quizzes and ab_tests
 */
?>
<?php do_action('enp_quiz_display_messages'); ?>
<section class="enp-container enp-dash-container">
    <header class="enp-dash__section-header">
        <h2 class="enp-dash__section-title">Quizzes</h2>
        <div class="enp-quiz-list__view">
            <!--<select class="enp-sort-by">
                <option>Date Created</option>
                <option>Most Results</option>
            </select>-->
        </div>
    </header>
    <ul class="enp-dash-list enp-dash-list--quiz">
        <li class="enp-dash-item enp-dash-item--add-new">
            <a class="enp-dash-link--add-new enp-dash-link--add-new-quiz" href="<?php echo ENP_QUIZ_CREATE_URL;?>new/">
                <svg class="enp-dash-link__icon enp-icon">
                  <use xlink:href="#icon-add" />
                </svg>
                New Quiz
            </a>
        </li>
        <?php
        $quizzes = $user->get_quizzes();
        if(!empty($quizzes)) {
            foreach($quizzes as $quiz) {
                $quiz = new Enp_quiz_Quiz($quiz);
                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/dashboard-quiz-item.php');
            }
        }
        ?>
    </ul>
</section>

<section class="enp-dash-container">
    <header class="enp-dash__section-header">
        <h2 class="enp-dash__section-title">A/B Test</h2>
        <div class="enp-quiz-list__view">

            <!--<select class="enp-sort-by">
                <option>Date Created</option>
                <option>Most Results</option>
            </select>-->
        </div>
    </header>
    <ul class="enp-dash-list enp-dash-list--ab">
        <li class="enp-dash-item enp-dash-item--add-new">
            <a class="enp-dash-link--add-new enp-dash-link--add-new-ab-test" href="<?php echo ENP_AB_TEST_URL;?>new/"><svg class="enp-dash-link__icon enp-icon">
              <use xlink:href="#icon-add" />
            </svg>New A/B Test</a>
        </li>
        <?php
        $ab_tests = $user->get_ab_tests();
        if(!empty($ab_tests)) {
            foreach($ab_tests as $ab_test) {
                $ab_test = new Enp_quiz_AB_test($ab_test);
                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/dashboard-ab-item.php');
            }
        } ?>
    </ul>
</section>
