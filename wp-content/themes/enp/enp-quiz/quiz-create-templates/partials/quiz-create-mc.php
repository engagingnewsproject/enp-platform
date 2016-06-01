<?
    // set a counter
    $mc_option_i = 0;
    // count the number of mc_options
    $mc_option_ids = $question->get_mc_options();

?>
<fieldset class="enp-mc-options">
    <legend class="enp-legend enp-mc-options__legend">Multiple Choice Options</legend>
    <ul class="enp-mc-options__list">
        <?php
        if(!empty($mc_option_ids)) {
            foreach($mc_option_ids as $mc_option_id) {

                include(ENP_QUIZ_CREATE_TEMPLATES_PATH.'/partials/quiz-create-mc-option.php');
                $mc_option_i++;
            }
        }

        ?>
        <li class="enp-mc-option enp-mc-option--add">
            <button class="enp-btn--add enp-quiz-submit enp-mc-option__add" name="enp-quiz-submit" value="add-mc-option__question-<?echo $question_id;?>"><svg class="enp-icon enp-icon--add enp-mc-option__add__icon"><use xlink:href="#icon-add" /></svg> Add Another Option</button>
        </li>
    </ul>
</fieldset>
