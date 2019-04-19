<fieldset class="enp-slider-options">
    <legend class="enp-legend enp-slider__legend">Slider Options</legend>
    <input class="enp-slider-id" type="hidden" name="enp_question[<?php echo $question_i;?>][slider][slider_id]" value="<?php echo $slider->get_slider_id();?>" />

    <div class="enp-slider-range__container">
        <div class="enp-slider-range-low__container">
            <?php echo $Quiz_create->get_slider_range_low_input($slider, $question_i);?>
        </div>
        <div class="enp-slider-range__helper">to</div>
        <div class="enp-slider-range-high__container">
            <?php echo $Quiz_create->get_slider_range_high_input($slider, $question_i);?>
        </div>
    </div>

    <div class="enp-slider-correct__container">
        <div class="enp-slider-correct-low__container">
            <?php echo $Quiz_create->get_slider_correct_low_input($slider, $question_i);?>
        </div>
        <div class="enp-slider-correct__helper">to</div>
        <div class="enp-slider-correct-high__container">
            <div class="enp-slider-correct-high__input-container">
                <?php echo $Quiz_create->get_slider_correct_high_input($slider, $question_i);?>
            </div>
        </div>
    </div>

    <div class="enp-slider-advanced-options__content">
        <?php echo $Quiz_create->get_slider_increment_input($slider, $question_i);?>

        <label class="enp-label enp-slider-prefix__label" for="enp-slider-prefix__<?php echo $slider->get_slider_id();?>">Slider Number Prefix</label>
        <input id="enp-slider-prefix__<?php echo $slider->get_slider_id();?>" class="enp-input enp-slider-prefix__input" type="text" maxlength="100" name="enp_question[<?php echo $question_i;?>][slider][slider_prefix]" value="<?php echo $slider->get_slider_prefix();?>">

        <label class="enp-label enp-slider-suffix__label" for="enp-slider-suffix__<?php echo $slider->get_slider_id();?>">Slider Number Suffix</label>
        <input id="enp-slider-suffix__<?php echo $slider->get_slider_id();?>" class="enp-input enp-slider-suffix__input" type="text" maxlength="100" name="enp_question[<?php echo $question_i;?>][slider][slider_suffix]" value="<?php echo $slider->get_slider_suffix();?>">
    </div>
</fieldset>
