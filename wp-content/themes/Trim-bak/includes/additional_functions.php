<?php

/* Meta boxes */

function feather_settings(){
	add_meta_box("et_post_meta", "ET Settings", "feather_display_options", "page", "normal", "high");
	add_meta_box("et_post_meta", "ET Settings", "feather_display_options", "post", "normal", "high");
}
add_action("admin_init", "feather_settings");

function feather_display_options($callback_args) {
	global $post, $themename;

	$post_type = $callback_args->post_type;
	$temp_array = array();

	$temp_array = maybe_unserialize(get_post_meta($post->ID,'_et_trim_settings',true));

	$et_is_featured = isset( $temp_array['et_is_featured'] ) ? (bool) $temp_array['et_is_featured'] : false;
	$et_fs_variation = isset( $temp_array['et_fs_variation'] ) ? (int) $temp_array['et_fs_variation'] : 1;
	$et_fs_video = isset( $temp_array['et_fs_video'] ) ? $temp_array['et_fs_video'] : '';
	$et_fs_video_embed = isset( $temp_array['et_fs_video_embed'] ) ? $temp_array['et_fs_video_embed'] : '';
	$et_fs_title = isset( $temp_array['et_fs_title'] ) ? $temp_array['et_fs_title'] : '';
	$et_fs_description = isset( $temp_array['et_fs_description'] ) ? $temp_array['et_fs_description'] : '';
	$et_fs_bottom_title = isset( $temp_array['et_fs_bottom_title'] ) ? $temp_array['et_fs_bottom_title'] : '';
	$et_fs_bottom_description = isset( $temp_array['et_fs_bottom_description'] ) ? $temp_array['et_fs_bottom_description'] : '';
	$et_fs_link = isset( $temp_array['et_fs_link'] ) ? $temp_array['et_fs_link'] : ''; ?>

	<?php wp_nonce_field( basename( __FILE__ ), 'et_settings_nonce' ); ?>

	<div id="et_custom_settings" style="margin: 13px 0 17px 4px;">
		<label class="selectit" for="et_is_featured" style="font-weight: bold;">
			<input type="checkbox" name="et_is_featured" id="et_is_featured" value=""<?php checked( $et_is_featured ); ?> /> <?php echo esc_html( sprintf( __('This %s is Featured', $themename), $post_type ) ); ?></label><br/>

		<div id="et_settings_featured_options" style="margin-top: 12px;">

			<div class="et_fs_setting" style="display: none; margin: 13px 0 26px 4px;">
				<label for="et_fs_variation" style="color: #000; font-weight: bold;"> <?php esc_html_e('Featured Slider:',$themename); ?> </label>
				<select id="et_fs_variation" name="et_fs_variation">
					<option value="1"<?php selected( $et_fs_variation, 1 ); ?>><?php esc_html_e('Full Width Image',$themename); ?></option>
					<option value="2"<?php selected( $et_fs_variation, 2 ); ?>><?php esc_html_e('Video',$themename); ?></option>
					<option value="3"<?php selected( $et_fs_variation, 3 ); ?>><?php esc_html_e('Description Only',$themename); ?></option>
				</select>
				<br />
			</div>

			<div class="et_fs_setting" style="display: none; margin: 13px 0 26px 4px;">
				<label for="et_fs_video" style="color: #000; font-weight: bold;"> <?php esc_html_e('Video url:',$themename); ?> </label>
				<input type="text" style="width: 30em;" value="<?php echo esc_url($et_fs_video); ?>" id="et_fs_video" name="et_fs_video" size="67" />
				<br />
				<small style="position: relative; top: 8px;">ex: <code><?php echo htmlspecialchars("http://www.youtube.com/watch?v=WkuHbkaieZ4");?></code></small>
			</div>

			<div class="et_fs_setting" style="display: none; margin: 13px 0 26px 4px;">
				<label for="et_fs_video_embed" style="color: #000; font-weight: bold;"> <?php esc_html_e('Video Embed Code:',$themename); ?> </label>
				<br />
				<textarea id="et_fs_video_embed" name="et_fs_video_embed" cols="40" rows="1" tabindex="6" style="display: inline; position: relative; top: 5px; width: 490px; height: 125px;"><?php echo esc_textarea($et_fs_video_embed); ?></textarea>
				<br />
				<small style="position: relative; top: 8px;"><?php esc_html_e('Paste embed code if video link cannot be used',$themename); ?></small>
			</div>

			<div class="et_fs_setting" style="display: none; margin: 13px 0 26px 4px;">
				<label for="et_fs_title" style="color: #000; font-weight: bold;"> <?php esc_html_e('Custom Title:',$themename); ?> </label>
				<input type="text" style="width: 30em;" value="<?php echo esc_attr($et_fs_title); ?>" id="et_fs_title" name="et_fs_title" size="67" />
				<br />
				<small style="position: relative; top: 8px;">ex: <code><?php echo htmlspecialchars("Innovative design is our passion");?></code></small>
			</div>

			<div class="et_fs_setting" style="display: none; margin: 13px 0 26px 4px;">
				<label for="et_fs_description" style="color: #000; font-weight: bold;"> <?php esc_html_e('Description Text:',$themename); ?> </label>
				<input type="text" style="width: 30em;" value="<?php echo esc_attr($et_fs_description); ?>" id="et_fs_description" name="et_fs_description" size="67" />
				<br />
				<small style="position: relative; top: 8px;">ex: <code><?php echo htmlspecialchars("We work hard every day to bring your ideas to life");?></code></small>
			</div>

			<div class="et_fs_setting" style="display: none; margin: 13px 0 26px 4px;">
				<label for="et_fs_bottom_title" style="color: #000; font-weight: bold;"> <?php esc_html_e('Bottom Tab Custom Title:',$themename); ?> </label>
				<input type="text" style="width: 30em;" value="<?php echo esc_attr($et_fs_bottom_title); ?>" id="et_fs_bottom_title" name="et_fs_bottom_title" size="67" />
				<br />
				<small style="position: relative; top: 8px;">ex: <code><?php echo htmlspecialchars("Innovative design");?></code></small>
			</div>

			<div class="et_fs_setting" style="display: none; margin: 13px 0 26px 4px;">
				<label for="et_fs_bottom_description" style="color: #000; font-weight: bold;"> <?php esc_html_e('Bottom Tab Description Text:',$themename); ?> </label>
				<input type="text" style="width: 30em;" value="<?php echo esc_attr($et_fs_bottom_description); ?>" id="et_fs_bottom_description" name="et_fs_bottom_description" size="67" />
				<br />
				<small style="position: relative; top: 8px;">ex: <code><?php echo htmlspecialchars("We work hard every day");?></code></small>
			</div>

			<div class="et_fs_setting" style="display: none; margin: 13px 0 26px 4px;">
				<label for="et_fs_link" style="color: #000; font-weight: bold;"> <?php esc_html_e('Custom Link:',$themename); ?> </label>
				<input type="text" style="width: 30em;" value="<?php echo esc_url($et_fs_link); ?>" id="et_fs_link" name="et_fs_link" size="67" />
				<br />
			</div>

		</div> <!-- #et_settings_featured_options -->
	</div> <!-- #et_custom_settings -->

	<?php
}

add_action( 'save_post', 'feather_save_details', 10, 2 );
function feather_save_details( $post_id, $post ){
	global $pagenow;
	if ( 'post.php' != $pagenow ) return $post_id;

	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		return $post_id;

	$post_type = get_post_type_object( $post->post_type );
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	if ( !isset( $_POST['et_settings_nonce'] ) || !wp_verify_nonce( $_POST['et_settings_nonce'], basename( __FILE__ ) ) )
        return $post_id;

	$temp_array = array();

	if ( !isset($_POST['et_is_featured']) ) {
		if ( get_post_meta( $post_id, "_et_trim_settings", true ) ) $temp_array = maybe_unserialize( get_post_meta( $post_id, "_et_trim_settings", true ) );
		$temp_array['et_is_featured'] = 0;
		update_post_meta( $post_id, "_et_trim_settings", $temp_array );

		return $post_id;
	}

	$temp_array['et_is_featured'] = isset( $_POST["et_is_featured"] ) ? 1 : 0;
	$temp_array['et_fs_variation'] = isset($_POST["et_fs_variation"]) ? (int) $_POST["et_fs_variation"] : '';
	$temp_array['et_fs_video'] = isset($_POST["et_fs_video"]) ? esc_url_raw($_POST["et_fs_video"]) : '';
	$temp_array['et_fs_video_embed'] = isset($_POST["et_fs_video_embed"]) ? $_POST["et_fs_video_embed"] : '';
	$temp_array['et_fs_title'] = isset($_POST["et_fs_title"]) ? wp_kses( $_POST["et_fs_title"], array( 'span' => array(), 'strong' => array(), 'br' => array() ) ) : '';
	$temp_array['et_fs_description'] = isset($_POST["et_fs_description"]) ? wp_kses( $_POST["et_fs_description"], array( 'span' => array(), 'strong' => array(), 'br' => array() ) ) : '';
	$temp_array['et_fs_bottom_title'] = isset($_POST["et_fs_bottom_title"]) ? wp_kses( $_POST["et_fs_bottom_title"], array( 'span' => array(), 'strong' => array(), 'br' => array() ) ) : '';
	$temp_array['et_fs_bottom_description'] = isset($_POST["et_fs_bottom_description"]) ? wp_kses( $_POST["et_fs_bottom_description"], array( 'span' => array(), 'strong' => array(), 'br' => array() ) ) : '';
	$temp_array['et_fs_link'] = isset($_POST["et_fs_link"]) ? esc_url_raw($_POST["et_fs_link"]) : '';

	update_post_meta( $post_id, "_et_trim_settings", $temp_array );
}

add_action( 'admin_enqueue_scripts', 'feather_metabox_upload_scripts' );
function feather_metabox_upload_scripts( $hook_suffix ) {
	if ( 'post.php' == $hook_suffix || 'post-new.php' == $hook_suffix ) {
		wp_register_script('et-categories', get_template_directory_uri() . '/js/et-categories.js', array('jquery'));
		wp_enqueue_script('et-categories');
	}
}