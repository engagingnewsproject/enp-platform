<?php 
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $DF_builder = new DF_home_builder; 
    //get block data
    $data = $DF_builder->get_data(); 
    //set query
    $my_query = $data[0]; 
    //extract array data
    extract($data[1]); 

    $count = $my_query->post_count;
    $i=1;
?>

<div class="clear"></div>
<!-- Category block news -->
<div class="category-block-news-2 clearfix">
    <!-- Category title -->
    <div class="category-title" style="background-color: <?php echo $pageColor;?>">
        <?php if($title) { ?>
            <h3><?php echo $title;?></h3>
        <?php } ?>
        <?php if($link) { ?>
            <a href="<?php echo $link;?>" class="category-link"></a>
        <?php } ?>
    </div>
    <!-- Block news list -->
    <ul class="block-news">
        <div<?php if($count>4) { ?> class="group-post-list"<?php } ?>>
            <?php if ($my_query->have_posts()) : while ($my_query->have_posts()) : $my_query->the_post(); ?>
                <?php
                    //get post rating
                    $rating = get_post_meta( $my_query->post->ID, THEME_NAME."_rating", true );
                ?>
                <!-- Post -->
                <li class="small-thumb-post">
                    <div class="cont-img">
                        <?php if(DF_image_icon($my_query->post->ID)!=false) { ?>
                            <?php echo DF_image_icon($my_query->post->ID);?>
                        <?php } ?>
                        <a href="<?php the_permalink();?>">
                            <?php echo df_image_html($my_query->post->ID,200,200); ?>
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
                <?php if($i%4==0) { ?>
                </div>
                <div<?php if($count>4) { ?> class="group-post-list"<?php } ?>>
                <?php } ?>
            <?php $i++; ?>
            <?php endwhile; ?>
            <?php endif; ?> 
        </div>
    </ul>                        
</div>