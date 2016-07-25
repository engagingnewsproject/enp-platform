<?php
/**
 * The template for a user to create a new A/B Test
 *
 * @since             0.0.1
 * @package           Enp_quiz
 */
?>
<?php echo $this->dashboard_breadcrumb_link();?>
<div class="enp-container enp-ab-create__container">
    <?php do_action('enp_quiz_display_messages'); ?>
    <?php
    if(count($quizzes) < 2):
        echo "You need at least two quizzes to create an A/B Test. <a href='".ENP_QUIZ_CREATE_URL."new'>Create a new quiz.</a>";
    else: ?>

        <form class="enp-form enp-ab-create__form" method="post" action="<?php echo htmlentities(ENP_AB_TEST_URL); ?>new">
            <h1 class="enp-page-title enp-ab-create__page-title">Create A/B Test</h1>

            <?php $enp_quiz_nonce->outputKey();?>
            <fieldset class="enp-fieldset enp-ab-create-title">
                <label class="enp-label enp-ab-create__label enp-ab-create-title__label" for="enp-ab-test-title">
                    A/B Test Name
                </label>
                <textarea id="enp-ab-test-title" class="enp-textarea enp-ab-create-title__textarea" name="enp-ab-test-title" maxlength="255"  placeholder="Name your A/B Test"/></textarea>
            </fieldset>

            <?php
                $ab_labels = array("a", "b");
                foreach($ab_labels as $ab_label) {
                    include( ENP_QUIZ_CREATE_TEMPLATES_PATH.'partials/ab-test-fieldset.php' );
                }

            ?>

            <button class="enp-btn enp-ab-create__submit" name="enp-ab-test-submit" type="submit" value="enp-ab-test-create">Create A/B Test</button>

        </form>
    <?php endif;?>
</div>
