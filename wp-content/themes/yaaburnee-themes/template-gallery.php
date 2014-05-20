<?php
/*
Template Name: Gallery Page
*/	
?>
<?php get_header(); ?>
<?php
	wp_reset_query();
	$paged = get_query_string_paged();
	$posts_per_page = get_option(THEME_NAME.'_gallery_items');
	
	if($posts_per_page == "") {
		$posts_per_page = get_option('posts_per_page');
	}
	
	$my_query = new WP_Query(array('post_type' => 'gallery-item', 'posts_per_page' => $posts_per_page, 'paged'=>$paged));  
	$categories = get_terms( 'gallery-cat', 'orderby=name&hide_empty=0' );

?>
<?php get_template_part(THEME_LOOP."loop","start"); ?> 
	<div class="blank-page-container">
		<?php get_template_part(THEME_SINGLE."page-title"); ?> 
        <!-- Filters -->
        <div class="photo-filters">
            <ul class="gallery-filter">
                <li><a href="#" class="selected-gallery-filter" data-filter="*"><?php _e('All', THEME_NAME); ?></a></li>
				<?php foreach ($categories as $category) { ?>
					<li><a href="#" data-filter=".<?php echo $category->slug;?>"><?php echo $category->name;?></a></li>
				<?php } ?>
            </ul>
        </div>
        <!-- Gallery -->
        <div class="gallery-content">
			<?php 
				$args = array(
					'post_type'     	=> 'gallery-item',
					'post_status'  	 	=> 'publish',
					'showposts' 		=> -1
				);

				$myposts = get_posts( $args );	
				$count_total = count($myposts);
					
			?>
			<?php if ( $my_query->have_posts() ) : while ( $my_query->have_posts() ) : $my_query->the_post(); ?>
			<?php 
				$c=1;
				$src = get_post_thumb($post->ID,350,300);
				$term_list = wp_get_post_terms($post->ID, 'gallery-cat');
						
			?>
					

                <div class="photo-stack<?php foreach ($term_list as $term) { echo " ".$term->slug; } ?>">
                    <a href="<?php the_permalink();?>"><img src="<?php echo $src['src'];?>" alt="<?php the_title();?>"/></a>
                    <h2><a href="<?php the_permalink();?>"><?php the_title();?></a></h2>
                    <?php add_filter('excerpt_length', 'new_excerpt_length_10');?>
                    <?php the_excerpt();?>
                </div>
			
			<?php 
				if ( $paged != 0 ) {
					$a = ($paged-1)*$posts_per_page;
				} else {		
					$a = 1;
				}
			?>
													
			<?php endwhile; ?>
			<?php else : ?>
				<h2 class="title"><?php _e( 'No items were found' , THEME_NAME );?></h2>
			<?php endif; ?>
			<?php gallery_nav_btns($paged, $my_query->max_num_pages); ?>
		</div>
	</div>
<?php get_template_part(THEME_LOOP."loop","end"); ?> 
<?php get_footer(); ?>