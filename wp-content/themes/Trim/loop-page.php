<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
	<article class="entry post clearfix">
		<h1 class="main_title"><?php the_title(); ?></h1>

		<div class="post-content clearfix">
			<?php
				$thumb = '';
				$width = (int) apply_filters('et_image_width',553);
				$height = (int) apply_filters('et_image_height',240);
				$classtext = '';
				$titletext = get_the_title();
				$thumbnail = get_thumbnail($width,$height,$classtext,$titletext,$titletext,false,'Page');
				$thumb = $thumbnail["thumb"];
			?>
			<?php if ( '' != $thumb && 'on' == get_option('trim_page_thumbnails') ) { ?>
				<div class="featured_box">
					<a href="<?php the_permalink(); ?>">
						<?php print_thumbnail($thumb, $thumbnail["use_timthumb"], $titletext, $width, $height, $classtext); ?>
					</a>
				</div> 	<!-- end .featured_box -->
			<?php } ?>

			<div class="entry_content">
				<?php the_content(); ?>
				<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Trim').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
				<?php edit_post_link(esc_attr__('Edit this page','Trim')); ?>
			</div> <!-- end .entry_content -->
		</div> <!-- end .post-content -->
	</article> <!-- end .post -->
<?php endwhile; // end of the loop. ?>