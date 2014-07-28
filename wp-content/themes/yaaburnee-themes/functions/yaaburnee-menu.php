<?php

$prefix = THEME_NAME.'_';
$image = '<img src="'.THEME_IMAGE_URL.'control-panel-images/logo-differentthemes-1.png" width="11" height="15" /> ';
$sidebarPosition = get_option ( THEME_NAME."_sidebar_position" ); 
$aboutAuthor = get_option ( THEME_NAME."_about_author" ); 
$imageSize = get_option ( THEME_NAME."_image_size" );
$showSingleThumb = get_option ( THEME_NAME."_show_single_thumb" ); 
$shareAll = get_option ( THEME_NAME."_share_all" ); 
$similarPosts = get_option ( THEME_NAME."_similar_posts" ); 
$galleryID = df_get_page('gallery');
$homeID = df_get_page('homepage');
$contactID = df_get_page('contact');


if(isset($_GET['post'])) {
	$currentID = $_GET['post'];
} else {
	$currentID = 0;
}

global $box_array, $post_id;

$box_array = array();

//post settings
$box_array[] = array( 'id' => 'post-title', 'title' => ''.$image.__("Show Title ", THEME_NAME), 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Title:", THEME_NAME), 'std' => '', 'id' => $prefix. 'show_title', 'type'=> 'show_hide' ) ), 'size' => 10, 'first' => 'yes' );
$box_array[] = array( 'id' => 'post-meta', 'title' => ''.$image.__("Show Date/Author/Category/View Count", THEME_NAME), 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Date/Author/Category/View Count:", THEME_NAME), 'std' => '', 'id' => $prefix. 'show_meta', 'type'=> 'show_hide' ) ), 'size' => 10, 'first' => 'no' );
$box_array[] = array( 'id' => 'post-type', 'title' => ''.$image.__("Featured Image Icon", THEME_NAME), 'page' => 'post', 'context' => 'side', 'priority' => 'low', 'fields' => array(array('name' => __("Icon:", THEME_NAME), 'std' => '', 'id' => $prefix. 'post_type', 'type'=> 'post_type' ) ), 'size' => 10,'first' => 'no'  );
$box_array[] = array( 'id' => 'video-url', 'title' => ''.$image.__("Vimeo/YouTube Video url", THEME_NAME), 'page' => 'post', 'context' => 'side', 'priority' => 'low', 'fields' => array(array('name' => __("URL:", THEME_NAME), 'std' => '', 'id' => $prefix. 'video', 'type'=> 'text' ) ), 'size' => 10,'first' => 'no'  );
$box_array[] = array( 'id' => 'audio-url', 'title' => ''.$image.__("SoundCloud Embed Code", THEME_NAME), 'page' => 'post', 'context' => 'side', 'priority' => 'low', 'fields' => array(array('name' => __("Code:", THEME_NAME), 'std' => '', 'id' => $prefix. 'audio', 'type'=> 'textarea' ) ), 'size' => 10,'first' => 'no'  );
$box_array[] = array( 'id' => 'post-slider-images', 'title' => ''.$image.__("Post Slider Images", THEME_NAME), 'page' => 'post', 'context' => 'side', 'priority' => 'low', 'fields' => array(array('name' => __("", THEME_NAME), 'std' => '', 'id' => $prefix. 'slider_images', 'type'=> 'image_select' ) ), 'size' => 0,'first' => 'no'  );
$box_array[] = array( 'id' => 'main-slider-post', 'title' => ''.$image.__("Show This Post in The Main Slider?", THEME_NAME), 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Show", THEME_NAME), 'std' => '', 'id' => $prefix. 'main_slider', 'type'=> 'no_yes' ) ), 'size' => 10,'first' => 'no'  );
$box_array[] = array( 'id' => 'main-slider-image', 'title' => ''.$image.__("Main Slider Image", THEME_NAME), 'page' => 'post', 'context' => 'side', 'priority' => 'low', 'fields' => array(array('name' => __("Image", THEME_NAME), 'std' => '', 'id' => $prefix. 'slider_image', 'type'=> 'upload' ) ), 'size' => 10,'first' => 'no'  );

$box_array[] = array( 'id' => 'breaking-post', 'title' => ''.$image.__("Show In Breaking News Slider", THEME_NAME), 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Show", THEME_NAME), 'std' => '', 'id' => $prefix. 'breaking_slider', 'type'=> 'no_yes' ) ), 'size' => 10,'first' => 'no'  );

$box_array[] = array( 'id' => 'color-page', 'title' => ''.$image.' Page Title Color In Homepage Blocks (Blog page) && Page Color in Main Menu', 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Page Color:', 'std' => get_option(THEME_NAME."_default_cat_color"), 'id' => $prefix. 'title_color', 'type'=> 'color' ) ), 'size' => 10, 'first' => 'yes' );

if($similarPosts=="custom") {
	$box_array[] = array( 'id' => 'similar-post', 'title' => ''.$image.' Similar Posts by Category', 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Similar Posts:', 'std' => 'hide', 'id' => $prefix. 'similar_posts', 'type'=> 'show_hide' ) ), 'size' => 10, 'first' => 'no' );
}



//carousel slider
$box_array[] = array( 'id' => 'carousel-type', 'title' => ''.$image.__("Carousel Slider News Type", THEME_NAME), 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Show", THEME_NAME), 'std' => '', 'id' => $prefix. 'main_carousel', 'type'=> 'carousel_type' ) ), 'size' => 10,'first' => 'no'  );
$box_array[] = array( 'id' => 'carousel-post', 'title' => ''.$image.__("Show Carousel News by Category Or Just Latest News?", THEME_NAME), 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Type", THEME_NAME), 'std' => '', 'id' => $prefix. 'carousel_type', 'type'=> 'category' ) ), 'size' => 10,'first' => 'no'  );

$box_array[] = array( 'id' => 'carousel-type', 'title' => ''.$image.__("Carousel Slider News Type", THEME_NAME), 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Show", THEME_NAME), 'std' => '', 'id' => $prefix. 'main_carousel', 'type'=> 'carousel_type' ) ), 'size' => 10,'first' => 'yes'  );
$box_array[] = array( 'id' => 'carousel-post', 'title' => ''.$image.__("Show Carousel News by Category Or Just Latest News?", THEME_NAME), 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Type", THEME_NAME), 'std' => '', 'id' => $prefix. 'carousel_type', 'type'=> 'category' ) ), 'size' => 10,'first' => 'no'  );
//page main slider options
$box_array[] = array( 'id' => 'main-slider-page', 'title' => ''.$image.__("Show This Page in The Main Slider?", THEME_NAME), 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Show", THEME_NAME), 'std' => '', 'id' => $prefix. 'main_slider', 'type'=> 'no_yes' ) ), 'size' => 10,'first' => 'no'  );
$box_array[] = array( 'id' => 'main-slider-image', 'title' => ''.$image.__("Main Slider Image", THEME_NAME), 'page' => 'page', 'context' => 'side', 'priority' => 'low', 'fields' => array(array('name' => __("Image", THEME_NAME), 'std' => '', 'id' => $prefix. 'slider_image', 'type'=> 'upload' ) ), 'size' => 10,'first' => 'no'  );


// post ratings
$box_array[] = array('id' => 'post-ratings', 'title' => ''.$image.__(" Rating", THEME_NAME),'page' => 'post','context' => 'normal','priority' => 'high','fields' => array(array('name' => __("Enter a rating, number from <b>0.1-5</b>", THEME_NAME),'std' => '','id' => $prefix. 'rating','type'=> 'text')),'size' => 10,'first' => 'no' );
$box_array[] = array('id' => 'post-ratings-summary', 'title' => ''.$image.__(" Rating Summary", THEME_NAME),'page' => 'post','context' => 'normal','priority' => 'high','fields' => array(array('name' => __("Summary", THEME_NAME),'std' => '','id' => $prefix. 'rating_summary','type'=> 'textarea')),'size' => 10,'first' => 'no' );
$box_array[] = array('id' => 'post-source', 'title' => ''.$image.__(" Post Source (Optional)", THEME_NAME),'page' => 'post','context' => 'normal','priority' => 'high','fields' => array(array('name' => __("Add it like this - <br/>Google|*|http://www.google.com|**|<br/>Twitter|*|http://www.twitter.com|**|<br/>ThemeForest|*|http://www.themeforest.net", THEME_NAME),'std' => '','id' => $prefix. 'source','type'=> 'textarea')),'size' => 10,'first' => 'no' );

//gallery images
$box_array[] = array( 'id' => 'post-slider-images', 'title' => ''.$image.__("Gallery Images", THEME_NAME), 'page' => 'gallery-item', 'context' => 'side', 'priority' => 'low', 'fields' => array(array('name' => __("", THEME_NAME), 'std' => '', 'id' => $prefix. 'gallery_images', 'type'=> 'image_select' ) ), 'size' => 0,'first' => 'no'  );


//show/hide page title
if(!in_array($currentID, $homeID)) {
	$box_array[] = array( 'id' => 'page-title', 'title' => ''.$image.' Page Title', 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Page Title:', 'std' => '', 'id' => $prefix. 'show_title', 'type'=> 'show_hide' ) ), 'size' => 10, 'first' => 'yes' );
}

if($aboutAuthor=="custom") {
	$box_array[] = array( 'id' => 'about-author-post', 'title' => ''.$image.__('About Author', THEME_NAME), 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __('About Author:', THEME_NAME), 'std' => '', 'id' => $prefix. 'about_author', 'type'=> 'show_hide' ) ), 'size' => 10, 'first' => 'no' );
}

$box_array[] = array( 'id' => 'sidebar-select-box', 'title' => ''.$image.' Sidebar', 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Sidebar name:', 'std' => '', 'id' => $prefix. 'sidebar_select', 'type'=> 'sidebar_select_box' ) ), 'size' => 10, 'first' => 'yes'  );
$box_array[] = array( 'id' => 'sidebar-select-box', 'title' => ''.$image.' Sidebar', 'page' => 'gallery-item', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Sidebar name:', 'std' => '', 'id' => $prefix. 'sidebar_select', 'type'=> 'sidebar_select_box' ) ), 'size' => 10, 'first' => 'yes'  );
$box_array[] = array( 'id' => 'sidebar-select-box', 'title' => ''.$image.' Sidebar', 'page' => 'product', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Sidebar name:', 'std' => '', 'id' => $prefix. 'sidebar_select', 'type'=> 'sidebar_select_box' ) ), 'size' => 10, 'first' => 'yes'  );
if($sidebarPosition=="custom") {
	$box_array[] = array( 'id' => 'sidebar-side-post', 'title' => ''.$image.' Sidebar Position', 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Sidebar Position:', 'std' => '', 'id' => $prefix. 'sidebar_position', 'type'=> 'position' ) ), 'size' => 10, 'first' => 'no' );
	$box_array[] = array( 'id' => 'sidebar-side-post', 'title' => ''.$image.' Sidebar Position', 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Sidebar Position:', 'std' => '', 'id' => $prefix. 'sidebar_position', 'type'=> 'position' ) ), 'size' => 10, 'first' => 'no' );
	$box_array[] = array( 'id' => 'sidebar-side-post', 'title' => ''.$image.' Sidebar Position', 'page' => 'gallery-item', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Sidebar Position:', 'std' => '', 'id' => $prefix. 'sidebar_position', 'type'=> 'position' ) ), 'size' => 10, 'first' => 'no' );
	$box_array[] = array( 'id' => 'sidebar-side-post', 'title' => ''.$image.' Sidebar Position', 'page' => 'product', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Sidebar Position:', 'std' => '', 'id' => $prefix. 'sidebar_position', 'type'=> 'position' ) ), 'size' => 10, 'first' => 'no' );
}


//contact page
if(in_array($currentID, $contactID) || isset($_POST['post_type'])) {
	$box_array[] = array( 'id' => 'email', 'title' => ''.$image.' Contact Form Email', 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Email:', 'std' => '', 'id' => $prefix. 'contact_mail', 'type'=> 'text' ) ), 'size' => 10, 'first' => 'yes' );
	$box_array[] = array( 'id' => 'google-maps', 'title' => ''.$image.' Google Maps', 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Google Maps Url:', 'std' => '', 'id' => $prefix. 'map', 'type'=> 'text' ) ), 'size' => 10, 'first' => 'no' );
}



$box_array[] = array( 'id' => 'sidebar-select-post', 'title' => ''.$image.' Sidebar', 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Sidebar name:', 'std' => '', 'id' => $prefix. 'sidebar_select', 'type'=> 'sidebar_select_box' ) ), 'size' => 10, 'first' => 'no' );



//images
if($showSingleThumb=="on" && !in_array($currentID, $homeID) && !in_array($currentID, $galleryID) && !in_array($currentID, $contactID) || isset($_POST['post_type']) || $currentID==0) {
	$box_array[] = array( 'id' => 'show-image-post', 'title' => ''.$image.' Show Image In Single Post', 'page' => 'post', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Image:', 'std' => '', 'id' => $prefix. 'single_image', 'type'=> 'show_hide' ) ), 'size' => 10, 'first' => 'no' );
	$box_array[] = array( 'id' => 'show-image-page', 'title' => ''.$image.' Show Image In Single Page', 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => 'Image:', 'std' => '', 'id' => $prefix. 'single_image', 'type'=> 'hide_show' ) ), 'size' => 10, 'first' => 'no' );
}


//homepage 
if(in_array($currentID, $homeID) || isset($_POST['post_type'])) {
	//home slider
	$box_array[] = array( 'id' => 'home-slider', 'title' => ''.$image.__("Homepage Main Slider", THEME_NAME), 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Option", THEME_NAME), 'std' => '', 'id' => $prefix. 'slider', 'type'=> 'off_on' ) ), 'size' => 10,'first' => 'no'  );

	$box_array[] = array( 'id' => 'home-drag-drop', 'title' => ''.$image.__("Homepage Builder", THEME_NAME), 'page' => 'page', 'context' => 'normal', 'priority' => 'high', 'fields' => array(array('name' => __("Homepage builder", THEME_NAME), 'std' => '', 'id' => $prefix. 'home_drag_drop', 'type'=> 'home_drag_drop' ) ), 'size' => 0,'first' => 'no'  );

}


// Add meta box
function add_sticky_box() {
	global $box_array;
	
	foreach ($box_array as $box) {
		add_meta_box($box['id'], $box['title'], 'sticky_show_box', $box['page'], $box['context'], $box['priority'], array('content'=>$box, 'first'=>$box['first'], 'size'=>$box['size']));
	}

}

function sticky_show_box( $post, $metabox) {
	show_box_funtion($metabox['args']['content'], $metabox['args']['first'], $metabox['args']['size']);
}

// Callback function to show fields in meta box
function show_box_funtion($fields, $first='no', $width='60') {
	global $post,$post_id;

	if($first=="yes") {
		echo '<input type="hidden" name="sticky_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
	}

	if($width!=0) {
		echo '<table class="form-table">';
	}

	foreach ($fields['fields'] as $field) {
		// get current post meta data
		$meta = get_post_meta($post->ID, $field['id'], true);
		//$post_num = htmlentities($_GET['post']);
		if($width!=0) {
			echo '<tr>';
				echo '<th style="width:',$width,'%"><label for="', $field['id'], '">', $field['name'], '</label></th>';
			echo '<td>';
		}
		switch ($field['type']) {
			case 'text':
				echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" ', $meta ? ' ' : '', ' value="', $meta ? remove_html_slashes($meta) : remove_html_slashes($field['std']), '"/> ';
				break;
			case 'upload':
				echo '<input class="upload input-text-1 df-upload-field" type="text" name="', $field['id'], '" id="', $field['id'], '" value="',  $meta ? remove_html_slashes($meta) :  remove_html_slashes($field['std']), '" style="width: 140px;"/><a href="#" class="df-upload-button">Button</a>';
				break;
			case 'image_select':
				df_gallery_image_select($field['id'],$meta);
				break;
			case 'color':
				echo '<input class="color" type="text" name="', $field['id'], '" id="', $field['id'], '" ', $meta ? ' ' : '', ' value="', $meta ? remove_html_slashes($meta) : remove_html_slashes($field['std']), '"/> ';
				break;
			case 'hide_show':
				$positions = array('Hide', 'Show');

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($positions as $position) {
	
						if ( $meta == strtolower($position)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $position != "" ) {
							echo "<option value=\"".strtolower($position)."\" ".$selected.">".$position."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'no_yes':
				$positions = array('No', 'Yes');

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($positions as $position) {
	
						if ( $meta == strtolower($position)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $position != "" ) {
							echo "<option value=\"".strtolower($position)."\" ".$selected.">".$position."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'yes_no':
				$positions = array('Yes', 'No');

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($positions as $position) {
	
						if ( $meta == strtolower($position)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $position != "" ) {
							echo "<option value=\"".strtolower($position)."\" ".$selected.">".$position."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'slider_type':
				$positions = array('Off', 'Tab Slider', 'Featured carousel');

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($positions as $position) {
	
						if ( $meta == strtolower($position)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $position != "" ) {
							echo "<option value=\"".strtolower($position)."\" ".$selected.">".$position."</option>";
						}
					}
				echo '	</select>';
				break;

			case 'post_type':
				$value = array('None', 'Video', 'Image', 'Music', 'Photo');

				echo '
				<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($value as $val) {
	
						if ( $meta == strtolower($val)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $val != "" ) {
							echo "<option value=\"".strtolower($val)."\" ".$selected.">".$val."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'show_hide':
				$positions = array('Show', 'Hide');

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($positions as $position) {
	
						if ( $meta == strtolower($position)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $position != "" ) {
							echo "<option value=\"".strtolower($position)."\" ".$selected.">".$position."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'on_off':
				$positions = array('On', 'Off');

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($positions as $position) {
	
						if ( $meta == strtolower($position)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $position != "" ) {
							echo "<option value=\"".strtolower($position)."\" ".$selected.">".$position."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'off_on':
				$positions = array('Off', 'On');

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($positions as $position) {
	
						if ( $meta == strtolower($position)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $position != "" ) {
							echo "<option value=\"".strtolower($position)."\" ".$selected.">".$position."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'carousel_type':
				$positions = array('No', 'Latest News', 'Latest Reviews', 'Top Reviews', 'Popular News');

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($positions as $position) {
	
						if ( $meta == strtolower($position)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $position != "" ) {
							echo "<option value=\"".strtolower($position)."\" ".$selected.">".$position."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'checkbox':
				echo '<input type="checkbox" value="1" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
				break;
			case 'sidebar_select_box':
				$sidebar_names = get_option( THEME_NAME."_sidebar_names" );
				$sidebar_names = explode( "|*|", $sidebar_names );
				if ( $meta == 'DFoff' ) {
					$selected="selected=\"selected\"";
				} else { 
					$selected=false;
				}

				echo '	<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
				echo "<option value=\"\">Default</option>";
				echo "<option value=\"DFoff\" ".$selected.">No sidebar</option>";
					foreach ($sidebar_names as $sidebar_name) {
	
						if ( $meta == $sidebar_name ) {
							$selected="selected=\"selected\"";
						} else { 
							$selected=false;
						}
						
						if ( $sidebar_name != "" ) {
							echo "<option value=\"".$sidebar_name."\" ".$selected.">".$sidebar_name."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'category_select':
				global $wpdb;
				$data = get_terms( "category", 'hide_empty=0' );	
				
				echo '	<select class="home-cat-select" name="', $field['id'], '[]" id="', $field['id'], '" style="min-width:200px; min-height:200px;" multiple>';
					foreach($data as $key => $d) {
						if(is_array($meta) && in_array($d->term_id,$meta)) { $selected=' selected'; } else { $selected=''; }
						echo "<option value=\"".$d->term_id."\" ".$selected.">".$d->name."</option>";
					}

				echo '	</select>';
				break;
			case 'category':
				global $wpdb;
				$data = get_terms( "category", 'hide_empty=0' );	
				
				echo '	<select class="home-cat-select" name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
				echo "<option value=\"latest\" ".$selected.">Latest News</option>";
					foreach($data as $key => $d) {
						if($meta==$d->term_id) { $selected=' selected'; } else { $selected=''; }
						echo "<option value=\"".$d->term_id."\" ".$selected.">".$d->name."</option>";
					}

				echo '	</select>';
				break;
			case 'layer_slider_select':
					// Get WPDB Object
					global $wpdb;

					// Table name
					$table_name = $wpdb->prefix . "layerslider";
					
					// Get sliders
					$sliders = $wpdb->get_results( "SELECT * FROM $table_name
														WHERE flag_hidden = '0' AND flag_deleted = '0'
														ORDER BY id ASC LIMIT 200" );	
				

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
				if(!empty($sliders)) :
					foreach($sliders as $key => $item) :
						$name = empty($item->name) ? 'Unnamed' : $item->name;
						if($meta == $item->id) { $selected='selected="selected"'; } else { $selected=''; }
						echo '<option value="'.$item->id.'" '.$selected.'>'.$name.'</option>';
					
					endforeach;
				endif;
				if(empty($sliders)) :
					echo '<option value="">'.__("You didn't create a slider yet.", THEME_NAME).'</option>';
				endif;
				echo '</select><br/><br/> Create a slider with size - <b>100% width and 600px height</b> and choose <b>fullwidth</b> skin' ;

				break;
			case 'category_select_2':
				global $wpdb;
				$data = get_terms( "category", 'hide_empty=0' );	
				
				echo '	<select class="home-cat-select" name="', $field['id'], '[]" id="', $field['id'], '" style="min-width:200px; min-height:200px;" multiple disabled>';
					foreach($data as $key => $d) {
						if(is_array($meta) && in_array($d->term_id,$meta)) { $selected=' selected'; } else { $selected=''; }
						echo "<option value=\"".$d->term_id."\" ".$selected.">".$d->name."</option>";
					}

				echo '	</select>';
				break;
			case 'position':
				$positions = array('Right', 'Left');

				echo '<select name="', $field['id'], '" id="', $field['id'], '" style="min-width:200px;">';
					foreach ($positions as $position) {
	
						if ( $meta == strtolower($position)) {
							$selected="selected=\"selected\"";
						} else { 
							$selected="";
						}
						
						if ( $position != "" ) {
							echo "<option value=\"".strtolower($position)."\" ".$selected.">".$position."</option>";
						}
					}
				echo '	</select>';
				break;
			case 'block_type':
				echo '
				<script>
					jQuery(document).ready(function($){

						$(".home-block-type").click(function() {
						    if($(this).val()=="categories") {
						    	$(".home-cat-select").removeAttr("disabled");
						    } else {
						    	$(".home-cat-select").attr("disabled","disabled");
						    }

						});
							
					    if($("input[name=', $field['id'], ']:checked").val()=="categories") {
					    	$(".home-cat-select").removeAttr("disabled");
					    }
						
					});

				</script>
				
				<input type="radio" class="home-block-type" name="', $field['id'], '" value="latest"', $meta=="latest" || !$meta ? ' CHECKED ' : '', 'id="', $field['id'], '" /><label style="display:inline-block;vertical-align:top; margin-left:5px;">Latest News</label><br/>
				<input type="radio" class="home-block-type" name="', $field['id'], '" value="popular"', $meta=="popular" ? ' CHECKED ' : '', 'id="', $field['id'], '" /><label style="display:inline-block;vertical-align:top; margin-left:5px;"">Popular News</label><br/>
				<input type="radio" class="home-block-type" name="', $field['id'], '" value="categories"', $meta=="categories" ? ' CHECKED ' : '', 'id="', $field['id'], '" /><label style="display:inline-block;vertical-align:top; margin-left:5px;"">Post Categories</label>

				';

				break;
			case 'textarea':
				echo '<textarea name="', $field['id'], '" id="', $field['id'], '" ', $meta ? ' ' : '', ' style="width:100%; height:100px;">', $meta ? remove_html_slashes($meta) : remove_html_slashes($field['std']), '</textarea>';
				if($field['id'] == THEME_NAME."_ratings") { 
					$average = df_avarage_rating($post_id);
					echo '<input type="hidden" name="', $field['id'], '_average" id="', $field['id'], '_average" value="',$average[0],'"/> ';
				}
				break;
			case 'home_drag_drop':
				global $DFfields;
				$DFfields = new DifferentThemesManagment(THEME_FULL_NAME,THEME_NAME);
				
				
				get_template_part(THEME_FUNCTIONS."drag-drop");
				$options = $DFfields->get_options();

				echo '
					<div style="vertical-align:top;">
						'.$DFfields->print_options().'
					</div>
					<div style="clear:both;"></div>
';
				break;
		}
		if($width!=0) {
			echo '<td>', '</tr>';
		}
	}
	if($width!=0) {
		echo '</table>';
	}
}

function save_data($fields) {
	global $post_id;

	foreach ($fields['fields'] as $field) {
		if($field['id']==THEME_NAME."_ratings") { 
			$old = get_post_meta($post_id, $field['id'], true);
			if(isset($_POST[$field['id']])) {
				$new = $_POST[$field['id']];
				
				if ($new && $new != $old) {
					update_post_meta($post_id, $field['id'], $new);
				} elseif ('' == $new && $old) {
					delete_post_meta($post_id, $field['id'], $old);
				}//else if closer
			}	

			$old = get_post_meta($post_id, $field['id']."_average", true);
			if(isset($_POST[$field['id']."_average"])) {
				$new =  df_avarage_rating($post_id);
				$new =  $new[0];
				
				if ($new && $new != $old) {
					update_post_meta($post_id, $field['id']."_average", $new);
				} elseif ('' == $new && $old) {
					delete_post_meta($post_id, $field['id']."_average", $old);
				}//else if closer
			}

		} else {
			$old = get_post_meta($post_id, $field['id'], true);
			if(isset($_POST[$field['id']])) {
				$new = $_POST[$field['id']];
				
				if ($new && $new != $old) {
					update_post_meta($post_id, $field['id'], $new);
				} elseif ('' == $new && $old) {
					delete_post_meta($post_id, $field['id'], $old);
				}//else if closer
			}	
		}
	}//foreach closer
	
}

function save_numbers($fields) { 
	global $post_id;
	foreach ($fields['fields'] as $field) {
		$old = get_post_meta($post_id, $field['id'], true);
		$new = $_POST[$field['id']];
		if(!is_numeric($new)) { 
			$new = preg_replace("/[^0-9]/","",$new);
		}
		if ($new && $new != $old) {
			update_post_meta($post_id, $field['id'], $new);
		} elseif ('' == $new && $old) {
			delete_post_meta($post_id, $field['id'], $old);
		}//else if closer
	}//foreach closer

}

// Save data from meta box
function save_sticky_data($post_id) {
	global $box_array;
	
	// verify nonce
	if (isset($_POST['sticky_meta_box_nonce']) && !wp_verify_nonce($_POST['sticky_meta_box_nonce'], basename(__FILE__))) {
		return $post_id;
	}

	// check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}

	// check permissions
	if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
		if (!current_user_can('edit_page', $post_id)) {
			return $post_id;
		}
	} elseif (!current_user_can('edit_post', $post_id)) {
		return $post_id;
	}
	
	foreach ($box_array as $box) {
		save_data($box);
	}

} //function closer

	add_action('admin_menu', 'add_sticky_box');	
	add_action('save_post', 'save_sticky_data');

	
?>
