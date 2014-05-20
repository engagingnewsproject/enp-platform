<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	wp_reset_query();

    //get current cat id
    $catId = get_cat_id( single_cat_title("",false) );

    //blog style
    if(is_category()) {
        $blogStyle = df_get_option($catId,"blog_style");
    } else {
        $blogStyle = get_option(THEME_NAME."_blog_style");
    }
    
    if(!isset($blogStyle) || $blogStyle==""){
        $blogStyle = get_option(THEME_NAME."_blog_style");
    }

	//post count
	$posts_per_page = get_option(THEME_NAME.'_posts_count_grid');

	if($posts_per_page == "") {
		$posts_per_page = get_option('posts_per_page');
	}

	$counter = 1;

	$count = $wp_query->post_count;

?>
<?php get_template_part(THEME_LOOP."loop","start"); ?> 
	<?php get_template_part(THEME_SINGLE."page-title"); ?>
	<?php
		if($blogStyle=="3") {
	?>
		<div class="category-block-news-2 clearfix">
		    <!-- Block news list -->
		    <ul class="block-news">
		    	<div<?php if($count>4) { ?> class="group-post-list"<?php } ?>>
	<?php } ?>
		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			<?php
				if($blogStyle=="3") {
					get_template_part(THEME_LOOP."post","grid");
				} else {
					get_template_part(THEME_LOOP."post");
				}
		 	?>

            <?php if($counter%4==0 && $blogStyle=="3") { ?>
           	</div>
            <div<?php if($count>4) { ?> class="group-post-list"<?php } ?>>
            <?php } ?>
      	 	<?php $counter++; ?>

		<?php endwhile; else: ?>
			<p><?php  _e('Sorry, no posts matched your criteria.' , THEME_NAME ); ?></p>
		<?php endif; ?>
	<?php
		if($blogStyle=="3") {
	?>
				</div>
			</ul>
		</div>
	<?php } ?>
	<?php customized_nav_btns($paged, $wp_query->max_num_pages); ?>	
<?php get_template_part(THEME_LOOP."loop","end"); ?> 	