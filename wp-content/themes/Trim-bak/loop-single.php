<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
	<?php if (get_option('trim_integration_single_top') <> '' && get_option('trim_integrate_singletop_enable') == 'on') echo (get_option('trim_integration_single_top')); ?>

	<article class="entry post clearfix">
		<?php if ( 'on' == get_option('trim_show_date_icon_single') ) { ?>
			<span class="post-meta"><?php echo get_the_time( 'D' ); ?><span><?php echo get_the_time( 'd' ); ?></span></span>
		<?php } ?>

		<h1 class="main_title"><?php the_title(); ?></h1>

		<?php
			$index_postinfo = get_option('trim_postinfo2');
			if ( $index_postinfo ){
				echo '<p class="meta">';
				et_postinfo_meta( $index_postinfo, get_option('trim_date_format'), esc_html__('0 comments','Trim'), esc_html__('1 comment','Trim'), '% ' . esc_html__('comments','Trim') );
				echo '</p>';
			}
		?>

		<div class="post-content clearfix">
			<?php
				$thumb = '';
				$width = (int) apply_filters('et_image_width',481);
				$height = (int) apply_filters('et_image_height',230);
				$classtext = '';
				$titletext = get_the_title();
				$thumbnail = get_thumbnail($width,$height,$classtext,$titletext,$titletext,false,'Entry');
				$thumb = $thumbnail["thumb"];
			?>
			<?php if ( '' != $thumb && 'on' == get_option('trim_thumbnails') ) { ?>
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

	<?php if (get_option('trim_integration_single_bottom') <> '' && get_option('trim_integrate_singlebottom_enable') == 'on') echo(get_option('trim_integration_single_bottom')); ?>

	<?php
		if ( get_option('trim_468_enable') == 'on' ){
			if ( get_option('trim_468_adsense') <> '' ) echo( get_option('trim_468_adsense') );
			else { ?>
			   <a href="<?php echo esc_url(get_option('trim_468_url')); ?>"><img src="<?php echo esc_attr(get_option('trim_468_image')); ?>" alt="468 ad" class="foursixeight" /></a>
	<?php 	}
		}
	?>

	<?php
		if ( 'on' == get_option('trim_show_postcomments') ) comments_template('', true);
	?>
<?php endwhile; // end of the loop. ?>