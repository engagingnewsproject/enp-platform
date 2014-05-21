<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    

    //similar news
    $similarPosts = get_option(THEME_NAME."_similar_posts");
    $similarPostsSingle = get_post_meta( $post->ID, THEME_NAME."_similar_posts", true ); 

    if($similarPosts == "show" || ($similarPosts=="custom" && $similarPostsSingle=="show")) {
        $similarPostsShow = true;
    } else {
        $similarPostsShow = false;  
    }

    if($similarPostsShow==true) {
    
        wp_reset_query();
        $categories = get_the_category($post->ID);
        
        if ($categories) {
            $category_ids = array();
            foreach($categories as $individual_category) $category_ids[] = $individual_category->term_id;

            $args=array(
                'category__in' => $category_ids,
                'post__not_in' => array($post->ID),
                'showposts'=>6,
                'ignore_sticky_posts'=>1,
                'orderby' => 'rand'
            );

            $my_query = new wp_query($args);
            $postCount = $my_query->post_count;
            $counter = 1;

?>  

                    <!-- Related article (Carousel) -->
                    <div class="related-articles">
                        <!-- Title -->
                        <div class="related-articles-title">
                            <h4><?php _e("Related articles", THEME_NAME);?></h4>
                        </div>
                        <!-- Group -->
                        <div class="related-articles-group">
                            <?php                                   
                                if( $my_query->have_posts() ) {
                                    while ($my_query->have_posts()) {
                                        $my_query->the_post();
                                        //get all post categories
                                        $categories = get_the_category($my_query->post->ID);
                                        $catCount = count($categories);
                                        //select a random category id
                                        $id = rand(0,$catCount-1);
                                        //cat id
                                        $catId = $categories[$id]->term_id

                            ?>
                                <!-- Post -->
                                <div class="related-post">
                                    <div class="cont-img">
                                        <div class="post-category" style="background-color: <?php df_title_color($catId, $type="category");?>">
                                            <a href="<?php echo get_category_link($catId);?>"><?php echo get_cat_name($catId);?></a>
                                        </div>
                                        <?php get_template_part(THEME_LOOP."image-slider"); ?>
                                    </div>
                                    <div class="entry-meta">
                                        <span class="post-date"><?php echo the_time("F d, Y"); ?></span>
                                    </div>
                                    <h2>
                                        <a href="<?php the_permalink();?>">
                                            <?php the_title();?>
                                        </a>
                                    </h2>
                                </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                    <!-- End related artciles -->
    <?php } ?>
<?php } ?>
<?php wp_reset_query();  ?>