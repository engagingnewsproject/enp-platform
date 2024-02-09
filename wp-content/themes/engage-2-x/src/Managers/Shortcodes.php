<?php

namespace Engage\Managers;

/**
*
**/
class Shortcodes
{
	
	public static function run()
	{
		// add_shortcode('tribe_events_cme', array(get_called_class(), 'tribe_events_cme'));
	}
	
	
	public static function tribe_events_cme($attr, $content, $shortcode_tag)
	{
		
		ob_start();
		// $output = \Timber::render('templates/_shortcodes/pullquote.twig', $context);
		$output = '';
		ob_end_clean();
		
		return $output;
	}
	
}
// DUMPING SHORTCODES THROUGHOUT THE THEME HERE FOR REFACTORING
/*
function enp_display_funders ($atts) {
	
	$a = shortcode_atts( array(
		'category' => '',
	), $atts );
	
	$args = array('post_type'=> 'funders', 'post_status' => 'publish', 'funders_category' => $a['category'], 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => -1 );
	
	$funders = get_posts( $args );
	
	ob_start();
	include( locate_template( 'templates/content-funders.php' ) );
	//get_template_part( 'templates/content', 'team' );
	
	$out = ob_get_clean();
	
	return $out;
	
}
add_shortcode('funders', __NAMESPACE__ . '\\enp_display_funders');





function enp_display_team ($atts) {
	
	$a = shortcode_atts( array(
		'category' => '',
	), $atts );
	
	$args = array('post_type'=> 'team', 'post_status' => 'publish', 'team_category' => $a['category'], 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => -1 );
	
	$team = get_posts( $args );
	
	ob_start();
	include( locate_template( 'templates/content-team.php' ) );
	//get_template_part( 'templates/content', 'team' );
	
	$out = ob_get_clean();
	
	return $out;
	
}
add_shortcode('team', __NAMESPACE__ . '\\enp_display_team');
*/