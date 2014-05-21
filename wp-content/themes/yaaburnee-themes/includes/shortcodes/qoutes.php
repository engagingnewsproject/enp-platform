<?php
	add_shortcode('blocktext', 'blocktext_handler');

	function blocktext_handler($atts, $content=null, $code="") {

		$return =  '<span class="pullquote-'.$atts['align'].'">';
		$return.=  do_shortcode($content);
		$return.=  '</span>';

		return $return;
	}
?>
