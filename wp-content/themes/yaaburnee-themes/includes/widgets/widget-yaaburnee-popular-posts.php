<?php
add_action('widgets_init', create_function('', 'return register_widget("DF_popular_posts");'));

class DF_popular_posts extends WP_Widget {
	function DF_popular_posts() {
		 parent::WP_Widget(false, $name = THEME_FULL_NAME.' Popular Posts',array( 'description' => THEME_FULL_NAME.__( " most popular posts by view count.", THEME_NAME )));	
	}

	function form($instance) {
		/* Set up some default widget settings. */
		$defaults = array(
			'title' => __('Popular Posts', THEME_NAME),
			'count' => '3',
			'showimage' => 'yes',
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );

		 $title = esc_attr($instance['title']);
		 $count = esc_attr($instance['count']);
		 $showimage = esc_attr($instance['showimage']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php printf ( __( 'Title:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('count'); ?>"><?php printf ( __( 'Post count:' , THEME_NAME ));?> <input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('showimage'); ?>"><?php printf ( __( 'Show Thumbnail:' , THEME_NAME ));?> 
				<select style="width: 100%; clear: both; margin: 0;" id="<?php echo $this->get_field_id('showimage'); ?>" name="<?php echo $this->get_field_name('showimage'); ?>">
					<option value="yes"<?php if($showimage=="yes") echo ' selected="selected"';?>><?php _e("Yes", THEME_NAME);?></option>
					<option value="no"<?php if($showimage=="no") echo ' selected="selected"';?>><?php _e("No", THEME_NAME);?></option>
				</select>
			</p>
		
        <?php 
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['count'] = strip_tags($new_instance['count']);
		$instance['showimage'] = strip_tags($new_instance['showimage']);

		return $instance;
	}

	function widget($args, $instance) {
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		$count = $instance['count'];
		$showImage = $instance['showimage'];
		
		$args=array(
			'posts_per_page' => $count,
			'order' => 'DESC',
			'orderby'	=> 'meta_value_num',
			'meta_key'	=> THEME_NAME.'_post_views_count',
			'post_type'=> 'post'
		);

		$the_query = new WP_Query($args);
		$counter = 1;
		
		$totalCount = $the_query->found_posts;
		
		$blogID = get_option('page_for_posts');
		

?>		
	<?php echo $before_widget; ?>
		<?php if($title) echo $before_title.$title.$after_title; ?>
		<ul class="widget-popular-posts">
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
				<p><?php  _e( 'No posts where found' , THEME_NAME);?></p>
			<?php endif; ?>
        </ul>

	<?php echo $after_widget; ?>
		
	
      <?php
	}
}
?>
