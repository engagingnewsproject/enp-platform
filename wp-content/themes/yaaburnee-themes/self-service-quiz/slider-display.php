<div class="slider-wrapper" id="slider-wapper">
  <div style="margin-bottom: 0.5em; position: relative; width: 100%">
  <input type="text" id="preview-slider" value="" data-slider-min="<?php echo $slider_options ? $slider_options->slider_low : 0; ?>" data-slider-max="<?php echo $slider_options ? $slider_options->slider_high : 10; ?>" data-slider-step="<?php echo $slider_options ? $slider_options->slider_increment : 1; ?>" data-slider-value="<?php echo $slider_options ? $slider_options->slider_start : 5; ?>" data-slider-orientation="horizontal" data-slider-tooltip="show" style="width:100%" >
  </div>
  <div>
  <b class="pull-left" style="margin-left: 2%;"><span class="slider-low-label"><?php echo $slider_options ? $slider_options->slider_low : 0; ?></span><?php if( $slider_options ) { echo $slider_options->slider_label == '%' ? '' : ' '; } ?><span class="slider-display-label"><?php echo $slider_options ? $slider_options->slider_label : '%'; ?></span></b>
  <b class="pull-right" style="margin-right: 2%;"><span class="slider-high-label"><?php echo $slider_options ? $slider_options->slider_high : 10; ?></span><?php if( $slider_options ) { echo $slider_options->slider_label == '%' ? '' : ' '; } ?><span class="slider-display-label"><?php echo $slider_options ? $slider_options->slider_label : '%'; ?></span></b>
  <br style="clear: both">
  </div>
</div>