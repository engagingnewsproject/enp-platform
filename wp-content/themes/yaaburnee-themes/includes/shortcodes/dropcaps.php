<?php
	add_shortcode('dropcaps', 'dropcaps_handler');

	function dropcaps_handler($atts, $content=null, $code="") {

		return '<p class="dropcap">'.do_shortcode($content).'</p>';
	
	}
?>