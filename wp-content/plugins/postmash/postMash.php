<?php
/*
Plugin Name: postMash
Plugin URI: http://joelstarnes.co.uk/postMash/
Description: postMash > Order Posts
Author: Joel Starnes
Version: 1.2.0
Author URI: http://joelstarnes.co.uk/
	
*/
#########CONFIG OPTIONS############################################
$minlevel = 7;  /*[deafult=7]*/
/* Minimum user level to access page order */

$switchDraftToPublishFeature = true;  /*[deafult=true]*/
/* Allows you to set pages not to be listed */

$ShowDegubInfo = false;  /*[deafult=false]*/
/* Show server response debug info */

###################################################################
/*
INSPIRATIONS/CREDITS:
Valerio Proietti - Mootools JS Framework [http://mootools.net/]
Stefan Lange-Hegermann - Mootools AJAX timeout class extension [http://www.blackmac.de/archives/44-Mootools-AJAX-timeout.html]
vladimir - Mootools Sortables class extension [http://vladimir.akilles.cl/scripts/sortables/]
ShiftThis - WP Page Order Plugin [http://www.shiftthis.net/wordpress-order-pages-plugin/]
Garrett Murphey - Page Link Manager [http://gmurphey.com/2006/10/05/wordpress-plugin-page-link-manager/]
*/

/*  Copyright 2008  Joel Starnes  (email : joel@joelstarnes.co.uk)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
// Guess the location
$postMash_url = WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__));

function postMash_getPages(){
	global $wpdb, $wp_version, $switchDraftToPublishFeature;
	
	//get pages from database
	$pageposts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type = 'post' ORDER BY menu_order");
	
	if ($pageposts == true){
		echo '<ul id="postMash_pages">';
		foreach ($pageposts as $page): //list pages, [the 'li' ID must be pm_'page ID'] ?>
			<li id="pm_<?php echo $page->ID; ?>"<?php if($page->post_status == "draft"){ echo ' class="remove"'; } //if page is draft, add class remove ?>>
				<span class="title"><?php echo $page->post_title;?></span>
				<span class="postMash_box">
					<span class="postMash_more">&raquo;</span>
					<span class="postMash_pageFunctions">
						id:<?php echo $page->ID;?>
						[<a href="<?php echo get_bloginfo('wpurl').'/wp-admin/post.php?action=edit&post='.$page->ID; ?>" title="Edit This Post">edit</a>]
						<?php if($switchDraftToPublishFeature): ?>
							[<a href="#" title="Draft|Publish" class="excludeLink" onclick="toggleRemove(this); return false">toggle-draft</a>]
						<?php endif; ?>
					</span>
				</span>
			</li>
		<?php endforeach;
		echo '</ul>';
		return true;
	} else {
		return false;
	}
}

function postMash_main(){
	global $switchDraftToPublishFeature, $ShowDegubInfo;
	?>
	<div id="debug_list"<?php if(false==$ShowDegubInfo) echo' style="display:none;"'; ?>>Press 'Update' and you should see the query text displayed here.</div>
	<div id="postMash" class="wrap">
		<div id="postMash_checkVersion" style="float:right; font-size:.7em; margin-top:5px;">
		    version 1.2.0
		</div>
		<h2 style="margin-bottom:0; clear:none;">postMash: Post Ordering</h2>
		<p style="margin-top:4px;">
			Just drag the posts <strong>up</strong> or <strong>down</strong> to change their order. <?php echo "The draft button will toggle the page between draft and published states."; ?>
		</p>
		
		<?php postMash_getPages(); ?>
		
		<p class="submit">
			<div id="update_status" style="float:left; margin-left:40px; opacity:0;"></div>
				<input type="submit" id="postMash_submit" tabindex="2" style="font-weight: bold; float:right;" value="Update" name="submit"/>
		</p>
		<br style="margin-bottom: .8em;" />
	</div>

	<div class="wrap" style="width:160px; margin-bottom:0; padding:0;"><p><a href="#" id="postMashInfo_toggle">Show|Hide Further Info</a></p></div>
	<div class="wrap" id="postMashInfo" style="margin-top:-1px;">
		<h2>How to Use</h2>
		<p>No longer do you need to modify any template code. Just grab the plug-in, activate and it's ready to use. If you previously added code to your template you may remove it now.</p>
		<p>If you have a question or some feedback, just <a href="http://joelstarnes.co.uk/contact/" title="email Joel Starnes">drop me a mail</a>.</p>
		<br />
		<p><a href="#" id="show_debug_list">Show debug info.</a></p>
		<br />
	</div>
	<?php
}

function postMash_head(){
	//stylesheet & javascript to go in page header
	global $postMash_url;
	
	wp_enqueue_script('postMash_mootools', $postMash_url.'/nest-mootools.v1.11.js', false, false); //code is not compatible with other releases of moo
	wp_deregister_script('prototype');//remove prototype since it is incompatible with mootools
	wp_enqueue_script('postMash', $postMash_url.'/postMash.js', array('postMash_mootools'), false);
	add_action('admin_head', 'postMash_add_css', 1);

}

function postMash_add_css(){
	global $postMash_url;
	printf('<link rel="stylesheet" type="text/css" href="%s/postMash.css" />', $postMash_url);
	?>
<!--                    __  __           _     
      WordPress Plugin |  \/  |         | |    
  _ __  __ _  __ _  ___| \  / | __ _ ___| |__  
 | '_ \/ _` |/ _` |/ _ \ |\/| |/ _` / __| '_ \ 
 | |_)  (_| | (_| |  __/ |  | | (_| \__ \ | | |
 | .__/\__,_|\__, |\___|_|  |_|\__,_|___/_| |_|
 | |          __/ |  Author: Joel Starnes
 |_|         |___/   URL: joelstarnes.co.uk
 
 >>postMash Admin Page
-->
	<?php
}

function postMash_add_pages(){
	//add menu link
	global $minlevel, $wp_version;
	if($wp_version >= 2.7){
		$page = add_submenu_page('edit.php', 'postMash: Order Posts', 'postMash', $minlevel,  __FILE__, 'postMash_main'); 
	}else{
		$page = add_management_page('postMash: Order Posts', 'postMash', $minlevel, __FILE__, 'postMash_main');
	}
	add_action("admin_print_scripts-$page", 'postMash_head'); //add css styles and JS code to head
}

function postMash_orderPosts($orderBy) {
	global $wpdb;
	$orderBy = "{$wpdb->posts}.menu_order ASC";
	return($orderBy);
}

add_action('admin_menu', 'postMash_add_pages'); //add admin menu under management tab
add_filter('posts_orderby', 'postMash_orderPosts'); //add filter for post ordering


?>