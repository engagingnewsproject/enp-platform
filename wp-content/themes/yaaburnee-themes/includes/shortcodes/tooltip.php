<?php
	add_shortcode('tooltip', 'tooltip_handler');

	function tooltip_handler($atts, $content=null, $code="") {
		/* Target */
		if(!isset($atts["target"]) || $atts["target"]=="blank") {
			$target="_blank";
		} else {
			$target="_self";
		}

		/* link */
		if(isset($atts["url"])) {
			$link = $atts["url"];
		} else {
			$link = "#";
		}
	
			return '<a href="'.$link.'" target="'.$target.'" data-tip="'.$atts['text'].'">'.do_shortcode($content).'</a>';
	}
	

?>