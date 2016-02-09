<?php while (have_posts()) : the_post(); ?>
  	<div class="main research-layout">
      <div class="row">
	  	<aside class="sidebar-left">
        <div class="widget widget-share-links">
    			<h5 class="widget-title">Share</h5>
    			<ul class="share-links">
    				<li><a href="https://www.facebook.com/dialog/share?app_id=1709815112597170&amp;display=popup&amp;href=<?php echo get_permalink(); ?>&amp;redirect_uri=<?php echo get_permalink(); ?>">Facebook</a></li>
    				<li><a href="https://twitter.com/share" class="twitter-share-button"{count} data-url="<?php echo get_permalink(); ?>" data-via="engagingnews" data-related="engagingnews">Twitter</a></li>
    			</ul>
        </div>
		  </aside>

    	<?php get_template_part('templates/content', 'page'); ?>

      <section class="post-post-content">
      <h3>Researchers</h3>
      <?php get_template_part('templates/content', 'team'); ?>
    </section>
      </div>
    </div>
<?php endwhile; ?>
