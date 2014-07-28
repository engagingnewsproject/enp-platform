<?php
global $different_themes_managment;
$differentThemes_sidebar_options= array(
 array(
	"type" => "navigation",
	"name" => "Sidebar Settings",
	"slug" => "sidebars"
),

array(
	"type" => "tab",
	"slug"=>'sidebar_settings'
),

array(
	"type" => "sub_navigation",
	"subname"=>array(
			array("slug"=>"sidebar", "name"=>"Sidebar")
		)
),

/* ------------------------------------------------------------------------*
 * SIDEBAR GENERATOR
 * ------------------------------------------------------------------------*/

 array(
	"type" => "sub_tab",
	"slug"=>'sidebar'
),
array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => "Sidebar Position"
),

array(
	"type" => "radio",
	"id" => $different_themes_managment->themeslug."_sidebar_position",
	"radio" => array(
		array("title" => "Left:", "value" => "left"),
		array("title" => "Right:", "value" => "right"),
		array("title" => "Custom For Each Page:", "value" => "custom")
	),
	"std" => "right"
),
array(
	"type" => "close"
),
array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => "Add Sidebar",
),

array(
	"type" => "add_text",
	"title" => "Add New Sidebar:",
	"id" => THEME_NAME."_sidebar_name"
),

array(
	"type" => "close"
),



array(
	"type" => "row"
),

array(
	"type" => "title",
	"title" => "Sidebar Sequence",
	"info" => "To sort the slides just drag and drop them!"
),

array(
	"type" => "sidebar_order",
	"title" => "Order Sidebars",
	"id" => THEME_NAME."_sidebar_name"
),
  
array(
	"type" => "close"
),

array(
	"type" => "closesubtab"
),

array(
	"type" => "closetab"
)
 
);

$different_themes_managment->add_options($differentThemes_sidebar_options);
?>