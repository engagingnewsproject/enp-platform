(function($) {
	'use strict';



/* -------------------------------------------------------------------------*
 * 								addLoadEvent
 * -------------------------------------------------------------------------*/
	function addLoadEvent(func) {
		var oldonload = window.onload;
		if (typeof window.onload != 'function') {
			window.onload = func;
		} else {
			window.onload = function() {
				if (oldonload) {
					oldonload();
				}
			func();
			}
		}
	}

	/*---------------------------------
	  CATEGORY HOVER
	  ---------------------------------*/
	jQuery("ul.widget-category li a").mouseover(function() {
		var thisel = jQuery(this);
		thisel.css("background-color", thisel.data("hovercolor"));
	}).mouseout(function() {
		jQuery(this).css("background-color", "transparent");
	});
	
})(jQuery);