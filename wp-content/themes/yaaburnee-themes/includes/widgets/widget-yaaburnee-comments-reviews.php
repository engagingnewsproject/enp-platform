<?php
add_action('widgets_init', create_function('', 'return register_widget("DF_triple_box");'));

class DF_triple_box extends WP_Widget {
	function DF_triple_box() {
		 parent::WP_Widget(false, $name = THEME_FULL_NAME.' Recent Comments & Reviews',array( 'description' => __( "Recent comments and reviews widget with tab switcher.", THEME_NAME )));	
	}

	function form($instance) {
		/* Set up some default widget settings. */
		$defaults = array(
			'count' => '3',
			'comentcount' => '3',
			'showimage' => 'yes',
			'showimagecomments' => 'no',
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );

		 $count = esc_attr($instance['count']);
		 $comentcount = esc_attr($instance['comentcount']);
		 $showimage = esc_attr($instance['showimage']);
		 $showimagecomments = esc_attr($instance['showimagecomments']);

        ?>
          	<p><label for="<?php echo $this->get_field_id('count'); ?>"><?php printf ( __( 'Post count:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('showimage'); ?>"><?php printf ( __( 'Show Review Thumbnail:' , THEME_NAME ));?> 
				<select style="width: 100%; clear: both; margin: 0;" id="<?php echo $this->get_field_id('showimage'); ?>" name="<?php echo $this->get_field_name('showimage'); ?>">
					<option value="yes"<?php if($showimage=="yes") echo ' selected="selected"';?>><?php _e("Yes", THEME_NAME);?></option>
					<option value="no"<?php if($showimage=="no") echo ' selected="selected"';?>><?php _e("No", THEME_NAME);?></option>
				</select>
			</p>
          	<p><label for="<?php echo $this->get_field_id('comentcount'); ?>"><?php printf ( __( 'Comment count:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('comentcount'); ?>" name="<?php echo $this->get_field_name('comentcount'); ?>" type="text" value="<?php echo $comentcount; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('showimagecomments'); ?>"><?php printf ( __( 'Show Comment Avatar:' , THEME_NAME ));?> 
				<select style="width: 100%; clear: both; margin: 0;" id="<?php echo $this->get_field_id('showimagecomments'); ?>" name="<?php echo $this->get_field_name('showimagecomments'); ?>">
					<option value="yes"<?php if($showimagecomments=="yes") echo ' selected="selected"';?>><?php _e("Yes", THEME_NAME);?></option>
					<option value="no"<?php if($showimagecomments=="no") echo ' selected="selected"';?>><?php _e("No", THEME_NAME);?></option>
				</select>
			</p>
        <?php 
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['count'] = strip_tags($new_instance['count']);
		$instance['showimagecomments'] = strip_tags($new_instance['showimagecomments']);
		$instance['showimage'] = strip_tags($new_instance['showimage']);
		$instance['comentcount'] = strip_tags($new_instance['comentcount']);
		return $instance;
	}

	function widget($args, $instance) {
		extract( $args );
		$count = $instance['count'];
		$comentcount = $instance['comentcount'];
		$showImageComments = $instance['showimagecomments'];
		$showImage = $instance['showimage'];
		
		if(!$comentcount) $comentcount = 3;
		if(!$count) $count = 3;

		
		$blogID = get_option('page_for_posts');
        ?>
			
		<?php echo $before_widget; ?>
			<div class="tabs">
					<div class="tab-container">
						<ul class="tabs-list">
							<li class="tab">
								<a href="#comments-1">
									<?php _e("Comments", THEME_NAME);?>
								</a>
							</li>
							<li class="tab">
								<a href="#latest-2">
									<?php _e("Reviews", THEME_NAME);?>
								</a>
							</li>
						</ul>
									
						<div id="comments-1">
							<ul class="latest-comments">
								<?php
									$args =	array(
										'status' => 'approve', 
										'order' => 'DESC',
										'number' => $comentcount
									);	
													
									$comments = get_comments($args);
									$totalCount = count($comments);
									$counter = 1;
												
									foreach($comments as $comment) {
									if($comment->user_id && $comment->user_id!="0") {
										$authorName = get_the_author_meta('display_name',$comment->user_id );
									} else {
										$authorName = $comment->comment_author;
									}							 
								?>
                                    <li class="small-thumb-post">
					                	<?php if($showImageComments=="yes") { ?>
						                    <div class="cont-img">
						                        <a href="<?php echo get_comment_link($comment);?>">
						                        	<img src="<?php echo get_avatar_url(get_avatar( $comment, 60));?>" alt="<?php echo $authorName; ?>" style="width: auto;"/>
						                        </a>
						                    </div>
					                    <?php } ?>
                                        <div class="description">
                                            <div class="entry-meta">
                                                <span class="post-author"><a href="<?php echo get_comment_link($comment);?>"><?php echo $authorName; ?></a></span>
                                                <span class="post-date"><?php comment_date("F d, Y", $comment ); ?> </span>
                                            </div>
                                            <h2><a href="<?php echo get_comment_link($comment);?>"><?php echo WordLimiter(get_comment_excerpt($comment->comment_ID),10);?></a></h2>
                                        </div>
                                    </li>
									<?php $counter++; ?>
								<?php } ?>
							</ul>		
						</div>		

						<div id="latest-2">
							<ul class="widget-popular-posts">
								<?php
									$args=array(
										'posts_per_page' => $count,
										'order' => 'DESC',
										'post_type'=> 'post',
										'ignore_sticky_posts' => "1",
										'meta_query' => array(
										    array(
										        'key' => THEME_NAME.'_rating',
										        'value'   => '0',
										        'compare' => '>='
										    )
										)
									);
									$the_query = new WP_Query($args);
									$myposts = get_posts( $args );	
									$totalCount = $the_query->post_count;
									$counter = 1;
								?>
								<?php if ($the_query->have_posts()) : while ($the_query->have_posts()) : $the_query->the_post(); ?>
									<?php 

									    //get post rating
									    $rating = get_post_meta( $the_query->post->ID, THEME_NAME."_rating", true );
									?>	
                                    <!-- Post -->
                                    <li class="small-thumb-post">
					                	<?php if($showImage=="yes") { ?>
						                    <div class="cont-img">
						                        <a href="<?php the_permalink();?>">
						                        	<?php echo df_image_html($the_query->post->ID,80,80); ?>
						                        </a>
						                    </div>
					                    <?php } ?>
                                        <div class="description">
                                            <div class="entry-meta">
                                                <span class="post-date"><?php echo the_time("F d, Y"); ?></span>
                                            </div>
                                            <h2><a href="<?php the_permalink();?>"><?php the_title();?></a></h2>
								            <?php
								                if($rating) {
								                    df_rating_html($rating);
								                } 
								             ?>
                                        </div>
                                    </li>
									<?php $counter++; ?>
								<?php endwhile; else: ?>
									<p><?php _e( 'No posts where found' , THEME_NAME );?></p>
								<?php endif; ?>	

							</ul>
						</div>

					</div>
				</div>
		
					<?php echo $after_widget; ?>
        <?php
	}
}
?>