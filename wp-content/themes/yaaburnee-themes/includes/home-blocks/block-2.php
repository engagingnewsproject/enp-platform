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
<div class="small-carousel bx-loading">
    <div class="carousel-title">
        <?php if($title) { ?>
            <h4><?php echo $title;?></h4>
        <?php } ?>
    </div>
    <div class="carousel-group" style="height:200px; visibility:hidden">
        <?php if ($my_query->have_posts()) : while ($my_query->have_posts()) : $my_query->the_post(); ?>
            <?php
                //get post rating
                $rating = get_post_meta( $my_query->post->ID, THEME_NAME."_rating", true );
           
                //get all post categories
                $categories = get_the_category($my_query->post->ID);
                $catCount = count($categories);
                //select a random category id
                $id = rand(0,$catCount-1);
                //cat id
                $catId = $categories[$id]->term_id
            ?>
            <div class="carousel-post">
                <div class="cont-img">
                    <div class="post-category" style="background-color: <?php df_title_color($catId, $type="category");?>">
                        <a href="<?php echo get_category_link($catId);?>"><?php echo get_cat_name($catId);?></a>
                    </div>
                    <?php get_template_part(THEME_LOOP."image-slider"); ?>
                </div>
                <div class="entry-meta">
                    <span class="post-date"><?php echo the_time("F d, Y"); ?></span>
                </div>
                <h2><a href="<?php the_permalink();?>"><?php the_title();?></a></h2>
            </div>
        <?php endwhile; ?>
         <?php endif; ?> 
    </div>
</div>
