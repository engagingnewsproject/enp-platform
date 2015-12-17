<?php
/**
 * Template Name: Homepage Template v2
 */
?>
<?php while (have_posts()) : the_post(); ?>
<section class="home-layout">
	<section id="featured-content" class="container">
		<div class="row">
		<div class="col-md-8 featured-story">
			<?php $featured = get_field('featured_post')[0]; ?>
			<div class="content-block">
				<figure class="featured-image">
					<?php echo get_the_post_thumbnail( $featured->ID, 'featured-post' ); ?>
				</figure>
				<h1><a href="<?php echo get_the_permalink($featured->ID); ?>"><?php echo $featured->post_title; ?></a></h1>
				<div class="entry-summary">
          <p><?php echo $featured->post_excerpt; ?></p>
        </div>
				<p><a href="<?php echo get_the_permalink($featured->ID); ?>">Read more -></a></p>
			</div>

		</div>
		<aside class="col-md-4">
			<!-- related stories widget -->
			<?php dynamic_sidebar('sidebar-home'); ?>
		</aside>
	</div>
	</section> <!-- END .featured-content -->
	<section id="about" class="callout">
    <div class="container">
	  <div class="col-md-10 col-md-offset-1">
      <div class="row">
		    <?php the_field('homepage_about'); ?>
      </div>
    </div>
    </div>
	</section>
	<section class="section-layout">

  <?php //get_template_part('templates/page', 'header'); ?>
  <?php //get_template_part('templates/content', 'page'); ?>

	</section>

</section>
<?php endwhile; ?>
