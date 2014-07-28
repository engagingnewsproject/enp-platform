<?php
add_action('widgets_init', create_function('', 'return register_widget("DF_ad125x125");'));

class DF_ad125x125 extends WP_Widget {
	function DF_ad125x125 () {
		 parent::WP_Widget(false, $name = THEME_FULL_NAME.' Custom 125x125 Ad');	
	}

	function form($instance) {
	
		/* Set up some default widget settings. */
		$defaults = array(
			'image1' => THEME_IMAGE_URL."ad-125x125.jpg",
			'link1' => 'http://www.different-themes.com',
			'image2' => THEME_IMAGE_URL."ad-125x125.jpg",
			'link2' => 'http://www.different-themes.com',
			'image3' => THEME_IMAGE_URL."ad-125x125.jpg",
			'link3' => 'http://www.different-themes.com',
			'image4' => THEME_IMAGE_URL."ad-125x125.jpg",
			'link4' => 'http://www.different-themes.com',
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );

		 $link1 = esc_attr($instance['link1']);
		 $image1 = esc_attr($instance['image1']);
		 $link2 = esc_attr($instance['link2']);
		 $image2 = esc_attr($instance['image2']);
		 $link3 = esc_attr($instance['link3']);
		 $image3 = esc_attr($instance['image3']);
		 $link4 = esc_attr($instance['link4']);
		 $image4 = esc_attr($instance['image4']);
        ?>
			

			<p><label for="<?php echo $this->get_field_id('link1'); ?>"><?php printf ( __( 'Link 1:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('link1'); ?>" name="<?php echo $this->get_field_name('link1'); ?>" type="text" value="<?php echo $link1; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('image1'); ?>"><?php printf ( __( 'Image Url 1:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('image1'); ?>" name="<?php echo $this->get_field_name('image1'); ?>" type="text" value="<?php echo $image1; ?>" /></label></p>
	
			<p><label for="<?php echo $this->get_field_id('link2'); ?>"><?php printf ( __( 'Link 2:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('link2'); ?>" name="<?php echo $this->get_field_name('link2'); ?>" type="text" value="<?php echo $link2; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('image2'); ?>"><?php printf ( __( 'Image Url 2:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('image2'); ?>" name="<?php echo $this->get_field_name('image2'); ?>" type="text" value="<?php echo $image2; ?>" /></label></p>
	
			<p><label for="<?php echo $this->get_field_id('link3'); ?>"><?php printf ( __( 'Link 3:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('link3'); ?>" name="<?php echo $this->get_field_name('link3'); ?>" type="text" value="<?php echo $link3; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('image3'); ?>"><?php printf ( __( 'Image Url 3:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('image3'); ?>" name="<?php echo $this->get_field_name('image3'); ?>" type="text" value="<?php echo $image3; ?>" /></label></p>
	
			<p><label for="<?php echo $this->get_field_id('link4'); ?>"><?php printf ( __( 'Link 4:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('link4'); ?>" name="<?php echo $this->get_field_name('link4'); ?>" type="text" value="<?php echo $link4; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('image4'); ?>"><?php printf ( __( 'Image Url 4:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('image4'); ?>" name="<?php echo $this->get_field_name('image4'); ?>" type="text" value="<?php echo $image4; ?>" /></label></p>

			
			
        <?php 
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['link1'] = strip_tags($new_instance['link1']);
		$instance['image1'] = strip_tags($new_instance['image1']);
		$instance['link2'] = strip_tags($new_instance['link2']);
		$instance['image2'] = strip_tags($new_instance['image2']);
		$instance['link3'] = strip_tags($new_instance['link3']);
		$instance['image3'] = strip_tags($new_instance['image3']);
		$instance['link4'] = strip_tags($new_instance['link4']);
		$instance['image4'] = strip_tags($new_instance['image4']);
		return $instance;
	}

	function widget($args, $instance) {
		extract( $args );
		 $title = apply_filters('widget_title', $instance['title']);
		

?>

	<?php echo $before_widget; ?>
		<ul class="banner-block-125">
			<?php 
				$i=1;
				$link = $instance['link'.$i];
				for($i=1; $i<=4; $i++) {
					
					$link = $instance['link'.$i];
					$image = $instance['image'.$i];
					if(!$image) { $image = THEME_IMAGE_URL."ad-125x125.jpg"; }
					if($link) {
			?>
				<li>
					<a href="<?php echo $link;?>" target="_blank"><img src="<?php echo $image;?>" alt="<?php _e("Banner",THEME_NAME);?>" title="<?php _e("Banner",THEME_NAME);?>"/></a>
				</li>
			<?php
					}
				}
			?>
		</ul>
		<div class="clear"></div>
	<?php echo $after_widget; ?>
		
	
      <?php
	}
}
?>