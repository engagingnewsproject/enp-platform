<!-- research cpt template when in series/index -->
<?php if ( is_post_type_archive() ) { ?>
<header class="entry-header">
    <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <?php get_template_part('templates/entry-meta'); ?>
  </header>
<article class="entry-content">
<?php the_excerpt(); ?>
</article>

<?php wp_link_pages(['before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']); ?>
<?php } else { ?>

<header class="entry-link research-link">
	<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
</header>
<?php } ?>
