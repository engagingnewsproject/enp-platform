<?php 
	get_header();
	$post_type = get_post_type();

	if($post_type == "gallery-item") {
		get_template_part(THEME_INCLUDES.'gallery', 'single');
	} else {
		get_template_part(THEME_INCLUDES.'news','single');

	}
	
	
	get_footer();
?>