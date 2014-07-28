<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	$width = 275;
	$height = 200;
	$image = get_post_thumb($post->ID,$width,$height); 

	//if($image['show']==true) { 

?> 
    <?php if(DF_image_icon($post->ID)!=false) { ?>
        <?php echo DF_image_icon($post->ID);?>
    <?php } ?>
    <a href="<?php the_permalink();?>">
    	<?php echo df_image_html($post->ID,$width,$height); ?>
    </a>
<?php //} ?>