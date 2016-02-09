<article <?php post_class(); ?>>
  <div class="row">
    <?php if( has_post_thumbnail() ) : ?>
      <div class="col-sm-4">
        <figure><?php the_post_thumbnail(); ?></figure>
      </div>
      <div class="col-sm-8">
    <?php else : ?><div class="col-md-12"> <?php endif; ?>
      <header>
        <h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <?php get_template_part('templates/entry-meta'); ?>
      </header>
      <div class="entry-summary">
        <?php the_excerpt(); ?>
      </div>
    </div>
  </div>
</article>
