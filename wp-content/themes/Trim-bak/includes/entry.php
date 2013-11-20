<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	<article class="entry post clearfix">
		<?php if ( 'on' == get_option('trim_show_date_icon_index') ) { ?>
			<span class="post-meta"><?php echo get_the_time( 'D' ); ?><span><?php echo get_the_time( 'd' ); ?></span></span>
		<?php } ?>

		<h1 class="main_title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
		<?php
			$index_postinfo = get_option('trim_postinfo1');
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
			<?php if ( '' != $thumb && 'on' == get_option('trim_thumbnails_index') ) { ?>
				<div class="featured_box">
					<a href="<?php the_permalink(); ?>">
						<?php print_thumbnail($thumb, $thumbnail["use_timthumb"], $titletext, $width, $height, $classtext); ?>
					</a>
				</div> 	<!-- end .featured_box -->
			<?php } ?>

			<div class="entry_content">
				<?php if (get_option('trim_blog_style') == 'on') the_content(''); else { ?>
					<p><?php truncate_post(450); ?></p>
				<?php } ?>
			</div> <!-- end .entry_content -->
		</div>
		<a href="<?php the_permalink(); ?>" class="readmore"><?php esc_html_e('Read More', 'Trim'); ?></a>
	</article> 	<!-- end .post-->
<?php
endwhile;
	if (function_exists('wp_pagenavi')) { wp_pagenavi(); }
	else { get_template_part('includes/navigation','entry'); }
else:
	get_template_part('includes/no-results','entry');
endif; ?>