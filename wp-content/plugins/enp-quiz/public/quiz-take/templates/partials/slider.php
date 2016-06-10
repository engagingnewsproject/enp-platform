<label for="enp-slider__<?php echo $slider->get_slider_id();?>" class="enp-slider__label enp-question__helper">
    Enter a number between <?php echo $slider->get_slider_range_low();?> and <?php echo $slider->get_slider_range_high();?>
</label>
<div class="enp-slider-input__container">
    <span class="enp-slider-input__prefix"><?php echo $slider->get_slider_prefix();?></span>
    <input id="enp-slider-input__<?php echo $slider->get_slider_id();?>" class="enp-slider-input__input" type="number" name="enp-question-response" min="<?php echo $slider->get_slider_range_low();?>" max="<?php echo $slider->get_slider_range_high();?>" step="<?php echo $slider->get_slider_increment();?>" size="<?php echo $slider->get_slider_input_size();?>" value="<?php echo $slider->get_slider_start();?>"/>
    <span class="enp-slider-input__suffix"><?php echo $slider->get_slider_suffix();?></span>
</div>
