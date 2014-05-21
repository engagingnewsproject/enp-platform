<?php


function add_p_tags($text) {
  return "<p>" . str_replace("\n", "</p><p>", $text) . "</p>";
}

function remove_html_slashes($content) {
	return filter_var(stripslashes($content), FILTER_SANITIZE_SPECIAL_CHARS);
}

function new_excerpt_length($length) {
	return 30;
}

function new_excerpt_length_10($length) {
	return 10;
}

function new_excerpt_length_16($length) {
	return 16;
}

function new_excerpt_length_20($length) {
	return 20;
}

function new_excerpt_length_40($length) {
	return 40;
}

function new_excerpt_length_50($length) {
	return 50;
}

function new_excerpt_length_70($length) {
    return 70;
}

function new_excerpt_length_80($length) {
    return 80;
}

function new_excerpt_length_90($length) {
    return 90;
}

function new_excerpt_length_100($length) {
	return 100;
}

function new_excerpt_length_5($length) {
	return 5;
}

function new_excerpt_more($more) {
	return '';
}

function remove_objects($content) {
	$content = preg_replace('/\<div(.*?)\>(.*?)\<\/div\>/s', '', $content);
	$content = preg_replace('/\<object(.*?)\>(.*?)\<\/object\>/s', '', $content);
	$content = preg_replace('/\<iframe(.*?)\>(.*?)\<\/iframe\>/s', '', $content);
	$content = preg_replace('/\<embed(.*?)\>(.*?)\<\/embed\>/s', '', $content);
	return $content;
}

function remove_images($content) {
	$content = preg_replace('#(<[/]?a.*><[/]?img.*></a>)#U', '', $content);
	$content = preg_replace('#(<[/]?img.*>)#U', '', $content);
	$content = preg_replace("/\[caption(.*)\](.*)\[\/caption\]/Usi", "", $content);
    return $content;
}

/* -------------------------------------------------------------------------*
 * 						REMOVE HTML TAGS FROM BLOG PAGE						*
 * -------------------------------------------------------------------------*/
 
function remove_html($content) {
	$content = strip_tags($content);
    return $content;
}

function filter_where( $where = '' ) {
	// posts in the last 30 days
	$where .= " AND post_date > '" . date('Y-m-d', strtotime('-99930 days')) . "'";
	return $where;
}

function page_read_more($content) {
	$result = preg_split('/<span id="more-\d+"><\/span>/', $content);
	return $result[0];
}


/* -------------------------------------------------------------------------*
 * 						CUSTOM BLOG READ MORE BUTTON						*
 * -------------------------------------------------------------------------*/
function DF_read_more($matches) {
	return '<p>'.$matches[1].'</p><p><a '.$matches[3].' class="more-link"><i class="icon-double-angle-right"></i>'.$matches[4].'</a></p> ';
}
				
	
function blog_read_more($content) {
	return preg_replace_callback('#(.*)(<a(.*) class="more-link">(.*)</a>(.*))#', "DF_read_more", $content);
}

/* -------------------------------------------------------------------------*
 * 						CUSTOM HOME READ MORE BUTTON						*
 * -------------------------------------------------------------------------*/
 
function home_read_more($content) {
    $content = preg_replace('#(<a(.*) class="more-link">(.*)</a>)#U', '</p><a $2 class="more-link"><span>$3</span></a>', $content);
    return $content;
}

function BigFirstChar ($content = '') {
     return '<p class="caps">' . $content;
}


/* -------------------------------------------------------------------------*
 * 							WORD LIMITER									*
 * -------------------------------------------------------------------------*/

function WordLimiter($string, $count){

	$string = remove_html(preg_replace('/\[\/.*?\]/', '', preg_replace('/\[.*?\]/', '', $string)));

	$words = explode(' ', $string);
	if (count($words) > $count){
		array_splice($words, $count);
		$string = implode(' ', $words);
	}
	return $string." [...]";
}


function convert_to_class($name){
	return strtolower( str_replace( array(' ',',','.','"',"'",'/',"\\",'+','=',')','(','*','&','^','%','$','#','@','!','~','`','<','>','?','[',']','{','}','|',':',),'',$name ) );
}

/* -------------------------------------------------------------------------*
 * 							AVATAR URL									*
 * -------------------------------------------------------------------------*/
 
function get_avatar_url($get_avatar){
    if(preg_match("/src='(.*?)'/i", $get_avatar, $matches)) {
    	preg_match("/src='(.*?)'/i", $get_avatar, $matches);
   		return $matches[1];
    } else {
    	preg_match("/src=\"(.*?)\"/i", $get_avatar, $matches);
   		return $matches[1];
    }
}

/* -------------------------------------------------------------------------*
 * 							CUSTOM USER PROFILE								*
 * -------------------------------------------------------------------------*/
 
function DF_extra_contact_info($contactmethods) {
    unset($contactmethods['aim']);
    unset($contactmethods['yim']);
    unset($contactmethods['jabber']);
    $contactmethods['rss'] = 'Rss Account Url';
    $contactmethods['github'] = 'Github Account Url';
    $contactmethods['instagram'] = 'Instagram Account Url';
    $contactmethods['tumblr'] = 'Tumblr Account Url';
    $contactmethods['flickr'] = 'Flickr Account Url';
    $contactmethods['skype'] = 'Skype Account Url';
    $contactmethods['pinterest'] = 'Pinterest in Account Url';
    $contactmethods['linkedin'] = 'Linkedin Account Url';
    $contactmethods['googleplus'] = 'Google+ Account Url';
    $contactmethods['youtube'] = 'Youtube Account Url';
    $contactmethods['dribbble'] = 'Dribbble Account Url';
    $contactmethods['facebook'] = 'Facebook Account Url';
    $contactmethods['twitter'] = 'Twitter Account Url';

    return $contactmethods;
}



/* -------------------------------------------------------------------------*
 * 							CUSTOM COMMENT FIELDS							*
 * -------------------------------------------------------------------------*/
 
function df_fields($fields) {
	$fields['author'] = '<p class="comment-form-author"><label>'.__("Name:",THEME_NAME).'<span>*</span></label><input type="text" placeholder="'.__("Name..",THEME_NAME).'" name="author" id="author">';
	$fields['email'] = '<p class="comment-form-email"><label>'.__("E-mail:",THEME_NAME).'<span>*</span></label><input type="text" placeholder="'.__("E-mail..",THEME_NAME).'" name="email" id="email"><div class="clear"></div>';
	$fields['url'] = '';

	return $fields;
}

/* -------------------------------------------------------------------------*
 * 							CUSTOM COMMENT FIELDS							*
 * -------------------------------------------------------------------------*/
 
function df_fields_rules($fields) {
	$fields['rules'] = '<h3 id="leave-a-reply">'.__("Leave a Reply",THEME_NAME).'</h3>';
	$fields['note'] = '<p class="comment-notes">'.__("Your email address will not be published. Required fields are marked ",THEME_NAME).'<span>*</span></p>';
	print $fields['rules'].$fields['note'];
}


/* -------------------------------------------------------------------------*
 * 								GET VIDEO INFO 								*
 * -------------------------------------------------------------------------*/

function df_get_video_info($vurl){
    $image_url = parse_url($vurl);
    // Test if the link is for youtube
    if($image_url['host'] == 'www.youtube.com' || $image_url['host'] == 'youtube.com'){
        $array = explode("&", $image_url['query']);
        return "http://img.youtube.com/vi/".substr($array[0], 2)."/0.jpg"; // Returns the largest Thumbnail
    // Test if the link is for the shortened youtube share link
    } else if($image_url['host'] == 'www.youtu.be' || $image_url['host'] == 'youtu.be'){
        $array = ltrim($image_url['path'],'/');
        return "http://img.youtube.com/vi/". $array ."/0.jpg"; // Returns the largest Thumbnail
    // Test if the link is for vimeo
    } else if($image_url['host'] == 'www.vimeo.com' || $image_url['host'] == 'vimeo.com'){
        $hash = unserialize(file_get_contents("http://vimeo.com/api/v2/video/".substr($image_url['path'], 1).".php"));
        return $hash[0]["thumbnail_medium"]; // Returns the medium Thumbnail
    }
}

 
 /* -------------------------------------------------------------------------*
 * 								GET EMBED CODE 								*
 * -------------------------------------------------------------------------*/

function df_get_video_embed($vurl,$width,$height){
    $image_url = parse_url($vurl);
    // Test if the link is for youtube
    if($image_url['host'] == 'www.youtube.com' || $image_url['host'] == 'youtube.com'){
        $array = explode("&", $image_url['query']);
        return '<iframe src="http://www.youtube.com/embed/' . substr($array[0], 2) . '?wmode=transparent" frameborder="0" width="'.$width.'" height="'.$height.'" allowfullscreen></iframe>'; // Returns the youtube iframe embed code
    // Test if the link is for the shortened youtube share link
    } else if($image_url['host'] == 'www.youtu.be' || $image_url['host'] == 'youtu.be'){
        $array = ltrim($image_url['path'],'/');
        return '<iframe src="http://www.youtube.com/embed/' . $array . '?wmode=transparent" frameborder="0" width="'.$width.'" height="'.$height.'" allowfullscreen></iframe>'; // Returns the youtube iframe embed code
    // Test if the link is for vimeo
    } else if($image_url['host'] == 'www.vimeo.com' || $image_url['host'] == 'vimeo.com'){
        $hash = substr($image_url['path'], 1);
        return '<iframe src="http://player.vimeo.com/video/' . $hash . '?title=0&byline=0&portrait=0" width="'.$width.'" height="'.$height.'" frameborder="0" webkitAllowFullScreen allowfullscreen></iframe>'; // Returns the vimeo iframe embed code
    }
}
 

/* -------------------------------------------------------------------------*
 * 		ADDING A CSS CLASS TO EACH LINK OF the_author_posts_link()			*
 * -------------------------------------------------------------------------*/

function the_author_posts_link_css_class($output) {
    // author id
    $user_ID = get_the_author_meta('ID');
    $googleplus = get_user_meta($user_ID, 'googleplus', true);
    if($googleplus) {
        $googleAuthor = '<a href="'.$googleplus.'" rel="author"> </a>';
    } else {
        $googleAuthor = false;
    }
	$output= preg_replace('#(<a(.*)>(.*)</a>)#U','<a $2>$3</a>'.$googleAuthor, $output);
    return $output;
}
/* -------------------------------------------------------------------------*
 * 		ADDING A CSS CLASS TO EACH LINK OF the_author_posts_link_single()			*
 * -------------------------------------------------------------------------*/


load_theme_textdomain(THEME_NAME, get_template_directory() . '/languages');
	$locale = get_locale();
	$locale_file = get_template_directory() . "/languages/$locale.php";
	if ( is_readable( $locale_file ) )
		require_once( $locale_file );

add_filter('excerpt_length', 'new_excerpt_length');
add_filter('excerpt_more', 'new_excerpt_more');
add_filter('the_author_posts_link','the_author_posts_link_css_class');

add_filter('user_contactmethods', 'DF_extra_contact_info');
add_filter('comment_form_default_fields','df_fields');
add_action('comment_form_top', 'df_fields_rules' );

?>