<fieldset class="enp-fieldset enp-ab-create-quiz-<?php echo $ab_label;?>">
    <label class="enp-label enp-ab-create__label enp-ab-create-quiz-<?php echo $ab_label;?>__label" for="quiz-<?php echo $ab_label;?>">Select Quiz <?php echo $ab_label;?></label>

    <select class="enp-select enp-ab-create__select enp-ab-create-quiz-<?php echo $ab_label;?>__select" name="enp-ab-test-quiz-<?php echo $ab_label;?>" id="quiz-<?php echo $ab_label;?>">
    <?php
    foreach($quizzes as $quiz) {
        $quiz = new Enp_quiz_Quiz($quiz);
        echo '<option value="'.$quiz->get_quiz_id().'">'.$quiz->get_quiz_title().'</option>';
    }
    ?>
    </select>
</fieldset>
