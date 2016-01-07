<?php use Roots\Sage\Titles; ?>

<div class="page-header" style="clear: both;">
	<div class="container">
			<p class="category"><?php the_taxonomies(); ?></p>
  			<h1 class="page-title"><?= Titles\title(); ?></h1>
  			<p class="byline"><?php the_author(); ?></p>
	</div>
</div>
