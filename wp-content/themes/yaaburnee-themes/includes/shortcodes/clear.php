<?php
	add_shortcode('clear', 'clear_handler');

	function clear_handler($atts, $content=null, $code="") {
		return '<div class="clear"></div>';
		
	}
	
	
?>