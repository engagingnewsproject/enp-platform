<?php

	//social share icons
	$shareAll = get_option(THEME_NAME."_share_all");
	$shareSingle = get_post_meta( $post->ID, THEME_NAME."_share_single", true ); 
	$image = get_post_thumb($post->ID,0,0); 
?>

	<?php if($shareAll == "show" || ($shareAll=="custom" && $shareSingle=="show")) { ?>
            <!-- Share this article -->
            <div id="share-this-article">
                <div class="share-text"><?php _e("Share this article", THEME_NAME);?></div>
                <ul class="social-icons">
                    <li class="twitter">
                    	<a href="#" data-hashtags="" data-url="<?php the_permalink();?>" data-via="<?php echo get_option(THEME_NAME.'_twitter_name');?>" data-text="<?php the_title();?>" class="social-icon df-tweet" title="<?php _e("Twitter", THEME_NAME);?>">
                            <i class="icon-twitter"></i>
                            <span class="count-number">0</span>
                        </a>
                    </li>
                    <li class="facebook">
                    	<a href="http://www.facebook.com/sharer/sharer.php?u=<?php the_permalink();?>" data-url="<?php the_permalink();?>" data-url="<?php the_permalink();?>" class="social-icon df-share" title="<?php _e("Facebook", THEME_NAME);?>">
                            <i class="icon-facebook"></i>
                            <span class="count-number">0</span>
                        </a>
                    </li>
                    <li class="linkedin">
                    	<a href="http://www.linkedin.com/shareArticle?mini=true&url=<?php the_permalink();?>&title=<?php the_title();?>" data-url="<?php the_permalink();?>" class="social-icon df-link" title="<?php _e("LinkedIn", THEME_NAME);?>">
                            <i class="icon-linkedin"></i>
                            <span class="count-number">0</span>
                        </a>
                    </li>
                    <li class="google-plus">
                    	<a href="https://plus.google.com/share?url=<?php the_permalink(); ?>" class="social-icon df-pluss" title="<?php _e("Google+", THEME_NAME);?>">
                            <i class="icon-google-plus"></i>
                            <span class="count-number"><?php echo df_plusones(get_permalink());?></span>
                        </a>
                    </li>
                    <li class="pinterest">
                    	<a href="http://pinterest.com/pin/create/button/?url=<?php the_permalink(); ?>&media=<?php echo $image['src']; ?>&description=<?php the_title(); ?>" data-url="<?php the_permalink();?>" class="social-icon df-pin" title="<?php _e("Pinterest", THEME_NAME);?>">
                            <i class="icon-pinterest"></i>
                            <span class="count-number">18</span>
                        </a>
                    </li>
                </ul>
            </div>

	<?php } ?>