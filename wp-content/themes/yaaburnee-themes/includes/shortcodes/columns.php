<?php

	add_shortcode('half', 'half_handler');

	function half_handler($atts, $content=null, $code="") {
		return "<div class=\"col6\">".do_shortcode($content)."</div>";
	}

	add_shortcode('one-third', 'one_third_handler');

	function one_third_handler($atts, $content=null, $code="") {
		return "<div class=\"col4\">".do_shortcode($content)."</div>";
	}
	add_shortcode('two-thirds', 'two_third_handler');

	function two_third_handler($atts, $content=null, $code="") {
		return "<div class=\"col8\">".do_shortcode($content)."</div>";
	}

	add_shortcode('four', 'four_handler');

	function four_handler($atts, $content=null, $code="") {
		return "<div class=\"col3\">".do_shortcode($content)."</div>";
	}

	add_shortcode('third', 'third_handler');

	function third_handler($atts, $content=null, $code="") {
		return "<div class=\"col4\">".do_shortcode($content)."</div>";
	}

	add_shortcode('row', 'row_handler');

	function row_handler($atts, $content=null, $code="") {
		return "<div class=\"row\">".do_shortcode($content)."</div>";
	}


?>