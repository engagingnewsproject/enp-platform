<?php
/**
 * Template Name: Research Template
 */
?>

<?php while (have_posts()) : the_post(); ?>
  <?php //get_template_part('templates/entry', 'header'); ?>
  <?php get_template_part('templates/content', 'page'); ?>
<?php endwhile; ?>

<?php

// query for research

$args = array(
	'post_type' => 'research',
	'post_status' => 'publish',
	//'taxonomy' =>
);

$papers = new WP_Query( $args );

?>

<?php while ($papers->have_posts()) : $papers->the_post(); ?>
  <?php //get_template_part('templates/entry', 'header'); ?>
  <?php get_template_part('templates/content', 'research'); ?>
<?php endwhile; ?>