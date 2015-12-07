<div class="page-layout">
<?php while (have_posts()) : the_post(); ?>
	<?php get_template_part('templates/entry', 'header'); ?>
	<?php get_template_part('templates/content', 'page'); ?>
<?php endwhile; ?>
</div>
