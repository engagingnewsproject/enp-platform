<?php
add_shortcode('button', 'button_handler');

function button_handler($atts, $content=null, $code="") {
	
	/* Color */
	$color=$atts["color"];
	if(isset($atts["style"])) {
		$style = $atts["style"];
	} else {
		$style = false;
	}
	/* Size */
	if(isset($atts["size"])) {
		$size = $atts["size"];
	} else {
		$size = false;
	}
	
	/* Target */
	if(!isset($atts["target"]) || $atts["target"]=="blank") {
		$target="_blank";
	} else {
		$target="_self";
	}

	/* Icon */
	if(isset($atts["icon"]) && $atts["icon"]!="none") {
		$icon = $atts["icon"];
		$icon = "<i class=\"fa ".$icon."\"></i> ";
	} else {
		$icon = false;
	}
	/* Tooltip */
	if(isset($atts["tooltip"]) && $atts["tooltip"]!="") {
		$tooltip = $atts["tooltip"];
		$tooltip = " data-tip=\"".$tooltip."\"";
	} else {
		$tooltip = false;
	}
	
	/* link */
	if(isset($atts["link"])) {
		$link = $atts["link"];
	} else {
		$link = "#";
	}

	$return = '<a href="'.$link.'" class="button '.$size.' '.$style.'"'.$tooltip.' target="'.$target.'"  style="background-color:#'.$color.'; color:#fff">'.$icon.$content.'</a>';


	return $return;
}

?>