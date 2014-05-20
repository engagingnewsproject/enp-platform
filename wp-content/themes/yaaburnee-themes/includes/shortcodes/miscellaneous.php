<?php
	add_shortcode('miscellaneous', 'miscellaneous_handler');

	function miscellaneous_handler($atts, $content=null, $code="") {
		if($atts['type']=="subscript") {
			return '<sub>'.$content.'</sub>';
		} else if ($atts['type']=="superscript"){
			return '<sup>'.$content.'</sup>';
		} else if ($atts['type']=="small"){
			return '<small>'.$content.'</small>';
		}
	
	}
?>