<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<div class="menu_containt_div" id="tabs-2">
	<p><?php _e( 'Open Graph tags used by Facebook, and other social networks, to render link share posts.', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></p>

	<?php do_action( 'fb_og_admin_settings_facebook_before' ); ?>

	<div class="postbox">
		<h3 class="hndle"><i class="dashicons-before dashicons-facebook-alt"></i> <?php _e( 'Facebook Open Graph Tags', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?></h3>
		<div class="inside">
			<table class="form-table">
				<tbody>
					
					<tr>
						<th><?php _e( 'Include Post/Page Title', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_title_show]" id="fb_title_show" value="1" <?php echo (intval($options['fb_title_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="og:title" content="..."/&gt;</i>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_title' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Site Name', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_sitename_show]" id="fb_sitename_show" value="1" <?php echo (intval($options['fb_sitename_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="og:site_name" content="..."/&gt;</i>
							<br/>
							- <?php _e( 'From Settings &gt; General &gt; Site Title', 'wonderm00ns-simple-facebook-open-graph-tags' );?> 
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include URL', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_url_show]" id="fb_url_show" value="1" <?php echo (intval($options['fb_url_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="og:url" content="..."/&gt;</i>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_url' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Description', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_desc_show]" id="fb_desc_show" value="1" <?php echo (intval($options['fb_desc_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="og:description" content="..."/&gt;</i>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_desc' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Image', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_show]" id="fb_image_show" value="1" <?php echo (intval($options['fb_image_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="og:image" content="..."/&gt;</i>
							<br/>
							- <?php printf( __('All images must have at least 200px on both dimensions in order to Facebook to load them at all. %dx%dpx for optimal results. Minimum of 600x315px is recommended.', 'wonderm00ns-simple-facebook-open-graph-tags' ), $webdados_fb->img_w, $webdados_fb->img_h );?>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_image' ); ?>
						</td>
					</tr>
					
					<tr class="fb_image_options">
						<th><?php _e( 'Include Image Dimensions', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_size_show]" id="fb_image_size_show" value="1" <?php echo (intval($options['fb_image_size_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr class="fb_image_options">
						<td colspan="2" class="info">
							<i>&lt;meta property="og:image:width" content="..."/&gt;</i> <?php _e('and', 'wonderm00ns-simple-facebook-open-graph-tags'); ?> <i>&lt;meta property="og:image:height" content="..."/&gt;</i>
							<br/>
							- <?php _e( 'Recommended only if Facebook is having problems loading the image when the post is shared for the first time, or else it adds extra unnecessary processing time', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Type', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_type_show]" id="fb_type_show" value="1" <?php echo (intval($options['fb_type_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="og:type" content="..."/&gt;</i>
							<br/>
							- <?php printf( __( 'Will be "%1$s" for posts and pages and "%2$s" or "%3$s" for the homepage', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'article', 'website', 'blog' ); ?>
							<br/>
							- <?php _e( 'Additional types may be used depending on 3rd party integrations', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_type' ); ?>
						</td>
					</tr>
					
					<tr class="fb_type_options">
						<th><?php _e( 'Homepage Type', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							website
							<input type="hidden" name="wonderm00n_open_graph_settings[fb_type_homepage]" value="website"/>
							<!--<select name="wonderm00n_open_graph_settings[fb_type_homepage]" id="fb_type_homepage">
								<option value="website"<?php if (trim($options['fb_type_homepage'])=='' || trim($options['fb_type_homepage'])=='website') echo ' selected="selected"'; ?>>website</option>
								<option value="blog"<?php if (trim($options['fb_type_homepage'])=='blog') echo ' selected="selected"'; ?>>blog</option>
							</select>-->
						</td>
					</tr>
					<tr class="fb_type_options">
						<td colspan="2" class="info">
							- <?php _e( 'Facebook does not support <i>blog</i> anymore, so we have to default to <i>website</i>', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Post/Page Author', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_author_show]" id="fb_author_show" value="1" <?php echo (intval($options['fb_author_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="article:author" content="..."/&gt;</i>
							<br/>
							- <?php _e( 'The user\'s Facebook URL must be filled in on his profile', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Published/Modified Dates', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_article_dates_show]" id="fb_article_dates_show" value="1" <?php echo (intval($options['fb_article_dates_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="article:published_time" content="..."/&gt;</i>, <i>&lt;meta property="article:modified_time" content="..."/&gt;</i> <?php _e('and', 'wonderm00ns-simple-facebook-open-graph-tags'); ?> <i>&lt;meta property="og:updated_time" content="..."/&gt;</i>
							<br/>
							- <?php _e( 'For posts only', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Article Sections', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_article_sections_show]" id="fb_article_sections_show" value="1" <?php echo (intval($options['fb_article_sections_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="article:section" content="..."/&gt;</i>
							<br/>
							- <?php _e( 'For posts only', 'wonderm00ns-simple-facebook-open-graph-tags' );?>, <?php _e('from the categories names', 'wonderm00ns-simple-facebook-open-graph-tags'); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Publisher', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_publisher_show]" id="fb_publisher_show" value="1" <?php echo (intval($options['fb_publisher_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="article:publisher" content="..."/&gt;</i>
							<br/>
							- <?php _e( 'The website\'s Facebook Page', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr class="fb_publisher_options">
						<th><?php _e( 'Website\'s Facebook Page', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="text" name="wonderm00n_open_graph_settings[fb_publisher]" id="fb_publisher" size="50" value="<?php echo trim(esc_attr($options['fb_publisher'])); ?>"/>
						</td>
					</tr>
					<tr class="fb_publisher_options">
						<td colspan="2" class="info">
							- <?php _e( 'Facebook Page URL (with https://)', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><a name="fblocale"></a><?php _e( 'Include Locale', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_locale_show]" id="fb_locale_show" value="1" <?php echo (intval($options['fb_locale_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="fb:locale" content="..."/&gt;</i>
							<br/>
							- <?php _e( 'The website\'s Facebook Page', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr class="fb_locale_options">
						<th><?php _e( 'Locale', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<?php
							$listLocales=false;
							$loadedOnline=false;
							$loadedOffline=false;
							//Online
							if (!empty($_GET['localeOnline'])) {
								if (intval($_GET['localeOnline'])==1) {
									if ($ch = curl_init('https://www.facebook.com/translations/FacebookLocales.xml')) {
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
										$fb_locales=curl_exec($ch);
										if (curl_errno($ch)) {
											//echo curl_error($ch);
										} else {
											$info = curl_getinfo($ch);
											if (intval($info['http_code'])==200) {
												//Save the file locally
												$fh = fopen(WP_PLUGIN_DIR . '/wonderm00ns-simple-facebook-open-graph-tags/includes/FacebookLocales.xml', 'w') or die("Can't open file");
												fwrite($fh, $fb_locales);
												fclose($fh);
												$listLocales=true;
												$loadedOnline=true;
											}
										}
										curl_close($ch);
									}
								}
							}
							//Offline
							if (!$listLocales) {
								if ($fb_locales=file_get_contents(WP_PLUGIN_DIR . '/wonderm00ns-simple-facebook-open-graph-tags/includes/FacebookLocales.xml')) {
									$listLocales=true;
									$loadedOffline=true;
								}
							}
							$locale = get_locale();
							$fb_locale = $webdados_fb->get_locale();
							$locale_txt = $locale;
							if ( $fb_locale!=$locale ) $locale_txt.=' -&gt; '.$fb_locale;
							?>
							<select name="wonderm00n_open_graph_settings[fb_locale]" id="fb_locale">
								<option value=""<?php if (trim($options['fb_locale'])=='') echo ' selected="selected"'; ?>><?php _e('WordPress current locale/language', 'wonderm00ns-simple-facebook-open-graph-tags'); ?> (<?php echo $locale_txt; ?>)</option>
								<?php
								//OK
								if ($listLocales) {
									$xml=simplexml_load_string($fb_locales);
									$json = json_encode($xml);
									$locales = json_decode($json,TRUE);
									if (is_array($locales['locale'])) {
										foreach ($locales['locale'] as $locale) {
											?><option value="<?php echo $locale['codes']['code']['standard']['representation']; ?>"<?php if (trim($options['fb_locale'])==trim($locale['codes']['code']['standard']['representation'])) echo ' selected="selected"'; ?>><?php echo $locale['englishName']; ?> (<?php echo $locale['codes']['code']['standard']['representation']; ?>)</option><?php
										}
									}
								}
								?>
							</select>
						</td>
					</tr>
					<tr class="fb_locale_options">
						<td colspan="2" class="info">
							- <?php
							if ($loadedOnline) {
								_e('List loaded from Facebook (online)', 'wonderm00ns-simple-facebook-open-graph-tags');
							} else {
								if ($loadedOffline) {
									_e('List loaded from local cache (offline)', 'wonderm00ns-simple-facebook-open-graph-tags'); ?>
									<!-- - <a href="?page=class-webdados-fb-open-graph-admin.php&amp;localeOnline=1" onClick="return(confirm('<?php _e('You\\\'l lose any changes you haven\\\'t saved. Are you sure?', 'wonderm00ns-simple-facebook-open-graph-tags'); ?>'));"><?php _e('Reload from Facebook', 'wonderm00ns-simple-facebook-open-graph-tags'); ?></a>-->
									<?php
								} else {
									_e('List not loaded', 'wonderm00ns-simple-facebook-open-graph-tags');
								}
							}
							?>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_locale' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Facebook Admin(s) ID', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_admin_id_show]" id="fb_admin_id_show" value="1" <?php echo (intval($options['fb_admin_id_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="fb:admins" content="..."/&gt;</i>
						</td>
					</tr>
					
					<tr class="fb_admin_id_options">
						<th><?php _e( 'Facebook Admin(s) ID', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="text" name="wonderm00n_open_graph_settings[fb_admin_id]" id="fb_admin_id" size="50" value="<?php echo trim(esc_attr($options['fb_admin_id'])); ?>"/>
						</td>
					</tr>
					<tr class="fb_admin_id_options">
						<td colspan="2" class="info">
							- <?php _e( 'Comma separated if more than one', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Facebook Platform App ID', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_app_id_show]" id="fb_app_id_show" value="1" <?php echo (intval($options['fb_app_id_show'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta property="fb:app_id" content="..."/&gt;</i>
						</td>
					</tr>
					
					<tr class="fb_app_id_options">
						<th><?php _e( 'Facebook Platform App ID', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="text" name="wonderm00n_open_graph_settings[fb_app_id]" id="fb_app_id" size="50" value="<?php echo trim(esc_attr($options['fb_app_id'])); ?>"/>
						</td>
					</tr>
					<tr class="fb_app_id_options">
						<td colspan="2" class="info">
							- <?php _e( 'From your Facebook Developers dashboard', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Declaration Method', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<select name="wonderm00n_open_graph_settings[fb_declaration_method]" id="fb_declaration_method">
								<option value="xmlns"<?php if (trim($options['fb_declaration_method'])=='' || trim($options['fb_declaration_method'])=='xmlns') echo ' selected="selected"'; ?>>xmlns</option>
								<option value="prefix"<?php if (trim($options['fb_declaration_method'])=='prefix') echo ' selected="selected"'; ?>>prefix</option>
							</select>
						</td>
					</tr>
					<tr class="fb_type_options">
						<td colspan="2" class="info">
							<i>&lt;html xmlns:og="http://ogp.me/ns#" xmlns:fb="http://ogp.me/ns/fb#"&gt;</i> <?php _e('or', 'wonderm00ns-simple-facebook-open-graph-tags'); ?> <i>&lt;html prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#"&gt;</i>
							<br/>
							- <?php _e( 'Prefix is recommended because it validates properly with the W3C validator, xmlns is the legacy method', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>
	<div class="postbox">
		<h3 class="hndle"><i class="dashicons-before dashicons-portfolio"></i> <?php _e( 'Facebook Open Graph Tags cache', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?></h3>
		<div class="inside">
			<table class="form-table">
				<tbody>
					
					<tr>
						<th><?php _e( 'Clear cache', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_adv_notify_fb]" id="fb_adv_notify_fb" value="1" <?php echo (intval($options['fb_adv_notify_fb'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <?php _e( 'Try to clear the Facebook Open Graph Tags cache when saving a post or page, so the link preview on Facebook is immediately updated', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr class="fb_adv_notify_fb_options">
						<th><?php _e( 'App ID', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="text" name="wonderm00n_open_graph_settings[fb_adv_notify_fb_app_id]" id="fb_adv_notify_fb_app_id" size="20" value="<?php echo trim(esc_attr($options['fb_adv_notify_fb_app_id'])); ?>"/>
						</td>
					</tr>
					
					<tr class="fb_adv_notify_fb_options">
						<th><?php _e( 'App Secret', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="text" name="wonderm00n_open_graph_settings[fb_adv_notify_fb_app_secret]" id="fb_adv_notify_fb_app_secret" size="39" value="<?php echo trim(esc_attr($options['fb_adv_notify_fb_app_secret'])); ?>"/>
						</td>
					</tr>
					<tr class="fb_adv_notify_fb_options">
						<td colspan="2" class="info">
							- <?php printf( __( 'Facebook no longer allows updating the cache anonymously, so you have to use a App ID and Secret to do it. <a href="%s" target="_blank">Read here</a> how to do it.', 'wonderm00ns-simple-facebook-open-graph-tags' ), esc_attr('https://www.webdados.pt/2017/12/successfully-update-facebook-cache-using-our-facebook-open-graph-plugin/'.$out_link_utm) ); ?>
							<br/>
							- <?php _e( 'If you are using the (now deprecated) <i>fb_og_update_cache_url</i> filter, this ID and Secret will NOT be used. You should stop using the filter and use these settings.', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
							<br/>
							- <?php _e( 'Please do not ask for support regarding this feature. Everything is explained in the blog post linked above.', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>
					
					<tr class="fb_adv_notify_fb_options">
						<th><?php _e( 'Suppress cache notices', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_adv_supress_fb_notice]" id="fb_adv_supress_fb_notice" value="1" <?php echo (intval($options['fb_adv_supress_fb_notice'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr class="fb_adv_notify_fb_options">
						<td colspan="2" class="info">
							- <?php _e( 'Sometimes we aren\'t able to update the cache and the post author will see a notice if this option is not checked', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>

	<?php do_action( 'fb_og_admin_settings_facebook_after' ); ?>

</div>