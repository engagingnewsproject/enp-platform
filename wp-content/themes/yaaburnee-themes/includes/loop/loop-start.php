<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	$sidebar = get_post_meta( df_page_id(), THEME_NAME.'_sidebar_select', true );
    $contentId = df_page_id();

    if(is_category()) {
        $sidebar = df_get_option( get_cat_id( single_cat_title("",false) ), 'sidebar_select', false );
    }

    //get current cat id
    $catId = get_cat_id( single_cat_title("",false) );
   
    //blog style
    if(is_category()) {
        $blogStyle = df_get_option($catId,"blog_style");
        $contentId = $catId;
    } else {
        $blogStyle = get_option(THEME_NAME."_blog_style");
    }
    
    if(!isset($blogStyle) || $blogStyle==""){
        $blogStyle = get_option(THEME_NAME."_blog_style");
    }


    if($blogStyle=="2") {
    	$pClass="category-block-news-5 ";
    } else {
    	$pClass="category-block-news-4 ";
    }

?>

<!-- Container -->
<div class="container">
	<?php if($sidebar=="DFoff") { ?>
	  	<!-- Primary fullwidth -->
	    <div id="primary-left">
    <?php } else { ?>
	    <!-- Primary left -->
	    <div id="primary-left">
    <?php } ?> 

    <?php if(df_page_id()==get_option('page_for_posts') || is_category() || is_tax() || is_archive() || is_search() || isset($_REQUEST['s'])) { ?>
        <!-- Category block news -->
        <div class="<?php echo $pClass;?>clearfix">
    <?php } ?>

