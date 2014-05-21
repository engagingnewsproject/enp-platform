<?php

/*

Template Name: Staff Template

*/	

?>
<?php get_header(); ?>
<!-- Container -->

<div class="container">
<div id="primary-fullwith">
  <!-- Blank page container -->
  <article <?php post_class(); ?>>
  <?php get_template_part(THEME_SINGLE."page-title"); ?>
  <div class="post-content">
    <?php query_posts( array( 'post_type'=> 'team', 'showposts' => '50' , 'order' => 'ASC') ); if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <div class="staff">
      <div class="loopstaff">
        <div class="imagebox"><img src="<?php the_field('member_image'); ?>" alt="<?php the_title();?>" width="300" height="300" class="alignnone size-medium wp-image-667" /></div>
        <div class="staffdesc">
          <h2><?php the_title();?></h2>
          <h3><?php the_field('member_designation'); ?></h3>
          <p><?php the_field('member_description'); ?></p>
        </div>
      </div>
      <?php endwhile; else: ?>
      <?php endif; ?>
    </div>
    </article>
    <?php wp_reset_query(); ?>
  </div>
</div>
<?php get_footer(); ?>
