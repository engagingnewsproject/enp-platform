<? // set-up the mc option
$mc_option = new Enp_quiz_MC_option($mc_option_id);
?>
<li id="enp-mc-option--<?php echo $mc_option_id;?>" class="enp-mc-option enp-mc-option--inputs<?php echo ((int)$mc_option->get_mc_option_correct() === 1 ? ' enp-mc-option--correct' : '');?>">
    <label for="enp-mc-option__<?php echo $question_i.'__'.$mc_option_i;?>" class="enp-screen-reader-text">Multiple Choice Option</label>
    <input class="enp-mc-option-id" type="hidden" name="enp_question[<?echo $question_i;?>][mc_option][<?echo $mc_option_i;?>][mc_option_id]" value="<?php echo $mc_option_id;?>" />

    <input id="enp-mc-option__<?php echo $question_i.'__'.$mc_option_i;?>" type="text" class="enp-input enp-mc-option__input" name="enp_question[<?echo $question_i;?>][mc_option][<?echo $mc_option_i;?>][mc_option_content]" maxlength="255" placeholder="It's one of the great mysteries of the universe." value="<?echo  $mc_option->get_mc_option_content();?>"/>


    <button class="enp-mc-option__button enp-quiz-submit enp-mc-option__button--correct"  name="enp-quiz-submit" value="mc-option--correct__question-<?echo $question_id;?>__mc-option-<?echo $mc_option_id;?>"<?php echo ($quiz_status === 'published' ? ' disabled' : '');?>>
        <svg class="enp-icon enp-icon--check enp-mc-option__icon enp-mc-option__icon--correct"><use xlink:href="#icon-check"><title>Mark Multiple Choice Option as Correct</title></use></svg>
    </button>

    <?php if($quiz_status === 'draft') {?>
        <button class="enp-mc-option__button enp-quiz-submit enp-mc-option__button--delete" name="enp-quiz-submit" value="mc-option--delete-<?echo $mc_option_id;?>">
            <svg class="enp-icon enp-icon--delete enp-mc-option__icon enp-mc-option__icon--delete"><use xlink:href="#icon-delete"><title>Delete Multiple Choice Option</title></use></svg>
        </button>
    <?php } ?>
</li>
