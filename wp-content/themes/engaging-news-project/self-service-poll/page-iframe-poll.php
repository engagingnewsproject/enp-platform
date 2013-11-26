<?php
/*
Template Name: iframe Poll
*/
?>
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
  <link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
  <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri() . '/self-service-poll/css/iframe.css'; ?>" type="text/css" media="screen" />
</head>
<div id="main_content" class="clearfix">
	<div id="left_area">
<?php get_template_part('self-service-poll/poll-display', 'page'); ?>
  </div> 
</div> <!-- end #main_content -->

<?php get_footer(); ?>