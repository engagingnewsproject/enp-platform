<?php
	$featured_slider_class = '';
	if ( 'on' == get_option('trim_slider_auto') ) $featured_slider_class .= ' et_slider_auto et_slider_speed_' . get_option('trim_slider_autospeed');
	if ( 'slide' == get_option('trim_slider_effect') ) $featured_slider_class .= ' et_slider_effect_slide';
?>
<div id="featured" class="flexslider<?php echo esc_attr( $featured_slider_class ); ?>">
	<ul class="slides">
	<?php
		global $wp_embed;

		$arr = array();
		$i=1;

		$bottom_tabs_content = '';
		$featured_cat = get_option('trim_feat_cat');
		$featured_num = (int) get_option('trim_featured_num');

		if (get_option('trim_use_pages') == 'false') query_posts("posts_per_page=$featured_num&cat=".get_catId($featured_cat));
		else {
			global $pages_number;

			if (get_option('trim_feat_pages') <> '') $featured_num = count(get_option('trim_feat_pages'));
			else $featured_num = $pages_number;

			$et_featured_pages_args = array(
				'post_type' => 'page',
				'orderby' => 'menu_order',
				'order' => 'ASC',
				'posts_per_page' => (int) $featured_num,
			);

			if ( is_array( et_get_option( 'trim_feat_pages', '', 'page' ) ) )
				$et_featured_pages_args['post__in'] = (array) array_map( 'intval', et_get_option( 'trim_feat_pages', '', 'page' ) );

			query_posts( $et_featured_pages_args );
		}

		while (have_posts()) : the_post();
			$et_trim_settings = maybe_unserialize( get_post_meta($post->ID,'_et_trim_settings',true) );

			$variation = isset( $et_trim_settings['et_fs_variation'] ) ? (int) $et_trim_settings['et_fs_variation'] : 1;
			$link = isset( $et_trim_settings['et_fs_link'] ) && !empty($et_trim_settings['et_fs_link']) ? $et_trim_settings['et_fs_link'] : get_permalink();
			$title = isset( $et_trim_settings['et_fs_title'] ) && !empty($et_trim_settings['et_fs_title']) ? $et_trim_settings['et_fs_title'] : get_the_title();
			$description = isset( $et_trim_settings['et_fs_description'] ) && !empty($et_trim_settings['et_fs_description']) ? $et_trim_settings['et_fs_description'] : truncate_post(80,false);
			$bottom_title = isset( $et_trim_settings['et_fs_bottom_title'] ) && !empty($et_trim_settings['et_fs_bottom_title']) ? $et_trim_settings['et_fs_bottom_title'] : $title;
			$bottom_description = isset( $et_trim_settings['et_fs_bottom_description'] ) && !empty($et_trim_settings['et_fs_bottom_description']) ? $et_trim_settings['et_fs_bottom_description'] : $description;
			$video = isset( $et_trim_settings['et_fs_video'] ) && !empty($et_trim_settings['et_fs_video']) ? $et_trim_settings['et_fs_video'] : '';
			$video_manual_embed = isset( $et_trim_settings['et_fs_video_embed'] ) && !empty($et_trim_settings['et_fs_video_embed']) ? $et_trim_settings['et_fs_video_embed'] : '';

			$additional_class = '';
			$width = 870;
			$height = 230;

			switch ($variation) {
				case 1:
					$additional_class .= ' et_slide_image';
					break;
				case 2:
					$additional_class .= ' et_slide_video';
					$width = 377;
					$height = 230;
					break;
				case 3:
					$additional_class .= ' et_slide_text';
					break;
			}
	?>
			<li class="slide<?php echo esc_attr($additional_class); ?>">
				<div class="slide_wrap">
					<?php
						$featured_description = '<h2 class="title"><a href="' . esc_url($link) . '">' . $title . '</a></h2>'
							. '<p>' . $description . '</p>';
					?>
					<?php if ( 1 == $variation ) { ?>
						<div class="featured_box">
							<a href="<?php echo esc_url($link); ?>">
								<?php
									$thumbnail = get_thumbnail($width,$height,'',$title,$title,false,'Featured');
									$thumb = $thumbnail["thumb"];
									print_thumbnail($thumb, $thumbnail["use_timthumb"], $title, $width, $height, '');
								?>
							</a>
							<div class="et_image_description">
								<div class="et_inner_description">
									<?php echo $featured_description; ?>
								</div> <!-- end .et_inner_description -->
							</div>
						</div> <!-- end .featured_box -->
					<?php } elseif ( 2 == $variation ) { ?>
						<div class="featured_box">
							<div class="video_slide">
								<?php
									if ( $video <> '' ) {
										$video_embed = apply_filters( 'the_content', $wp_embed->shortcode( '', esc_url( $video ) ) );
										if ( $video_embed == '<a href="'.esc_url($video).'">'.esc_html($video).'</a>' ) $video_embed = $video_manual_embed;
									} else {
										$video_embed = $video_manual_embed;
									}

									$video_embed = preg_replace('/<embed /','<embed wmode="transparent" ',$video_embed);
									$video_embed = preg_replace('/<\/object>/','<param name="wmode" value="transparent" /></object>',$video_embed);
									$video_embed = preg_replace("/height=\"[0-9]*\"/", "height={$height}", $video_embed);
									$video_embed = preg_replace("/width=\"[0-9]*\"/", "width={$width}", $video_embed);

									echo $video_embed;
								?>
							</div> <!-- end .video_slide -->
						</div> <!-- end .featured_box -->

						<div class="featured_description">
							<?php echo $featured_description; ?>
						</div> <!-- end .featured_description -->
					<?php } else { ?>
						<div class="et_text_slide">
							<?php echo $featured_description; ?>
							<a href="<?php echo esc_url($link); ?>" class="readmore"><?php esc_html_e( 'Read More', 'Trim' ); ?></a>
						</div> <!-- end .et_text_slide -->
					<?php } ?>
				</div> <!-- end .slide_wrap -->
			</li>
	<?php
			$bottom_tabs_content .=
				'<li' . ( 1 == $i ? ' class="first active-slide"' : '' ) . ( 4 == $i ? ' class="last"' : '' ) . '>' .
					'<div class="et_slide_hover"></div>
					<div class="controller">'
						. '<h2>' . $bottom_title . '</h2>'
						. '<p>' . $bottom_description . '</p>' .
					'</div>' .
				'</li>';

			$i++;
		endwhile; wp_reset_query();
	?>
	</ul>
</div>	<!-- end #featured -->

<div id="content">
	<?php if ( $featured_num <= 4 ){ ?>
		<ul id="featured-controllers" class="clearfix">
			<?php echo $bottom_tabs_content; ?>
		</ul> <!-- end #featured-controllers -->
	<?php } else { ?>
		<ul id="featured_controls">
			<?php for ( $i = 1; $i <= $featured_num; $i++ ) { ?>
				<li<?php if ( 1 == $i ) echo ' class="active-slide"'; ?>><a href="#"><?php echo $i; ?></a></li>
			<?php } ?>
		</ul>
	<?php } ?>