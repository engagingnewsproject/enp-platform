<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
$ok_url=plugins_url( '../images/rsz_11button_ok.png',__FILE__);
$cancel_url=plugins_url( '../images/rsz_1onebit_33.png',__FILE__);?>
<style>input.user-validator-valid {
background-color: #CFFAD7;
background-image:url('<?php echo $ok_url?>');
background-position:right;
background-repeat:no-repeat;
color: #2C823C;
font-weight:bold;
}
input.user-validator-invalid {
background-color: #FCCDC5;
background-image:url('<?php echo $cancel_url?>');
background-position:right;
color: #660011;
background-repeat:no-repeat;
font-weight:bold;
}
</style>
<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Widget Title: ', 'viva-twitter-feed' ); ?><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($widget_title); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('name'); ?>"><?php _e( 'Twitter User Name: ', 'viva-twitter-feed' ); ?><input class="widefat twitter_user_name" id="<?php echo $this->get_field_id('name'); ?>" name="<?php echo $this->get_field_name('name'); ?>" type="text" value="<?php echo esc_attr($name); ?>" /></label>
			<span class="widefat user-validator"><?php _e( 'Start entering your user name: ', 'viva-twitter-feed' ); ?></span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('store_time'); ?>"><?php _e( 'Tweets Cache Time (in minutes): ', 'viva-twitter-feed' ); ?><input class="widefat" id="<?php echo $this->get_field_id('store_time'); ?>" name="<?php echo $this->get_field_name('store_time'); ?>" type="text" value="<?php echo esc_attr($timeto_store); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo  $this->get_field_id('tweets_cnt'); ?>"><?php _e( 'Number of Tweets to Fetch: ', 'viva-twitter-feed' ); ?><input class="widefat" id="<?php echo $this->get_field_id('tweets_cnt'); ?>" name="<?php echo $this->get_field_name('tweets_cnt'); ?>" type="text" value="<?php echo esc_attr($tweets_count); ?>" /></label>
		</p>
		
<h4  style="width:100%; text-align:center;"><?php _e( 'Twitter API Settings', 'viva-twitter-feed' ); ?></h4>
			<div style="padding:10px;">
				<p>
					<label for="<?php echo $this->get_field_id('consumerKey'); ?>"><?php _e( 'API key: ', 'viva-twitter-feed' ); ?><input class="widefat" id="<?php echo $this->get_field_id('consumerKey'); ?>" name="<?php echo $this->get_field_name('consumerKey'); ?>" type="text" value="<?php echo esc_attr($consumerKey); ?>" /></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('consumerSecret'); ?>"><?php _e( 'API secret: ', 'viva-twitter-feed' ); ?><input class="widefat" id="<?php echo $this->get_field_id('consumerSecret'); ?>" name="<?php echo $this->get_field_name('consumerSecret'); ?>" type="text" value="<?php echo esc_attr($consumerSecret); ?>" /></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('accessToken'); ?>"><?php _e( 'Access Token: ', 'viva-twitter-feed' ); ?><input class="widefat" id="<?php echo $this->get_field_id('accessToken'); ?>" name="<?php echo $this->get_field_name('accessToken'); ?>" type="text" value="<?php echo esc_attr($accessToken); ?>" /></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('accessTokenSecret'); ?>"><?php _e( 'Access Token Secret: ', 'viva-twitter-feed' ); ?><input class="widefat" id="<?php echo $this->get_field_id('accessTokenSecret'); ?>" name="<?php echo $this->get_field_name('accessTokenSecret'); ?>" type="text" value="<?php echo esc_attr($accessTokenSecret); ?>" /></label>
				</p>
				
			</div>
			<h4  style="width:100%; text-align:center;"><?php _e( 'Advanced Options', 'viva-twitter-feed' ); ?></h4>
			<div style="padding:10px;">
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['twitterIntents']) && ($instance['twitterIntents'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'twitterIntents' ); ?>" name="<?php echo $this->get_field_name( 'twitterIntents' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'twitterIntents' ); ?>"><?php _e( 'Show Twitter Intents', 'viva-twitter-feed' ); ?></label>
				</p>
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['twitterIntentsText']) && ($instance['twitterIntentsText'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'twitterIntentsText' ); ?>" name="<?php echo $this->get_field_name( 'twitterIntentsText' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'twitterIntentsText' ); ?>"><?php _e( 'Hide Twitter Intents Text', 'viva-twitter-feed' ); ?></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('intentColor'); ?>"><?php _e( 'Colour for Intent icons: ', 'viva-twitter-feed' ); ?></label><input class="intentColor widefat" id="<?php echo $this->get_field_id('intentColor'); ?>" name="<?php echo $this->get_field_name('intentColor'); ?>" type="text" value="<?php echo esc_attr($color_intents); ?>" />
					<div id="colorpicker"></div>
				</p>
                                <p>
                                  <label for="<?php echo $this->get_field_id( 'slide_style' ); ?>"><?php _e( 'Style:', 'viva-twitter-feed' ); ?></label>
					<select name="<?php echo $this->get_field_name( 'slide_style' ); ?>" id="<?php echo $this->get_field_id( 'slide_style' ); ?>" style="width: 100%;">
					    <option value="list" <?php if(isset($slide_style) && $slide_style=='list'){echo 'selected';} ?>><?php _e( 'List', 'viva-twitter-feed' ); ?></option>
					    <option value="slider" <?php if(isset($slide_style) && $slide_style=='slider'){echo 'selected';} ?>><?php _e( 'Slider', 'viva-twitter-feed' ); ?></option>
					</select>       
                                </p> 
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( ( isset( $instance['border_rad']) && ($instance['showAvatar'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'border_rad' ); ?>" name="<?php echo $this->get_field_name( 'border_rad' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'border_rad' ); ?>"><?php _e( 'Circular Avatar image', 'viva-twitter-feed' ); ?></label>
				</p>  
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( ( isset( $instance['showAvatar']) && ($instance['showAvatar'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'showAvatar' ); ?>" name="<?php echo $this->get_field_name( 'showAvatar' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'showAvatar' ); ?>"><?php _e( 'Display avatar image', 'viva-twitter-feed' ); ?></label>
				</p>
				
				<p>
				    <input class="checkbox" type="checkbox" <?php checked( isset( $instance['replies_excl']), true ); ?> id="<?php echo $this->get_field_id( 'replies_excl' ); ?>" value="true" name="<?php echo $this->get_field_name( 'replies_excl' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'replies_excl' ); ?>">Exclude @replies</label>
				</p>
					<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['timeAgo']) && ($instance['timeAgo'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'timeAgo' ); ?>" name="<?php echo $this->get_field_name( 'timeAgo' ); ?>" value="true" />
				    <label for="<?php echo $this->get_field_id( 'timeAgo' ); ?>">Show "ago" after the time</label>
				</p>
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['timeRef']) && ($instance['timeRef'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'timeRef' ); ?>" name="<?php echo $this->get_field_name( 'timeRef' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'timeRef' ); ?>"><?php _e( 'Set Twitter like short time', 'viva-twitter-feed' ); ?></label>
				</p>	
				<p>
				    <input class="checkbox" type="checkbox" <?php checked( (isset( $instance['disp_scr_name']) && ($instance['disp_scr_name'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'disp_scr_name' ); ?>" name="<?php echo $this->get_field_name( 'disp_scr_name' ); ?>" value="true" />
				    <label for="<?php echo $this->get_field_id( 'disp_scr_name' ); ?>"><?php _e( 'Show Twitter Screen Name', 'viva-twitter-feed' ); ?></label>
				</p>
				<p>
				    <label for="<?php echo $this->get_field_id( 'tweet_border' ); ?>"><?php _e( 'Show Twitter Widget Border:', 'viva-twitter-feed' ); ?></label><br/>
					<select name="<?php echo $this->get_field_name( 'tweet_border' ); ?>" id="<?php echo $this->get_field_id( 'tweet_border' ); ?>" style="width: 100%;">
					<option value="true" <?php if(isset($tweet_border) && $tweet_border=='true'){echo 'selected';} ?>><?php _e( 'Yes', 'viva-twitter-feed' ); ?></option>
					<option value="false" <?php if(isset($tweet_border) && $tweet_border=='false'){echo 'selected';} ?>><?php _e( 'No', 'viva-twitter-feed' ); ?></option>
					</select>
				</p>
				<p>
				<label for="<?php echo $this->get_field_id( 'tweet_theme' ); ?>"><?php _e( 'Twitter Widget Theme:', 'viva-twitter-feed' ); ?></label><br/>
					<select name="<?php echo $this->get_field_name( 'tweet_theme' ); ?>" id="<?php echo $this->get_field_id( 'tweet_theme' ); ?>" style="width: 100%;">
					<option value="light" <?php if(isset($tweet_theme) && $tweet_theme=='light'){echo 'selected';} ?>><?php _e( 'Light', 'viva-twitter-feed' ); ?></option>
					<option value="dark" <?php if(isset($tweet_theme) && $tweet_theme=='dark'){echo 'selected';} ?>><?php _e( 'Dark', 'viva-twitter-feed' ); ?></option>
					</select>
				</p>
			</div>