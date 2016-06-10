<fieldset class="enp-slider-options">
    <legend class="enp-legend enp-slider__legend">Slider Options</legend>
    <input class="enp-slider-id" type="hidden" name="enp_question[<?php echo $question_i;?>][slider][slider_id]" value="<?php echo $slider->get_slider_id();?>" />

    <div class="enp-slider-range__container">
        <div class="enp-slider-range-low__container">
            <label class="enp-label enp-slider-range-low__label" for="enp-slider-range-low__<?php echo $slider->get_slider_id();?>">Slider Start</label>
            <input id="enp-slider-range-low__<?php echo $slider->get_slider_id();?>" class="enp-input enp-slider-range-low__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question[<?php echo $question_i;?>][slider][slider_range_low]" value="<?php echo $slider->get_slider_range_low();?>" step="any">
        </div>
        <div class="enp-slider-range__helper">to</div>
        <div class="enp-slider-range-high__container">
            <label class="enp-label enp-slider-range-high__label" for="enp-slider-range-high__<?php echo $slider->get_slider_id();?>">Slider End</label>
            <input id="enp-slider-range-high__<?php echo $slider->get_slider_id();?>" class="enp-input enp-slider-range-high__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question[<?php echo $question_i;?>][slider][slider_range_high]" value="<?php echo $slider->get_slider_range_high();?>" step="any">
        </div>
    </div>

    <div class="enp-slider-correct__container">
        <div class="enp-slider-correct-low__container">
            <label class="enp-label enp-slider-correct-low__label" for="enp-slider-correct-low__<?php echo $slider->get_slider_id();?>">Slider Answer Low</label>
            <input id="enp-slider-correct-low__<?php echo $slider->get_slider_id();?>" class="enp-input enp-slider-correct-low__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question[<?php echo $question_i;?>][slider][slider_correct_low]" value="<?php echo $slider->get_slider_correct_low();?>" step="any">
        </div>
        <div class="enp-slider-correct__helper">to</div>
        <div class="enp-slider-correct-high__container">
            <div class="enp-slider-correct-high__input-container">
                <label class="enp-label enp-slider-correct-high__label" for="enp-slider-correct-high__<?php echo $slider->get_slider_id();?>">Slider Answer High</label>
                <input id="enp-slider-correct-high__<?php echo $slider->get_slider_id();?>" class="enp-input enp-slider-correct-high__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question[<?php echo $question_i;?>][slider][slider_correct_high]" value="<?php echo $slider->get_slider_correct_high();?>" step="any">
            </div>
        </div>
    </div>

    <div class="enp-slider-advanced-options__content">
        <label class="enp-label enp-slider-increment__label" for="enp-slider-increment__<?php echo $slider->get_slider_id();?>">Slider Increment</label>
        <input id="enp-slider-increment__<?php echo $slider->get_slider_id();?>" class="enp-input enp-slider-increment__input" type="number" min="-9999999999999999.9999" max="9999999999999999.9999" name="enp_question[<?php echo $question_i;?>][slider][slider_increment]" value="<?php echo $slider->get_slider_increment();?>" step="any">

        <label class="enp-label enp-slider-prefix__label" for="enp-slider-prefix__<?php echo $slider->get_slider_id();?>">Slider Number Prefix</label>
        <input id="enp-slider-prefix__<?php echo $slider->get_slider_id();?>" class="enp-input enp-slider-prefix__input" type="text" maxlength="100" name="enp_question[<?php echo $question_i;?>][slider][slider_prefix]" value="<?php echo $slider->get_slider_prefix();?>">

        <label class="enp-label enp-slider-suffix__label" for="enp-slider-suffix__<?php echo $slider->get_slider_id();?>">Slider Number Suffix</label>
        <input id="enp-slider-suffix__<?php echo $slider->get_slider_id();?>" class="enp-input enp-slider-suffix__input" type="text" maxlength="100" name="enp_question[<?php echo $question_i;?>][slider][slider_suffix]" value="<?php echo $slider->get_slider_suffix();?>">
    </div>
</fieldset>
