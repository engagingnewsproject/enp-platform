<?php
	header("Content-type: text/css");
	
	$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
	require_once( $parse_uri[0] . 'wp-load.php' );

	//fonts
	$google_font_1 = get_option(THEME_NAME."_google_font_1");
	$google_font_2 = get_option(THEME_NAME."_google_font_2");
	$google_font_3 = get_option(THEME_NAME."_google_font_3");
	$google_font_4 = get_option(THEME_NAME."_google_font_4");
	$google_font_5 = get_option(THEME_NAME."_google_font_5");
	$google_font_6 = get_option(THEME_NAME."_google_font_6");
	$google_font_7 = get_option(THEME_NAME."_google_font_7");

	$font_size_1 = get_option(THEME_NAME."_font_size_1");
	$font_size_2 = get_option(THEME_NAME."_font_size_2");
	$font_size_3 = get_option(THEME_NAME."_font_size_3");
	$font_size_4 = get_option(THEME_NAME."_font_size_4");
	$font_size_5 = get_option(THEME_NAME."_font_size_5");
	$font_size_6 = get_option(THEME_NAME."_font_size_6");
	$font_size_7 = get_option(THEME_NAME."_font_size_7");
	$font_size_8 = get_option(THEME_NAME."_font_size_8");
	$font_size_9 = get_option(THEME_NAME."_font_size_9");
	$font_size_10 = get_option(THEME_NAME."_font_size_10");
	$font_size_11 = get_option(THEME_NAME."_font_size_11");
	$font_size_10 = get_option(THEME_NAME."_font_size_12");


?>

/* ==============================================================================	
	Body / Menu mobile
============================================================================== */
body, .menu-mobile {
	font-family: '<?php echo $google_font_1;?>', sans-serif;
	<?php if($font_size_1) { ?>
	font-size: <?php echo $font_size_1;?>px;
	<?php } ?>
}

/* ==============================================================================	
	Headings
============================================================================== */
h1, h2, h3, h4, h5, h6 {font-family: '<?php echo $google_font_2;?>', serif;}


/* ==============================================================================	
	Top menu
============================================================================== */
ul.top-navigation {
	font-family: '<?php echo $google_font_3;?>', serif;
	<?php if($font_size_2) { ?>
	font-size: <?php echo $font_size_2;?>px;
	<?php } ?>
}

/* ==============================================================================	
	Main menu
============================================================================== */
ul.primary-navigation {
	font-family: '<?php echo $google_font_4;?>', serif;
	<?php if($font_size_3) { ?>
	font-size: <?php echo $font_size_3;?>px;
	<?php } ?>
}

/* ==============================================================================	
	Footer menu
============================================================================== */
ul.footer-navigation {
	font-family: '<?php echo $google_font_5;?>', serif;
	<?php if($font_size_4) { ?>
	font-size: <?php echo $font_size_4;?>px;
	<?php } ?>
}

/* ==============================================================================	
	Widgets titles
============================================================================== */
h3.widget-title {
	font-family: '<?php echo $google_font_6;?>', serif;
	<?php if($font_size_5) { ?>
	font-size: <?php echo $font_size_5;?>px;
	<?php } ?>
}

/* ==============================================================================	
	Slider caption / Review / Tag list span / Tabs header / Accordion header
        Shop widget cart / Best seller links / Blockquotes / Pullquotes / Dropcap
============================================================================== */
.bx-wrapper .bx-caption span, 
.review-block .rev-score, 
.review-block .rev-title,
.tag-list span,
.tabs ul.tabs-list li a,
ul.cart_list li a,
ul.product_list_widget li a,
blockquote p,
.pullquote-left,
.pullquote-right,
.dropcap:first-letter {
	font-family: '<?php echo $google_font_7;?>', serif;
	<?php if($font_size_6) { ?>
	font-size: <?php echo $font_size_6;?>px;
	<?php } ?>
}
