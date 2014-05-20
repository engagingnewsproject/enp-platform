<?php
add_action('widgets_init', create_function('', 'return register_widget("adeodatus_social");'));

class adeodatus_social extends WP_Widget {
	private $socials = array('Rss', 'Github', 'Instagram', 'Tumblr', 'Flickr', 'Skype', 'Pinterest', 'Linkedin', 'Google-plus', 'Youtube-play', 'Dribbble', 'Facebook', 'Twitter');


	function adeodatus_social() {
		 parent::WP_Widget(false, $name = THEME_FULL_NAME.' Social Icons',array( 'description' => __( "Social page icons.", THEME_NAME )));	
	}

	function form($instance) {
		/* Set up some default widget settings. */
		$defaults = array(
			'mainTitle' => __('Follow us', THEME_NAME),
			'rss' => '',
			'github' => '',
			'instagram' => '',
			'tumblr' => '',
			'flickr' => '',
			'skype' => '',
			'pinterest' => '',
			'linkedin' => '',
			'google-plus' => '',
			'youtube-play' => '',
			'dribbble' => '',
			'facebook' => '',
			'twitter' => '',
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );

		$socials = $this->socials;
		$mainTitle = esc_attr($instance['mainTitle']);
		?>
			<p><label for="<?php echo $this->get_field_id('mainTitle'); ?>"><?php _e("Title:", THEME_NAME); ?> <input class="widefat" id="<?php echo $this->get_field_id('mainTitle'); ?>" name="<?php echo $this->get_field_name('mainTitle'); ?>" type="text" value="<?php echo $mainTitle; ?>" /></label></p>	

		
		<?php 
		foreach($socials as $social) {
			$title = esc_attr($instance[strtolower($social)]);
        ?>
            <p><label for="<?php echo $this->get_field_id(strtolower($social)); ?>"><?php _e("Account Url To ".$social.":", THEME_NAME); ?> <input class="widefat" id="<?php echo $this->get_field_id(strtolower($social)); ?>" name="<?php echo $this->get_field_name(strtolower($social)); ?>" type="text" value="<?php echo $title; ?>" /></label></p>	
        <?php 
		}
	}

	function update($new_instance, $old_instance) {
		$socials = $this->socials;
		$instance = $old_instance;
		$instance['mainTitle'] = strip_tags($new_instance['mainTitle']);
		
		foreach($socials as $social) {
			$instance[strtolower($social)] = strip_tags($new_instance[strtolower($social)]);
			
		}

		return $instance;
	}

	function widget($args, $instance) {
		extract( $args );
		$socials = $this->socials;
		$mainTitle = apply_filters('widget_title', $instance['mainTitle']);
        ?>
	<?php echo $before_widget; ?>
		<?php if($mainTitle) echo $before_title.$mainTitle.$after_title; ?>
                <ul class="social-icons">
					<?php
						foreach($socials as $social) {
							$link = apply_filters('widget_title', $instance[strtolower($social)]);
							if($link && $link!="") {
							$liClass=strtolower(str_replace("-", '', $social));
							if($liClass=="youtubeplay") {
								$liClass=str_replace("play", '', $liClass);	
							}
							
					?>
						<li class="<?php echo $liClass;?>">
							<a href="<?php echo $link;?>">
								<i class="fa fa-<?php echo strtolower($social);?>"></i>
							</a>
						</li>
							<?php } ?>
					<?php } ?>
                </ul>
	<?php echo $after_widget; ?>

        <?php
	}
}
?>