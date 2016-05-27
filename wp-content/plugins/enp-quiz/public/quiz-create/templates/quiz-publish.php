<?php
/**
 * The template for the "publish" page when a user
 * finishes creating a quiz. This also has the embed code
 * on it.
 *
 * @since             0.0.1
 * @package           Enp_quiz
 * Data available to this view:
 * $quiz = quiz object (if exits), error page if it doesn't (TODO)
 */
?>
<?php echo $this->dashboard_breadcrumb_link();?>
<div class="enp-container enp-publish-page-container">
    <?php include_once(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-breadcrumbs.php');?>
    <?php do_action('enp_quiz_display_messages'); ?>
    <div class="enp-flex enp-publish-page-flex-container">
        <section class="enp-container enp-publish-container">
            <h1 class="enp-page-title enp-publish-page__title">Embed</h1>
            <p>Copy and paste the embed code onto your website where you'd like it to appear.</p>
            <textarea class="enp-embed-code enp-publish-page__embed-code" rows="7"><script type="text/javascript" src="<?php echo ENP_QUIZ_PLUGIN_URL;?>public/quiz-take/js/dist/iframe-parent.js"></script>
<iframe id="enp-quiz-iframe-<?php echo $quiz->get_quiz_id();?>" class="enp-quiz-iframe" src="<?php echo ENP_QUIZ_URL.$quiz->get_quiz_id();?>" style="width: <? echo $quiz->get_quiz_width();?>; height: 500px;"></iframe>
            </textarea>

        </section>

        <section class="enp-container enp-aside-container enp-publish-page__aside-container">
            <aside class="enp-aside enp-share-quiz__container">
                <h3 class="enp-aside-title enp-share-quiz__title">Share Your Quiz</h3>
                <a class="enp-share-quiz__url" href="<? echo ENP_QUIZ_URL.$quiz->get_quiz_id();?>"><? echo ENP_QUIZ_URL.$quiz->get_quiz_id();?></a></p>
                <ul class="enp-share-quiz">
                    <li class="enp-share-quiz__item"><a class="enp-share-quiz__link enp-share-quiz__item--facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(ENP_QUIZ_URL.$quiz->get_quiz_id());?>">
                        <svg class="enp-icon enp-icon--facebook enp-share-quiz__item__icon enp-share-quiz__item__icon--facebook">
                          <use xlink:href="#icon-facebook" />
                        </svg>
                    </a></li>
                    <li class="enp-share-quiz__item"><a class="enp-share-quiz__link enp-share-quiz__item--twitter" href="http://twitter.com/intent/tweet?text=<?php echo urlencode('How many can you get right on the '.$quiz->get_quiz_title().' Quiz?');?>&url=<?php echo ENP_QUIZ_URL.$quiz->get_quiz_id();?>">
                        <svg class="enp-icon enp-icon--twitter enp-share-quiz__item__icon enp-share-quiz__item__icon--twitter">
                          <use xlink:href="#icon-twitter" />
                        </svg>
                    </a></li>
                    <li class="enp-share-quiz__item"><a class="enp-share-quiz__link enp-share-quiz__item--email" href="mailto:?subject=<?php echo rawurlencode( $quiz->get_quiz_title().' Quiz');?>&body=<?php echo rawurlencode('I just made the '.$quiz->get_quiz_title().' Quiz. How many can you get right?');?>">
                        <svg class="enp-icon enp-icon--mail enp-share-quiz__item__icon enp-share-quiz__item__icon--email">
                          <use xlink:href="#icon-mail" />
                        </svg>
                    </a></li>
                </ul>
            </aside>

            <aside class="enp-aside enp-ab-ad__container">
                <h3 class="enp-aside-title enp-ab-ad__title">A/B Test</h3>
                <p class="enp-ab-ad__description">Some description on what an A/B Test is.</p>
                <a class="enp-btn enp-ab-ad__link" href="<?echo ENP_AB_TEST_URL;?>">New A/B Test</a>
            </aside>
        </section>
    </div>
</div>
