<?php
	add_shortcode('social', 'social_handler');
	add_shortcode('account', 'social_handler');
	

	function social_handler($atts, $content=null, $code="") {

		
		if($code == "account") {
			/* Icon */
			$icon=$atts["icon"];
			$iconClass=str_replace("fa-", '', $icon);
			$iconClass=str_replace("-", '', $iconClass);
			if($iconClass=="youtubeplay") {
				$iconClass=str_replace("play", '', $iconClass);	
			} 
			return '<li class="'.$iconClass.'"><a href="'.$content.'" target="_blank"><i class="fa '.$icon.'"></i></a></li>';
		} elseif($code == "social") {
			$content = '<ul class="social-icons">'.$content.'</ul>';
		}
		
		$content = do_shortcode($content);
		$content = remove_br($content);
		return $content;
	}
	
?>