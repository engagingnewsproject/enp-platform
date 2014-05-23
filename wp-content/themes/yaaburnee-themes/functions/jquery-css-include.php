<?php
	
	function different_themes_scripts() { 
		global $wp_styles, $wp_scripts;
		$banner_type = get_option ( THEME_NAME."_banner_type" );
		$layout = get_option ( THEME_NAME."_layout" );
		$mainMenuStyle = get_option ( THEME_NAME."_main_menu" );
		$post_type = get_post_type();
		$menu_type = get_option ( THEME_NAME."_menu_type" );
		$pageWidth = get_option(THEME_NAME.'_page_width');	

		$protocol = is_ssl() ? 'https' : 'http';
		
		//include google fonts
		$google_fonts = array();
		for($i=1; $i<=7; $i++) {
			if(get_option(THEME_NAME."_google_font_".$i)) {
				$google_fonts[] = get_option(THEME_NAME."_google_font_".$i);	
			}
			
		}
		$google_fonts = array_unique($google_fonts);
		$i=1;
		foreach($google_fonts as $google_font) {
			$protocol = is_ssl() ? 'https' : 'http';
			if($google_font && $google_font!="Arial" ) {
				wp_enqueue_style( 'google-fonts-'.$i, $protocol."://fonts.googleapis.com/css?family=".str_replace(" ", "+", $google_font));
			}
			$i++;
		}

		
		wp_enqueue_style("main-style", THEME_CSS_URL."style.css", Array());
		wp_enqueue_style("layout", THEME_CSS_URL."layout.css", Array());
		wp_enqueue_style("background", THEME_CSS_URL."background.css", Array());
		wp_enqueue_style("mobile", THEME_CSS_URL."mobile.css", Array());
		wp_enqueue_style("icons", THEME_CSS_URL."icons.css", Array());
		wp_enqueue_style("lightbox", THEME_CSS_URL."lightbox.css", Array());

		switch ($pageWidth) {
			case '1':
				wp_enqueue_style("df-width-1200", THEME_CSS_URL."1200.css", Array());
				break;
			case '2':
				wp_enqueue_style("df-width-1100", THEME_CSS_URL."1100.css", Array());
				break;
			case '3':
				wp_enqueue_style("df-width-1024", THEME_CSS_URL."1024.css", Array());
				break;
			default:
				wp_enqueue_style("df-width-1200", THEME_CSS_URL."1200.css", Array());
				break;
		}

		wp_enqueue_style("fonts", THEME_CSS_URL."fonts.php", Array());
		wp_enqueue_style("df-dynamic-css", THEME_CSS_URL."dynamic-css.php", Array());
 		wp_enqueue_style("style", get_stylesheet_uri(), Array());
 		
		wp_enqueue_script("jquery");
		wp_enqueue_script("query-ui-core");
		wp_enqueue_script("jquery-ui-tabs");
		wp_enqueue_script("html5" , "http://html5shiv.googlecode.com/svn/trunk/html5.js", Array('jquery'), "", false);
		$wp_scripts->add_data('html5', 'conditional', 'lt IE 9');

		wp_enqueue_script("cookies" , THEME_JS_URL."admin/jquery.c00kie.js", Array('jquery'), "1.0", true);
		if($banner_type && $banner_type!="off") {
			wp_enqueue_script("banner" , THEME_JS_URL."jquery.floating_popup.1.3.min.js", Array('jquery'), "1.0", true);
		}
		if (is_page_template ( 'template-contact.php' )) {
			wp_enqueue_script("contact" , THEME_JS_URL."jquery.contact.js", Array('jquery'), '', true);
		}
		wp_enqueue_script("easing" , THEME_JS_URL."jquery.easing.js", Array('jquery'), '', true);
		wp_enqueue_script("menu" , THEME_JS_URL."jquery.menu.js", Array('jquery'), '', true);

		if($mainMenuStyle!="normal") {
      // CUSTOM CODE: HioWeb May 22nd...causing JS errors
			//wp_enqueue_script("sticky" , THEME_JS_URL."jquery.sticky.js", Array('jquery'), '', true);
		}
		
		wp_enqueue_script("bxslider" , THEME_JS_URL."jquery.bxslider.js", Array('jquery'), '', true);
		wp_enqueue_script("lightbox" , THEME_JS_URL."jquery.lightbox.js", Array('jquery'), '', true);
		wp_enqueue_script("flickr" , THEME_JS_URL."jquery.flickr.js", Array('jquery'), '', false);
		wp_enqueue_script("fitvids" , THEME_JS_URL."jquery.fitvids.js", Array('jquery'), '', false);
		wp_enqueue_script("jquery-ui" , THEME_JS_URL."jquery.ui.js", Array('jquery'), '', false);
		wp_enqueue_script("customselect" , THEME_JS_URL."jquery.customselect.js", Array('jquery'), '', true);
		wp_enqueue_script("custom" , THEME_JS_URL."jquery.custom.js", Array('jquery'), '', true);

		wp_enqueue_script(THEME_NAME, THEME_JS_URL.THEME_NAME.".js", Array('jquery'), '', true);
		wp_enqueue_script("scripts" , THEME_JS_URL."scripts.php", Array('jquery'), '', true);
		
		if (is_page_template ( 'template-gallery.php' ) || $post_type=='gallery-item') {
			wp_enqueue_script("isotope" , THEME_JS_URL."jquery.isotope.js", Array('jquery'), '', true);
			wp_enqueue_script("gallery" , THEME_JS_URL."jquery.gallery.js", Array('jquery'), '', true);
			wp_enqueue_script("infinitescroll" , THEME_JS_URL."jquery.infinitescroll.min.js", Array('jquery'), '', true);
			wp_enqueue_script("df-gallery" , THEME_JS_URL."df_gallery.js", Array('jquery'), '', false);
		} 

		

		if ( is_singular() ) { wp_enqueue_script( "comment-reply" ); }

		switch ($pageWidth) {
			case '1':
				$responsiveON = 1256;
				break;
			case '2':
				$responsiveON = 1115;
				break;
			case '3':
				$responsiveON = 1034;
				break;
			default:
				$responsiveON = 1256;
				break;
		}

		
		wp_localize_script('custom','df',
			array(
				'adminUrl' => admin_url("admin-ajax.php"),
				'imageUrl' => THEME_IMAGE_URL,
				'cssUrl' => THEME_CSS_URL,
				'themeUrl' => THEME_URL,
				'responsiveON' => $responsiveON
			)
		);
		
	}

	add_action( 'wp_enqueue_scripts', 'different_themes_scripts');
?>