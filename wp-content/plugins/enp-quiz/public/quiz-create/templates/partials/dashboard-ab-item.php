<?php
$quiz_a = new Enp_quiz_Quiz($ab_test->get_quiz_id_a());
$quiz_b = new Enp_quiz_Quiz($ab_test->get_quiz_id_b());
?>
<li class="enp-dash-item enp-dash-item--published">
    <div class="enp-dash-item__header">
        <h3 class="enp-dash-item__title enp-dash-item__title--ab-test"><a href="<?php echo ENP_AB_RESULTS_URL.$ab_test->get_ab_test_id();?>"><?php echo $ab_test->get_ab_test_title();?></a></h3>
        <ul class="enp-dash-item__nav">
            <li class="enp-dash-item__nav__item"><a href="<?php echo ENP_AB_RESULTS_URL.$ab_test->get_ab_test_id();?>">Results</a></li>
            <li class="enp-dash-item__nav__item"><a href="<?php echo ENP_AB_RESULTS_URL.$ab_test->get_ab_test_id();?>#enp-ab-embed-code">Embed</a></li>
        </ul>
    </div>
    <div class="enp-dash-item__content">
        <ul class="enp-dash-item__ab-quizzes">
            <li class="enp-dash-item__ab-quizzes__quiz">A. <?php echo $quiz_a->get_quiz_title();?></li>
            <li class="enp-dash-item__ab-quizzes__quiz">B. <?php echo $quiz_b->get_quiz_title();?></li>
        </ul>
    </div>
</li>
