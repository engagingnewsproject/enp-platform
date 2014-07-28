<?php
global $different_themes_managment;
$differentThemes_slider_options= array(
 array(
	"type" => "navigation",
	"name" => "Slider Settings",
	"slug" => "sliders"
),

array(
	"type" => "tab",
	"slug"=>'custom-styling'
),

array(
	"type" => "sub_navigation",
	"subname"=>array(
		array("slug"=>"slider_settings", "name"=>"Slider Settings"),
		)
),

/* ------------------------------------------------------------------------*
 * HEADER SLIDER SETTINGS
 * ------------------------------------------------------------------------*/

 array(
	"type" => "sub_tab",
	"slug"=> 'slider_settings'
),

array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => __("Breaking Slider", THEME_NAME),
),

array(
	"type" => "checkbox",
	"title" => __("Show Breaking News Slider:", THEME_NAME),
	"id"=>$different_themes_managment->themeslug."_breaking_slider"
),

array(
	"type" => "input",
	"title" => __("Breaking News Slider Post Count:", THEME_NAME),
	"id" => $different_themes_managment->themeslug."_breaking_slider_count",
	"number" => "yes",
	"std" => "3",
	"protected" => array(
		array("id" => $different_themes_managment->themeslug."_breaking_slider", "value" => "on")
	)
),

array(
	"type" => "select",
	"title" => "Auto Start",
	"id" => $different_themes_managment->themeslug."_breaking_auto",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "true",
	"protected" => array(
		array("id" => $different_themes_managment->themeslug."_breaking_slider", "value" => "on")
	)
),

array(
	"type" => "select",
	"title" => "Slide Mode",
	"id" => $different_themes_managment->themeslug."_breaking_mode",
	"options"=>array(
		array("slug"=>"horizontal", "name"=>"Horizontal"), 
		array("slug"=>"vertical", "name"=>"Vertical"),
		array("slug"=>"fade", "name"=>"Fade")
	),
	"std" => "vertical",
	"protected" => array(
		array("id" => $different_themes_managment->themeslug."_breaking_slider", "value" => "on")
	)
),
array(
	"type" => "select",
	"title" => "Slide Pause",
	"id" => $different_themes_managment->themeslug."_breaking_pause",
	"options"=>array(
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "2000",
	"protected" => array(
		array("id" => $different_themes_managment->themeslug."_breaking_slider", "value" => "on")
	)
),
array(
	"type" => "select",
	"title" => "Slide Speed",
	"id" => $different_themes_managment->themeslug."_breaking_speed",
	"options"=>array(
		array("slug"=>"500", "name"=>"0.5 seccond"), 
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"1500", "name"=>"1.5 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"2500", "name"=>"2.5 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "500",
	"protected" => array(
		array("id" => $different_themes_managment->themeslug."_breaking_slider", "value" => "on")
	)
),
array(
	"type" => "close"
),

array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => __("Main Touch Carousel Slider", THEME_NAME),
),

array(
	"type" => "input",
	"title" => __("Touch Carousel Slider Post Count:", THEME_NAME),
	"id" => $different_themes_managment->themeslug."_carousel_slider_count",
	"number" => "yes",
	"std" => "8",
),

array(
	"type" => "select",
	"title" => "Auto Start",
	"id" => $different_themes_managment->themeslug."_main_carousel_auto",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "false"
),

array(
	"type" => "select",
	"title" => "Slide Pause",
	"id" => $different_themes_managment->themeslug."_main_carousel_pause",
	"options"=>array(
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "2000"
),
array(
	"type" => "select",
	"title" => "Slide Speed",
	"id" => $different_themes_managment->themeslug."_main_carousel_speed",
	"options"=>array(
		array("slug"=>"500", "name"=>"0.5 seccond"), 
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"1500", "name"=>"1.5 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"2500", "name"=>"2.5 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "500"
),


array(
	"type" => "close"
),

array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => __("Main Homepage Slider", THEME_NAME),
),

array(
	"type" => "input",
	"title" => __("Slider Post Count:", THEME_NAME),
	"id" => $different_themes_managment->themeslug."_main_slider_count",
	"number" => "yes",
	"std" => "5",
),

array(
	"type" => "select",
	"title" => "Auto Start",
	"id" => $different_themes_managment->themeslug."_main_auto",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "true"
),
array(
	"type" => "select",
	"title" => "Text Caption",
	"id" => $different_themes_managment->themeslug."_main_caption",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "true"
),
array(
	"type" => "select",
	"title" => "Slide Mode",
	"id" => $different_themes_managment->themeslug."_main_mode",
	"options"=>array(
		array("slug"=>"horizontal", "name"=>"Horizontal"), 
		array("slug"=>"vertical", "name"=>"Vertical"),
		array("slug"=>"fade", "name"=>"Fade")
	),
	"std" => "fade"
),
array(
	"type" => "select",
	"title" => "Slide Pause",
	"id" => $different_themes_managment->themeslug."_main_pause",
	"options"=>array(
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "2000"
),
array(
	"type" => "select",
	"title" => "Slide Speed",
	"id" => $different_themes_managment->themeslug."_main_speed",
	"options"=>array(
		array("slug"=>"500", "name"=>"0.5 seccond"), 
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"1500", "name"=>"1.5 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"2500", "name"=>"2.5 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "500"
),

array(
	"type" => "close"
),

array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => __("Small Touch Carousel Slider", THEME_NAME),
),

array(
	"type" => "select",
	"title" => "Auto Start",
	"id" => $different_themes_managment->themeslug."_small_carousel_auto",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "false"
),

array(
	"type" => "select",
	"title" => "Slide Pause",
	"id" => $different_themes_managment->themeslug."_small_carousel_pause",
	"options"=>array(
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "2000"
),
array(
	"type" => "select",
	"title" => "Slide Speed",
	"id" => $different_themes_managment->themeslug."_small_carousel_speed",
	"options"=>array(
		array("slug"=>"500", "name"=>"0.5 seccond"), 
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"1500", "name"=>"1.5 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"2500", "name"=>"2.5 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "500"
),


array(
	"type" => "close"
),
array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => "Post Slider Options",
),

array(
	"type" => "select",
	"title" => "Auto Start",
	"id" => $different_themes_managment->themeslug."_post_auto",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "false"
),

array(
	"type" => "select",
	"title" => "Slide Mode",
	"id" => $different_themes_managment->themeslug."_post_mode",
	"options"=>array(
		array("slug"=>"horizontal", "name"=>"Horizontal"), 
		array("slug"=>"vertical", "name"=>"Vertical"),
		array("slug"=>"fade", "name"=>"Fade")
	),
	"std" => "fade"
),
array(
	"type" => "select",
	"title" => "Slide Controls",
	"id" => $different_themes_managment->themeslug."_post_controls",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "true"
),
array(
	"type" => "select",
	"title" => "Slide Caption",
	"id" => $different_themes_managment->themeslug."_post_caption",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "true"
),

array(
	"type" => "select",
	"title" => "Slide Pause",
	"id" => $different_themes_managment->themeslug."_post_pause",
	"options"=>array(
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "2000"
),
array(
	"type" => "select",
	"title" => "Slide Speed",
	"id" => $different_themes_managment->themeslug."_post_speed",
	"options"=>array(
		array("slug"=>"500", "name"=>"0.5 seccond"), 
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"1500", "name"=>"1.5 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"2500", "name"=>"2.5 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "500"
),

array(
	"type" => "close"

),
array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => "Woocommerce Slider Options",
),

array(
	"type" => "select",
	"title" => "Auto Start",
	"id" => $different_themes_managment->themeslug."_woocommerce_auto",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "false"
),

array(
	"type" => "select",
	"title" => "Slide Mode",
	"id" => $different_themes_managment->themeslug."_woocommerce_mode",
	"options"=>array(
		array("slug"=>"horizontal", "name"=>"Horizontal"), 
		array("slug"=>"vertical", "name"=>"Vertical"),
		array("slug"=>"fade", "name"=>"Fade")
	),
	"std" => "fade"
),
array(
	"type" => "select",
	"title" => "Slide Controls",
	"id" => $different_themes_managment->themeslug."_woocommerce_controls",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "true"
),
array(
	"type" => "select",
	"title" => "Slide Caption",
	"id" => $different_themes_managment->themeslug."_woocommerce_caption",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "true"
),

array(
	"type" => "select",
	"title" => "Slide Pause",
	"id" => $different_themes_managment->themeslug."_woocommerce_pause",
	"options"=>array(
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "2000"
),
array(
	"type" => "select",
	"title" => "Slide Speed",
	"id" => $different_themes_managment->themeslug."_woocommerce_speed",
	"options"=>array(
		array("slug"=>"500", "name"=>"0.5 seccond"), 
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"1500", "name"=>"1.5 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"2500", "name"=>"2.5 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "500"
),

array(
	"type" => "close"

),

array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => "Widget Slider Options",
),

array(
	"type" => "select",
	"title" => "Auto Start",
	"id" => $different_themes_managment->themeslug."_widget_auto",
	"options"=>array(
		array("slug"=>"true", "name"=>"True"), 
		array("slug"=>"false", "name"=>"False")
	),
	"std" => "false"
),
array(
	"type" => "select",
	"title" => "Slide Pause",
	"id" => $different_themes_managment->themeslug."_widget_pause",
	"options"=>array(
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "2000"
),
array(
	"type" => "select",
	"title" => "Slide Speed",
	"id" => $different_themes_managment->themeslug."_widget_speed",
	"options"=>array(
		array("slug"=>"500", "name"=>"0.5 seccond"), 
		array("slug"=>"1000", "name"=>"1 seccond"), 
		array("slug"=>"1500", "name"=>"1.5 seccond"), 
		array("slug"=>"2000", "name"=>"2 seccond"),
		array("slug"=>"2500", "name"=>"2.5 seccond"),
		array("slug"=>"3000", "name"=>"3 seccond"),
		array("slug"=>"4000", "name"=>"4 seccond"),
		array("slug"=>"5000", "name"=>"5 seccond"),
		array("slug"=>"6000", "name"=>"6 seccond"),
		array("slug"=>"7000", "name"=>"7 seccond"),
		array("slug"=>"8000", "name"=>"8 seccond"),
		array("slug"=>"9000", "name"=>"9 seccond")
	),
	"std" => "500"
),

array(
	"type" => "close"

),


array(
	"type" => "save",
	"title" => "Save Changes"
),
   
array(
	"type" => "closesubtab"
),

array(
	"type" => "closetab"
)
 
);

$different_themes_managment->add_options($differentThemes_slider_options);
?>