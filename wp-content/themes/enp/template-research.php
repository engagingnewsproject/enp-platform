<?php
/**
 * Template Name: Research Template
 */
?>

<div class="row">


<div class="sidebar-nav">
  <ul class="page-menu research-menu">
    <?php  enp_research_categories_list(); ?>
  </ul>

<!-- <ul class="page-menu research-menu">
<?php foreach( $categories as $cat ) : ?>
  <li>
    <a href="<?php echo get_term_link($cat); ?>" class="icon-link">
    <?php echo wp_get_attachment_image( $cat->cat_icon['id'], 'icon' ); ?>
    <?php echo $cat->cat_name; ?>
    </a>
  </li>
<?php endforeach; ?>
</ul> -->


</div>

<div class="col-md-9">
<?php while (have_posts()) : the_post(); ?>
  <?php //get_template_part('templates/entry', 'header'); ?>
  <?php //get_template_part('templates/content', 'page'); ?>
<?php endwhile; ?>

  <section class="research-section">
  <?php
  ?><!-- <pre><?php print_r($cat); ?></pre> -->

  <?php
  $args = array(
  	'post_type' => 'research',
  	'post_status' => 'publish',
    /*'tax_query' => array(
		array(
			'taxonomy' => 'research-categories',
			'field'    => 'id',
			'terms'    => $cat->cat_ID,
		),
	),*/
  );

  $papers = new WP_Query( $args );

  ?>

  <?php while ($papers->have_posts()) : $papers->the_post(); ?>
    <?php //get_template_part('templates/entry', 'header'); ?>
    <?php get_template_part('templates/content', 'research'); ?>
  <?php endwhile; ?>
  </section>
  <?php wp_reset_postdata(); ?>


</div>

</div> <!-- end .row -->
