<?php
    $slider_range_low = $slider->get_slider_range_low();
    $slider_range_high = $slider->get_slider_range_high();
    $slider_correct_low = $slider->get_slider_correct_low();
    $slider_correct_high = $slider->get_slider_correct_high();
?>



<ul class="enp-results-question__options">
    <?php
    // check if you can select an answer under the correct range
    if($slider_range_low !== $slider_correct_low) {?>
        <li class="enp-results-question__option enp-results-question__option--incorrect">
            <?php echo $this->option_correct_icon('0');?>
            <div class="enp-results-question__option__text">
                <span class="enp-results-question__option__text__helper">low</span> <?php echo $slider_range_low;?> to <?php echo ( $slider_correct_low - $slider->get_slider_increment() );?>
            </div>
            <div class="enp-results-question__option__number-selected">
                <?php echo $slider->get_slider_responses_low();?>&nbsp;/&nbsp;<span class="enp-results-question__option__percentage enp-results-question__option__percentage--incorrect"><?php echo $this->percentagize( $slider->get_slider_responses_low(), $slider->get_slider_responses_total(), 1);?>%</span>
            </div>
        </li>
    <?php } ?>

    <li class="enp-results-question__option enp-results-question__option--correct">
        <?php echo $this->option_correct_icon('1');?>
        <div class="enp-results-question__option__text">
            <span class="enp-results-question__option__text__helper">correct</span> <?php echo $slider_correct_low . ($slider_correct_low === $slider_correct_high ? '' :' to '. $slider_correct_high);?>
        </div>
        <div class="enp-results-question__option__number-selected">
            <?php echo $slider->get_slider_responses_correct();?>&nbsp;/&nbsp;<span class="enp-results-question__option__percentage enp-results-question__option__percentage--correct"><?php echo $this->percentagize( $slider->get_slider_responses_correct(), $slider->get_slider_responses_total(), 1);?>%</span>
        </div>
    </li>

    <?php
    // check if you can select an answer under the correct range
    if($slider_range_high !== $slider_correct_high) {?>
        <li class="enp-results-question__option enp-results-question__option--incorrect">
            <?php echo $this->option_correct_icon('0');?>
            <div class="enp-results-question__option__text">
                <span class="enp-results-question__option__text__helper">high</span> <?php echo ( $slider_correct_high + $slider->get_slider_increment() );?> to <?php echo $slider->get_slider_range_high();?>
            </div>
            <div class="enp-results-question__option__number-selected">
                <?php echo $slider->get_slider_responses_high();?>&nbsp;/&nbsp;<span class="enp-results-question__option__percentage enp-results-question__option__percentage--incorrect"><?php echo $this->percentagize( $slider->get_slider_responses_high(), $slider->get_slider_responses_total(), 1);?>%</span>
            </div>
        </li>
    <?php } ?>
</ul>

<div class="enp-slider-responses">
    <h4 class="enp-slider-responses__title">Response Distribution</h4>
    <?php
    // outputs slider response JSON
    $this->slider_results_json($slider);?>
    <div id="enp-slider-responses__line-chart--<?php echo $slider_id;?>" data-slider-id="<?php echo $slider_id;?>" class="enp-slider-responses__line-chart"></div>
</div>

<div class="enp-slider-responses-table__content">
    <table class="enp-slider-responses-table">
        <tbody>
            <tr>
                <th class="enp-slider-responses-table__response">Response</th>
                <th class="enp-slider-responses-table__response-frequency"><span class="enp-slider-responses-table__response-frequency__text"># of Responses</span></th>
            </tr>
            <?php
            $response_frequency = $slider->get_slider_responses_frequency();
            foreach($response_frequency as $key => $val) {
                // see if it's correct or not
                $check_if_correct = $slider->check_slider_answer($key);
                ?>
                <tr class="enp-slider-responses-table__row <?php echo ($check_if_correct === 'correct' ? 'enp-slider-responses-table__response--correct' : 'enp-slider-responses-table__response--incorrect');?><?php echo ($val === 0 ? ' enp-slider-responses-table__frequency--zero' : '');?>">
                    <td class="enp-slider-responses-table__response"><?php echo (float) $key;?></td>
                    <td class="enp-slider-responses-table__frequency"><?php echo $val;?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
