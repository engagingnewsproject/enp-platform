<?php
/**
 * Template Name: Research CPT Template
 */
?>

<?php while (have_posts()) : the_post(); ?>
  	<div class="main article-layout"><!-- TODO: column-one -->
	  	<aside class="sidebar-left">
      <div class="widget">
  			<h5>Share</h5>
  			<ul class="share-links">
  				<li><a href="#">Facebook</a></li>
  				<li><a href="#">Twitter</a></li>
  			</ul>
      </div>
		</aside>
    <div class="research-layout">
  		<?php get_template_part('templates/content', 'page'); ?>
    </div>
  	</div>
<?php endwhile; ?>
