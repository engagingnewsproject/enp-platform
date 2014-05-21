<?php 
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
   
    
    global $DFcounter;
    //get current cat id
    $catId = get_cat_id( single_cat_title("",false) );

	$image = get_post_thumb($post->ID,0,0); 


    //get video url
    $video = get_post_meta( $post->ID, THEME_NAME."_video", true );

    //slider images
    $slider = get_post_meta( $post->ID, THEME_NAME."_slider_images", true );

	if(get_option(THEME_NAME."_show_first_thumb") != "on" || $image['show']!=true && !$slider && !$video) {
		$class = " post-with-no-image";
	} else {
		$class = false;
	}

    //post count
    $post_count = $wp_query->post_count;



    //get post rating
    $rating = get_post_meta( $post->ID, THEME_NAME."_rating", true );

?>
        <!-- Post -->
        <article <?php post_class("main-post"); ?>>
            <?php get_template_part(THEME_LOOP."image"); ?>
            <?php get_template_part(THEME_LOOP."post-meta"); ?>
            <h2 class="entry-title"><a href="<?php the_permalink();?>"><?php the_title();?></a></h2>
            <?php
                if($rating) {
                    df_rating_html($rating);
                } 
             ?>
            <?php 
                add_filter('excerpt_length', 'new_excerpt_length_70');
                the_excerpt();
            ?>
        </article>

<?php $DFcounter++; ?>