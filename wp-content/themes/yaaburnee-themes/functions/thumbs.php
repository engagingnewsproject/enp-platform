<?php

function get_post_thumb($post_id,$width,$height,$custom="image", $file=false, $original=false, $retina=true) {		//universal thumb function

	$show_no_image = get_option(THEME_NAME."_show_no_image_thumb");

	$custom_image = get_post_custom_values($custom,$post_id);	//get custom field value
	$custom_image = $custom_image[0];
	$upload_dir = wp_upload_dir();

	$meta = get_post_meta($post_id, "_thumbnail_id",true); 		//get wordpress built in thumbnail value
	//$first_from_post = get_first_image($post_id);				//get first image form post
	$first_from_post = false;

	if($custom_image && $custom!="image" && $file==false) {		//built in thumb
		$custom_image = get_post_custom_values($custom,$post_id);	//get custom field value
		$file = $custom_image[0];
		$src=array();
		if($original==false) {
			$src['url'] = mr_image_resize( $file, $width, $height, true, '', $retina );
		} else {
			$src['url']=$file;
		}
		$show_image = true;		
	} elseif(isset($first_from_post) && $first_from_post!="" && !get_the_post_thumbnail($post_id) && $custom=="image" && $file==false) {		//first attached image
		$file = $first_from_post;
		if(strpos($file,"wp-content") !== false) {
			$pos = strpos($file,"/wp-content");
			$file = substr($file,$pos);
		}
		$file = site_url().$file;
		$src=array();
		if($original==false) {

			$src['url'] = mr_image_resize( $file, $width, $height, true, '', $retina );
		} else {
			$src['url']=$file;
		}
		$show_image = true;	
	} elseif(get_the_post_thumbnail($post_id) != '' && $file==false) {		//built in thumb	
		$featured = $upload_dir["baseurl"]."/".get_post_meta($meta, "_wp_attached_file",true);		
		$src=array();
		if($original==false) {
			$src['url'] = mr_image_resize( $featured, $width, $height, true, '', $retina );
		} else {
			$src['url']=$featured;
		}
		$show_image = true;		
	} elseif($file!=false) {
		if($original==false) {
			$src['url'] = mr_image_resize( $file, $width, $height, true, '', $retina );
		} else {
			$src['url']=$file;
		}
		$show_image = true;	
	}	else {		//no image
		$src['url'] = THEME_IMAGE_URL.$width.'x'.$height.'.gif';
		if($show_no_image == "on") {
			$show_image = true;
		} else {
			$show_image = false;
		}
	}

	return array("src"=>$src['url'],"show"=>$show_image);
}

function add_image_thumb($content)	//add thumb in the begining of the post
{
	global $post;
	$img = get_post_thumb($post->ID,680,230);
	if($img['show'] != false) {
		if($img['src'] != "") {

			$img = '<a href="#" class="image-overlay-1 main-image"><span><img src="'.$img['src'].'" class="trans-1" alt="'.get_the_title().'"/></span></a>';
			return $img." ".$content;
		} else {
			return $content;
		}
	} else {
		return $content;
	}
}

function get_first_image($post_id)  {		//retrieves first post attachment, check if is image
	
	$args = array(
		'post_type' => 'attachment',
		'numberposts' => null,
		'post_status' => null,
		'post_parent' => $post_id
	);
	
	$attachments = get_posts($args);
	if ($attachments) {
		foreach ($attachments as $attachment) {
			if(is_image($attachment->guid)) {
				return $attachment->guid;
			}
		}
	}
	
	return false;  
}


function is_image($src) {		//check if src extension is image like
	$extensions = array('.jpg','.gif','.png');
	if(in_array(substr($src,-4),$extensions)) {
		return true;
	} else {
		return false;
	}
}

?>