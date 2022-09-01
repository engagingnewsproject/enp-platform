<?php

declare(strict_types=1);

namespace wpengine\cache_plugin;

require_once __DIR__ . '/../clear-all-cache-status.php';
require_once __DIR__ . '/../cache-db-settings.php';
require_once __DIR__ . '/../security/security-checks.php';
require_once __DIR__ . '/../logging-trait.php';
require_once __DIR__ . '/../cache-view-helpers.php';

\wpengine\cache_plugin\check_security();

class CacheTimes {
	use CachePluginLoggingTrait;

	const VALID_CACHE_CONTROL_OPTIONS = array(
		'default'    => CacheSettingValues::SETTING_DEFAULT_VALUE,
		'10 Minutes' => 600,
		'1 Hour'     => 3600,
		'4 Hours'    => 14400,
		'12 Hours'   => 43200,
		'1 Day'      => 86400,
		'3 Days'     => 259200,
		'7 Days'     => 604800,
		'4 Weeks'    => 2419200,
	);

	public static function display() {
		if ( ! is_network_admin() ) {
			?>
		<div class="wpe-common-plugin-container wpe-cache-times-panel">
			<?php
			CacheDbSettings::get_instance()->init_settings();
			self::open_form();
			self::display_cache_times_header();
			self::display_cache_times_options();
			self::display_cache_times_submit_button();
			self::close_form();
			self::display_custom_notices();
			?>
		</div>
			<?php
		}
	}



	public static function display_custom_notices() {
		$screen = get_current_screen();
		if ( 'toplevel_page_wpengine-common' === $screen->id ) {
			// ignoring nonce requirement as we're using settings_fields to handle verification, we only handle display of notice here.
			// https://codex.wordpress.org/Settings_API .
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['settings-updated'] ) ) {
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				if ( 'true' === $_GET['settings-updated'] ) :
					?>
						<script>
							document.getElementById("wpe-cache-times-success-toast").style.display = "block";
						</script>
					<?php else : ?>
						<script>
							document.getElementById("wpe-cache-times-error-toast").style.display = "block";
						</script>
						<?php
					endif;
			}
		}
	}

	private static function display_cache_times_header() {
		?>
		<h2>Cache Times</h2>
		<?php
			self::display_password_protected_info_text()
		?>
		<p>Increasing the cache times on the server will allow more users to see Cached copies of your pages. Cached copies of pages are served from outside of WordPress, which conserves server resources and saves time for your users.</p>
		<p>The cache is purged in most functions that update post content, so oftentimes it's best to set limits as high as possible. If you regularly update content and notice your posts take a while to update, it may be best to reduce these limits. If you are making a one-off change, the purge cache button will update the content for your visitors.</p>
		<?php
	}

	private static function display_cache_times_options() {
		try {
			self::display_cache_times_post_types();
			self::display_cache_times_rest_api_namespaces();
			self::display_smarter_cache();
			self::display_last_modified_headers();
		} catch ( \Exception $e ) {
			self::log_warning_static( "Caught exception while calling display_cache_times_options: {$e->getMessage()} {$e->getTraceAsString()}" );
		}
	}

	private static function display_password_protected_info_text() {
		if ( CacheViewHelper::is_current_site_password_protected() ) {
			?>
			<p id="wpe-password-protected-info-text" style="font-weight:bold;">Your environment is password protected. Cache settings will not apply until the password protection is removed.</p>
			<?php
		}
	}

	private static function open_form() {
		?>
		<form method="post" action="options.php">
		<?php
		settings_fields( CacheDbSettings::SETTINGS_GROUP );
		do_settings_sections( CacheDbSettings::SETTINGS_GROUP );
	}

	private static function close_form() {
		?>
		</form>
		<?php
	}

	private static function display_cache_times_post_types() {
		$options = CacheDbSettings::get_instance()->get();
		?>
		<div class="wpe-cache-time-option-panel">
			<h3>Post Types</h3>
			<p>Use the below options to alter the cache expiry times on the <b>public post types</b> on your site. Site performance will improve with longer time.<br> Longer caching timeout will make your website work better.</p>
			<?php
			foreach ( $options['sanitized_post_types'] as $post_type ) {
				self::cache_menu_settings_page_options( $post_type );
			}
			?>
		</div>
		<?php
	}

	private static function cache_menu_settings_page_options( $post_type ) {
		$current_cache_time = CacheDbSettings::get_instance()->get( $post_type . '_cache_expires_value' );
		?>
			<div class="wpe-common-select-panel">
				<label class="wpe-cache-time-option-label"><?php echo esc_html( ucfirst( $post_type ) ); ?> Cache Length:</label>
				<select name="<?php echo esc_attr( CacheDbSettings::CONFIG_OPTION . '[' . $post_type . '_cache_expires_value]' ); ?>" class='wpe-cache-expires-value'>
					<?php self::build_cache_menu( $current_cache_time ); ?>
				</select>
			</div>
		<?php
	}

	private static function build_cache_menu( $current_cache_time ) {
		foreach ( self::VALID_CACHE_CONTROL_OPTIONS as $human_readable => $seconds ) {
			echo '<option value="' . esc_attr( $seconds ) . '" ' . selected( $current_cache_time, $seconds ) . '>';
			echo esc_html( $human_readable );
			echo '</option>';
		}
	}

	private static function display_cache_times_rest_api_namespaces() {
		$options = CacheDbSettings::get_instance()->get();
		?>
		<div class="wpe-cache-time-option-panel">
			<hr />
			<div class="wpe-rest-api-namespaces-panel">
				<h3>REST API Namespaces</h3>
				<p>Use the below options to alter the cache expiry times on the <b>REST API</b> end-points on your site.
				<?php
				foreach ( $options['namespaces'] as $namespace ) {
					if ( ( false === strpos( $namespace, 'wpe_sign_on_plugin' ) ) && ( false === strpos( $namespace, 'wpe/cache-plugin' ) ) ) {
						self::cache_menu_settings_page_options( $namespace );
					}
				}
				?>
			</div>
		</div>
		<?php
	}

	private static function display_smarter_cache() {
		$smarter_cache_enabled = CacheDbSettings::get_instance()->get( 'smarter_cache_enabled' );
		?>
		<div class="wpe-cache-time-option-panel">
			<hr />
			<div class="wpe-rest-api-namespaces-panel">
				<h3>Smarter Cache</h3>
				<p>This option will allow your posts and pages to be cached for longer if they haven't been modified in a while.</p>
				<p>If your posts and pages have gone more than 4 weeks without being updated, this option will allow you to cache them for up to 6 months by default.<br/> 
					As posts pass 4 weeks without being updated, the cache header will be updated to 6 months.</p>	
				<input 
					type="checkbox"
					name="wpe_cache_config[smarter_cache_enabled]"
					value="1"
					id="smarter_cache_checkbox"
					<?php
						checked( $smarter_cache_enabled, 1 );
					?>
				>
				<label for="smarter_cache_checkbox" class="wpe-checkbox-label">
					<strong>Smarter cache</strong>
				</label>
			</div>
		</div>
		<?php
	}

	private static function display_last_modified_headers() {
		$last_modified_enabled = CacheDbSettings::get_instance()->get( 'last_modified_enabled' );
		?>
		<div class="wpe-cache-time-option-panel">
			<hr />
			<div class="wpe-rest-api-namespaces-panel">
				<h3>Last-Modified Headers</h3>
				<p>Last modified headers will encourage bots and users to use local cache instead of pulling content from the server each time. 
					This will speed up their responses, and decrease the impact a heavy bot crawl could have on your site.</p>
				<p>Last-Modified headers are updated on specific posts based on the last time they were modified, and the most recent comment. 
					They are also updated Globally on Theme change, or Menu updates. If a major change is made, the global Last-Modified headers can be updated using the option below. 
					The Last-Modified headers sent from the server are always the most recent of those options.</p>
				<input 
					type="checkbox"
					name="wpe_cache_config[last_modified_enabled]"
					value="1"
					id="last_modified_headers_checkbox"
					<?php
						checked( $last_modified_enabled, 1 );
					?>
				>
				<label for="last_modified_headers_checkbox" class="wpe-checkbox-label">
					<strong>Last-Modified Headers</strong>
				</label>
			</div>
		</div>
		<?php
	}

	private static function display_cache_times_submit_button() {
		?>
		<div><?php submit_button( 'Save all changes', $type = 'wpe-admin-button-primary' ); ?> </div>
		<?php
	}
}
