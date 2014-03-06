<?php get_header(); ?>

<?php if ( 'on' != get_option('trim_blog_style') ) { ?>
	<?php if ( 'on' == get_option('trim_display_blurbs') ){ ?>
		<div id="services" class="clearfix">
			<?php
				for ($i=1; $i <= 3; $i++) {
					$service_query = new WP_Query('page_id=' . get_pageId(html_entity_decode(get_option('trim_home_page_'.$i))));
					while ( $service_query->have_posts() ) : $service_query->the_post();
						global $more; $more = 0;
						$page_title = apply_filters( 'the_title', get_the_title() ); ?>
						<div class="service<?php if ( 1 == $i ) echo ' first'; if ( 3 == $i ) echo ' last'; ?>">
							<h3><?php echo $page_title; ?></h3>

							<?php
								if ( ( $icon = get_post_meta($post->ID, 'Icon', true) ) && '' != $icon ) echo '<img class="icon" alt="' . esc_attr( $page_title ) . '" src="' . esc_attr( $icon ) . '" />';
							?>

							<?php the_content(''); ?>
						</div> <!-- end .service -->
				<?php endwhile; wp_reset_postdata(); ?>
			<?php } ?>
		</div> <!-- end #services -->
	<?php } ?>

	<?php if ( 'on' == get_option('trim_quote') ) { ?>
		<div id="quote">
			<p><?php echo esc_html( get_option('trim_quote_text') ); ?></p>
		</div> <!-- end #quote -->
	<?php } ?>


	<?php
		$display_recentwork_section = get_option('trim_display_recentwork_section');
		$display_fromblog_section = get_option('trim_display_fromblog_section');
	?>
	<?php if ( 'on' == $display_recentwork_section || 'on' == $display_fromblog_section ) { ?>
		<div id="home-sections" class="clearfix">
			<?php if ( 'on' == $display_recentwork_section ) { ?>
				<div id="recent-work">
					<h3><?php esc_html_e('Our Recent Work','Trim'); ?></h3>

					<div id="recent_work_thumbs" class="clearfix">
						<?php
							$recent_work_category_id = (int) get_catId( get_option('trim_home_recentwork_category') );

							$recentwork_args = apply_filters( 'et_recentwork_args', array(
								'posts_per_page' => (int) get_option('trim_recentwork_posts_num'),
								'cat' => $recent_work_category_id,
							) );
							$recentwork_query = new WP_Query( $recentwork_args );
						?>

						<?php if ( $recentwork_query->have_posts() ) : while ( $recentwork_query->have_posts() ) : $recentwork_query->the_post(); ?>
							<?php
								$width = (int) apply_filters( 'et_recentwork_width', 76 );
								$height = (int) apply_filters( 'et_recentwork_height', 76 );
								$titletext = get_the_title();
								$thumbnail = get_thumbnail($width,$height,'r-work-image',$titletext,$titletext,true,'Work');
								$thumb = $thumbnail["thumb"];
							?>
							<div class="thumb">
								<a href="<?php the_permalink(); ?>">
									<?php print_thumbnail($thumb, $thumbnail["use_timthumb"], $titletext, $width, $height, ''); ?>
								</a>
							</div> 	<!-- end .thumb -->
						<?php endwhile; endif; wp_reset_postdata(); ?>
					</div> <!-- end #recent_work_thumbs -->

					<a href="<?php echo esc_url( get_category_link( $recent_work_category_id ) ); ?>" class="readmore"><?php esc_html_e('Read More','Trim'); ?></a>
				</div> 	<!-- end #recent-work -->
			<?php } ?>

			<?php if ( 'on' == $display_fromblog_section ) { ?>
				<div id="from-the-blog">
					<h3><?php esc_html_e('From The Blog','Trim'); ?></h3>
					<?php
						$recent_fromblog_category_id = (int) get_catId( get_option('trim_home_recentblog_category') );

						if ( have_posts() ) : while ( have_posts() ) : the_post();
					?>
							<div class="blog-post">
								<p class="post_meta"><?php echo get_the_time( get_option('trim_date_format') ); ?></p>
								<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
								<div class="post_excerpt">
									<p><?php truncate_post( 60 ); ?></p>
								</div> <!-- end .post_excerpt -->
							</div> <!-- end .blog-post -->
						<?php endwhile; endif; ?>

					<a href="<?php echo esc_url( get_category_link( $recent_fromblog_category_id ) ); ?>" class="readmore"><?php esc_html_e('Read More','Trim'); ?></a>
				</div> <!-- end #from-the-blog -->
			<?php } ?>
		</div> 	<!-- end #home-sections -->
	<?php } ?>
<?php } else { ?>
	<div id="main_content" class="clearfix">
		<div id="left_area">
			<?php get_template_part('includes/entry', 'home'); ?>
		</div> <!-- end #left_area -->

		<?php get_sidebar(); ?>
	</div> <!-- end #main_content -->
<?php } ?>

<?php get_footer(); ?>