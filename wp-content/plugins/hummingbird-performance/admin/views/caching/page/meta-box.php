<?php
/**
 * Page caching meta box.
 *
 * @package Hummingbird
 *
 * @var bool          $admins_can_disable  Blog admins can disable page caching.
 * @var bool          $blog_is_frontpage   Is the Blog set as the Frontpage.
 * @var bool          $can_compress        Can enable compression.
 * @var bool          $cdn_active          Asset optimization CDN status.
 * @var array         $clear_interval      Clear interval array. Format [ (int) hours, (string) hours|days ].
 * @var array         $custom_post_types   Array of custom post types.
 * @var string        $deactivate_url      Deactivate URL.
 * @var string        $download_url        Download logs URL.
 * @var bool|WP_Error $error               Error if present.
 * @var bool|string   $logs_link           Link to the log file.
 * @var bool          $minify_active       Status of asset optimization module.
 * @var bool          $opcache_enabled     Is opcache enabled.
 * @var array         $options             Module options from the database.
 * @var array         $pages               A list of page types.
 * @var array         $settings            Settings array.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p><?php esc_html_e( 'Hummingbird stores static HTML copies of your pages and posts to decrease page load time.', 'wphb' ); ?></p>
<?php
if ( is_wp_error( $error ) ) {
	$this->admin_notices->show_inline( $error->get_error_message(), 'error' );
} else {
	$notice = esc_html__( 'Page caching is currently active.', 'wphb' );
	if ( $minify_active && $cdn_active ) {
		$notice = esc_html__( 'Page caching is currently active. You are storing your assets on the CDN in the Asset Optimization module. Hummingbird will automatically purge your cache when assets on the CDN expire (after every two months).', 'wphb' );
	}

	$this->admin_notices->show_inline( $notice );
}
?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Page Types', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php ( ! is_multisite() ) ? esc_html_e( ' Select which page types you wish to cache.', 'wphb' ) : false; ?>
		</span>
		<?php if ( is_multisite() ) : ?>
			<span class="sui-description">
				<?php esc_html_e( 'Subsites will inherit the settings you use here, except any additional custom post types or taxonomies will be cached by default.', 'wphb' ); ?>
			</span>
			<span class="sui-description">
				<?php esc_html_e( 'Your subsite admins can use the DONOTCACHEPAGE constant to prevent caching on their custom post types.', 'wphb' ); ?>
			</span>
		<?php endif; ?>
	</div>
	<div class="sui-box-settings-col-2">

		<div class="wphb-dash-table three-columns sui-margin-bottom">
			<?php foreach ( $pages as $page_type => $page_name ) : ?>
				<div class="wphb-dash-table-row">
					<div><?php echo esc_html( $page_name ); ?></div>
					<?php if ( 'home' === $page_type && $blog_is_frontpage ) : ?>
						<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Your blog is your frontpage', 'wphb' ); ?></span>
					<?php else : ?>
						<span class="sub"><?php echo esc_html( $page_type ); ?></span>
						<label class="sui-toggle">
							<input type="checkbox" name="page_types[<?php echo esc_attr( $page_type ); ?>]" id="<?php echo esc_attr( $page_type ); ?>" <?php checked( in_array( $page_type, $settings['page_types'], true ) ); ?>>
							<span class="sui-toggle-slider"></span>
						</label>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<?php foreach ( $custom_post_types  as $custom_post_type ) : ?>
				<div class="wphb-dash-table-row">
					<div><?php echo esc_html( $custom_post_type->label ); ?></div>
					<span class="sub"><?php echo esc_html( $custom_post_type->name ); ?></span>
					<input type="hidden" name="custom_post_types[<?php echo esc_attr( $custom_post_type->name ); ?>]" value="1">
					<label class="sui-toggle">
						<input type="checkbox" name="custom_post_types[<?php echo esc_attr( $custom_post_type->name ); ?>]" id="<?php echo esc_attr( $custom_post_type->name ); ?>" <?php checked( ! in_array( $custom_post_type->name, $settings['custom_post_types'], true ) ); ?> value="0">
						<span class="sui-toggle-slider"></span>
					</label>
				</div>
			<?php endforeach; ?>
		</div>

		<?php
		$this->admin_notices->show_inline(
			sprintf( /* translators: %s: code snippet. */
				esc_html__( 'You can use the %s constant to instruct Hummingbird not to cache specific pages or templates.', 'wphb' ),
				'<code>define(\'DONOTCACHEPAGE\', true);</code>'
			),
			'grey'
		);
		?>
	</div>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Preload caching', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Enable this feature to automatically create cached versions of your homepage or any page or post. This can be a resource-intensive operation, so use it only when required.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<label for="preload" class="sui-toggle">
				<input type="checkbox" name="preload[enabled]" id="preload" value="1" aria-labelledby="preload-label" <?php checked( $options['preload'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="preload-label" class="sui-toggle-label">
					<?php esc_html_e( 'Enable preload caching', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( "The homepage will be preloaded once you enable this feature and then automatically whenever an action triggers your cache to be cleared. A page or post will be preloaded automatically if it's updated or its cached version is cleared.", 'wphb' ); ?>
				</span>
			</label>

			<div class="sui-border-frame sui-toggle-content <?php echo $options['preload'] ? '' : 'sui-hidden'; ?>" id="page_cache_preload_type">
				<span class="sui-description">
					<?php esc_html_e( 'Choose which pages you want to trigger cache preload. We recommend you always preload the homepage.', 'wphb' ); ?>
				</span>
				<div class="sui-form-field">
					<label for="home_page" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
						<input type="checkbox" name="preload_type[home_page]" id="home_page" <?php checked( isset( $options['preload_type'] ) && $options['preload_type']['home_page'] ); ?>>
						<span aria-hidden="true"></span>
						<span><?php esc_html_e( 'Homepage', 'wphb' ); ?></span>
					</label>
					<label for="on_clear" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
						<input type="checkbox" name="preload_type[on_clear]" id="on_clear" <?php checked( isset( $options['preload_type'] ) && $options['preload_type']['on_clear'] ); ?>>
						<span aria-hidden="true"></span>
						<span><?php esc_html_e( "Any page or post that's been updated, or for which the cache was cleared", 'wphb' ); ?></span>
					</label>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Cache interval', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Enable this feature to automatically clear cache at regular intervals only if you are required to do so. Frequent cache clearing can create significant server issues.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<label for="clear_interval" class="sui-toggle">
				<input type="checkbox" name="clear_interval[enabled]" value="1" id="clear_interval" aria-labelledby="clear_interval-label" <?php checked( $settings['clear_interval']['enabled'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="clear_interval-label" class="sui-toggle-label">
					<?php esc_html_e( 'Clear cache on interval', 'wphb' ); ?>
				</span>
			</label>

			<span class="sui-description sui-toggle-description">
				<?php esc_html_e( 'A cache cleanup will occur following page or post updates at the interval you set.', 'wphb' ); ?>
			</span>

			<div class="sui-border-frame sui-toggle-content <?php echo $settings['clear_interval']['enabled'] ? '' : 'sui-hidden'; ?>" id="page_cache_clear_interval">
				<label class="sui-label" for="clear-cache-interval">
					<?php esc_html_e( 'Clear cache timing', 'wphb' ); ?>
				</label>
				<div class="sui-form-field sui-form-field-inline sui-no-margin-bottom">
					<input type="text" class="sui-form-control sui-input-sm" name="clear_interval[interval]" id="clear-cache-interval" value="<?php echo absint( $clear_interval[0] ); ?>" />

					<select class="sui-select sui-input-sm" name="clear_interval[period]" data-width="200">
						<option value="hours" <?php selected( $clear_interval[1], 'hours' ); ?>><?php esc_html_e( 'hours', 'wphb' ); ?></option>
						<option value="days" <?php selected( $clear_interval[1], 'days' ); ?>><?php esc_html_e( 'days', 'wphb' ); ?></option>
					</select>
				</div>
				<span class="sui-description">
					<?php esc_html_e( 'We recommend setting the interval to no less than 24 hours. Short intervals increase server loads and could negatively impact performance.', 'wphb' ); ?>
				</span>
			</div>
		</div>
	</div>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Integrations', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Hummingbird will detect and enable the integrated caching options and will use those when clearing the cache.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<label for="varnish" class="sui-toggle">
				<input type="checkbox" name="integrations[varnish]" id="varnish" value="1" aria-labelledby="varnish-label" <?php checked( isset( $options['integrations']['varnish'] ) && $options['integrations']['varnish'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="varnish-label" class="sui-toggle-label">
					<?php esc_html_e( 'Purge Varnish caching', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( 'Varnish caching increases response to HTTP requests and reduces server workload, significantly accelerating delivery. When enabled it will purge Varnish cache when you publish posts, pages or comments.', 'wphb' ); ?>
				</span>
			</label>
		</div>

		<div class="sui-form-field">
			<label for="opcache" class="sui-toggle">
				<input type="checkbox" name="integrations[opcache]" id="opcache" value="1" aria-labelledby="opcache-label" <?php checked( isset( $options['integrations']['opcache'] ) && $options['integrations']['opcache'] ); ?> <?php disabled( $opcache_enabled, false ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="opcache-label" class="sui-toggle-label">
					<?php esc_html_e( 'Purge OpCache', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( "OpCache stores script bytecode in memory so PHP scripts don't have to be loaded and parsed with every request. OpCache cache will be cleared when you click “Clear cache” button located in the admin bar.", 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php
					if ( ! $opcache_enabled ) {
						$notice = esc_html__( 'Note: OpCache is only available if you have the service setup on your server.', 'wphb' );
						if ( isset( $_SERVER['WPMUDEV_HOSTED'] ) && $_SERVER['WPMUDEV_HOSTED'] ) {
							$notice = esc_html__( 'OpCache is already enabled and optimally preconfigured in our servers. It will automatically detect and recache any php file changes.', 'wphb' );
						}

						$this->admin_notices->show_inline( $notice, 'grey' );
					}
					?>
				</span>
			</label>
		</div>
	</div>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Settings', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Fine tune page caching to work how you want it to.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<label for="logged_in" class="sui-toggle">
				<input type="checkbox" name="settings[logged_in]" id="logged_in" value="1" aria-labelledby="logged_in-label" <?php checked( $settings['settings']['logged_in'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="logged_in-label" class="sui-toggle-label">
					<?php esc_html_e( 'Include logged in users', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( 'Caching pages for logged in users can reduce load on your server, but can cause strange behavior with some themes/plugins.', 'wphb' ); ?>
				</span>
			</label>
		</div>

		<div class="sui-form-field">
			<label for="url_queries" class="sui-toggle">
				<input type="checkbox" name="settings[url_queries]" id="url_queries" value="1" aria-labelledby="url_queries-label" <?php checked( $settings['settings']['url_queries'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="url_queries-label" class="sui-toggle-label">
					<?php esc_html_e( 'Cache URL queries', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( 'You can turn on caching pages with GET parameters (?x=y at the end of a url), though generally this isn’t a good idea if those pages are dynamic.', 'wphb' ); ?>
				</span>
			</label>
		</div>

		<div class="sui-form-field">
			<label for="cache_404" class="sui-toggle">
				<input type="checkbox" name="settings[cache_404]" id="cache_404" value="1" aria-labelledby="cache_404-label" <?php checked( $settings['settings']['cache_404'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="cache_404-label" class="sui-toggle-label">
					<?php esc_html_e( 'Cache 404 requests', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( 'Even though 404s are bad and you will want to avoid them with redirects, you can still choose to cache your 404 page to avoid additional load on your server.', 'wphb' ); ?>
				</span>
			</label>
		</div>

		<div class="sui-form-field">
			<label for="clear_update" class="sui-toggle">
				<input type="checkbox" name="settings[clear_update]" id="clear_update" value="1" aria-labelledby="clear_update-label" <?php checked( $settings['settings']['clear_update'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="clear_update-label" class="sui-toggle-label">
					<?php esc_html_e( 'Clear full cache when post/page is updated', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( 'If one of your pages or posts gets updated, turning this setting on will also regenerate all cached archives and taxonomies for all post types.', 'wphb' ); ?>
				</span>
			</label>
		</div>

		<div class="sui-form-field">
			<label for="debug_log" class="sui-toggle">
				<input type="checkbox" name="settings[debug_log]" id="debug_log" value="1" aria-labelledby="debug_log-label" <?php checked( $settings['settings']['debug_log'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="debug_log-label" class="sui-toggle-label">
					<?php esc_html_e( 'Enable debug log', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( 'If you’re having issues with page caching, turn on the debug log to get insight into what’s going on.', 'wphb' ); ?>
				</span>
			</label>
			<div class="sui-description sui-toggle-content sui-border-frame with-padding wphb-logging-box <?php echo $settings['settings']['debug_log'] ? '' : 'sui-hidden'; ?>">
				<?php esc_html_e( 'Debug logging is active. Logs are stored for 30 days, you can download the log file below.', 'wphb' ); ?>
				<div class="wphb-logging-buttons">
					<a href="<?php echo esc_url( $download_url ); ?>" class="sui-button sui-button-ghost" <?php disabled( ! $logs_link, true ); ?>>
						<span class="sui-icon-download" aria-hidden="true"></span>
						<?php esc_html_e( 'Download Logs', 'wphb' ); ?>
					</a>
					<a href="#" class="sui-button sui-button-ghost sui-button-red wphb-logs-clear" data-module="page_cache" <?php disabled( ! $logs_link, true ); ?>>
						<span class="sui-icon-trash" aria-hidden="true"></span>
						<?php esc_html_e( 'Clear', 'wphb' ); ?>
					</a>
				</div>
			</div>
		</div>

		<div class="sui-form-field">
			<label for="cache_identifier" class="sui-toggle">
				<input type="hidden" name="settings[cache_identifier]" value="0">
				<input type="checkbox" name="settings[cache_identifier]" id="cache_identifier" value="1" aria-labelledby="cache_identifier-label"
					<?php checked( $settings['settings']['cache_identifier'] ); ?>
					<?php disabled( ! \Hummingbird\Core\Utils::is_member() ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="cache_identifier-label" class="sui-toggle-label">
					<?php esc_html_e( 'Identify cached pages', 'wphb' ); ?>
					<?php if ( ! \Hummingbird\Core\Utils::is_member() ) : ?>
						<span class="sui-tag sui-tag-pro"><?php esc_html_e( 'Pro', 'wphb' ); ?></span>
					<?php endif; ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( 'Hummingbird will insert a comment into your page’s <head> tag to easily identify if it’s cached or not.', 'wphb' ); ?>
				</span>
			</label>
		</div>

		<div class="sui-form-field">
			<label for="compress" class="sui-toggle">
				<input type="checkbox" name="settings[compress]" id="compress" value="1" aria-labelledby="compress-label" <?php checked( $settings['settings']['compress'] && $can_compress ); ?> <?php disabled( ! $can_compress ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="compress-label" class="sui-toggle-label">
					<?php esc_html_e( 'Serve compressed versions of cached files', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( 'Improves performance on servers, where gzip compression is disabled or not available.', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php
					if ( ! $can_compress ) {
						$this->admin_notices->show_inline(
							esc_html__( 'Note: Gzip compression already enabled on the server.', 'wphb' ),
							'grey'
						);
					}
					?>
				</span>
			</label>
		</div>

		<div class="sui-form-field">
			<label for="mobile" class="sui-toggle">
				<input type="hidden" name="settings[mobile]" value="0">
				<input type="checkbox" name="settings[mobile]" id="mobile" value="1" aria-labelledby="mobile-label" <?php checked( $settings['settings']['mobile'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="mobile-label" class="sui-toggle-label">
					<?php esc_html_e( 'Cache on mobile devices', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( "By default, page caching is enabled for mobile devices. If you don't want to use mobile caching, simply disable this setting.", 'wphb' ); ?>
				</span>
			</label>
		</div>

		<div class="sui-form-field">
			<label for="comment_clear" class="sui-toggle">
				<input type="hidden" name="settings[comment_clear]" value="0">
				<input type="checkbox" name="settings[comment_clear]" id="comment_clear" value="1" aria-labelledby="comment_clear-label" <?php checked( $settings['settings']['comment_clear'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="comment_clear-label" class="sui-toggle-label">
					<?php esc_html_e( 'Clear cache on comment post', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( 'The page cache will be cleared after each comment made on a post.', 'wphb' ); ?>
				</span>
			</label>
		</div>

		<div class="sui-form-field">
			<label for="cache_headers" class="sui-toggle">
				<input type="hidden" name="settings[cache_headers]" value="0">
				<input type="checkbox" name="settings[cache_headers]" id="cache_headers" value="1" aria-labelledby="cache_headers-label" <?php checked( $settings['settings']['cache_headers'] ); ?>>
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="cache_headers-label" class="sui-toggle-label">
					<?php esc_html_e( 'Cache HTTP headers', 'wphb' ); ?>
				</span>
				<span class="sui-description sui-toggle-description">
					<?php esc_html_e( "By default, Hummingbird won't cache HTTP headers. Enable this feature to include them.", 'wphb' ); ?>
				</span>
			</label>
		</div>
	</div><!-- end sui-box-settings-col-2 -->
</div><!-- end row -->

<?php if ( is_multisite() ) : ?>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'Subsites', 'wphb' ); ?></span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label for="admins_disable_caching" class="sui-toggle">
					<input type="hidden" name="admins_disable_caching" value="0">
					<input type="checkbox" name="settings[admins_disable_caching]" id="admins_disable_caching" value="1" aria-labelledby="admins_disable_caching-label" <?php checked( $admins_can_disable ); ?>>
					<span class="sui-toggle-slider" aria-hidden="true"></span>
					<span id="admins_disable_caching-label" class="sui-toggle-label">
						<?php esc_html_e( 'Allow subsites to disable page caching', 'wphb' ); ?>
					</span>
					<span class="sui-description sui-toggle-description">
						<?php esc_html_e( 'This setting adds the Page Caching tab to Hummingbird and allows a network or subsite admin to disable Page Caching if they wish to. Note: It does not allow them to modify your network settings.', 'wphb' ); ?>
					</span>
				</label>
			</div>
		</div>
	</div>
<?php endif; ?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Exclusions', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Specify any particular URLs you don’t want to cache at all.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<span class="sui-settings-label"><?php esc_html_e( 'URL Strings', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'You can tell Hummingbird not to cache specific URLs, or any URLs that contain strings. Add one entry per line.', 'wphb' ); ?>
		</span>
		<div class="sui-form-field">
			<?php
			$strings = '';
			if ( isset( $settings['exclude'] ) && isset( $settings['exclude']['url_strings'] ) && is_array( $settings['exclude']['url_strings'] ) ) {
				$strings = join( PHP_EOL, $settings['exclude']['url_strings'] );
			}
			?>
			<textarea class="sui-form-control" name="url_strings"><?php echo $strings; ?></textarea>
		</div>
		<span class="sui-description sui-with-bottom-border">
			<?php
			echo sprintf(
				/* translators: %1$s - opening a tag, %2$s - closing a tag */
				esc_html__(
					'For example, if you want to not cache any pages that are nested under your Forums
				area you might add "/forums/" as a rule. When Hummingbird goes to cache pages, she will ignore any
				URL that contains "/forums/". To exclude a specific page you might add "/forums/thread-title". Accepts
				regular expression syntax, for more complex exclusions it can be helpful to test
				on %1$sregex101.com%2$s. Note: Hummingbird will auto convert
				your input into valid regex syntax.',
					'wphb'
				),
				'<a href="https://regex101.com" target="_blank">',
				'</a>'
			);
			?>
		</span>

		<span class="sui-settings-label"><?php esc_html_e( 'User agents', 'wphb' ); ?></span>
		<span class="sui-description ">
			<?php esc_html_e( 'Specify any user agents you don’t want to send cached pages to like bots, spiders and crawlers. We’ve added a couple of common ones for you.', 'wphb' ); ?>
		</span>
		<div class="sui-form-field sui-with-bottom-border">
			<?php
			$user_agent = '';
			if ( isset( $settings['exclude'] ) && isset( $settings['exclude']['user_agents'] ) && is_array( $settings['exclude']['user_agents'] ) ) {
				$user_agent = join( PHP_EOL, $settings['exclude']['user_agents'] );
			}
			?>
			<textarea class="sui-form-control"  name="user_agents"><?php echo $user_agent; ?></textarea>
		</div>

		<span class="sui-settings-label"><?php esc_html_e( 'Cookies', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( "Specify the cookie IDs you don't want cached. Add one ID per line.", 'wphb' ); ?>
		</span>
		<div class="sui-form-field">
			<?php
			$cookies = '';
			if ( isset( $settings['exclude'] ) && isset( $settings['exclude']['cookies'] ) && is_array( $settings['exclude']['cookies'] ) ) {
				$cookies = join( PHP_EOL, $settings['exclude']['cookies'] );
			}
			?>
			<textarea class="sui-form-control"  name="cookies"><?php echo $cookies; ?></textarea>
		</div>
	</div>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<strong><?php esc_html_e( 'Deactivate', 'wphb' ); ?></strong>
		<span class="sui-description">
			<?php esc_html_e( 'You can deactivate page caching at any time. ', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2 wphb-deactivate-pc">
		<a href="<?php echo esc_url( $deactivate_url ); ?>" class="sui-button sui-button-ghost sui-button-icon-left" onclick="WPHB_Admin.Tracking.disableFeature( 'Page Caching' )">
			<span class="sui-icon-power-on-off" aria-hidden="true"></span>
			<?php esc_html_e( 'Deactivate', 'wphb' ); ?>
		</a>
		<span class="sui-description">
			<?php esc_html_e( 'Note: Deactivating won’t lose any of your website data, only the cached pages will be removed and won’t be served to your visitors any longer. Remember this may result in slower page loads unless you have another caching plugin activate.', 'wphb' ); ?>
		</span>
	</div>
</div>
