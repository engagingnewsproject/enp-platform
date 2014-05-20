<?php
add_action('widgets_init', create_function('', 'return register_widget("DF_latest_posts");'));

class DF_latest_posts extends WP_Widget {
	function DF_latest_posts() {
		 parent::WP_Widget(false, $name = THEME_FULL_NAME.' Latest Post Slider',array( 'description' => __( "Latest post slider widget with option to show also latest category posts.", THEME_NAME )));	
	}

	function form($instance) {
		/* Set up some default widget settings. */
		$defaults = array(
			'title' => __('Post Slider', THEME_NAME),
			'cat' => '',
			'count' => '3',
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );

		$cat = esc_attr($instance['cat']);
		$count = esc_attr($instance['count']);
		$title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php printf ( __( 'Title:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('count'); ?>"><?php printf ( __( 'Count:' , THEME_NAME ));?> <input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></label></p>
			<?php
			$args = array(
				'type'                     => 'post',
				'child_of'                 => 0,
				'orderby'                  => 'name',
				'order'                    => 'ASC',
				'hide_empty'               => 1,
				'hierarchical'             => 1,
				'taxonomy'                 => 'category');
				$args = get_categories( $args ); 
			?> 	
			<p><label for="<?php echo $this->get_field_id('cat'); ?>"><?php printf ( __( 'Category:' , THEME_NAME ));?> 
				<select name="<?php echo $this->get_field_name('cat'); ?>" style="width: 100%; clear: both; margin: 0;">
					<option value=""><?php _e("Latest Posts", THEME_NAME);?></option>
					<?php foreach($args as $ar) { ?>
						<option value="<?php echo $ar->term_id; ?>" <?php if($ar->term_id==$cat)  {echo 'selected="selected"';} ?>><?php echo $ar->cat_name; ?></option>
					<?php } ?>
				</select>
			</p>

		
        <?php 
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['count'] = strip_tags($new_instance['count']);
		$instance['cat'] = strip_tags($new_instance['cat']);

		return $instance;
	}

	function widget($args, $instance) {
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		$count = $instance['count'];
		$cat = $instance['cat'];
		$args=array(
			'cat'=> $cat,
			'posts_per_page'=> $count
		);
		
		$the_query = new WP_Query($args);
		$counter = 1;
		

		

?>		
	<?php echo $before_widget; ?>
		<?php if($title) echo $before_title.$title.$after_title; ?>
            <div class="widget-slider bx-loading">
                <ul style="height:200px; visibility:hidden">
                	<?php if ($the_query->have_posts()) : while ($the_query->have_posts()) : $the_query->the_post(); ?>
                    	<li>
                    		<div class="caption"><h2><a href="<?php the_permalink();?>"><?php the_title();?></a></h2></div>
                    		<a href="<?php the_permalink();?>">
                    			<?php echo df_image_html($the_query->post->ID,300,250); ?>
                    		</a>
                    	</li>
                    <?php endwhile; else: ?>
						<li><?php  _e( 'No posts where found' , THEME_NAME);?></li>
					<?php endif; ?>
                </ul>
            </div>

	
	<?php echo $after_widget; ?>
		
	
      <?php
	}
}
?>
