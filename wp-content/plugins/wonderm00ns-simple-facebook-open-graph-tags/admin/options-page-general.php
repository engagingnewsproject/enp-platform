<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $webdados_fb;

?>
<div class="menu_containt_div" id="tabs-1">
	<p><?php _e( 'General settings that will apply to all tags types.', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></p>

	<?php do_action( 'fb_og_admin_settings_general_before' ); ?>

	<div class="postbox">
		<h3 class="hndle"><i class="dashicons-before dashicons-editor-alignleft"></i> <?php _e( 'Description settings', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?></h3>
		<div class="inside">
			<table class="form-table">
				<tbody>

					<tr>
						<th><?php _e( 'Description maximum length', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<input type="text" name="wonderm00n_open_graph_settings[fb_desc_chars]" id="fb_desc_chars" size="4" maxlength="3" value="<?php echo (intval($options['fb_desc_chars'])>0 ? intval($options['fb_desc_chars']) : '' ); ?>"/> <?php _e( 'characters', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( '0 (zero) or blank for no maximum length', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
							<?php if ( $webdados_fb->is_yoast_seo_active() ) { ?>
								<div class="fb_wpseoyoast_options">
									- <?php _e( 'Because Yoast SEO integration is active, this value may be overwritten', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
								</div>
							<?php } ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Homepage description', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<?php
							$hide_home_description=false;
							if ( get_option( 'show_on_front' )=='page' ) {
								$hide_home_description=true;
								_e( 'The description of your front page:', 'wonderm00ns-simple-facebook-open-graph-tags' );
								echo ' <a href="'.get_edit_post_link(get_option( 'page_on_front' )).'" target="_blank">'.get_the_title(get_option( 'page_on_front' )).'</a>';
							}; ?>
							<div<?php if ($hide_home_description) echo ' style="display: none;"'; ?>>
								<select name="wonderm00n_open_graph_settings[fb_desc_homepage]" id="fb_desc_homepage">
									<option value=""<?php if (trim($options['fb_desc_homepage'])=='' ) echo ' selected="selected"'; ?>><?php _e( 'Website tagline', 'wonderm00ns-simple-facebook-open-graph-tags' );?>&nbsp;</option>
									<option value="custom"<?php if (trim($options['fb_desc_homepage'])=='custom' ) echo ' selected="selected"'; ?>><?php _e( 'Custom text', 'wonderm00ns-simple-facebook-open-graph-tags' );?>&nbsp;</option>
								</select>
								<div class="fb_desc_homepage_customtext_div">
									<textarea name="wonderm00n_open_graph_settings[fb_desc_homepage_customtext]" id="fb_desc_homepage_customtext" rows="3" cols="50"><?php echo trim(esc_attr($options['fb_desc_homepage_customtext'])); ?></textarea>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<?php if ( $webdados_fb->is_yoast_seo_active() ) { ?>
								<div class="fb_wpseoyoast_options">
									- <?php _e( 'Because Yoast SEO integration is active, this value may be overwritten', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
								</div>
							<?php } ?>
							<?php if ( $webdados_fb->is_wpml_active() ) { ?>
								<div class="fb_desc_homepage_customtext_div">- 
								<?php printf(
									__( 'WPML users: Set the main language homepage description here, save changes and then go to <a href="%s" target="_blank">WPML &gt; String translation</a> to set it for other languages.', 'wonderm00ns-simple-facebook-open-graph-tags' ),
									'admin.php?page=wpml-string-translation/menu/string-translation.php&amp;context=wd-fb-og'
								); ?>
								</div>
							<?php } ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Default description', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<select name="wonderm00n_open_graph_settings[fb_desc_default_option]" id="fb_desc_default_option">
								<option value=""<?php if (trim($options['fb_desc_default_option'])=='' ) echo ' selected="selected"'; ?>><?php _e( 'Homepage description', 'wonderm00ns-simple-facebook-open-graph-tags' );?>&nbsp;</option>
								<option value="custom"<?php if (trim($options['fb_desc_default_option'])=='custom' ) echo ' selected="selected"'; ?>><?php _e( 'Custom text', 'wonderm00ns-simple-facebook-open-graph-tags' );?>&nbsp;</option>
							</select>
							<div class="fb_desc_default_customtext_div">
								<textarea name="wonderm00n_open_graph_settings[fb_desc_default]" id="fb_desc_default" rows="3" cols="50"><?php echo trim(esc_attr($options['fb_desc_default'])); ?></textarea>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'The default description to be used on any post / page / cpt / archive / search / ... that has a blank description', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							<?php if ( $webdados_fb->is_yoast_seo_active() ) { ?>
								<div class="fb_wpseoyoast_options">
									- <?php _e( 'Because Yoast SEO integration is active, this value may be overwritten', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
								</div>
							<?php } ?>
							<?php if ( $webdados_fb->is_wpml_active() ) { ?>
								<div class="fb_desc_default_customtext_div">- 
								<?php printf(
									__( 'WPML users: Set the main language default description here, save changes and then go to <a href="%s" target="_blank">WPML &gt; String translation</a> to set it for other languages.', 'wonderm00ns-simple-facebook-open-graph-tags' ),
									'admin.php?page=wpml-string-translation/menu/string-translation.php&amp;context=wd-fb-og'
								); ?>
								</div>
							<?php } ?>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>
	<div class="postbox">
		<h3 class="hndle"><i class="dashicons-before dashicons-format-image"></i> <?php _e( 'Image settings', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?></h3>
		<div class="inside">
			<table class="form-table">
				<tbody>

					<?php do_action( 'fb_og_admin_settings_general_image_before' ); ?>
					
					<tr>
						<th><?php _e( 'Default image', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<input type="text" name="wonderm00n_open_graph_settings[fb_image]" id="fb_image" size="45" value="<?php echo trim(esc_attr($options['fb_image'])); ?>" class="<?php echo( trim($options['fb_image'])=='' ? 'error' : '' ); ?>"/>
							<input id="fb_image_button" class="button" type="button" value="<?php echo esc_attr( __('Upload/Choose', 'wonderm00ns-simple-facebook-open-graph-tags')  ); ?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'URL (with http(s)://)', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
							<br/>
							- <?php printf( __( 'Recommended size: %dx%dpx', 'wonderm00ns-simple-facebook-open-graph-tags' ), $webdados_fb->img_w, $webdados_fb->img_h ); ?>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_image' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'On Post/Page, use image from', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<div>
								1) <input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_use_specific]" id="fb_image_use_specific" value="1" <?php echo (intval($options['fb_image_use_specific'])==1 ? ' checked="checked"' : '' ); ?>/>
								<small><?php _e( '"Open Graph Image" custom field on the post', 'wonderm00ns-simple-facebook-open-graph-tags' );?></small>
							</div>
							<div>
								2) <input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_use_featured]" id="fb_image_use_featured" value="1" <?php echo (intval($options['fb_image_use_featured'])==1 ? ' checked="checked"' : '' ); ?>/>
								<small><?php _e( 'Post/page featured image', 'wonderm00ns-simple-facebook-open-graph-tags' );?></small>
							</div>
							<div>
								3) <input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_use_content]" id="fb_image_use_content" value="1" <?php echo (intval($options['fb_image_use_content'])==1 ? ' checked="checked"' : '' ); ?>/>
								<small><?php _e( 'First image from the post/page content', 'wonderm00ns-simple-facebook-open-graph-tags' );?></small>
							</div>
							<div>
								4) <input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_use_media]" id="fb_image_use_media" value="1" <?php echo (intval($options['fb_image_use_media'])==1 ? ' checked="checked"' : '' ); ?>/>
								<small><?php _e( 'First image from the post/page media gallery', 'wonderm00ns-simple-facebook-open-graph-tags' );?></small>
							</div>
							<div>
								5) <input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_use_default]" id="fb_image_use_default" value="1" <?php echo (intval($options['fb_image_use_default'])==1 ? ' checked="checked"' : '' ); ?>/>
								<small><?php _e( 'Default image specified above', 'wonderm00ns-simple-facebook-open-graph-tags' );?></small>
							</div>
							<!-- mShots not working on Facebook - Needs more testing -->
							<!-- <div>
								&nbsp; &nbsp; &nbsp;or
							</div>
							<div>
								&nbsp; &nbsp; &nbsp;<input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_use_mshot]" id="fb_image_use_mshot" value="1" <?php echo (intval($options['fb_image_use_mshot'])==1 ? ' checked="checked"' : '' ); ?>/>
								<small><?php _e( 'Page screenshot (provided by WordPress.com mShots API)', 'wonderm00ns-simple-facebook-open-graph-tags' );?></small>
							</div> -->
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'On posts/pages the first image found, using the priority above, will be used. On the homepage, archives and other website sections the default image is always used.', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Overlay PNG logo', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<?php if ( extension_loaded( 'gd' ) ) { ?>
								<input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_overlay]" id="fb_image_overlay" value="1" <?php echo (intval($options['fb_image_overlay'])==1 ? ' checked="checked"' : '' ); ?>/>
							<?php } else { ?>
								<?php _e( 'No', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
								<input type="hidden" name="wonderm00n_open_graph_settings[fb_image_overlay]" id="fb_image_overlay" value="0"/>
							<?php } ?>
						</td>
					</tr>
					<?php if ( extension_loaded( 'gd' ) ) { ?>
						<tr>
							<td colspan="2" class="info">
								- <?php printf( __( 'The original image will be resized/cropped to %dx%dpx, or shrunk and centered, and the chosen PNG (that should also have this size) will be overlaid on it. It will only work for locally hosted images.', 'wonderm00ns-simple-facebook-open-graph-tags' ), $webdados_fb->img_w, $webdados_fb->img_h );?>
								<br/>
								- <?php printf( __( 'You can see an example of the end result <a href="%s" target="_blank">here</a>', '' ), 'https://www.flickr.com/photos/wonderm00n/29890263040/in/dateposted-public/' ); ?>
								<br/>
								- <?php printf( __( 'If you activate this option globally, you can disable it based on your conditions using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_image_overlay' ); ?>
							</td>
						</tr>
						
						<tr class="fb_image_overlay_options">
							<th><?php _e( 'PNG logo', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
							<td>
								<input type="text" name="wonderm00n_open_graph_settings[fb_image_overlay_image]" id="fb_image_overlay_image" size="45" value="<?php echo trim(esc_attr($options['fb_image_overlay_image'])); ?>"/>
								<input id="fb_image_overlay_button" class="button" type="button" value="<?php echo esc_attr( __('Upload/Choose', 'wonderm00ns-simple-facebook-open-graph-tags')  ); ?>" />
							</td>
						</tr>
						<tr class="fb_image_overlay_options">
							<td colspan="2" class="info">
								- <?php _e( 'URL (with http(s)://)', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
								<br/>
								- <?php printf( __( 'Size: %dx%dpx', 'wonderm00ns-simple-facebook-open-graph-tags' ), $webdados_fb->img_w, $webdados_fb->img_h ); ?>
							</td>
						</tr>
						
						<tr class="fb_image_overlay_options">
							<th><?php _e( 'Original image behavior', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
							<td>
								<select name="wonderm00n_open_graph_settings[fb_image_overlay_original_behavior]" id="fb_image_overlay_original_behavior">
									<option value=""<?php if (trim($options['fb_image_overlay_original_behavior'])=='' ) echo ' selected="selected"'; ?>><?php _e( 'Resize and crop (default)', 'wonderm00ns-simple-facebook-open-graph-tags' );?>&nbsp;</option>
									<option value="shrinkcenter"<?php if (trim($options['fb_image_overlay_original_behavior'])=='shrinkcenter' ) echo ' selected="selected"'; ?>><?php _e( 'Shrink and center', 'wonderm00ns-simple-facebook-open-graph-tags' );?>&nbsp;</option>
								</select>
							</td>
						</tr>
						<tr class="fb_image_overlay_options">
							<td colspan="2" class="info">
								- <?php _e( 'By default, the original image will be resized and cropped to fill the entire canvas, but you can also choose to shrink and center it over a white background', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							</td>
						</tr>
						
						<tr class="fb_image_overlay_options">
							<th><?php _e( 'No overlay for default', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
							<td>
								<input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_overlay_not_for_default]" id="fb_image_overlay_not_for_default" value="1" <?php echo (intval($options['fb_image_overlay_not_for_default'])==1 ? ' checked="checked"' : '' ); ?>/>
							</td>
						</tr>
						<tr class="fb_image_overlay_options">
							<td colspan="2" class="info">
								- <?php _e( 'Do not apply the overlay image to the default image set above', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							</td>
						</tr>

						<?php do_action( 'fb_og_admin_settings_general_image_overlayoptions' ); ?>
						
					<?php } else { ?>
						<tr>
							<td colspan="2" class="info">
								- <?php printf( __( 'You need the <a href="%s" target="_blank">PHP GD library</a> to use this feature. Please ask your hosting company to enable it.', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'http://php.net/manual/en/book.image.php' ); ?>
							</td>
						</tr>
					<?php } ?>
					
					<tr>
						<th><?php _e( 'Add image to RSS/RSS2 feeds', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_rss]" id="fb_image_rss" value="1" <?php echo (intval($options['fb_image_rss'])==1 ? ' checked="checked"' : '' ); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'For auto-posting apps like RSS Graffiti, twitterfeed, ...', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Force getimagesize on local file', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_adv_force_local]" id="fb_adv_force_local" value="1" <?php echo (intval($options['fb_adv_force_local'])==1 ? ' checked="checked"' : '' ); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'Deprecated - (might be moved to the PRO add-on in the future)', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							<br/>
							- <strong><?php _e( 'This is an advanced option: Don\'t mess with this unless you know what you\'re doing', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></strong>
							<br/>
							- <?php _e( 'Force getimagesize on local file even if allow_url_fopen=1', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							<br/>
							- <?php _e( 'May cause problems with some multisite configurations but fixes "HTTP request failed" errors', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Do not get image size', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_adv_disable_image_size]" id="fb_adv_disable_image_size" value="1" <?php echo (intval($options['fb_adv_disable_image_size'])==1 ? ' checked="checked"' : '' ); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'Deprecated - (might be moved to the PRO add-on in the future)', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							<br/>
							- <strong><?php _e( 'This is an advanced option: Don\'t mess with this unless you know what you\'re doing', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></strong>
							<br/>
							- <?php _e( 'You should only activate this option if you\'re getting fatal errors (white screen of death) and only keep it active if this options does solve those errors', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							<br/>
							- <?php _e( 'Should not be needed on version 2.2 and above', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							<br/>
							- <?php _e( 'This can render the "Add image to RSS/RSS2 feeds" and "Open Graph - Include Image Dimensions" options useless', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>

					<?php do_action( 'fb_og_admin_settings_general_image_after' ); ?>

				</tbody>
			</table>
		</div>
	</div>
	<div class="postbox">
		<h3 class="hndle"><i class="dashicons-before dashicons-admin-links"></i> <?php _e( 'URL settings', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?></h3>
		<div class="inside">
			<table class="form-table">
				<tbody>
					
					<tr>
						<th><?php _e( 'Add trailing slash at the end', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_url_add_trailing]" id="fb_url_add_trailing" value="1" <?php echo (intval($options['fb_url_add_trailing'])==1 ? ' checked="checked"' : '' ); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'If missing, a trailing slash will be added at the end', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							<br/>
							- <?php _e( 'Homepage example:', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>: <i><?php echo get_option( 'siteurl' ); ?><span id="fb_url_add_trailing_example">/</span></i>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>
	<div class="postbox">
		<h3 class="hndle"><i class="dashicons-before dashicons-admin-users"></i> <?php _e( 'Author settings', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?></h3>
		<div class="inside">
			<table class="form-table">
				<tbody>
					
					<tr>
						<th><?php _e( 'Hide Author on Pages', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_author_hide_on_pages]" id="fb_author_hide_on_pages" value="1" <?php echo (intval($options['fb_author_hide_on_pages'])==1 ? ' checked="checked"' : '' ); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'Hides all Author tags on Pages', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>
	<div class="postbox">
		<h3 class="hndle"><i class="dashicons-before dashicons-general"></i> <?php _e( 'Other settings', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?></h3>
		<div class="inside">
			<table class="form-table">
				<tbody>
					
					<tr>
						<th><?php _e( 'Keep data on uninstall', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_keep_data_uninstall]" id="fb_keep_data_uninstall" value="1" <?php echo (intval($options['fb_keep_data_uninstall'])==1 ? ' checked="checked"' : '' ); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'Keep the plugin settings on the database even if the plugin is uninstalled', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>
					
	<?php do_action( 'fb_og_admin_settings_general_after' ); ?>

</div>