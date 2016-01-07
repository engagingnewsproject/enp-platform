<?php
use Roots\Sage\Extras;

/**
 * Template Name: Section Template
 */
?>
<div class="section-layout row">
	<?php if( Extras\is_tree($post->ID) ) : ?>
	<aside class="sidebar-left">
			<?php get_template_part('templates/page', 'menu'); ?>
	</aside>
	<?php endif; ?>
	<main class="page-content">
<?php while (have_posts()) : the_post(); ?>
  <?php //get_template_part('templates/page', 'header'); ?>
  <?php get_template_part('templates/content', 'page'); ?>
<?php endwhile; ?>
	</main>
</div>
