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


	/*---------------
	  Mobile Menu 
	  ----------------*/

	//first, wrap header, #main, footer into a blank 'div'
	//$('header, #main, footer').wrapAll('<div></div>');

	$('#mobile-menu').mmenu({
		classes: 'mm-light'
	}, {
	   // configuration:
	   selectedClass  : "current_page_item",
	   labelClass     : "menu-item",
	   panelNodetype  : "div, ul, ol, header, footer",
	});

	$.fn.mmenu.debug = function( msg ) {
	    console.warn( msg );
	};
	
})(jQuery);