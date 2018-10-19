<?php // set-up the mc option
$mc_option = new Enp_quiz_MC_option($mc_option_id);
?>
<li id="enp-mc-option--<?php echo $mc_option_id;?>" class="enp-mc-option enp-mc-option--inputs<?php echo ((int)$mc_option->get_mc_option_correct() === 1 ? ' enp-mc-option--correct' : '');?>">
    <label for="enp-mc-option__<?php echo $question_i.'__'.$mc_option_i;?>" class="enp-screen-reader-text">Multiple Choice Option</label>
    <input class="enp-mc-option-id" type="hidden" name="enp_question[<?php echo $question_i;?>][mc_option][<?php echo $mc_option_i;?>][mc_option_id]" value="<?php echo $mc_option_id;?>" />

    <input id="enp-mc-option__<?php echo $question_i.'__'.$mc_option_i;?>" type="text" class="enp-input enp-mc-option__input" name="enp_question[<?php echo $question_i;?>][mc_option][<?php echo $mc_option_i;?>][mc_option_content]" maxlength="255" placeholder="It's one of the great mysteries of the universe." value="<?php echo  $mc_option->get_mc_option_content();?>"/>

    <?php
    echo $Quiz_create->get_mc_option_correct_button($question_id, $mc_option_id);
    echo $Quiz_create->get_mc_option_delete_button($mc_option_id);
    ?>
</li>
