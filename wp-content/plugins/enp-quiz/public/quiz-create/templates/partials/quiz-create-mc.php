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
        <?php echo $Quiz_create->get_mc_option_add_button($question_id);?>
    </ul>
</fieldset>
