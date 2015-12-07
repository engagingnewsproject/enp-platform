<?php
/**
 * Template Name: Research CPT Template
 */
?>

<?php while (have_posts()) : the_post(); ?>
  	<div class="main article-layout"><!-- TODO: column-one -->
	  	<aside class="sidebar-left">
			<h3>Share</h3>
			<ul class="share-links">
				<li><a href="#">Facebook</a></li>
				<li><a href="#">Twitter</a></li>
			</ul>
		</aside>
    <div class="research-layout">
  		<?php get_template_part('templates/content', 'page'); ?>
    </div>
  	</div>
<?php endwhile; ?>
