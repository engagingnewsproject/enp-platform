<!-- research cpt template when in series/index -->
<?php if ( true || is_post_type_archive() ) { ?>
<header class="entry-header">
    <h4 class="entry-title research-link"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
    <?php // get_template_part('templates/entry-meta'); ?>
  </header>
<article class="entry-content">
<?php the_excerpt(); ?>
</article>

<?php wp_link_pages(['before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']); ?>
<?php } else { ?>

<div class="entry-link research-link">
	<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
</div>
<?php } ?>
