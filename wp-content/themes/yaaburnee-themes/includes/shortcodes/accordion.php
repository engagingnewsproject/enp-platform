<?php
	add_shortcode('accordion', 'accordion_handler');
	add_shortcode('acc', 'acc_handler');


	function accordion_handler($atts, $content=null, $code="") {
        //$return =  '<div class="accordion">';
       	 $return =  do_shortcode($content);
        //$return.=  '</div>';

		return $return;
	}

	function acc_handler($atts, $content=null, $code="") {
		extract(shortcode_atts(array('title' => null,), $atts) );


		$return='
			<div class="accordion">
				<h4>'.$title.'</h4>
				<div>
					'.do_shortcode(wpautop($content)).'
				</div>
			</div>
			';
		return $return;

	}
?>
