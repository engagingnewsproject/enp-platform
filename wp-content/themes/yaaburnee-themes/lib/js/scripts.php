<?php
	header("Content-type: text/javascript");
	
	$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
	require_once( $parse_uri[0] . 'quiz_previewwp-load.php' );

	//main slider settings
	$mainPause = get_option ( THEME_NAME."_main_pause" );
	$mainSpeed = get_option ( THEME_NAME."_main_speed" );
	$mainAuto = get_option ( THEME_NAME."_main_auto" );
	$mainMode = get_option ( THEME_NAME."_main_mode" );
	$mainCaption = get_option ( THEME_NAME."_main_caption" );

	//main carousel slider settings
	$mainCarouselPause = get_option ( THEME_NAME."_main_carousel_pause" );
	$mainCarouselSpeed = get_option ( THEME_NAME."_main_carousel_speed" );
	$mainCarouselAuto = get_option ( THEME_NAME."_main_carousel_auto" );

	//small carousel slider settings
	$smallCarouselPause = get_option ( THEME_NAME."_small_carousel_pause" );
	$smallCarouselSpeed = get_option ( THEME_NAME."_small_carousel_speed" );
	$smallCarouselAuto = get_option ( THEME_NAME."_small_carousel_auto" );

	//widget slider settings
	$widgetAuto = get_option ( THEME_NAME."_widget_auto" );
	$widgetPause = get_option ( THEME_NAME."_widget_pause" );
	$widgetSpeed = get_option ( THEME_NAME."_widget_speed" );

	//breaking slider settings
	$breakingAuto = get_option ( THEME_NAME."_breaking_auto" );
	$breakingPause = get_option ( THEME_NAME."_breaking_pause" );
	$breakingSpeed = get_option ( THEME_NAME."_breaking_speed" );
	$breakingMode = get_option ( THEME_NAME."_breaking_mode" );

	//post slider settings
	$postAuto = get_option ( THEME_NAME."_post_auto" );
	$postMode = get_option ( THEME_NAME."_post_mode" );
	$postControls = get_option ( THEME_NAME."_post_controls" );
	$postPause = get_option ( THEME_NAME."_post_pause" );
	$postSpeed = get_option ( THEME_NAME."_post_speed" );

	//woocommerce slider settings
	$woocommerceAuto = get_option ( THEME_NAME."_woocommerce_auto" );
	$woocommerceMode = get_option ( THEME_NAME."_woocommerce_mode" );
	$woocommerceControls = get_option ( THEME_NAME."_woocommerce_controls" );
	$woocommercePause = get_option ( THEME_NAME."_woocommerce_pause" );
	$woocommerceSpeed = get_option ( THEME_NAME."_woocommerce_speed" );
?>

	//form validation
	function validateName(fld) {
			
		var error = "";
				
		if (fld.value === '' || fld.value === '<?php _e("Nickname", THEME_NAME);?>' || fld.value === '<?php _e("First Name", THEME_NAME);?>') {
			error = "<?php _e( 'You didn\'t enter Your First Name.' , THEME_NAME );?>\n";
		} else if ((fld.value.length < 2) || (fld.value.length > 50)) {
			error = "<?php _e( 'First Name is the wrong length.' , THEME_NAME );?>\n";
		}
		return error;
	}
			
	function validateEmail(fld) {

		var error="";
		var illegalChars = /^[^@]+@[^@.]+\.[^@]*\w\w$/;
				
		if (fld.value === "") {
			error = "<?php _e( 'You didn\'t enter an email address.' , THEME_NAME );?>\n";
		} else if ( fld.value.match(illegalChars) === null) {
			error = "<?php _e( 'The email address contains illegal characters.' , THEME_NAME );?>\n";
		}

		return error;

	}
			
	function validateMessage(fld) {

		var error = "";
				
		if (fld.value === '' || fld.value === '<?php _e("Message", THEME_NAME);?>') {
			error = "<?php _e( 'You didn\'t enter Your message.' , THEME_NAME );?>\n";
		} else if (fld.value.length < 3) {
			error = "<?php _e( 'The message is to short.' , THEME_NAME );?>\n";
		}

		return error;
	}

	function validateLastname(fld) {
			
		var error = "";

				
		if (fld.value === '' || fld.value === 'Nickname' || fld.value === 'Enter Your Name..' || fld.value === 'Your Name..') {
			error = "<?php _e( 'You didn\'t enter Your last name.' , THEME_NAME );?>\n";
		} else if ((fld.value.length < 2) || (fld.value.length > 50)) {
			error = "<?php  _e( 'Last Name is the wrong length.' , THEME_NAME );?>\n";
		}
		return error;
	}

	function validatePhone(fld) {
			
		var error = "";
		var illegalChars = /^\+?s*\d+\s*$/;

		if (fld.value === '') {
			error = "<?php _e( 'You didn\'t enter Your phone number.' , THEME_NAME );?>\n";
		} else if ( fld.value.match(illegalChars) === null) {
			error = "<?php _e( 'Please enter a valid phone number.' , THEME_NAME );?>\n";
		}
		return error;
	}


(function($) {   
'use strict'; 

	$(document).ready(function(){
	    /*---------------------------------
			Main carousel
		---------------------------------*/
		$("#main-carousel div.carousel-group").bxSlider({
            pause: <?php echo $mainCarouselPause;?>,        // In milisecunds, the duration between each slide transition.
            speed: <?php echo $mainCarouselSpeed;?>,         // In milisecunds, duration of time slide transitions will occupy.
            minSlides: 1,
            maxSlides: 4,
            slideWidth: 285,
            slideMargin: 19,
            prevText: "&#xf190;",
            nextText: "&#xf18e;",
            adaptiveHeight: false,
            controls: true,
            pager: false,
            auto: <?php echo $mainCarouselAuto;?>,        // true - Start automatically.
            autoHover: true,
            onSliderLoad: function(){
	        	$("#main-carousel div.carousel-group").css({
				      "visibility": "visible",
				      "height": "auto"
				});
	        	$("#main-carousel").removeClass("bx-loading");
	      	}
	    });
    
	    
	    /*---------------------------------
			Small carousel
		---------------------------------*/
		$(".small-carousel div.carousel-group").bxSlider({
            pause: <?php echo $smallCarouselPause;?>,        // In milisecunds, the duration between each slide transition.
            speed: <?php echo $smallCarouselSpeed;?>,         // In milisecunds, duration of time slide transitions will occupy.
            minSlides: 2,
            maxSlides: 4,
            slideWidth: 206,
            slideMargin: 13,
            prevText: "&#xf190;",
            nextText: "&#xf18e;",
            adaptiveHeight: false,
            controls: true,
            pager: false,
            auto: <?php echo $smallCarouselAuto;?>,        // true - Start automatically.
            autoHover: true,
            onSliderLoad: function(){
	        	$(".small-carousel div.carousel-group").css({
				      "visibility": "visible",
				      "height": "auto"
				});
	        	$(".small-carousel").removeClass("bx-loading");
	      	}
	    });

		/*---------------------------------
			Slider
		---------------------------------*/
        $('.slider').bxSlider({
            mode: '<?php echo $mainMode;?>',
            pause: <?php echo $mainPause;?>,        // In milisecunds, the duration between each slide transition.
            speed: <?php echo $mainSpeed;?>,         // In milisecunds, duration of time slide transitions will occupy.
            auto: <?php echo $mainAuto;?>,        // true - Start automatically.
            autoHover: true,    // true, false - if true show will pause on mouseover
            pager: true,
            onSliderLoad: function(){
	        	$("#main-slider ul.slider").css({
				      "visibility": "visible",
				      "height": "auto"
				});
	        	$("#main-slider").removeClass("bx-loading");
	      	}
		});
	        
	    /*---------------------------------
			Gallery block
		---------------------------------*/
		$('.gallery-block ul').bxSlider({
            pause: <?php echo $postPause;?>,        // In milisecunds, the duration between each slide transition.
            speed: <?php echo $postSpeed;?>,         // In milisecunds, duration of time slide transitions will occupy.
            mode: '<?php echo $postMode;?>',
            controls: <?php echo $postControls;?>,
            auto: <?php echo $postAuto;?>,        // true - Start automatically.
            prevText: "&#xf190;",
            nextText: "&#xf18e;",
            pager: true,
            pagerCustom: '.gallery-pager',
           	onSliderLoad: function(){
	        	$(".gallery-block ul").css({
				      "visibility": "visible",
				      "height": "auto"
				});
	        	$(".gallery-block").removeClass("bx-loading");
	      	}
		});
	        	        
	    /*---------------------------------
			Woocommerce block
		---------------------------------*/
		$('.woocommerce-block ul').bxSlider({
            pause: <?php echo $woocommercePause;?>,        // In milisecunds, the duration between each slide transition.
            speed: <?php echo $woocommerceSpeed;?>,         // In milisecunds, duration of time slide transitions will occupy.
            mode: '<?php echo $woocommerceMode;?>',
            controls: <?php echo $woocommerceControls;?>,
            auto: <?php echo $woocommerceAuto;?>,        // true - Start automatically.
            prevText: "&#xf190;",
            nextText: "&#xf18e;",
            pager: true,
            adaptiveHeight: true,
            pagerCustom: '.woocommerce-pager',
           	onSliderLoad: function(){
	        	$(".woocommerce-block ul").css({
				      "visibility": "visible",
				      "height": "auto"
				});
	        	$(".woocommerce-block").removeClass("bx-loading");
	      	}
		});
	        
	    /*---------------------------------
			Related articles
		---------------------------------*/
		$('.related-articles-group').bxSlider({
            mode: 'horizontal',
            captions: false,
            pager: false,
            controls: true,
            prevText: "&#xf190;",
            nextText: "&#xf18e;",
            minSlides: 2,
            maxSlides: 4,
            slideWidth: 206,
            slideMargin: 13
		});
	        
	        /*---------------------------------
			Widget slider
		---------------------------------*/
		$('.widget-slider ul').bxSlider({
            captions: false,
            pager: false,
            controls: true,
            auto: <?php echo $widgetAuto;?>,        // true - Start automatically.
            pause: <?php echo $widgetPause;?>,        // In milisecunds, the duration between each slide transition.
            speed: <?php echo $widgetSpeed;?>,         // In milisecunds, duration of time slide transitions will occupy.
            prevText: "&#xf190;",
            nextText: "&#xf18e;",
            onSliderLoad: function(){
	        	$(".widget-slider ul").css({
				      "visibility": "visible",
				      "height": "auto"
				});
	        	$(".widget-slider").removeClass("bx-loading");
	      	}
		});
    
	    /*---------------------------------
		Breaking news
		---------------------------------*/
		$("#breaking-news ul").bxSlider({
            mode: '<?php echo $breakingMode;?>',
            auto: <?php echo $breakingAuto;?>,        // true - Start automatically.
            autoHover: true,    // true, false - if true show will pause on mouseover
            pause: <?php echo $breakingPause;?>,        // In milisecunds, the duration between each slide transition.
            speed: <?php echo $breakingSpeed;?>,         // In milisecunds, duration of time slide transitions will occupy.
            onSliderLoad: function(){
	        	$("#breaking-news ul").css({
				      "visibility": "visible",
				      "height": "auto"
				});
	        	$("#breaking-news").removeClass("bx-loading");
	      	}
	    });
    });

})(jQuery);