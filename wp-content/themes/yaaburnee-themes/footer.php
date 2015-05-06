<?php

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	// footer info

	$footerText = get_option ( THEME_NAME."_footer_text" );



	// pop up banner

	$banner_type = get_option ( THEME_NAME."_banner_type" );

	

	$banner_fly_in = get_option ( THEME_NAME."_banner_fly_in" );

	$banner_fly_out = get_option ( THEME_NAME."_banner_fly_out" );

	$banner_start = get_option ( THEME_NAME."_banner_start" );

	$banner_close = get_option ( THEME_NAME."_banner_close" );

	$banner_overlay = get_option ( THEME_NAME."_banner_overlay" );

	$banner_views = get_option ( THEME_NAME."_banner_views" );

	$banner_timeout = get_option ( THEME_NAME."_banner_timeout" );

	

	$banner_text_image_img = get_option ( THEME_NAME."_banner_text_image_img" ) ;

	$banner_image = get_option ( THEME_NAME."_banner_image" );

	$banner_text = stripslashes ( get_option ( THEME_NAME."_banner_text" ) );

	

	if ( $banner_type == "image" ) {

	//Image Banner

		$cookie_name = substr ( md5 ( $banner_image ), 1,6 );

	} else if ( $banner_type == "text" ) { 

	//Text Banner

		$cookie_name = substr ( md5 ( $banner_text ), 1,6 );

	} else if ( $banner_type == "text_image" ) { 

	//Image And Text Banner

		$cookie_name = substr ( md5 ( $banner_text_image_img ), 1,6 );

	} else {

		$cookie_name = "popup";

	}



	if ( !$banner_start) {

		$banner_start = 0;

	}

	

	if ( !$banner_close) {

		$banner_close = 0;

	}

	

	if ( $banner_overlay == "on") {

		$banner_overlay = "true";

	} else {

		$banner_overlay = "false";

	}

	



	?>

            <!-- Footer -->

            <footer id="footer">

				<div class="navmenu"><?php 

						$args = array(

							'container' => '',

							'theme_location' => 'footer-menu',

							"link_before" => '',

							"menu_class" => 'footer-navigation',

							"link_after" => '' ,

							'items_wrap' => '<ul class="%2$s">%3$s</ul>',

							'depth' => 1,

							"echo" => false

						);

											

						if(has_nav_menu('footer-menu')) {

						?>

							<!-- Footer navigation -->

			                <nav class="inner">

			                    <a class="click-to-open-menu"><i class="fa fa-align-justify"></i></a>

			             	   	<?php echo add_menu_arrows(wp_nav_menu($args));	?>
<div class="footersocialmedia"><a href="<?php bloginfo('rss_url'); ?>" class="icon3"></a><a href="<?php the_field('facebook',412); ?>" class="icon1"></a><a href="<?php the_field('twitter',412); ?>" class="icon2"></a></div>
			                </nav>
                            


						<?php	

						} 

				?>

</div>

                <!-- Footer widgets -->

                <div class="container">

                    <div class="row">  

					<?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('footer') ) : ?>

					<?php endif; ?>

	                </div>                        

	            </div>  

                <!-- Copyright -->

                   

            </footer>

		</div>

<?php

			//pop up banner

			if ( $banner_type != "off" ) {

		?>

		

		<script type="text/javascript">

		<!--

		

		jQuery(document).ready(function($){

			$('#popup_content').popup( {

				starttime 			 : <?php echo $banner_start; ?>,

				selfclose			 : <?php echo $banner_close; ?>,

				popup_div			 : 'popup',

				overlay_div	 		 : 'overlay',

				close_id			 : 'baner_close',

				overlay				 : <?php echo $banner_overlay; ?>,

				opacity_level		 : 0.7,

				overlay_cc			 : false,

				centered			 : true,

				top	 		   		 : 130,

				left	 			 : 130,

				setcookie 			 : true,

				cookie_name	 		 : '<?php echo $cookie_name;?>',

				cookie_timeout 	 	 : <?php echo $banner_timeout; ?>,

				cookie_views 		 : <?php echo $banner_views ; ?>,

				floating	 		 : true,

				floating_reaction	 : 700,

				floating_speed 		 : 12,

				<?php 

					if ( $banner_fly_in != "off") { 

						echo "fly_in : true,

						fly_from : '".$banner_fly_in."', "; 

					} else {

						echo "fly_in : false,";

					}

				?>

				<?php 

					if ( $banner_fly_out != "off") { 

						echo "fly_out : true,

						fly_to : '".$banner_fly_out."', "; 

					} else {

						echo "fly_out : false,";

					}

				?>

				popup_appear  		 : 'show',

				popup_appear_time 	 : 0,

				confirm_close	 	 : false,

				confirm_close_text 	 : 'Do you really want to close?'

			} );

		});

		-->

		</script>

		<?php } ?>

		<div class="help-tab">
			
			<a href="<?php echo get_permalink(611); ?>">Need Help?</a>
			
		</div>

		<?php wp_footer(); ?>

	</body>

</html>