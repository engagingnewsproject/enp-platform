<?php use Roots\Sage\Titles; ?>

<div class="page-header" style="clear: both;">
	<div class="container">
			<p class="category"><?php
			echo get_the_term_list( get_the_ID(), 'research-categories', '', ', ', '' );
			//the_taxonomies(array('template' => __( '<span style="display: none">%s</span> %l' )));
			?></p>
  			<h1 class="page-title"><?= Titles\title(); ?></h1>
  			<?php if( is_single() ) : ?><p class="byline author"><?php the_author(); ?></p><?php endif; ?>
	</div>
</div>
