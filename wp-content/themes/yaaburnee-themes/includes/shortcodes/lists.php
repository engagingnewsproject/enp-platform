<?php
	add_shortcode('list', 'list_handler');
	add_shortcode('item', 'list_handler');
	

	function list_handler($atts, $content=null, $code="") {

	
		if($code == "item") {
			/* Icon */
			if(isset($atts["icon"])) {
				$icon = $atts["icon"];
				$icon = "<i class=\"fa-li fa ".$icon."\"></i>";
			} else {
				$icon = false;
			}
		
			return '<li>'.$icon.$content.'</li>';
		} elseif($code == "list") {
			$content = '<ul class="fa-ul">'.$content.'</ul>';
		}
		
		$content = do_shortcode($content);
		$content = remove_br($content);
		return $content;
	}
	
?>