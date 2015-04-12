<?php
	header("Content-type: text/css");

	$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
	require_once( $parse_uri[0] . 'wp/wp-load.php' );

	
	$banner_type = get_option ( THEME_NAME."_banner_type" );

	$color_1 = get_option ( THEME_NAME."_color_1" );
	$color_2 = get_option ( THEME_NAME."_color_2" );
	$color_3 = get_option ( THEME_NAME."_color_3" );
	$color_4 = get_option ( THEME_NAME."_color_4" );
	$color_5 = get_option ( THEME_NAME."_color_5" );
	$color_6 = get_option ( THEME_NAME."_color_6" );
	$color_7 = get_option ( THEME_NAME."_color_7" );


	//body bg options
	$bodyBgType = get_option ( THEME_NAME."_body_bg_type" );
	$bodyPattern = get_option ( THEME_NAME."_body_pattern" );
	$bodyColor = get_option ( THEME_NAME."_body_color" );
	$bodyImage = get_option ( THEME_NAME."_body_image" );

	
	
?>



/* POPUP BANNER */

<?php
	if ( $banner_type == "image" ) {
	//Image Banner
?>
		#overlay { width:100%; height:100%; position:fixed;  _position:absolute; top:0; left:0; z-index:10003; background-color:#000000; overflow: hidden;  }
		#popup { display: none; position:absolute; width:auto; height:auto; z-index:10004; color: #000; font-family: Tahoma,sans-serif;font-size: 14px; }
		#baner_close { width: 22px; height: 25px; background: url(<?php echo THEME_IMAGE_URL; ?>close.png) 0 0 repeat; text-indent: -5000px; position: absolute; right: -10px; top: -10px; }
<?php
	} else if ( $banner_type == "text" ) {
	//Text Banner
?>
		#overlay { width:100%; height:100%; position:fixed;  _position:absolute; top:0; left:0; z-index:10003; background-color:#000000; overflow: hidden;  }
		#popup { display: none; position:absolute; width:auto; height:auto; max-width:700px; z-index:10004; border: 1px solid #000; background: #e5e5e5 url(<?php echo THEME_IMAGE_URL; ?>dotted-bg-6.png) 0 0 repeat; color: #000; font-family: Tahoma,sans-serif;font-size: 14px; line-height: 24px; border: 1px solid #cccccc; -moz-border-radius: 4px; -webkit-border-radius: 4px; border-radius: 4px; text-shadow: #fff 0 1px 0; }
		#popup center { display: block; padding: 20px 20px 20px 20px; }
		#baner_close { width: 22px; height: 25px; background: url(<?php echo THEME_IMAGE_URL; ?>close.png) 0 0 repeat; text-indent: -5000px; position: absolute; right: -12px; top: -12px; }
<?php 
	} else if ( $banner_type == "text_image" ) {
	//Image And Text Banner
?>
		#overlay { width:100%; height:100%; position:fixed;  _position:absolute; top:0; left:0; z-index:10003; background-color:#000000; overflow: hidden;  }
		#popup { display: none; position:absolute; width:auto; z-index:10004; color: #000; font-size: 11px; font-weight: bold; }
		#popup center { padding: 15px 0 0 0; }
		#baner_close { width: 22px; height: 25px; background: url(<?php echo THEME_IMAGE_URL; ?>close.png) 0 0 repeat; text-indent: -5000px; position: absolute; right: -10px; top: -10px; }
<?php } ?>

/* ==============================================================================	
	Text colors
        Links / 404 Page smile
============================================================================== */
a, 
#page-404 h3 span {color:#<?php echo $color_1;?>}
/* ==============================================================================	
	Background colors
        Body  / Colored butons / Date and comment in slider / Filter price range / Add to cart on single product
============================================================================== */

.button.btn-colored, 
#main-slider .caption .entry-meta,
.ui-slider .ui-slider-range,
.widget_shopping_cart_content p.buttons a,
form.cart button i,
input.checkout-button,
input.checkout-button:hover {background-color:#<?php echo $color_2;?>}

/* ==============================================================================	
	Widget title arrow / Widget tabs arrow / Carousel arrow
============================================================================== */
.widget h3.widget-title:after,
.tabs ul.tabs-list li.ui-tabs-active:after,
.carousel-title:after {border-top-color: #<?php echo $color_3;?>}

/* ==============================================================================	
	Widget title lines / Widget tab lines / Carousel line
============================================================================== */
.widget h3.widget-title,
.tabs ul.tabs-list li.ui-tabs-active a,
.tabs ul.tabs-list,
.carousel-title {border-color: #<?php echo $color_4;?>}



/* ==========================================================================
   Body
   ========================================================================== */
<?php if($bodyBgType=="pattern") { ?>
body { 
	background-color: #ddd; 
	background-image: url(../img/patterns/<?php echo $bodyPattern;?>.png)
}
<?php } elseif($bodyBgType=="color") { ?>
body { 
	background-color: #<?php echo $bodyColor;?>; 
}
<?php } elseif($bodyBgType=="image") { ?>
body { 
	background-image: url(<?php echo $bodyImage;?>);
	background-attachment:fixed;
	background-position:center;
}
<?php } ?>
