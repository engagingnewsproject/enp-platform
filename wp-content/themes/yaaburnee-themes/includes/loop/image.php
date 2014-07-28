<?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
  
    $DF_builder = new DF_home_builder; 
    //get block data
    $data = $DF_builder->get_data(); 

    //get current cat id
    $catId = get_cat_id( single_cat_title("",false) );

    //blog style
    if(is_category()) {
        $blogStyle = df_get_option($catId,"blog_style");
    } elseif(is_page_template('template-homepage.php')) {
        $blogStyle = $data[1]['style'];
    } else {
        $blogStyle = get_option(THEME_NAME."_blog_style");
    }

    if(!isset($blogStyle) || $blogStyle==""){
        $blogStyle = get_option(THEME_NAME."_blog_style");
    }


    //set image size
    if($blogStyle=="3") {
        $width = 200;
        $height = 200;
    } elseif($blogStyle=="2") {
        $width = 275;
        $height = 200;
    } else {
        $width = 858;
        $height = 350;
    }
    

	$image = get_post_thumb($post->ID,$width,$height); 
	$imageL = get_post_thumb($post->ID,0,0); 

	if(get_option(THEME_NAME."_show_first_thumb") == "on" && $image['show']==true) {

?>
    <div class="cont-img">
        <?php if(DF_image_icon($post->ID)!=false) { ?>
            <?php echo DF_image_icon($post->ID);?>
        <?php } ?>
        <a href="<?php the_permalink();?>">
    	   <?php echo df_image_html($post->ID,$width,$height); ?>
        </a>
    </div>
<?php } ?>