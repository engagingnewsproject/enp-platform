<?php
/**
 * Template Name: Homepage Template v2
 */
?>
<?php while (have_posts()) : the_post(); ?>
<section class="home-layout">
	<section id="featured-content" class="container">
		<div class="row">
			<?php $featured = get_field('featured_post'); ?>
				<figure class="col-sm-6 featured-image">
					<?php echo get_the_post_thumbnail( $featured->ID, 'featured-post' ); ?>
				</figure>
				<div class="col-sm-6 featured-content">
				<div class="category"><?php echo get_the_term_list( $featured->ID, 'research-categories', '', ', ', '' ); ?></div>
				<h1 class="headline"><a href="<?php echo get_the_permalink($featured->ID); ?>"><?php echo $featured->post_title; ?></a></h1>
				<div class="entry-summary">
          <p><?php echo $featured->post_excerpt; ?></p>
        </div>
				<p><a href="<?php echo get_the_permalink($featured->ID); ?>">Read more -></a></p>
			</div>
		</div>
	</section> <!-- END .featured-content -->
	<section class="enp-related-research">
		<div class="container">
			<!-- related research widget -->
			<?php dynamic_sidebar('sidebar-home'); ?>
		</div>
	</section>
	<section class="enp-latest">
		<div class="container widget">
			<h3>Latest</h3>
			<div class="row">
			<?php
				$recent_posts = wp_get_recent_posts(array('numberposts'=>3, 'post_status' => 'publish'));
				foreach( $recent_posts as $recent ){ ?>
					<div class="col-sm-4 clearfix"><figure><?php echo get_the_post_thumbnail( $recent["ID"], 'thumbnail' ); ?></figure>
					<a href="<?= get_permalink($recent["ID"]) ?>"><?= $recent["post_title"] ?></a><br>
					<time><?= get_the_date('', $recent["ID"])?></time>
				</div>
				<?php }
			?>
			</div>
		</div>
	</section>
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
