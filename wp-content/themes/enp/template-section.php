<?php
use Roots\Sage\Extras;

/**
 * Template Name: Section Template
 */
?>

<section class="main page-layout">
	<?php if( Extras\is_tree($post->ID) ) : ?>
	<aside class="sidebar-left">
			<?php get_template_part('templates/page', 'menu'); ?>
	</aside>
	<?php endif; ?>
	<section class="section-layout">
<?php while (have_posts()) : the_post(); ?>
  <?php //get_template_part('templates/page', 'header'); ?>
  <?php get_template_part('templates/content', 'page'); ?>
<?php endwhile; ?>
	</section>
</section>
