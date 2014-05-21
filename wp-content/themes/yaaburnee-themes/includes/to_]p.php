<?php



	$logo = get_option(THEME_NAME.'_logo');			

	//search

	$search = get_option(THEME_NAME.'_search');	





	//menu color

	$menuStyle = get_option(THEME_NAME.'_menu_style');	



	//breaking news slider

	$breaking = get_option(THEME_NAME.'_breaking_slider');	



	//top banner

	$bannerTop = get_option(THEME_NAME.'_banner_top');	

	$bannerCode = get_option(THEME_NAME.'_banner_code');	



	//layout	

	$layout = get_option(THEME_NAME.'_page_layout');	

	$headerLayput = get_option(THEME_NAME.'_header_layout');	



	//main carousel

	$mainCarousel = get_post_meta(df_page_id(), THEME_NAME."_main_carousel", true);

	$mainCarouselType = get_post_meta(df_page_id(), THEME_NAME."_carousel_type", true);



	//weather forecast

	$weatherSet = get_option(THEME_NAME."_weather");



	$locationType = get_option(THEME_NAME."_weather_location_type");

	if($locationType == "custom") {

		$weather = DF_weather_forecast(str_replace(' ', '+', get_option(THEME_NAME."_weather_city")));

	} else {

		$weather = DF_weather_forecast($_SERVER['REMOTE_ADDR']);

	}





?>

        <!-- Main wrapper -->

        <div id="wrapper" class="<?php echo $layout;?>">

        



            <!-- Header -->

            <header id="header" class="<?php echo $headerLayput;?> container">

            	<div id="logo">

				    <?php if($logo) { ?> 

						<a href="<?php echo home_url(); ?>">

							<img src="<?php echo $logo;?>" alt="<?php echo get_bloginfo('name');?>">

						</a>  

					<?php } else { ?>

						<h1 class="site-title">

				    		<a href="<?php echo home_url(); ?>">

					       	 	<?php echo get_bloginfo('name');?>

					       	</a>

					    </h1>

					    <?php if(bloginfo('description')) { ?>

					    	<h2 class="site-description"><?php echo bloginfo('description');?></h2>

						<?php } ?>

					<?php } ?>

				</div>

				<?php if($bannerTop=="on" && $bannerCode) { ?>

	                <!-- Top banner block -->

	                <div class="top-banner-block"><?php echo stripslashes($bannerCode);?></div>

	            <?php } ?>

            



            <!-- Primary navigation -->
				
            <nav id="primary-top" class="<?php echo $menuStyle;?>">
<div class="socialmedia"><a href="<?php bloginfo('rss_url'); ?>" class="icon3"></a><a href="https://www.facebook.com/EngagingNewsProject

" class="icon1"></a><a href="https://twitter.com/EngagingNews" class="icon2"></a></div>
                <div class="inner">

                  <?php 
							$args = array(
								'container' => '',
								'theme_location' => 'top-menu',
								"link_before" => '',
								"menu_class" => 'top-navigation',
								"link_after" => '' ,
								'items_wrap' => '<ul class="%2$s">%3$s</ul>',
								'depth' => 3,
								"echo" => false
							);
												
							if(has_nav_menu('top-menu')) {
								echo add_menu_arrows(wp_nav_menu($args));		
							} 
						?>

                </div>

            </nav>

            <?php if($breaking=="on" || $weatherSet=="on") { ?>

            	<div class="container top-wrapp-holder">

            		<?php if($breaking=="on") { ?>

		            	<?php

		            		//cat id

							$catId = get_cat_id( single_cat_title("",false) );

		            		//show breaking posts by category

		            		if(is_category() && df_get_option($catId,"breaking_by_cat", false)) {

		            			//show default breaking posts

								$args=array(

									'posts_per_page' => get_option(THEME_NAME."_breaking_slider_count"),

									'cat'	=> $catId,

									'order'	=> 'DESC',

									'orderby'	=> 'date',

									'meta_key'	=> THEME_NAME.'_breaking_slider',

									'meta_value'	=> 'yes',

									'post_type'	=> 'post',

									'ignore_sticky_posts '	=> 1,

									'post_status '	=> 'publish'

								);

		            		} else {

		            			//show default breaking posts

								$args=array(

									'posts_per_page' => get_option(THEME_NAME."_breaking_slider_count"),

									'order'	=> 'DESC',

									'orderby'	=> 'date',

									'meta_key'	=> THEME_NAME.'_breaking_slider',

									'meta_value'	=> 'yes',

									'post_type'	=> 'post',

									'ignore_sticky_posts '	=> 1,

									'post_status '	=> 'publish'

								);

							}

							$the_query = new WP_Query($args);



		            	?>

			            <!-- Breaking news -->

			            <div id="breaking-news" class="bx-loading" style="width: 850px;">

			                <span><?php _e("Breaking news", THEME_NAME);?></span>

			                <ul style="height:10px; visibility:hidden">

			                	<?php if ($the_query->have_posts()) : while ($the_query->have_posts()) : $the_query->the_post(); ?>

			                    	<li><a href="<?php the_permalink();?>"><?php the_title();?></a></li>

								<?php endwhile; else: ?>

									<li><?php  _e( 'No posts where found' , THEME_NAME);?></li>

								<?php endif; ?>

			                </ul>

			            </div>

            		<?php } ?>

		            <?php if($weatherSet=="on") { ?>

					    <!-- Weather -->

					    <div id="weather"<?php if($breaking!="on") { echo ' class="no-breaking"';} ?>>

					    	<?php if(!isset($weather['error'])) { ?>

						        <!-- Hover -->

						        <div class="report-city">

						            <span class="report-temp"><?php echo $weather['temp_'.get_option(THEME_NAME."_temperature")];?></span>

						            <strong><?php printf( __( '%s', THEME_NAME ), $weather['weatherDesc'] );?></strong>

						            <img src="<?php echo THEME_IMAGE_URL.$weather['image'];?>.png" alt="<?php _e("Weather", THEME_NAME);?>"/>

						            <p style="display:none;"><i class="icon-map-marker"></i><?php echo $weather['city'].', '.$weather['country'];?></p>

						        </div>





							<?php 

								} else {

									echo "<span class='error'>".$weather['error']."</span>";

								} 

							?>

					    </div>



	

					<?php } ?>

				</div>

			<?php } ?>		

           
</header>
<div class="slidebox"><?php echo do_shortcode('[layerslider id="3"]'); ?></div>
<div class="calltoaction"><div class="container"><span>The Engaging News </span><font>Project researches </font><span>commercially-viable</span><font> and 	</font><span>democratically-beneficial </span><font>ways to improve </font><span>online news</span></div></div>