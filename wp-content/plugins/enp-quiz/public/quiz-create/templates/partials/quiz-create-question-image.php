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
</div>
