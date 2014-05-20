<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	//about author
	$aboutAuthor = get_option(THEME_NAME."_about_author");
	$aboutAuthorSingle = get_post_meta( $post->ID, THEME_NAME."_about_author", true ); 
	
	// author id
	$user_ID = get_the_author_meta('ID');

	if($aboutAuthor == "show" || ($aboutAuthor=="custom" && $aboutAuthorSingle=="show")) { 
		//social
		$rss = get_user_meta($user_ID, 'rss', true);
		$github = get_user_meta($user_ID, 'github', true);
		$instagram = get_user_meta($user_ID, 'instagram', true);
		$tumblr = get_user_meta($user_ID, 'tumblr', true);
		$flickr = get_user_meta($user_ID, 'flickr', true);
		$skype = get_user_meta($user_ID, 'skype', true);
		$pinterest = get_user_meta($user_ID, 'pinterest', true);
		$linkedin = get_user_meta($user_ID, 'linkedin', true);
		$googleplus = get_user_meta($user_ID, 'googleplus', true);
		$youtube = get_user_meta($user_ID, 'youtube', true);
		$dribbble = get_user_meta($user_ID, 'dribbble', true);
		$facebook = get_user_meta($user_ID, 'facebook', true);
		$twitter = get_user_meta($user_ID, 'twitter', true);
		
		$user_info = get_userdata($user_ID); 

		$display_name = get_the_author_meta('display_name');
?>

<!-- About author -->
<div class="about-author" itemscope itemtype="http://data-vocabulary.org/Person">
    
    <!-- Title -->
    <div class="related-articles-title">
        <h4><?php _e("About author", THEME_NAME); ?></h4>
    </div>
    <span class="vcard author">
        <span class="fn">
            <!-- Author -->
            <div class="author-details">
                <!-- Image -->
                <img src="<?php echo get_gravatar(get_the_author_meta('user_email'), '68', THEME_IMAGE_URL.'autor.png', 'G', false, $atts = array() );?>" alt="<?php echo $display_name;?>" itemprop="photo"/>
                <!-- Header -->
                <div class="author-details-header">
                    <h3 itemprop="name"><?php echo $display_name;?></h3>
                    <a href="<?php echo get_author_posts_url($user_ID, $user_info->user_login );?>" itemprop="url"><?php _e("View all posts", THEME_NAME); ?></a>
                </div>
                <!-- Description -->
                <p><?php echo get_the_author_meta('description');?></p>
                <!-- Social icons -->
                <ul class="social-icons">
                    <?php if($rss) { ?><li class="rss"><a href="<?php echo $rss;?>" target="_blank" itemprop="contact"><i class="fa fa-rss"></i></a></li><?php } ?>
                    <?php if($github) { ?><li class="github"><a href="<?php echo $github;?>" target="_blank" itemprop="contact"><i class="fa fa-github"></i></a></li><?php } ?>
                    <?php if($instagram) { ?><li class="instagram"><a href="<?php echo $instagram;?>" target="_blank" itemprop="contact"><i class="fa fa-instagram"></i></a></li><?php } ?>
                    <?php if($tumblr) { ?><li class="tumblr"><a href="<?php echo $tumblr;?>" target="_blank" itemprop="contact"><i class="fa fa-tumblr"></i></a></li><?php } ?>
                    <?php if($flickr) { ?><li class="flickr"><a href="<?php echo $flickr;?>" target="_blank" itemprop="contact"><i class="fa fa-flickr"></i></a></li><?php } ?>
                    <?php if($skype) { ?><li class="skype"><a href="<?php echo $skype;?>" target="_blank" itemprop="contact"><i class="fa fa-skype"></i></a></li><?php } ?>
                    <?php if($pinterest) { ?><li class="pinterest"><a href="<?php echo $pinterest;?>" target="_blank" itemprop="contact"><i class="fa fa-pinterest"></i></a></li><?php } ?>
                    <?php if($linkedin) { ?><li class="linkedin"><a href="<?php echo $linkedin;?>" target="_blank" itemprop="contact"><i class="fa fa-linkedin"></i></a></li><?php } ?>
                    <?php if($googleplus) { ?><li class="googleplus"><a href="<?php echo $googleplus;?>" target="_blank" rel="author" itemprop="contact"><i class="fa fa-google-plus"></i></a></li><?php } ?>
                    <?php if($youtube) { ?><li class="youtube"><a href="<?php echo $youtube;?>" target="_blank" itemprop="contact"><i class="fa fa-youtube-play"></i></a></li><?php } ?>
                    <?php if($dribbble) { ?><li class="dribbble"><a href="<?php echo $dribbble;?>" target="_blank" itemprop="contact"><i class="fa fa-dribbble"></i></a></li><?php } ?>
                    <?php if($facebook) { ?><li class="facebook"><a href="<?php echo $facebook;?>" target="_blank" itemprop="contact"><i class="fa fa-facebook"></i></a></li><?php } ?>
                    <?php if($twitter) { ?><li class="twitter"><a href="<?php echo $twitter;?>" target="_blank" itemprop="contact"><i class="fa fa-twitter"></i></a></li><?php } ?>
                </ul>
            </div>
        </span>
    </span>
    
</div>
<?php } ?>
