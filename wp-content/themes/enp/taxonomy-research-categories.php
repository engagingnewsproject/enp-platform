<?php
/**
 * Template Name: Research Categories
 */
?>

<div class="row">
<div class="sidebar-nav">
  <ul class="page-menu research-menu">
  <?php enp_research_categories_list(); ?>
</ul>
</div>

<div class="col-md-8">
<div class="research-section">
<?php if (!have_posts()) : ?>
  <div class="alert alert-warning">
    <?php _e('Sorry, no results were found.', 'sage'); ?>
  </div>
  <?php get_search_form(); ?>
<?php endif; ?>

<?php while (have_posts()) : the_post(); ?>
  <?php get_template_part('templates/content', get_post_type() != 'post' ? get_post_type() : get_post_format()); ?>
<?php endwhile; ?>

<?php the_posts_navigation(); ?>
</div>
</div>
</div>
