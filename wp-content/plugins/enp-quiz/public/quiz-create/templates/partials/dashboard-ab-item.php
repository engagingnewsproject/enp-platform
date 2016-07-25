<?php
$quiz_a = new Enp_quiz_Quiz($ab_test->get_quiz_id_a());
$quiz_b = new Enp_quiz_Quiz($ab_test->get_quiz_id_b());
$unique_ab_test_id = $ab_test->get_ab_test_id().'a'.$ab_test->get_quiz_id_a().'b'.$ab_test->get_quiz_id_b();
?>
<li id="enp-dash-item--<?php echo $unique_ab_test_id;?>" class="enp-dash-item enp-dash-item--published">
    <div class="enp-dash-item__header">
        <h3 class="enp-dash-item__title enp-dash-item__title--ab-test"><a href="<?php echo ENP_AB_RESULTS_URL.$ab_test->get_ab_test_id();?>"><?php echo $ab_test->get_ab_test_title();?></a></h3>
        <ul id="enp-dash-item__nav--<?php echo $unique_ab_test_id;?>" class="enp-dash-item__nav">
            <li class="enp-dash-item__nav__item"><a href="<?php echo ENP_AB_RESULTS_URL.$ab_test->get_ab_test_id();?>">Results</a></li>
            <li class="enp-dash-item__nav__item"><a href="<?php echo ENP_AB_RESULTS_URL.$ab_test->get_ab_test_id();?>#enp-ab-embed-code">Embed</a></li>
            <li class="enp-dash-item__nav__item">
                <form id="enp-delete-ab-test-<?php echo $unique_ab_test_id;?>" method="post" action="<?php echo htmlentities(ENP_QUIZ_DASHBOARD_URL.'user/'); ?>">
                    <?php echo $nonce_input;?>
                    <input type="hidden" class="enp-dash-item__ab-test-id" name="ab_test_id" value="<?php echo $ab_test->get_ab_test_id()?>" />
                    <input type="hidden" class="enp-dash-item__quiz-id-a" name="quiz_id_a" value="<?php echo $ab_test->get_quiz_id_a()?>" />
                    <input type="hidden" class="enp-dash-item__quiz-id-b" name="quiz_id_b" value="<?php echo $ab_test->get_quiz_id_b()?>" />
                    <button name="enp-ab-test-submit" class="enp-ab-test-submit enp-dash-item__delete" value="delete-ab-test">
                        <svg class="enp-dash-item__delete__icon enp-icon enp-icon--delete">
                          <use xlink:href="#icon-delete">
                              <title>Delete AB Test - <?php echo $ab_test->get_ab_test_title();?></title>
                          </use>
                        </svg>
                    </button>
                </form>
            </li>
        </ul>
    </div>
    <div class="enp-dash-item__content">
        <ul class="enp-dash-item__ab-quizzes">
            <li class="enp-dash-item__ab-quizzes__quiz">A. <?php echo $quiz_a->get_quiz_title();?></li>
            <li class="enp-dash-item__ab-quizzes__quiz">B. <?php echo $quiz_b->get_quiz_title();?></li>
        </ul>
    </div>
</li>
