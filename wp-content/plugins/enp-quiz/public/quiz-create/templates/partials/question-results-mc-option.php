<?php
    $correct = $mc_option->get_mc_option_correct();
    if($correct === '1') {
        $correct_string = 'correct';
    } else {
        $correct_string = 'incorrect';
    }
?>
<li class="enp-results-question__option enp-results-question__option--<?php echo $correct_string;?>">
    <?php echo $this->option_correct_icon($correct);?>
    <div class="enp-results-question__option__text">
        <?php echo $mc_option->get_mc_option_content();?>
    </div>
    <div class="enp-results-question__option__number-selected">
        <?php echo $mc_option->get_mc_option_responses();?>&nbsp;/&nbsp;<span class="enp-results-question__option__percentage enp-results-question__option__percentage--<?php echo $correct_string;?>"><?php echo $this->percentagize( $mc_option->get_mc_option_responses(), $question->get_question_responses(), 1);?>%</span>
    </div>
</li>
