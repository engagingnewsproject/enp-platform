<?php
/**
 * Template Name: No Sidebar Template
 */
?>
<div class="no-sidebar">
<?php while (have_posts()) : the_post(); ?>
  <?php //get_template_part('templates/entry', 'header'); ?>
  <?php get_template_part('templates/content', 'page'); ?>
<?php endwhile; ?>
</div>
