<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<div class="menu_containt_div" id="tabs-4">
	<p><strong><?php _e( 'This feature is deprecated and will soon be removed.', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></strong></p>
	<p><?php _e( 'Schema.org tags used by Google+ to render link share posts.', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></p>

	<?php do_action( 'fb_og_admin_settings_schema_before' ); ?>

	<div class="postbox">
		<h3 class="hndle"><i class="dashicons-before dashicons-googleplus"></i> <?php _e( 'Google+ / Schema.org Tags', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?></h3>
		<div class="inside">
			<table class="form-table">
				<tbody>
					
					<tr>
						<th><?php _e( 'Include Title', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_title_show_schema]" id="fb_title_show_schema" value="1" <?php echo (intval($options['fb_title_show_schema'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta itemprop="name" content="..."/&gt;</i> <?php _e('and', 'wonderm00ns-simple-facebook-open-graph-tags'); ?> <i>&lt;meta itemprop="headline" content="..."/&gt;</i>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_title' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Description', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_desc_show_schema]" id="fb_desc_show_schema" value="1" <?php echo (intval($options['fb_desc_show_schema'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta itemprop="description" content="..."/&gt;</i>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_desc' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Image', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_image_show_schema]" id="fb_image_show_schema" value="1" <?php echo (intval($options['fb_image_show_schema'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta itemprop="image" content="..."/&gt;</i>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_og_image' ); ?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Type', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_type_show_schema]" id="fb_type_show_schema" value="1" <?php echo (intval($options['fb_type_show_schema'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;html ... itemscope itemtype="http://schema.org/..."/&gt;</i>
							<br/>
							- <?php _e('Experimental', 'wonderm00ns-simple-facebook-open-graph-tags'); ?>
							<br/>
							- <?php _e('Added to the HTML tag, if you want to avoid W3C and Structured Data validation errors', 'wonderm00ns-simple-facebook-open-graph-tags'); ?>
							<br/>
							- <?php printf( __( 'You can change this value using the <i>%1$s</i> filter', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'fb_type_schema' ); ?>
						</td>
					</tr>
					
					<tr class="fb_type_schema_options">
						<th><?php _e( 'Homepage Type', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<select name="wonderm00n_open_graph_settings[fb_type_schema_homepage]" id="fb_type_schema_homepage">
								<option value="WebSite"<?php if (trim($options['fb_type_schema_homepage'])=='' || trim($options['fb_type_schema_homepage'])=='WebSite') echo ' selected="selected"'; ?>>WebSite</option>
								<option value="Blog"<?php if (trim($options['fb_type_schema_homepage'])=='Blog') echo ' selected="selected"'; ?>>Blog</option>
							</select>
						</td>
					</tr>
					<tr class="fb_type_schema_options">
						<td colspan="2" class="info">
						</td>
					</tr>
					
					<tr class="fb_type_schema_options">
						<th><?php _e( 'Default (including Post/Page) Type', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<select name="wonderm00n_open_graph_settings[fb_type_schema_post]" id="fb_type_schema_post">
								<option value="Article"<?php if (trim($options['fb_type_schema_post'])=='' || trim($options['fb_type_schema_post'])=='Article') echo ' selected="selected"'; ?>>Article</option>
								<option value="NewsArticle"<?php if (trim($options['fb_type_schema_post'])=='NewsArticle') echo ' selected="selected"'; ?>>NewsArticle</option>
								<option value="Report"<?php if (trim($options['fb_type_schema_post'])=='Report') echo ' selected="selected"'; ?>>Report</option>
								<option value="ScholarlyArticle"<?php if (trim($options['fb_type_schema_post'])=='ScholarlyArticle') echo ' selected="selected"'; ?>>ScholarlyArticle</option>
								<option value="TechArticle"<?php if (trim($options['fb_type_schema_post'])=='TechArticle') echo ' selected="selected"'; ?>>TechArticle</option>
							</select>
						</td>
					</tr>
					<tr class="fb_type_schema_options">
						<td colspan="2" class="info">
						</td>
					</tr>
					
					<!-- Removed in 2.0 - https://support.google.com/webmasters/answer/6083347 -->
					<!--<tr>
						<th><?php _e( 'Include Post/Page Author', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_author_show_linkrelgp]" id="fb_author_show_linkrelgp" value="1" <?php echo (intval($options['fb_author_show_linkrelgp'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;link rel="author" content="..."/&gt;</i>
							<br/>
							- <?php _e('The user\'s Google+ URL must be filled in on his profile', 'wonderm00ns-simple-facebook-open-graph-tags');?>
						</td>
					</tr>-->

					<tr>
						<th><?php _e( 'Include Post/Page Author', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_author_show_schema]" id="fb_author_show_schema" value="1" <?php echo (intval($options['fb_author_show_schema'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;meta itemprop="author" content="..."/&gt;</i>
							<br/>
							- <?php _e('From the user Display name', 'wonderm00ns-simple-facebook-open-graph-tags');?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Published/Modified Dates', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_article_dates_show_schema]" id="fb_article_dates_show_schema" value="1" <?php echo (intval($options['fb_article_dates_show_schema'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;itemprop="datePublished" content="..."/&gt;</i> <?php _e('and', 'wonderm00ns-simple-facebook-open-graph-tags'); ?> <i>&lt;meta itemprop="dateModified" content="..."/&gt;</i>
							<br/>
							- <?php _e( 'For posts only', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr>
						<th><?php _e( 'Include Publisher', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="checkbox" name="wonderm00n_open_graph_settings[fb_publisher_show_schema]" id="fb_publisher_show_schema" value="1" <?php echo (intval($options['fb_publisher_show_schema'])==1 ? ' checked="checked"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							<i>&lt;link rel="publisher" href="..."/&gt;</i>
							<br/>
							- <?php _e( 'The website\'s Google+ Page', 'wonderm00ns-simple-facebook-open-graph-tags' );?>
						</td>
					</tr>
					
					<tr class="fb_publisher_schema_options">
						<th><?php _e( 'Website\'s Google+ Page', 'wonderm00ns-simple-facebook-open-graph-tags' );?>:</th>
						<td>
							<input type="text" name="wonderm00n_open_graph_settings[fb_publisher_schema]" id="fb_publisher_schema" size="50" value="<?php echo trim(esc_attr($options['fb_publisher_schema'])); ?>"/>
						</td>
					</tr>
					<tr class="fb_publisher_schema_options">
						<td colspan="2" class="info">
							- <?php _e( 'Google+ Page URL (with https://)', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>

	<?php do_action( 'fb_og_admin_settings_schema_after' ); ?>

</div>