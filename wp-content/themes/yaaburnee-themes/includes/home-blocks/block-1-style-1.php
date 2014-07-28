<?php 
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $DF_builder = new DF_home_builder; 
    //get block data
    $data = $DF_builder->get_data(); 
    //set query
    $my_query = $data[0]; 
    //extract array data
    extract($data[1]); 
?>

<div class="clear"></div>

<!-- Category block news -->
<div class="category-block-news-1 clearfix">
    <!-- Category title -->
    <div class="category-title" style="background-color: <?php echo $pageColor;?>">
        <?php if($title) { ?>
            <h3><?php echo $title;?></h3>
        <?php } ?>
        <?php if($link) { ?>
            <a href="<?php echo $link;?>" class="category-link"></a>
        <?php } ?>
    </div>
    <?php if ($my_query->have_posts()) : $my_query->the_post();?>
        <?php
            //get post rating
            $rating = get_post_meta( $my_query->post->ID, THEME_NAME."_rating", true );
        ?>
        <!-- Main post -->
        <div class="main-post">
            <div class="cont-img">
                <?php if(DF_image_icon($my_query->post->ID)!=false) { ?>
                    <?php echo DF_image_icon($my_query->post->ID);?>
                <?php } ?>
                <a href="<?php the_permalink();?>">
                    <?php echo df_image_html($my_query->post->ID,622,250); ?>
                </a>
            </div>
            <?php get_template_part(THEME_LOOP."post-meta"); ?>
            <h2>
                <a href="<?php the_permalink();?>"><?php the_title();?></a>
            </h2>
            <?php
                if($rating) {
                    df_rating_html($rating);
                } 
             ?>
            <?php 
                add_filter('excerpt_length', 'new_excerpt_length_50');
                the_excerpt();
            ?>             
        </div>
    <?php endif; ?> 
    <!-- Block list news -->
    <ul class="block-news">
        <?php if ($my_query->have_posts()) : while ($my_query->have_posts()) : $my_query->the_post(); ?>
            <?php
                //get post rating
                $rating = get_post_meta( $my_query->post->ID, THEME_NAME."_rating", true );
            ?>
            <!-- Post -->
            <li class="small-thumb-post">
                <div class="cont-img">
                    <a href="<?php the_permalink();?>">
                        <?php echo df_image_html($my_query->post->ID,80,80); ?>
                    </a>
                </div>
                <div class="description">
                    <div class="entry-meta">
                        <span class="post-date"><?php echo the_time("F d, Y"); ?></span>
                    </div>
                    <h2>
                        <a href="<?php the_permalink();?>"><?php the_title();?></a>
                    </h2>
                    <?php
                        if($rating) {
                            df_rating_html($rating);
                        } 
                     ?>
                </div>
            </li>
        <?php endwhile; ?>
        <?php endif; ?> 
    </ul>                         
</div>