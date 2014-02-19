<!DOCTYPE html>
<!--[if IE 6]>
<html id="ie6" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php elegant_titles(); ?></title>
	<?php elegant_description(); ?>
	<?php elegant_keywords(); ?>
	<?php elegant_canonical(); ?>

	<?php do_action('et_head_meta'); ?>

	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie6style.css" />
		<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/js/DD_belatedPNG_0.0.8a-min.js"></script>
		<script type="text/javascript">DD_belatedPNG.fix('img#logo, span.overlay, a.zoom-icon, a.more-icon, #menu, #menu-right, #menu-content, ul#top-menu ul, #menu-bar, .footer-widget ul li, span.post-overlay, #content-area, .avatar-overlay, .comment-arrow, .testimonials-item-bottom, #quote, #bottom-shadow, #quote .container');</script>
	<![endif]-->
	<!--[if IE 7]>
		<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie7style.css" />
	<![endif]-->
	<!--[if IE 8]>
		<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie8style.css" />
	<![endif]-->
	<!--[if lt IE 9]>
		<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
	<![endif]-->

	<script type="text/javascript">
		document.documentElement.className = 'js';
	</script>

	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<div id="container">
		<div id="wrapper">
			<header id="main-header" class="clearfix">
				<div id="top-area">
					<?php do_action('et_header_top'); ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php $logo = (get_option('trim_logo') <> '') ? esc_attr(get_option('trim_logo')) : get_template_directory_uri() . '/images/logo.png'; ?>
						<img src="<?php echo esc_attr( $logo ); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" id="logo"/>
					</a>
					<div id="search-form">
						<form method="get" id="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>/">
							<input type="text" value="<?php esc_attr_e('Search...', 'Trim'); ?>" name="s" id="searchinput" />
							<input type="image" alt="<?php echo esc_attr( 'Submit', 'Trim' ); ?>" src="<?php echo esc_url( get_template_directory_uri() . '/images/search_btn.png' ); ?>" id="searchsubmit" />
						</form>
					</div> <!-- end #search-form -->
				</div> <!-- end #top-area -->

				<div id="menu" class="clearfix">
					<?php do_action('et_header_menu'); ?>

					<nav id="main-menu">
						<?php
							$menuClass = 'nav';
							if ( get_option('trim_disable_toptier') == 'on' ) $menuClass .= ' et_disable_top_tier';
							$primaryNav = '';
							if (function_exists('wp_nav_menu')) {
								$primaryNav = wp_nav_menu( array( 'theme_location' => 'primary-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => $menuClass, 'echo' => false ) );
							}
							if ($primaryNav == '') { ?>
								<ul class="<?php echo esc_attr( $menuClass ); ?>">
									<?php if (get_option('trim_home_link') == 'on') { ?>
										<li <?php if (is_home()) echo('class="current_page_item"') ?>><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e('Home','Trim') ?></a></li>
									<?php }; ?>

									<?php show_page_menu($menuClass,false,false); ?>
									<?php show_categories_menu($menuClass,false); ?>
								</ul>
							<?php }
							else echo($primaryNav);
						?>
					</nav>

					<div id="social-icons">
						<?php
							$social_icons = '';
							$et_rss_url = get_option('trim_rss_url') <> '' ? get_option('trim_rss_url') : get_bloginfo('comments_rss2_url');
							if ( get_option('trim_show_twitter_icon') == 'on' ) $social_icons['twitter'] = array('image' => get_bloginfo('template_directory') . '/images/twitter.png', 'url' => get_option('trim_twitter_url'), 'alt' => 'Twitter' );
							if ( get_option('trim_show_rss_icon') == 'on' ) $social_icons['rss'] = array('image' => get_bloginfo('template_directory') . '/images/rss.png', 'url' => $et_rss_url, 'alt' => 'Rss' );
							if ( get_option('trim_show_facebook_icon') == 'on' ) $social_icons['facebook'] = array('image' => get_bloginfo('template_directory') . '/images/facebook.png', 'url' => get_option('trim_facebook_url'), 'alt' => 'Facebook' );
							$social_icons = apply_filters('et_social_icons', $social_icons);
							if ( !empty($social_icons) ) {
								foreach ($social_icons as $icon) {
									echo "<a href='" . esc_url($icon['url']) . "' target='_blank'><img alt='" . esc_attr($icon['alt']) . "' src='" . esc_attr($icon['image']) . "' /></a>";
								}
							}
						?>
					</div> <!-- end #social-icons -->
				</div> <!-- end #menu -->
			</header> <!-- end #main-header -->

			<?php
				if ( 'on' == get_option('trim_featured') && is_home() ) get_template_part( 'includes/featured', 'home' );
				else echo '<div id="content">';
			?>