<?php

declare(strict_types=1);

namespace wpengine\cache_plugin;

require_once __DIR__ . '/quick-actions.php';
require_once __DIR__ . '/cache-times.php';
require_once __DIR__ . '/../security/security-checks.php';
require_once __DIR__ . '/../cache-db-settings.php';

\wpengine\cache_plugin\check_security();

class WpeCachePage {
	const NOTIFICATION_SUCCESS            = 'success';
	const NOTIFICATION_FAILURE            = 'failure';
	const NOTIFICATION_RATE_LIMIT_REACHED = 'rate_limit_reached';

	private const HTML_DISPLAY_NOTIFICATION = 'display: block;';
	private const HTML_HIDE_NOTIFICATION    = 'display: none;';

	public static function display_cache_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		self::setup_css();
		?>
		<div class="wrap">
			<?php QuickActions::display(); ?>
			<?php CacheTimes::display(); ?>
		</div>
		<?php
	}

	private static function setup_css() {
		wp_enqueue_style( 'wpe-common', WPE_PLUGIN_URL . '/css/wpe-common.css', array(), WPE_PLUGIN_VERSION );
	}

	private static function notification_success_style( $notification ) {
		self::notification_display_style( self::NOTIFICATION_SUCCESS === $notification );
	}

	private static function notification_failure_style( $notification ) {
		self::notification_display_style( self::NOTIFICATION_FAILURE === $notification );
	}

	private static function notification_rate_limit_reached_style( $notification ) {
		self::notification_display_style( self::NOTIFICATION_RATE_LIMIT_REACHED === $notification );
	}

	private static function notification_display_style( $display ) {
		echo $display ? esc_attr( self::HTML_DISPLAY_NOTIFICATION ) : esc_attr( self::HTML_HIDE_NOTIFICATION );
	}

	private static function get_notification_query_parameter() {
		return isset( $_GET['notification'] ) ? sanitize_text_field( wp_unslash( $_GET['notification'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification
	}

	public static function setup_error_toasts() {

		$notification = self::get_notification_query_parameter();

		?>
		<div class="notice wpe-error is-dismissible inline" id="wpe-cache-error-toast" style='<?php self::notification_failure_style( $notification ); ?>'>
			<p><?php echo esc_html( __( 'Oops, something went wrong clearing all caches. Please try again in a few minutes.' ) ); ?></p>
		</div>
		<div class="notice wpe-error is-dismissible inline" id="wpe-cache-times-error-toast"  style="display: none;">
			<p><?php echo esc_html( __( 'Oops, something went wrong saving your settings. Please try again in a few minutes.' ) ); ?></p>
		</div>
		<div class="notice wpe-success is-dismissible inline" id="wpe-cache-times-success-toast" style="display: none;">
			<p><?php echo esc_html( __( 'Settings Saved' ) ); ?></p>
		</div>
		<div class="notice wpe-success is-dismissible inline" id="wpe-cache-success-toast" style='<?php self::notification_success_style( $notification ); ?>'>
			<p><?php echo esc_html( __( 'The caches have been cleared' ) ); ?></p>
		</div>
		<div class="notice wpe-info is-dismissible inline" id="wpe-cache-rate-limit-applied-toast" style='<?php self::notification_rate_limit_reached_style( $notification ); ?>'>
			<p><?php echo esc_html( __( 'Your cache can only be cleared every 5 minutes.  You can clear it again when the rate limit expires.' ) ); ?></p>
		</div>
		<?php
	}
}
