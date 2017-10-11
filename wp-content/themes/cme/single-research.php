<?php while (have_posts()) : the_post(); ?>
<?php 
$permalink = get_permalink();
$find = array( 'http://', 'https://' );
$replace = '';
$output = str_replace( $find, $replace, $permalink );
?>
  	<div class="main research-layout">
      <div class="row">
	  	<aside class="sidebar-left">
        <div class="widget widget-share-links">
    			<h5 class="widget-title">Share</h5>
    			<ul class="share-links">
                    <li><a href="https://www.facebook.com/sharer/sharer.php?u=http%3A//<?php echo $output ?>" target="_blank">Facebook</a></li>
                    <li><a href="https://twitter.com/share" target="_blank" class="twitter-share-button"{count} data-url="<?php echo get_permalink(); ?>" data-via="engagingnews" data-related="engagingnews">Twitter</a></li>
    			</ul>
        </div>
		  </aside>

      <section class="visible-xs-block">

        <?php the_widget( 'ENP_Research_Resources_Widget' ); # , $instance, $args ?>

      </section>

    	<?php get_template_part('templates/content', 'page'); ?>

      <section class="post-post-content">
      <h3>Researchers</h3>
      <?php get_template_part('templates/content', 'team'); ?>
    </section>
      </div>
    </div>
<?php endwhile; ?>
