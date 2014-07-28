<?php



	$favicon = get_option(THEME_NAME."_favicon");

	$bgImage = get_option(THEME_NAME."_bg_image");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-Strict.dtd">
<html lang="en-US" prefix="og: http://ogp.me/ns#">

	<head>

		<title>

			<?php

				if ( is_single() ) { single_post_title(); print ' | '; bloginfo('name'); }      

				elseif ( is_home() || is_front_page() ) { bloginfo('name'); if(get_bloginfo('description')) { print ' | '; bloginfo('description'); } }

				elseif ( is_page() ) { single_post_title(''); if(get_bloginfo('description')) { print ' | '; bloginfo('description'); } }

				elseif ( is_search() ) { bloginfo('name'); print ' | Search results ' . esc_html($s); }

				elseif ( is_404() ) { bloginfo('name'); print ' | Page not found'; }

				else { bloginfo('name'); print ' | '; wp_title(''); }

			?>

		</title>



		<!-- Meta Tags -->

		<meta content="<?php bloginfo('html_type'); ?>" charset="<?php bloginfo('charset'); ?>" >


		<meta name="description" content="<?php bloginfo('description');?>">

		<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">

		<!-- Favicon -->

		<?php 

			if($favicon) {

		?>

			<link rel="shortcut icon" href="<?php echo $favicon;?>" type="image/x-icon" />

		<?php } else { ?>

			<link rel="shortcut icon" href="<?php echo THEME_IMAGE_URL; ?>favicon.ico" type="image/x-icon" />

		<?php } ?>

		

		<link rel="alternate" type="application/rss+xml" href="<?php bloginfo('rss2_url'); ?>" title="<?php printf( __( '%s latest posts', THEME_NAME), esc_html( get_bloginfo('name'), 1 ) ); ?>" />

		<link rel="alternate" type="application/rss+xml" href="<?php bloginfo('comments_rss2_url') ?>" title="<?php printf( __( '%s latest comments', THEME_NAME), esc_html( get_bloginfo('name'), 1 ) ); ?>" />

		<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

		<?php wp_head(); ?>	



	<!-- END head -->

	</head>

	

	<!-- BEGIN body -->

	<body <?php body_class(); ?>>
<div class="tophead">
  <div class="container">
    <img src="<?php bloginfo('template_url'); ?>/images/toptitle.jpg" alt="">
    <?php if ( is_user_logged_in() ) { ?>
      <a href="<?php echo wp_logout_url(); ?>" title="Logout" class="header-logout">Logout</a>
    <?php } ?>
  </div>
</div>
		<?php get_template_part(THEME_INCLUDES."top");?>
