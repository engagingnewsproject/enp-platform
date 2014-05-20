<?php
global $DFfields;
$differentThemes_general_options= array(



/* ------------------------------------------------------------------------*
 * HOME SETTINGS
 * ------------------------------------------------------------------------*/   

array(
	"type" => "homepage_blocks",
	"title" => "Homepage Blocks:",
	"id" => $DFfields->themeslug."_homepage_blocks",
	"blocks" => array(
		array(
			"title" => "News & Category Block",
			"type" => "homepage_news_block",
			"options" => array(
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_title", "title" => "Title:", "home" => "yes" ),
				array( "type" => "scroller", "id" => $DFfields->themeslug."_homepage_news_block_count", "title" => "Count:", "max" => 30, "home" => "yes" ),
				array(
					"type" => "categories",
					"id" => $DFfields->themeslug."_homepage_news_block_cat",
					"taxonomy" => "category",
					"title" => "Set Category",
					"home" => "yes"
				),
				array( 
					"type" => "select", 
					"id" => $DFfields->themeslug."_homepage_news_block_style", 
					"title" => "Style:", 
					"home" => "yes",
					"options"=>array(
						array("slug"=>"1", "name"=>"Default"), 
						array("slug"=>"2", "name"=>"Style 2"), 
						array("slug"=>"3", "name"=>"Style 3"), 
					),
				),
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_offset", "title" => __("From which post should start the loop (for example 4 ), for default leave it empty, or add 0. (Offset):", THEME_NAME), "home" => "yes" ),
			),
		),
		array(
			"title" => "News & Category Touch Carousel",
			"type" => "homepage_news_block_2",
			"options" => array(
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_2_title", "title" => "Title:", "home" => "yes" ),
				array( "type" => "scroller", "id" => $DFfields->themeslug."_homepage_news_block_2_count", "title" => "Count:", "max" => 30, "home" => "yes" ),
				array(
					"type" => "categories",
					"id" => $DFfields->themeslug."_homepage_news_block_2_cat",
					"taxonomy" => "category",
					"title" => "Set Category",
					"home" => "yes"
				),
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_2_offset", "title" => __("From which post should start the loop (for example 4 ), for default leave it empty, or add 0. (Offset):", THEME_NAME), "home" => "yes" ),
			),
			
		),

		array(
			"title" => "News & Category - Blog Style",
			"type" => "homepage_news_block_3",
			"options" => array(
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_3_title", "title" => "Title:", "home" => "yes" ),
				array( "type" => "scroller", "id" => $DFfields->themeslug."_homepage_news_block_3_count", "title" => "Count:", "max" => 30, "home" => "yes" ),
				array( "type" => "checkbox", "id" => $DFfields->themeslug."_homepage_news_block_3_pagination", "title" => "Allow Pagination:", "home" => "yes" ),
				array(
					"type" => "categories",
					"id" => $DFfields->themeslug."_homepage_news_block_3_cat",
					"taxonomy" => "category",
					"title" => "Set Category",
					"home" => "yes"
				),				
				array( 
					"type" => "select", 
					"id" => $DFfields->themeslug."_homepage_news_block_3_style", 
					"title" => "Style:", 
					"home" => "yes",
					"options"=>array(
						array("slug"=>"1", "name"=>"Large Images (default)"), 
						array("slug"=>"2", "name"=>"Small Images"), 
					),
				),
			),
		),
	
		array(
			"title" => "Popular Posts Block",
			"type" => "homepage_news_block_4",
			"options" => array(
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_4_title", "title" => "Title:", "home" => "yes" ),
				array( "type" => "scroller", "id" => $DFfields->themeslug."_homepage_news_block_4_count", "title" => "Count:", "max" => 30, "home" => "yes" ),
				array(
					"type" => "categories",
					"id" => $DFfields->themeslug."_homepage_news_block_4_cat",
					"taxonomy" => "category",
					"title" => "Set Category",
					"home" => "yes"
				),				
				array( 
					"type" => "select", 
					"id" => $DFfields->themeslug."_homepage_news_block_4_style", 
					"title" => "Style:", 
					"home" => "yes",
					"options"=>array(
						array("slug"=>"1", "name"=>"Default"), 
						array("slug"=>"2", "name"=>"Style 2"), 
						array("slug"=>"3", "name"=>"Style 3"), 
					),
				),
				array( "type" => "color", "id" => $DFfields->themeslug."_homepage_news_block_4_color", "title" => "Color:", "home" => "yes" ),
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_4_offset", "title" => __("From which post should start the loop (for example 4 ), for default leave it empty, or add 0. (Offset):", THEME_NAME), "home" => "yes" ),

			),
		),		
		array(
			"title" => "Popular Posts Touch Carousel",
			"type" => "homepage_news_block_5",
			"options" => array(
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_5_title", "title" => "Title:", "home" => "yes" ),
				array( "type" => "scroller", "id" => $DFfields->themeslug."_homepage_news_block_5_count", "title" => "Count:", "max" => 30, "home" => "yes" ),
				array(
					"type" => "categories",
					"id" => $DFfields->themeslug."_homepage_news_block_5_cat",
					"taxonomy" => "category",
					"title" => "Set Category",
					"home" => "yes"
				),
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_5_offset", "title" => __("From which post should start the loop (for example 4 ), for default leave it empty, or add 0. (Offset):", THEME_NAME), "home" => "yes" ),

			),

		),		
		array(
			"title" => "Latest Reviews Block",
			"type" => "homepage_news_block_6",
			"options" => array(
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_6_title", "title" => "Title:", "home" => "yes" ),
				array( "type" => "scroller", "id" => $DFfields->themeslug."_homepage_news_block_6_count", "title" => "Count:", "max" => 30, "home" => "yes" ),
				array(
					"type" => "categories",
					"id" => $DFfields->themeslug."_homepage_news_block_6_cat",
					"taxonomy" => "category",
					"title" => "Set Category",
					"home" => "yes"
				),				
				array( 
					"type" => "select", 
					"id" => $DFfields->themeslug."_homepage_news_block_6_style", 
					"title" => "Style:", 
					"home" => "yes",
					"options"=>array(
						array("slug"=>"1", "name"=>"Default"), 
						array("slug"=>"2", "name"=>"Style 2"), 
						array("slug"=>"3", "name"=>"Style 3"), 
					),
				),
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_6_offset", "title" => __("From which post should start the loop (for example 4 ), for default leave it empty, or add 0. (Offset):", THEME_NAME), "home" => "yes" ),

			),
		),		
		array(
			"title" => "Latest Reviews Touch Carousel",
			"type" => "homepage_news_block_7",
			"options" => array(
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_7_title", "title" => "Title:", "home" => "yes" ),
				array( "type" => "scroller", "id" => $DFfields->themeslug."_homepage_news_block_7_count", "title" => "Count:", "max" => 30, "home" => "yes" ),
				array(
					"type" => "categories",
					"id" => $DFfields->themeslug."_homepage_news_block_7_cat",
					"taxonomy" => "category",
					"title" => "Set Category",
					"home" => "yes"
				),
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_7_offset", "title" => __("From which post should start the loop (for example 4 ), for default leave it empty, or add 0. (Offset):", THEME_NAME), "home" => "yes" ),
			),
		),	
		
		array(
			"title" => "Featured Shop Items",
			"type" => "homepage_news_block_8",
			"options" => array(
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_8_title", "title" => "Title:", "home" => "yes" ),
				array( "type" => "scroller", "id" => $DFfields->themeslug."_homepage_news_block_8_count", "title" => "Count:", "max" => 30, "home" => "yes" ),
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_8_offset", "title" => __("From which post should start the loop (for example 4 ), for default leave it empty, or add 0. (Offset):", THEME_NAME), "home" => "yes" ),
				array( "type" => "color", "id" => $DFfields->themeslug."_homepage_news_block_8_color", "title" => "Color:", "home" => "yes" ),
			),
		),			
		array(
			"title" => "Latest Shop Items",
			"type" => "homepage_news_block_9",
			"options" => array(
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_9_title", "title" => "Title:", "home" => "yes" ),
				array( "type" => "scroller", "id" => $DFfields->themeslug."_homepage_news_block_9_count", "title" => "Count:", "max" => 30, "home" => "yes" ),
				array(
					"type" => "categories",
					"id" => $DFfields->themeslug."_homepage_news_block_9_cat",
					"taxonomy" => "product_cat",
					"title" => "Set Category",
					"home" => "yes"
				),
				array( "type" => "input", "id" => $DFfields->themeslug."_homepage_news_block_9_offset", "title" => __("From which post should start the loop (for example 4 ), for default leave it empty, or add 0. (Offset):", THEME_NAME), "home" => "yes" ),
				array( "type" => "color", "id" => $DFfields->themeslug."_homepage_news_block_9_color", "title" => "Color:", "home" => "yes" ),
			),
		),	


		array(
			"title" => "Banner",
			"type" => "homepage_banner",
			"options" => array(
				array( "type" => "textarea", "id" => $DFfields->themeslug."_homepage_banner", "title" => "HTML Code:","sample" => '<a href="http://www.different-themes.com" target="_blank"><img src="'.THEME_IMAGE_URL.'ad-728x90.jpg" alt="" title="" /></a>', "home" => "yes" ),
			),
		),
		array(
			"title" => "HTML Code",
			"type" => "homepage_html",
			"options" => array(
				array( "type" => "textarea", "id" => $DFfields->themeslug."_homepage_html", "title" => "HTML Code:", "home" => "yes" ),
			),
		),
	)
),


 
 );


$DFfields->add_options($differentThemes_general_options);
?>