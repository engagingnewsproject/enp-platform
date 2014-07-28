<?php
	add_shortcode('textmarker', 'textmarker_handler');

	function textmarker_handler($atts, $content=null, $code="") {
		if($atts['type']=="background color") {
			return '<span class="highlight" style="background: #'.$atts['color'].'; color: white;">'.$content.'</span>';
		} else {
			return '<strong class="color" style="color: #'.$atts['color'].';">'.$content.'</strong>';
		}
	
	}
?>