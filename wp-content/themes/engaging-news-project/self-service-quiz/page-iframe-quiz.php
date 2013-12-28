<?php
/*
Template Name: iframe Quiz
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
  <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri() . '/self-service-quiz/css/iframe.css'; ?>" type="text/css" media="screen" />
  <?php do_action('et_head_meta'); ?>  
</head>
<body <?php body_class(); ?>>
<div class="quiz-iframe">
<?php get_template_part('self-service-quiz/quiz-display', 'page'); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>