<?php
	$banner_type = get_option ( THEME_NAME."_banner_type" );
	$banner_image = get_option ( THEME_NAME."_banner_image" );
	$banner_text = stripslashes ( get_option ( THEME_NAME."_banner_text" ) );
	$banner_text_image_txt = remove_html_slashes ( get_option ( THEME_NAME."_banner_text_image_txt" ) );
	$banner_text_image_img = get_option ( THEME_NAME."_banner_text_image_img" ) ;
	
	if ( !$banner_image) {
		$banner_image = THEME_IMAGE_URL."custom-banner.png";
	}	
	if ( !$banner_text_image_img) {
		$banner_text_image_img = THEME_IMAGE_URL."custom-banner.png";
	}
?>	
<?php
	if ( $banner_type == "image" ) {
	//Image Banner
?>
		<div id="popup_content" style="display:none;">
			<a href="#" id="baner_close">Close</a>
			<img src="<?php echo $banner_image; ?>" />
		</div>
<?php
	} else if ( $banner_type == "text" ) { 
	//Text Banner
?>
		<div id="popup_content" class="text-banner-add" style="display:none;">
			<div style="border: 1px solid #fff; position: relative; -moz-border-radius: 4px; -webkit-border-radius: 4px; border-radius: 4px;">
				<a href="#" id="baner_close">Close</a>
				<center><?php echo $banner_text;?></center>
			</div>
		</div>
<?php 
	} else if ( $banner_type == "text_image" ) { 
	//Image And Text Banner
?>
		<div id="popup_content" style="display:none;">
			<a href="#" id="baner_close">Close</a>
			<center><img src="<?php echo $banner_text_image_img;?>"/></center>
			<center><?php echo $banner_text_image_txt;?></center>
		</div>
<?php }
?>