<?php

/* -------------------------------------------------------------------------*
 * 							THEME STYLE AND JS FILES						*
 * -------------------------------------------------------------------------*/

function different_themes_css() {
	$safari = strpos($_SERVER["HTTP_USER_AGENT"], 'Safari') ? true : false;
	$chrome = strpos($_SERVER["HTTP_USER_AGENT"], 'Chrome') ? true : false;

	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-datepicker');
	wp_enqueue_script("cookie" , THEME_JS_ADMIN_URL."jquery.c00kie.js", Array('jquery'));
	
	add_editor_style('includes/admin/buttons-formatting/custom-editor-style.css');
		
	if (!isset($_GET["page"])) {
		wp_enqueue_style("custom-editor-style", THEME_ADMIN_URL."buttons-formatting/custom-editor-style.css", Array());
	}
		
	if(!isset($_GET["page"])) {
		wp_enqueue_style("df-post", THEME_CSS_ADMIN_URL."post.css", Array());
	}

	wp_enqueue_script("jscolors" , THEME_ADMIN_URL."jscolor/jscolor.js", Array('jquery'));
	wp_enqueue_script("options" , THEME_JS_ADMIN_URL."options.js", Array('jquery'));

	wp_enqueue_script("jquery-uniform" , THEME_JS_ADMIN_URL."jquery.uniform.js", Array('jquery'));
	wp_enqueue_script("jquery-ui-sortable");
	wp_enqueue_script("jquery-ui-core");
	wp_enqueue_script("jquery-ui-tabs");
	wp_enqueue_script("jquery-ui-mouse");
	wp_enqueue_script("jquery-ui-slider");
	wp_enqueue_script("jquery-ui-widget");
	wp_enqueue_script("jquery-ui-draggable");
	wp_enqueue_script("jquery-ui-droppable");

	wp_enqueue_media();

	if(!isset($_GET["page"])) {
		wp_enqueue_script("scripts-admin" , THEME_JS_ADMIN_URL."scripts.js", Array('jquery'));
	} 

	if(isset($_GET["page"]) && $_GET["page"]=="theme-configuration") {
		wp_deregister_style("social-fans-admin-style-ui");
		wp_deregister_style("jquery-ui");
		wp_enqueue_script("scripts-admin" , THEME_JS_ADMIN_URL."scripts.js", Array('jquery'), '', true);
		
		wp_enqueue_script("jquery-uniform" , THEME_JS_ADMIN_URL."jquery.uniform.js", Array('jquery'));
		wp_enqueue_script("main-javascripts" , THEME_JS_ADMIN_URL."main-javascripts.js", Array('jquery'), '', true);

		

		wp_enqueue_style("fonts", "http://fonts.googleapis.com/css?family=Ropa+Sans", Array());
		wp_enqueue_style("different-themes-control-panel", THEME_CSS_ADMIN_URL."/main-stylesheet.css", Array());
		wp_enqueue_style("different-themes-control-panel-responsive", THEME_CSS_ADMIN_URL."main-responsive.css", Array());
		wp_enqueue_style("different-themes-control-panel", THEME_CSS_ADMIN_URL."/ie678-fix.css", Array());
		
		if ($safari && !$chrome) { 
			wp_enqueue_style("different-themes-safari", THEME_CSS_ADMIN_URL."safari.css", Array());	
		}
	}
 	
	wp_localize_script(
		'scripts-admin',
		'scripts',
		array(
			'adminUrl' => admin_url("admin-ajax.php"),
			'themeName' => THEME_NAME,
			'uploadHandler' => THEME_FUNCTIONS_URL."upload-handler.php",
			'themeUploadUrl' => THEME_UPLOADS_URL
		)
	);

}

	add_action('admin_enqueue_scripts', 'different_themes_css');

?>