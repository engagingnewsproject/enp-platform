<?php
/*
Plugin Name: FD Footnotes
Plugin URI: http://flagrantdisregard.com/footnotes-plugin
Description: Elegant and easy to use footnotes
Author: John Watson
Version: 1.36
Author URI: http://flagrantdisregard.com

Copyright (C) 2008 John Watson
john@flagrantdisregard.com
http://flagrantdisregard.com/footnotes-plugin

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

define('FDFOOTNOTE_TEXTDOMAIN', 'fdfootnote');

if (function_exists('load_plugin_textdomain')) {
	load_plugin_textdomain(FDFOOTNOTE_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)).'/languages' );
}

add_action('admin_menu', 'fdfootnote_config_page');
add_action('wp_enqueue_scripts', 'fdfootnote_enqueue_scripts');

function fdfootnote_enqueue_scripts() {
	if (is_admin()) return;
	
	wp_enqueue_script('jquery');
	
	wp_register_script('fdfootnote_script', get_bloginfo('wpurl').'/'.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/fdfootnotes.js', array('jquery'), '1.34');
	wp_enqueue_script('fdfootnote_script');
}

function fdfootnote_config_page() {
	global $wpdb;
	if ( function_exists('add_submenu_page') )
		add_submenu_page('options-general.php',
			__('Footnotes', FDFOOTNOTE_TEXTDOMAIN),
			__('Footnotes', FDFOOTNOTE_TEXTDOMAIN),
			'manage_options', __FILE__, 'fdfootnote_conf');
}

function fdfootnote_conf() {
	$options = get_option('fdfootnote');

	if (!isset($options['fdfootnote_collapse'])) $options['fdfootnote_collapse'] = 0;
	
	$updated = false;
	if ( isset($_POST['submit']) ) {
		check_admin_referer('fdfootnote', 'fdfootnote-admin');
		
		if (isset($_POST['fdfootnote_collapse'])) {
			$options['fdfootnote_collapse'] = 1;
		} else {
			$options['fdfootnote_collapse'] = 0;
		}

		if (isset($_POST['fdfootnote_single'])) {
			$options['fdfootnote_single'] = 1;
		} else {
			$options['fdfootnote_single'] = 0;
		}

		update_option('fdfootnote', $options);

		$updated = true;
	}
	?>
	<div class="wrap">
	<?php
	if ($updated) {
		echo "<div id='message' class='updated fade'><p>";
		_e('Configuration updated.', FDFOOTNOTE_TEXTDOMAIN);
		echo "</p></div>";
	}
	?>
	<h2><?php _e('Footnotes Configuration', FDFOOTNOTE_TEXTDOMAIN); ?></h2>
	<form action="" method="post" id="fdfootnote-conf">
	
	<p>
		<input id="fdfootnote_single" name="fdfootnote_single" type="checkbox" value="1"<?php if ($options['fdfootnote_single']==1) echo ' checked'; ?> />
		<label for="fdfootnote_single"><?php _e('Only show footnotes on single post/page', FDFOOTNOTE_TEXTDOMAIN); ?></label>
	</p>

	<p>
		<input id="fdfootnote_collapse" name="fdfootnote_collapse" type="checkbox" value="1"<?php if ($options['fdfootnote_collapse']==1) echo ' checked'; ?> />
		<label for="fdfootnote_collapse"><?php _e('Collapse footnotes until clicked', FDFOOTNOTE_TEXTDOMAIN); ?></label>
	</p>

	<p class="submit" style="text-align: left"><?php wp_nonce_field('fdfootnote', 'fdfootnote-admin'); ?><input type="submit" name="submit" value="<?php _e('Save', FDFOOTNOTE_TEXTDOMAIN); ?> &raquo;" /></p>
	</form>
	</div>
	<?php
}

// Converts footnote markup into actual footnotes
function fdfootnote_convert($content) {
	$options = get_option('fdfootnote');
	$collapse = 0;
	$single = 0;
	$linksingle = false;
	if (isset($options['fdfootnote_collapse'])) $collapse = $options['fdfootnote_collapse'];
	if (isset($options['fdfootnote_single'])) $single = $options['fdfootnote_single'];
	if (!is_page() && !is_single() && $single) $linksingle = true;
	
	$post_id = get_the_ID();

	$n = 1;
	$notes = array();
	if (preg_match_all('/\[(\d+\..*?)\]/s', $content, $matches)) {
		foreach($matches[0] as $fn) {
			$note = preg_replace('/\[\d+\.(.*?)\]/s', '\1', $fn);
			$notes[$n] = $note;

			$singleurl = '';
			if ($linksingle) $singleurl = get_permalink();
			
			$content = str_replace($fn, "<sup class='footnote'><a href='$singleurl#fn-$post_id-$n' id='fnref-$post_id-$n' onclick='return fdfootnote_show($post_id)'>$n</a></sup>", $content);
			$n++;
		}

		// *****************************************************************************************************
		// Workaround for wpautop() bug. Otherwise it sometimes inserts an opening <p> but not the closing </p>.
		// There are a bunch of open wpautop tickets. See 4298 and 7988 in particular.
		$content .= "\n\n";
		// *****************************************************************************************************

		if (!$linksingle) {
			$content .= "<div class='footnotes' id='footnotes-$post_id'>";
			$content .= "<div class='footnotedivider'></div>";
			
			if ($collapse) {
				$content .= "<a href='#' onclick='return fdfootnote_togglevisible($post_id)' class='footnotetoggle'>";
				$content .= "<span class='footnoteshow'>".sprintf(_n('Show %d footnote', 'Show %d footnotes', $n-1, FDFOOTNOTE_TEXTDOMAIN), $n-1)."</span>";
				$content .= "</a>";
				
				$content .= "<ol style='display: none'>";
			} else {
				$content .= "<ol>";
			}
			for($i=1; $i<$n; $i++) {
				$content .= "<li id='fn-$post_id-$i'>$notes[$i] <span class='footnotereverse'><a href='#fnref-$post_id-$i'>&#8617;</a></span></li>";
			}
			$content .= "</ol>";
			$content .= "</div>";
		}
	}

	return($content);
}

add_action('the_content', 'fdfootnote_convert', 1);
?>