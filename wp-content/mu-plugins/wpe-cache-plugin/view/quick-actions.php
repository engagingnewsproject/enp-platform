<?php

declare(strict_types=1);

namespace wpengine\cache_plugin;

require_once __DIR__ . '/../clear-all-cache-status.php';
require_once __DIR__ . '/../security/security-checks.php';
require_once __DIR__ . '/../wpe-common-adapter.php';
require_once __DIR__ . '/../max-cdn-provider.php';

\wpengine\cache_plugin\check_security();

class QuickActions {

	public static function display( int $status = ClearAllCacheStatus::DEFAULT ) {

		?>
		<div class="wpe-quick-actions wpe-common-plugin-container">
			<h2>Quick Actions</h2>
			<p>Caching reduces the load on your site by storing the results of a request, so that it can be served to the next visitor. This significantly increases the speed of the site. However, this can lead to a visitor receiving an old version of the page. This can be fixed by clearing the cache below.</p>
			<?php self::display_maxcdn_info(); ?>
			<p><b>Note:</b> Cache is cleared by environment not by domain. This does not clear <b>Global Edge Security</b> cache.</p>
			<?php
			self::display_panel_links();
			self::display_all_cache_button_panel( $status );
			?>
		</div>
		<?php
	}

	private static function display_maxcdn_info() {
		$cdn_provider = new MaxCDNProvider();
		if ( ! $cdn_provider->is_enabled() ) {
			return;
		}
		$site_name = WpeCommonAdapter::get_instance()->get_site_name();
		?>
		<p>CDN cache can be cleared every five minutes. <a href=<?php echo esc_url( 'https://my.wpengine.com/installs/' . $site_name . '/cdn' ); ?> target='_blank'>Disable CDN.</a></p>
		<?php
	}

	private static function display_panel_links() {
		$site_name = WpeCommonAdapter::get_instance()->get_site_name();
		?>
		<div class="wpe-admin-button-controls">
			<a href=<?php echo esc_url( 'https://my.wpengine.com/installs/' . $site_name . '/domains' ); ?> target='_blank'> Clear Global Edge Security caches by domain in Portal</a>
			<a href=<?php echo esc_url( 'https://my.wpengine.com/installs/' . $site_name . '/cache_dashboard' ); ?> target='_blank'> Clear Advanced Network cache in Portal</a>
			<a href='https://wpengine.com/support/cache/' target='_blank'>Learn more about caching</a>
		</div>
		<?php
	}

	private static function display_all_cache_button_panel( int $status ) {
		?>
		<div class="wpe-common-cta-panel">
			<div class="wpe-common-cta-wrap">
				<div id="wpe-clear-all-cache-icon" class="wpe-admin-icon-check-solid"></div>
				<div class="wpe-common-cta-text">
					<p class="wpe-common-cta-heading">Clear all caches at once</p>
					<p>This will slow down your site until caches are rebuilt.</p>
					<?php self::display_multisite_info_text(); ?>
					<p id="wpe-last-cleared-text"></p>
					<p id="wpe-last-cleared-error-text"></p>
				</div>
			</div>
			<div class="wpe-common-cta-button">
				<button id="wpe-clear-all-cache-btn" class="wpe-admin-button-primary">Clear all caches</button>
			</div>
		</div>
		<?php
	}

	private static function display_multisite_info_text() {
		if ( is_multisite() ) {
			?>
			<p id="wpe-multisite-info-text" style="font-size: small; max-width: 400px;">For multisite environments, using this tool will clear caches for all sites. This will slow down your sites until caches are rebuilt</p>
			<?php
		}
	}
}
