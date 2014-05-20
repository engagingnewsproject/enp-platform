<?php
	add_shortcode('spacer', 'spacer_handler');

	function spacer_handler($atts, $content=null, $code="") {
		switch ($atts['style']) {
			case '1':
				return '<hr />';
				break;
			case '2':
				return '<hr class="alt1" />';
				break;
			case '3':
				return '<hr class="alt2" />';
				break;
			default:
				return '<hr />';
				break;
		}
		

	}
?>