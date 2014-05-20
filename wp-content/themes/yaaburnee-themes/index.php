<?php 

	global $query_string, $post;
	$post_type = get_post_type();

	get_header();
	get_template_part(THEME_INCLUDES."news");
	get_footer();
	
?>