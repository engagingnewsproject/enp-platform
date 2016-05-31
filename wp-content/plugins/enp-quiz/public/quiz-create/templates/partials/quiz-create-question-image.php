<div class="enp-question-image__container">
    <? if ($question_id !== '{{question_id}}') {?>
        <img
            class="enp-question-image"
            src="<? echo $question->get_question_image_src();?>"
            srcset="<? echo $question->get_question_image_srcset();?>"
            alt="<? echo $question->get_question_image_alt();?>"
        />
    <? } ?>
    <button class="enp-button enp-quiz-submit enp-button__question-image-delete" name="enp-quiz-submit" value="question-image--delete-<? echo $question_id;?>"><svg class="enp-icon enp-icon--delete enp-question__icon--question-image-delete"><use xlink:href="#icon-delete" /></svg></button>

    <label class="enp-label enp-question-image-alt__label" for="enp-question-image-alt--<? echo $question_id;?>">Image Description</label>
    <input id="enp-question-image-alt--<? echo $question_id;?>" class="enp-input enp-input--has-description enp-question-image-alt__input" type="text" maxlength="255"  name="enp_question[<?echo $question_i;?>][question_image_alt]" value="<? echo $question->get_question_image_alt();?>" aria-describedby="enp-question-image-alt-description--<? echo $question_id;?>">
    <p id="enp-question-image-alt-description--<? echo $question_id;?>" class="enp-input-description enp-question-image-alt__description">Used for Assistive Technology (i.e. screen readers) and SEO. Does not show on the question. </p>

</div>
