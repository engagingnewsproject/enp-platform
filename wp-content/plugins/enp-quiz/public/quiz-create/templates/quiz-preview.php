<?php
/**
 * The template for a user to preview and test their quiz before
 * publishing it.
 *
 * @since             0.0.1
 * @package           Enp_quiz
 *
 * Data available to this view:
 * $quiz = quiz object (if exits), error page if it doesn't (TODO)
*/
?>
<?php echo $this->dashboard_breadcrumb_link();?>
<div class="enp-container enp-preview-page-container js-enp-quiz-create-form-container">
    <?php include_once(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-breadcrumbs.php');?>
    <?php do_action('enp_quiz_display_messages'); ?>
    <div class="enp-flex enp-preview-page-flex-container">
        <section class="enp-container enp-quiz-settings-container">
            <h1 class="enp-quiz-settings__title">Quiz Settings</h1>
            <form class="enp-form enp-quiz-settings__form" method="post" action="<?php echo htmlentities(ENP_QUIZ_PREVIEW_URL.$quiz->get_quiz_id().'/'); ?>">
                <?php $enp_quiz_nonce->outputKey();?>
                <input type="hidden" name="enp_quiz[quiz_id]" value="<? echo $quiz->get_quiz_id();?>" />

                <fieldset class="enp-fieldset enp-fieldset--section enp-quiz-styles">
                    <legend class="enp-legend enp-quiz-styles__legend">Quiz Styles</legend>

                    <fieldset class="enp-fieldset enp-title-display">
                        <legend class="enp-legend enp-title-display__legend">Title Display</legend>
                        <? $quiz_title_display = $quiz->get_quiz_title_display();?>
                        <input id="enp-quiz-title-show" class="enp-radio enp-title-display__input enp-title-display__input--title-display" type="radio" name="enp_quiz[quiz_title_display]" value="show" <?php checked( $quiz_title_display, 'show' ); ?>/>
                        <label class="enp-label enp-title-display__label enp-title-display__label--title-display" for="enp-quiz-title-show">
                            Show Title
                        </label>
                        <input class="enp-radio enp-title-display__input enp-title-display__input--hide-title" id="enp-quiz-title-hide" type="radio" name="enp_quiz[quiz_title_display]" value="hide" <?php checked( $quiz_title_display, 'hide' ); ?>/>
                        <label class="enp-label enp-title-display__label enp-title-display__label--hide-title" for="enp-quiz-title-hide">
                            Hide Title
                        </label>
                    </fieldset>


                    <label class="enp-label enp-quiz-styles__label enp-quiz-styles__label--width" for="enp-quiz-width">
                        Width
                    </label>
                    <input id="enp-quiz-width" class="enp-input enp-quiz-styles__input enp-quiz-styles__input--width" type="text" maxlength="8" name="enp_quiz[quiz_width]" value="<? echo $quiz->get_quiz_width();?>"/>

                    <label class="enp-label enp-quiz-styles__label enp-quiz-styles__label--bg-color" for="enp-quiz-bg-color">
                        Background Color
                    </label>
                    <input id="enp-quiz-bg-color" class="enp-input enp-quiz-styles__input enp-quiz-styles__input--color enp-quiz-styles__input--bg-color" type="text" name="enp_quiz[quiz_bg_color]" maxlength="7" value="<? echo $quiz->get_quiz_bg_color();?>" data-default="#ffffff"/>

                    <label class="enp-label enp-quiz-styles__label enp-quiz-styles__label--text-color" for="enp-quiz-text-color">
                        Text Color
                    </label>
                    <input id="enp-quiz-text-color" class="enp-input enp-quiz-styles__input enp-quiz-styles__input--color enp-quiz-styles__input--text-color" type="text" name="enp_quiz[quiz_text_color]" maxlength="7" value="<? echo $quiz->get_quiz_text_color();?>" data-default="#444444"/>

                </fieldset>

                <fieldset class="enp-fieldset enp-fieldset--section">
                    <legend class="enp-legend enp-fieldset--section__title enp-quiz-share__legend">Quiz Share Text</legend>
                        <fieldset class="enp-fieldset enp-quiz-share enp-quiz-share--before">
                        <legend class="enp-legend enp-quiz-share__legend">Before Taking the Quiz</legend>
                        <p id="enp-share-start__description" class="enp-input-description">What should the Facebook and Twitter share descriptions say if someone shares the quiz before taking it?</p>

                        <label class="enp-label enp-quiz-share__label" for="enp-facebook-title-start">
                            Facebook Share Title - Before
                        </label>

                        <textarea id="enp-facebook-title-start" class="enp-textarea enp-quiz-share__textarea enp-textarea" maxlength="255"  aria-describedby="enp-share-start__description" name="enp_quiz[facebook_title_start]"><?php echo $quiz->get_facebook_title_start();?></textarea>

                        <label class="enp-label enp-quiz-share__label" for="enp-facebook-description-start">
                            Facebook Share Description - Before
                        </label>
                        <textarea id="enp-facebook-description-start" class="enp-textarea enp-quiz-share__textarea enp-textarea" maxlength="255" name="enp_quiz[facebook_description_start]" aria-describedby="enp-share-start__description"><?php echo $quiz->get_facebook_description_start();?></textarea>


                        <label class="enp-label enp-quiz-share__label" for="enp-tweet-start">
                            Tweet - Before
                        </label>
                        <textarea id="enp-tweet-start" class="enp-textarea enp-quiz-share__textarea enp-quiz-share__textarea--tweet" maxlength="255" name="enp_quiz[tweet_start]" aria-describedby="enp-share-start__description"><?php echo $quiz->get_tweet_start();?></textarea>

                    </fieldset>

                    <fieldset class="enp-fieldset enp-quiz-share enp-quiz-share--after">
                        <legend class="enp-legend enp-quiz-share__legend">After Taking the Quiz</legend>
                        <p id="enp-share-after__description" class="enp-input-description">What should the Facebook and Twitter share descriptions say after someone takes the quiz? Use <strong>{{score_percentage}}</strong> to show their score in the description.</p>

                        <label class="enp-label enp-quiz-share__label" for="enp-facebook-title-start">
                            Facebook Share Title - After
                        </label>

                        <textarea id="enp-facebook-title-end" class="enp-textarea enp-quiz-share__textarea enp-textarea" maxlength="255"  aria-describedby="enp-share-after__description" name="enp_quiz[facebook_title_end]"><?php echo $quiz->get_facebook_title_end();?></textarea>

                        <label class="enp-label enp-quiz-share__label" for="enp-facebook-description-end">
                            Facebook Share Description - After
                        </label>
                        <textarea id="enp-facebook-description-end" class="enp-textarea enp-quiz-share__textarea enp-textarea" maxlength="255" name="enp_quiz[facebook_description_end]" aria-describedby="enp-share-after__description"><?php echo $quiz->get_facebook_description_end();?></textarea>

                        <label class="enp-label enp-quiz-share__label" for="enp-tweet-end">
                            Tweet - After
                        </label>
                        <textarea id="enp-tweet-end" class="enp-textarea enp-quiz-share__textarea enp-quiz-share__textarea--tweet enp-quiz-share__textarea--after" maxlength="255" name="enp_quiz[tweet_end]" aria-describedby="enp-share-after__description"><?php echo $quiz->get_tweet_end();?></textarea>

                    </fieldset>
                </fieldset>

                <button type="submit" class="enp-btn--submit enp-preview-form__submit" name="enp-quiz-submit" value="quiz-save">Save</button>

                <button type="submit" id="enp-btn--next-step" class="enp-btn--submit enp-btn--next-step enp-preview-form__submit--publish" name="enp-quiz-submit" value="quiz-publish"><?echo $enp_next_button_name;?> <svg class="enp-icon enp-icon--chevron-right enp-btn--next-step__icon enp-preview-form__submit__icon">
                  <use xlink:href="#icon-chevron-right" />
                </svg></button>
            </form>
        </section>

        <section class="enp-container enp-quiz-preview-container">
            <h2 class="enp-quiz-preview__title">Quiz Preview</h2>

            <script type="text/javascript" src="<?php echo ENP_QUIZ_PLUGIN_URL;?>public/quiz-take/js/dist/iframe-parent.js"></script>

            <iframe id="enp-quiz-iframe-<?php echo $quiz->get_quiz_id();?>" class="enp-quiz-iframe" src="<? echo ENP_QUIZ_URL.$quiz->get_quiz_id();?>" style="width: <? echo $quiz->get_quiz_width();?>; height: 500px;"></iframe>

        </section>
    </div>
</div>
