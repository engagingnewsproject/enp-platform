<?php
/**
 * Template Name: Research CPT Template
 */
?>

<?php while (have_posts()) : the_post(); ?>
  	<div class="main research-layout">
      <div class="row">
	  	<aside class="sidebar-left">
        <div class="widget">
    			<h5>Share</h5>
    			<ul class="share-links">
    				<li><a href="#">Facebook</a></li>
    				<li><a href="#">Twitter</a></li>
    			</ul>
        </div>
		  </aside>

    	<?php get_template_part('templates/content', 'page'); ?>

      </div>
    </div>
<?php endwhile; ?>
