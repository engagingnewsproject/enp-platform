<?php
/**
 * Template Name: Research Template
 */
?>
<div class="index-layout">
<?php while (have_posts()) : the_post(); ?>
  <?php //get_template_part('templates/entry', 'header'); ?>
  <?php get_template_part('templates/content', 'page'); ?>
<?php endwhile; ?>

<?php

// query for research papers

// categories

$args = array(
	'type'                     => 'research',
	'orderby'                  => 'name',
	'order'                    => 'ASC',
	'hide_empty'               => 1,
	'taxonomy'                 => 'research-categories',

);

$categories = get_categories( $args );

foreach( $categories as $cat ) : ?>
  <section class="research-section">
  <?php
  ?><!-- <pre><?php //print_r($cat); ?></pre> -->
  <header class="research-category">
    <figure class="category-icon">
      <?php echo wp_get_attachment_image( $cat->cat_icon['id'], 'thumbnail' ); ?>
    </figure>
    <div class="entry-content">
    <h5><?php echo $cat->cat_name; ?></h5>
    <p><?php echo $cat->category_description; ?></p>
  </div>
  </header>
  <?php
  $args = array(
  	'post_type' => 'research',
  	'post_status' => 'publish',
    'tax_query' => array(
		array(
			'taxonomy' => 'research-categories',
			'field'    => 'id',
			'terms'    => $cat->cat_ID,
		),
	),
  );

  $papers = new WP_Query( $args );

  ?>

  <?php while ($papers->have_posts()) : $papers->the_post(); ?>
    <?php //get_template_part('templates/entry', 'header'); ?>
    <?php get_template_part('templates/content', 'research'); ?>
  <?php endwhile; ?>
  </section>
  <?php wp_reset_postdata(); ?>
<?php endforeach; ?>

</div>
