<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	$width = 864;
	$height = 400;
	$image = get_post_thumb($post->ID,$width,$height); 
	$imageL = get_post_thumb($post->ID,0,0); 

	//post details
	$singleImage = get_post_meta( $post->ID, THEME_NAME."_single_image", true );

	//get video
	$video = get_post_meta( $post->ID, THEME_NAME."_video", true );

    //slider images
    $slider = get_post_meta( $post->ID, THEME_NAME."_slider_images", true );
    $postCaption = get_option ( THEME_NAME."_post_caption" );

    //audio
    $audio = get_post_meta( $post->ID, THEME_NAME."_audio", true );

	if(((get_option(THEME_NAME."_show_single_thumb") == "on" && $singleImage=="show" && $image['show']==true) || (get_option(THEME_NAME."_show_single_thumb") == "on" && !$singleImage && $image['show']==true)) && !$slider && !$video && !$audio) { 

?>
    <!-- Image -->
	<div class="image-post">
		<?php echo df_image_html($post->ID,$width,$height); ?>
	</div>   

<?php } else if($singleImage=="show" && $slider && !$video && !$audio) { ?>
    <!-- Gallery -->
    <div class="gallery-block bx-loading">
        <ul style="height:400px; visibility:hidden">
            <?php 
                $imageIds = explode(',',$slider);
                foreach($imageIds as $id) {
                	if($id) {
	                    $slideImage = wp_get_attachment_image_src( $id, 'full');
	                    $description = get_post( $id );
	                    $image = get_post_thumb(false,$width,$height, false, $slideImage[0]); 
            ?>
                <li>
                	<?php if($description->post_content && $postCaption=="true") { ?>
                		<div class="caption"><h2><?php echo $description->post_content;?></h2></div>
                	<?php } ?>
                	<img src="<?php echo $image['src'];?>" alt="<?php echo $description->post_content;?>"/>
                </li>
            	<?php } ?>
            <?php } ?>

        </ul>
        <div class="gallery-pager">
            <?php 
                $imageIds = explode(',',$slider);
                $i=0;
                foreach($imageIds as $id) {
                	if($id) {
	                    $slideImage = wp_get_attachment_image_src( $id, 'full');
	                    $image = get_post_thumb(false,60,60, false, $slideImage[0]); 
            ?>
                <a data-slide-index="<?php echo $i;?>" href="javascript:void(0);">
                	<img src="<?php echo $image['src'];?>" alt="<?php the_title();?>"/>
                </a>
            	<?php $i++; ?>
            	<?php } ?>
            <?php } ?>
        </div>
    </div>
<?php } else if(($video && $singleImage=="show") || ($video && !$singleImage) && !$audio) { ?>
    <!-- Video -->
    <div class="video-post">
		<?php echo df_get_video_embed($video,1200,503);?>
	</div>
<?php } else if(($audio && $singleImage=="show") || ($audio && !$singleImage)) { ?>
    <!-- Audio -->
    <div class="audio-post">
        <?php echo stripslashes($audio);?>
    </div>
<?php } ?>
