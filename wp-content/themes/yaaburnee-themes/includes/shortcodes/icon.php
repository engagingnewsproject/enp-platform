<?php
	add_shortcode('icon', 'icon_handler');

	function icon_handler($atts, $content=null, $code="") {
		extract(shortcode_atts(array('style' => null,'size' => null,'color' => null), $atts) );
		return '<i class="fa '.$style.' fa-'.$size.'" style="color:#'.$color.'"></i>';
	}

?>