<?php
add_action('widgets_init', create_function('', 'return register_widget("DF_latest_comments");'));

class DF_latest_comments extends WP_Widget {
	function DF_latest_comments() {
		 parent::WP_Widget(false, $name = THEME_FULL_NAME.' Latest Comments',array( 'description' => __( "Your site's most recent comments.", THEME_NAME )));	
	}

	function form($instance) {
		/* Set up some default widget settings. */
		$defaults = array(
			'title' => 'Latest Comments',
			'count' => '3',
			'showimagecomments' => '',

		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		 $count = esc_attr($instance['count']);
		 $title = esc_attr($instance['title']);
		 $showimagecomments = esc_attr($instance['showimagecomments']);
        ?>
        	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php printf ( __( 'Title:' , THEME_NAME )); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('count'); ?>"><?php printf ( __( 'Count:' , THEME_NAME ));?> <input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></label></p>
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
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['count'] = strip_tags($new_instance['count']);
		$instance['showimagecomments'] = strip_tags($new_instance['showimagecomments']);

		return $instance;
	}

	function widget($args, $instance) {
		extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		$count = $instance['count'];
		$showImageComments = $instance['showimagecomments'];

		$args =	array(
			'status' => 'approve', 
			'order' => 'DESC',
			'number' => $count
		);	
						
		$comments = get_comments($args);
		$totalCount = count($comments);
		$counter = 1;
							

		
?>		
	<?php echo $before_widget; ?>
		<?php if($title) echo $before_title.$title.$after_title; ?>
        <ul class="latest-comments">
        	<?php 
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
            <?php } ?>
        </ul>
	
<?php echo $after_widget; ?>
		
	
      <?php
	}
}
?>
