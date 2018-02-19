<?php use Roots\Sage\Titles; ?>
<?php //$hero = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full' ); ?>

<div class="page-header" style="clear: both;">
	<div class="container">
			<?php if( is_single() ) : ?><p class="category"><?php
				echo get_the_term_list( get_the_ID(), 'research-categories', '', ', ', '' );
			//the_taxonomies(array('template' => __( '<span style="display: none">%s</span> %l' )));
			?></p><?php endif; ?>
  			<h1 class="page-title"><?= Titles\title(); ?></h1>
  			<?php if( is_single() ) : ?><p class="byline author"><?php the_author(); ?></p><?php endif; ?>
				<?php if( is_tax() ) : echo term_description(); endif; ?>
				<?php if( is_page('research') ) : the_content(); endif; ?>
	</div>
</div>
