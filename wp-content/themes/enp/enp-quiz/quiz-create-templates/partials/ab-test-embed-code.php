<div class="enp-container enp-ab-embed-code">
    <h3 class="enp-ab-embed-code__title">Embed Code</h3>
    <textarea class="enp-embed-code enp-embed-code__textarea" rows="8"><script type="text/javascript" src="<?php echo ENP_QUIZ_PLUGIN_URL;?>public/quiz-take/js/dist/iframe-parent.js"></script>
<iframe id="enp-ab-test-iframe-<?php echo $ab_test->get_ab_test_id();?>" class="enp-quiz-iframe enp-ab-test-iframe" src="<?php echo ENP_TAKE_AB_TEST_URL.$ab_test->get_ab_test_id();?>" style="width: <?php echo $quiz_a->get_quiz_width();?>; height: 500px;"></iframe></textarea>
    <div class="enp-embed-code__instructions">
        <p>Copy and paste this code into your website where you want the quiz to appear. Each time someone views this A/B test on your website, they will always see the same quiz.</p>
    </div>
</div>
