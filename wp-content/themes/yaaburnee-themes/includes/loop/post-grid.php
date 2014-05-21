<?php 
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
   
    //get post rating
    $rating = get_post_meta( $post->ID, THEME_NAME."_rating", true );

?>
    <!-- Post -->
    <li <?php post_class("small-thumb-post"); ?>>
        <?php get_template_part(THEME_LOOP."image"); ?>
        <div class="description">
            <div class="entry-meta">
                <span class="post-date"><?php echo the_time("F d, Y"); ?></span>
            </div>
            <h2 class="entry-title">
                <a href="<?php the_permalink();?>"><?php the_title();?></a>
            </h2>
            <?php
                if($rating) {
                    df_rating_html($rating);
                } 
             ?>
        </div>
    </li>