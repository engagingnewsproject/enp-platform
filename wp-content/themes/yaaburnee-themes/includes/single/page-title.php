<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	//single page titile
	$titleShow = get_post_meta ( df_page_id(), THEME_NAME."_show_title", true );
	wp_reset_query();
?>					

<?php 
	//check if bbpress
	if (function_exists("is_bbpress") && is_bbpress()) {
		$DFbbpress = true;
	} else {
		$DFbbpress = false;
	}

	if ((df_page_id()==get_option('page_for_posts') || is_category() || is_tax() || is_archive() || is_search() || isset($_REQUEST['s'])) && (get_post_type()=="post" || get_post_type()=="page") && $DFbbpress!=true && $titleShow!="hide") { 

		//if it's blog page
		if(df_page_id()==get_option('page_for_posts')) {
			$titleColor = df_title_color(df_page_id(), "page", false);	
		} elseif(is_category()) {
			// if it's a category
			$category = get_category( get_query_var( 'cat' ) );
			$cat_id = $category->cat_ID;
			$titleColor = df_title_color($cat_id, "category", false);
		} else {
			$titleColor = "#".get_option(THEME_NAME."_default_cat_color");
		}
	?>
	<div class="category-title" style="background:<?php echo $titleColor;?>">
		<h3 class="entry-title"><?php echo df_page_title(); ?></h3>
	</div>
<?php } elseif($titleShow!="hide") { ?>
	<div class="post-title">
		<h1 class="entry-title"><?php echo df_page_title(); ?></h1>
	</div>
<?php } ?>