<?php
add_action('widgets_init', create_function('', 'return register_widget("DF_text");'));

class DF_text extends WP_Widget {
	function DF_text() {
		 parent::WP_Widget(false, $name = THEME_FULL_NAME.' Text Widget',array( 'description' => __( "Simple Text widget, that support also HTML and shortcodes.", THEME_NAME )));	
	}

	function form($instance) {
		/* Set up some default widget settings. */
		$defaults = array(
			'title' => '',
			'text' => '',

		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );
		 $title = esc_attr($instance['title']);
		 $text = esc_attr($instance['text']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php printf ( __( 'Title:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

			
			<p><label for="<?php echo $this->get_field_id('text'); ?>"><?php  printf ( __( 'Text:' , THEME_NAME )); ?> <textarea style="height:200px;" class="widefat" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea></label></p>

		
        <?php 
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['text'] = strip_tags($new_instance['text']);

		return $instance;
	}

	function widget($args, $instance) {
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		$text = $instance['text'];
		
?>		
	<?php echo $before_widget; ?>
		<?php if($title) echo $before_title.$title.$after_title; ?>
			<div class="widget-text">
				 <p><?php echo wpautop(stripslashes(do_shortcode($text)));?></p>
			</div>
	<?php echo $after_widget; ?>
		
	
      <?php
	}
}
?>
