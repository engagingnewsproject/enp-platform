<?php
add_action('widgets_init', create_function('', 'return register_widget("DF_cats");'));

class DF_cats extends WP_Widget {
	function DF_cats() {
		 parent::WP_Widget(false, $name = THEME_FULL_NAME.' Categories',array( 'description' => __( "Colored post categories with post count", THEME_NAME )));	
	}

	function form($instance) {
		/* Set up some default widget settings. */
		$defaults = array(
			'title' => 'Categories',
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );

		 $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', THEME_NAME); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
			
        <?php 
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	function widget($args, $instance) {
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
 

        ?>
	<?php echo $before_widget; ?>
		<?php if($title) echo $before_title.$title.$after_title; ?>
			<ul class="widget-category">
				<?php
					$posttags = get_categories(array('type'=> 'post','taxonomy' => 'category'));
					$html ="";
					if ($posttags) {
						foreach($posttags as $tag) {
							$tag_link = get_category_link($tag->term_id);
							$titleColor = df_title_color($tag->term_id, "category", false);
												
							echo '<li>
									<a href="'.$tag_link.'" title="'.$tag->name.'" class="'.$tag->slug.'"  data-hovercolor="'.$titleColor.'">'.$tag->name.'<span>'.$tag->count.'</span></a>
									
								</li>';
							
							
						}
					}
								

				?>
			</ul>
	<?php echo $after_widget; ?>
        <?php
	}
}
?>