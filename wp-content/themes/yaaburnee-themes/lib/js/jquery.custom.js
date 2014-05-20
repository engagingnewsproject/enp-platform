(function($) {
'use strict';
/*---------------------------------
  Responsive navigation
  ---------------------------------*/
function setupMenu( $menu ) {
    $menu.each(function() {
        var mobileMenu = $(this).clone();
        var mobileMenuWrap = $('<div></div>').append(mobileMenu);
        mobileMenuWrap.attr('class', "open-close-wrapper");
        $(this).parent().append(mobileMenuWrap);
        mobileMenu.attr('class', 'menu-mobile');
    });
}
function setupMobileMenu() {
    $(".inner").each(function() {
        var clickTopOpenMenu = $(this).find(".click-to-open-menu");
        clickTopOpenMenu.click(function() {
            $(this).parent().find('.open-close-wrapper').slideToggle("fast");
        });
    });
}




$(document).ready(function($) {
	/*---------------------------------
		Navigation
	---------------------------------*/
        setupMenu($('ul.top-navigation'));
        setupMenu($('ul.primary-navigation'));
        setupMenu($('ul.footer-navigation'));
        setupMobileMenu();
        $('ul.top-navigation').superfish({cssArrows:true});
		$('ul.primary-navigation').superfish({cssArrows:false});
        
   
	/*---------------------------------
	  Lightbox
	  ---------------------------------*/
	$("a[rel^='lightbox']").prettyPhoto({
		animation_speed: 'fast', /* fast/slow/normal */
		slideshow: 5000, /* false OR interval time in ms */
		autoplay_slideshow: false, /* true/false */
		opacity: 0.80, /* Value between 0 and 1 */
		show_title: true, /* true/false */
		allow_resize: true, /* Resize the photos bigger than viewport. true/false */
		default_width: 500,
		default_height: 344,
		theme: 'pp_default', /* light_rounded / dark_rounded / light_square / dark_square / facebook */
		hideflash: false, /* Hides all the flash object on a page, set to TRUE if flash appears over prettyPhoto */
		wmode: 'opaque', /* Set the flash wmode attribute */
		autoplay: true, /* Automatically start videos: True/False */
		deeplinking: true, /* Allow prettyPhoto to update the url to enable deeplinking. */
		overlay_gallery: true, /* If set to true, a gallery will overlay the fullscreen image on mouse over */
		keyboard_shortcuts: true /* Set to false if you open forms inside prettyPhoto */
	});

	/*---------------------------------
	  Fitvids
	  ---------------------------------*/
	$("body").fitVids();
        
        /*---------------------------------
	  Tabs
	  ---------------------------------*/
        $(".tabs").tabs({
            heightStyle: "content"  // "auto": All panels will be set to the height of the tallest panel.
        });
        
        /*---------------------------------
	  Tabs
	  ---------------------------------*/
        $(".zodiac-tabs").tabs({
            heightStyle: "content"  // "auto": All panels will be set to the height of the tallest panel.
        });
        
        /*---------------------------------
	  Accordion
	  ---------------------------------*/
        $( ".accordion" ).accordion({
            header: "h4",
            icons: false,
            active: false,
            animate: true,          // True - If to animate changing panels.
            collapsible: true,      // Whether all the sections can be closed at once.
            heightStyle: "content"  // Don't change this.
        });

        
        /*---------------------------------
	  Custom select box
	  ---------------------------------*/
        $('.orderby').customSelect();

	/*---------------------------------
	  Back to top
	  ---------------------------------*/
	$("#back-to-top").click(function () {
		$("body,html").animate({
			scrollTop: 0
		}, 800);
		return false;
	});   
        
        /*---------------------------------
	  CONTACT FORM
	  ---------------------------------*/
	$('#contactform').submit(function() {
		var action = $(this).attr('action');
		var values = $(this).serialize();
		$('#submit').attr('disabled', 'disabled').after('<img src="lib/images/contact-form/ajax-loader.gif" class="loader" />');
		$("#message").slideUp(750, function() {
			$('#message').hide();
			$.post(action, values, function(data) {
				$('#message').html(data);
				$('#message').slideDown('slow');
				$('#contactform img.loader').fadeOut('fast', function() {
					$(this).remove()
				});
				$('#submit').removeAttr('disabled');
				if (data.match('success') != null) $('#contactform').slideUp('slow');
			});
		});
		return false;
	});
        
});
        
    /*---------------------------------
	  HIDE WOO EMPTY CART
	---------------------------------*/


	if ( $.cookie( "woocommerce_items_in_cart" ) > 0 ) {
		$('.hide_cart_widget_if_empty').closest('.widget').show();
	} else {
		$('.hide_cart_widget_if_empty').closest('.widget').hide();
	}

	jQuery('body').bind('adding_to_cart', function(){
	    jQuery(this).find('.hide_cart_widget_if_empty').closest('.widget').fadeIn();
	});

    /*---------------------------------
	  WEATHER
	---------------------------------*/
	$("#weather .report-city strong").css("white-space","nowrap");
	$("#weather .report-city p").css("white-space","nowrap");
	$("#weather .report-city").css("white-space","nowrap");

	var weatherWidth = $("#weather .report-city span").outerWidth() + $("#weather .report-city strong").outerWidth() + 38;
	var breakingWidth = $("#primary-navigation .inner").outerWidth() - weatherWidth
	var weatherWidthFull = $("#weather .report-city span").outerWidth() + $("#weather .report-city strong").outerWidth() + $("#weather .report-city p").outerWidth() + 50;
	var breakingWidthSmall = $("#primary-navigation .inner").outerWidth() - weatherWidthFull;

	$("#weather").css('width', weatherWidth+"px");
	$("#breaking-news").css('width', breakingWidth+"px");
	
	//if the breaking news slider is off
	$("#weather.no-breaking").css('width', weatherWidthFull+"px");
	$("#weather.no-breaking .report-city p").css("display","inline-block");

	//error
	$("#weather span.error").css("white-space","nowrap");
	$("#weather>span.error").parent().css('width', "170px");
	$("#weather>span.error").parent().parent().find('#breaking-news').css('width', $("#primary-navigation .inner").outerWidth()-170+"px");


	$("#weather:not(#weather.no-breaking)").toggle(showWeather,hideWeather);

	function showWeather() {
		$("#weather .report-city p").css("display","inline-block");
		$("#weather").animate({ width: weatherWidthFull+"px"}, 1000);
		$("#breaking-news").animate({ width: breakingWidthSmall+"px"}, 1000);
	}
	function hideWeather() {
		$("#weather").animate({ width: weatherWidth+"px"}, 1000);
		$("#breaking-news").animate({ width: breakingWidth+"px"}, 1000);
		$('#weather .report-city p').delay(1000)
		  .queue( function(next){ 
		    $(this).css('display','none'); 
		    next(); 
		});
	}
	


})(jQuery);