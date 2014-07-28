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
<?php if($style=="2") { ?>
    <div class="category-block-news-5 clearfix">
<?php } else { ?>
    <div class="category-block-news-4 clearfix">
<?php } ?>
    <!-- Title -->
    <div class="category-title" style="background-color: <?php echo $pageColor;?>">
        <?php if($title) { ?>
            <h3><?php echo $title;?></h3>
        <?php } ?>
        <?php if($link) { ?>
            <a href="<?php echo $link;?>" class="category-link"></a>
        <?php } ?>
    </div>
    <?php if ($my_query->have_posts()) : while ($my_query->have_posts()) : $my_query->the_post(); ?>
        <?php get_template_part(THEME_LOOP."post"); ?>
    <?php endwhile; ?>
    <?php endif; ?> 
</div>
<?php 
    if($pagination=="on") { customized_nav_btns($paged, $my_query->max_num_pages); }
?>