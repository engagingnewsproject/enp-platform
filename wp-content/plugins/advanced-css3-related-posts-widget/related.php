<?php
/*
Plugin Name: Related Posts Widget with Thumbnails
Plugin URI: http://www.wp-buy.com/
Description: Our plugin displaying related posts in a very great way as a sidebar widget to help visitors staying longer on your site.You can use this plugin to increasing the page rank of your internal posts to improve your SEO score and increase the internal links preiority in google webmaster tools
Works with all custom post types!
Version: 1.0.0.4
Author: wp-buy
Author URI: http://www.wp-buy.com/
*/
//define all variables the needed alot

include 'the_globals.php';
//-------------------------------------------------------SimpleTabs scripts
function rpw_enqueue_scripts() {
	global $rpwpluginsurl;
	wp_register_script( 'simpletabsjs', $rpwpluginsurl .'/js/simpletabs_1.3.js');
	wp_enqueue_script( 'simpletabsjs' );
	wp_register_style( 'simpletabscss', $rpwpluginsurl .'/css/simpletabs.css');
	wp_enqueue_style('simpletabscss');
}
// Hook into the 'wp_enqueue_scripts' action
add_action( 'admin_head', 'rpw_enqueue_scripts' );
//--------------------------------------------------------
$rpw_related_posts_settings = rpw_read_options();
//echo $rpwpluginsurl;

//-------------------------------------------------------Function to read options from the database
function rpw_read_options()
{
	if (get_option('rpw_settings'))
		$rpw_related_posts_settings = get_option('rpw_settings');
	else
		$rpw_related_posts_settings = rpw_default_options();
	return $rpw_related_posts_settings;
}
//-------------------------------------------------------Set default values to the array
function rpw_default_options(){

	$pluginsurl = plugins_url( '', __FILE__ );

	$default_thumb = $pluginsurl.'/images/noimage.png';

	$rpw_related_posts_settings = 

	Array (
			'rpw_show_thumbs' => 'Yes', // Display thumbs or not?
			
			'rpw_thumbw' => '40', // Thumbnail thumb width
			
			'rpw_thumbh' => '40', // Thumbnail thumb height
			
			'rpw_posts_limit' => '7', // How many posts to display?
			
			'rpw_show_excerpt' => 'Yes',
			
			'rpw_excerpt_length' => '13',
			
			'rpw_use_css3_effects' => 'Yes',
			
			'rpw_css3_shadow' => '5',
			
			'rpw_css3_thumb_radius' => '45',
			
			'default_thumb' => $default_thumb, // Default thumbnail
			
			'rpw_image_direction' => 'left',
		
			'rpw_text_direction' => 'ltr'
		);
	return $rpw_related_posts_settings;
}
//-------------------------------------------------------get the taglist
function rpw_get_taglist(){

//get the tag id's as al list

wp_reset_query();

global $post;

$tags = wp_get_post_tags($post->ID);

if(!isset($tags))
{
	$tagcount = count($tags);

	$result = $tags;

	if ($tagcount > 1) {

	for ($i = 0; $i < $tagcount; $i++)

	   {

		$mytags[$i]['term_id'] = $tags[$i]->term_id;

		$mytags[$i]['count'] = $tags[$i]->count;

	   }

	$result = rpw_sortTwoDimensionArrayByKey($mytags,'count');

	}

	$taglist_full = "'" . $result[0]->term_id. "'";

	$taglist = "'" . $result[0]->term_id. "'";

	$countlist = "'" . $result[0]->count. "'";

	$mysum = 0;

	$myavg = 0;

	for ($i = 1; $i < $tagcount; $i++) {

		$mysum = $mysum + $result[$i]['count'];

	}

	if($tagcount != 0) $myavg = $mysum / $tagcount;

	if ($myavg < 4 || $tagcount < 5) $myoperator = 20;

	else

	$myoperator = $myavg + 3;

	if ($tagcount > 1) {

		for ($i = 1; $i < $tagcount; $i++) {

			$taglist_full = $taglist_full . ", '" . $result[$i]['term_id'] . "'";

			if ($result[$i]['count'] < $myoperator) {

			   $taglist = $taglist . ", '" . $result[$i]['term_id'] . "'";

			   $countlist = $countlist . ", '" . $result[$i]['count'] . "'";}

		}

	}
}
else
{
	$sub_title = $post->post_title;
	
	if($sub_title != '') {
		
		$sub_title = str_replace(' ' , ',' , $sub_title);
	}else{
		$sub_title = 'google';
	}
		
	$taglist = "'" .$sub_title. "'";
}
return $taglist;

}
//------------------------------------------------------------------------

function get_rpw_searches($taglist)
{
	global $wpdb, $post, $single;

	$stuff = '';

	$searches = '';

	$limit = 5;

	$rpw_related_posts_settings = rpw_read_options();

	global $rstyle;
	
	$limit = (stripslashes($rpw_related_posts_settings['rpw_posts_limit']));

	// Make sure the post is not from the future

	$time_difference = get_option('gmt_offset');

	$now = gmdate("Y-m-d H:i:s",(time()+($time_difference*3600)));

	$stuff = addslashes($post->post_title);

	//$stuff = '';

	if ((is_int($post->ID))) {

		$sql = "SELECT DISTINCT ID,post_title,post_date "

		. "FROM ". "$wpdb->posts , $wpdb->term_taxonomy t_t, $wpdb->term_relationships t_r"

		." WHERE "

		." (t_t.taxonomy ='post_tag' AND t_t.term_taxonomy_id = t_r.term_taxonomy_id  AND  t_r.object_id  = ID AND (t_t.term_id IN ($taglist))) "

		. "AND post_date <= '".$now."' "

		. "AND post_status = 'publish' "

		. "AND id != ".$post->ID." "

		."AND post_type = 'post' "

		." order by id desc "

		."LIMIT ".$limit;

		$search_counter = 0;

		$searches = $wpdb->get_results($sql);

		$mycount = count($searches);

	} else {

		$searches = false;

	}

$idslist = "'" . $post->ID. "'";

if ($mycount > 0) {

	for ($i = 0; $i < $mycount; $i++) {

		   $idslist = $idslist . ", '" . $searches[$i]->ID. "'";

	}

}

$new_limit = $limit - $mycount;

$sql = "SELECT ID,post_title,post_date "

		. "FROM ". "$wpdb->posts "

		." WHERE "

		."post_type = 'post' and post_status = 'publish' "

		. "AND (id NOT IN ($idslist)) "

		." order by post_title "

		."LIMIT ".$new_limit;

		$searches2 = $wpdb->get_results($sql);

$merged_searches = array_merge($searches, $searches2);

return $merged_searches;

}

//------------------------------------------------------------------------

function get_related_posts_rpw()

{

if (is_single()) {

$output = get_related_posts_rpw_output();

	return $output;

}else {

        return '';

    }

}

//add_filter('the_content','get_related_posts_rpw');

//------------------------------------------------------------------------

function get_related_posts_rpw_output()

{

if (is_single()) {

	$echoed_content = '';

	global $rstyle;

	$rstyle = 'related_list';

	if($rstyle == 'related_list'){

		$echoed_content = include 'related_list_rpw.php';

	}
	return $echoed_content;

}else {

        return $content;

    }

}

//------------------------------------------------------------------------

function get_related_posts_rpw_style()

{

if (is_single()) {

	$echoed_content = '';

	global $rstyle;

	$rstyle = 'related_list';

	if($rstyle == 'related_list'){

		$echoed_content = include 'related_list_style.php';
	}

	echo $echoed_content;
	}

}

add_filter('wp_head','get_related_posts_rpw_style');

//------------------------------------------------------------------------

function rpw_sortTwoDimensionArrayByKey($arr, $arrKey, $sortOrder=SORT_ASC){

foreach ($arr as $key => $row){

$key_arr[$key] = $row[$arrKey];

}

array_multisort($key_arr, $sortOrder, $arr);

if (isset($arr))
	return $arr;
else
	return '';

}

//------------------------------------------------------------------------
class RelatedPostWidget extends WP_Widget
{
function RelatedPostWidgetnull()
 {}
  function RelatedPostWidget()
  {
    $widget_ops = array('classname' => 'RelatedPostsWidget', 'description' => 'Displays a relates posts with thumbnail' );

    parent::__construct('RelatedPostsWidget', 'Related Posts With Thumbnail widget', $widget_ops);
	
  }

function form($instance)
  {

    $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );

    $title = $instance['title'];

    if ($title == ''){$title = 'Related Posts';}

?>

  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
    <p><a href="options-general.php?page=RelatedPostswidgetoptions">Settings</a></p>
<?php

  }

  function update($new_instance, $old_instance)

  {

    if (empty($new_instance['title'])) $new_instance['title'] = 'Related Posts';

    $instance = $old_instance;

    $instance['title'] = $new_instance['title'];

    return $instance;

  }

 

  function widget($args, $instance)

  {

    extract($args, EXTR_SKIP);

 

    echo $before_widget;

    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
    
    if(is_single()){

		if (!empty($title))
		
			echo $before_title . $title . $after_title;
		
		else
		
			echo $before_title . 'Related Posts' . $after_title;
		
		
		
		// WIDGET CODE GOES HERE
		
		echo get_related_posts_rpw();
		
		
		
		echo $after_widget;
		
		}
	}

}

add_action( 'widgets_init', create_function('', 'return register_widget("RelatedPostWidget");') );

//-------------------------------------------------------
function rpw_html2txt($document){
    $search = array('@<script[^>]*?>.*?</script>@si', // Strip out javascript
    '@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
    '@<[?]php[^>].*?[?]>@si', //scripts php
    '@<[?][^>].*?[?]>@si', //scripts php
    '@<[\/\!]*?[^<>]*?>@si', // Strip out HTML tags
    '@<![\s\S]*?--[ \t\n\r]*>@' // Strip multi-line comments including CDATA
    );$text = preg_replace($search, '', $document);
    return $text;
    }
//-------------------------------------------------------
function rpw_excerpt($id,$excerpt_length){
	$content = get_post($id)->post_excerpt;
	if ($content == '') $content = get_post($id)->post_content;
	$out = strip_tags($content);
	$blah = explode(' ',$out);
    //$html_source = file_get_contents('http://www.anysite.com');
    $blah = rpw_html2txt($blah);
	if (!$excerpt_length) $excerpt_length = 10;
	if(count($blah) > $excerpt_length){
		$k = $excerpt_length;
		$use_dotdotdot = 1;
	}else{
		$k = count($blah);
		$use_dotdotdot = 0;
	}
	$excerpt = '';
	for($i=0; $i<$k; $i++){
		$excerpt .= $blah[$i].' ';
	}
	$excerpt .= ($use_dotdotdot) ? '..' : '';
	$out = $excerpt;
	return $out;
}
//------------------------------------------------------------------------
function rpw_title_shorter($title,$title_length){

	$content = $title;

	$out = strip_tags($content);

	$blah = explode(' ',$out);

	if (!$title_length) $title_length = 10;

	if(count($blah) > $title_length){

		$k = $title_length;

		$use_dotdotdot = 1;

	}else{

		$k = count($blah);

		$use_dotdotdot = 0;

	}

	$new_title = '';

	for($i=0; $i<$k; $i++){

		$new_title .= $blah[$i].' ';

	}

	$new_title .= ($use_dotdotdot) ? '..' : '';

	$out = $new_title;

	return $out;

}

//------------------------------------------------------------------------
function rpw_image_attachments_define_image_sizes() {
  add_theme_support('post-thumbnails');
  $rpw_related_posts_settings = rpw_read_options();
  if ( function_exists( 'add_image_size' ) ) {
			add_image_size( 'rpw-thumb', $rpw_related_posts_settings['rpw_thumbw'] , $rpw_related_posts_settings['rpw_thumbh'] ,true); ////(True = cropped)
		}
}
add_action('admin_init', 'rpw_image_attachments_define_image_sizes');
//------------------------------------------------------------------------

//First use the add_action to add onto the WordPress menu.

add_action('admin_menu', 'rpw_add_options');

//Make our function to call the WordPress function to add to the correct menu.

function rpw_add_options() {

	add_options_page('Related Posts widget Options Page', 'Related Posts widget', 'manage_options', 'RelatedPostswidgetoptions', 'rpw_options_page');

}
//------------------------------------------------------------------------

function rpw_options_page() {

      include "admin-core.php";

}

//-----------disable the widget when its in home page---
//add_filter( 'sidebars_widgets', 'my_disable_widget' ,10);

function my_disable_widget( $sidebars_widgets ) {

    /* disable it only singular pages */
    if ( is_home() ){
        /* get each sidebar / widget area */
        foreach( $sidebars_widgets as $widget_area => $widget_list ){

            /* get all widget list in the area */
            foreach( $widget_list as $pos => $widget_id ){
			$position = strpos($widget_id, "relatedpostswidget");
			echo $position;
                if ( $widget_id == 'relatedpostswidget-2')/* widget with id "relatedpostswidget" */
                {
                    /* remove it */
                    unset( $sidebars_widgets[$widget_area][$pos] );
                }
            }

        }
    }

    return $sidebars_widgets;
}
//-------------------------------------------------------
?>