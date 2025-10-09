<?php
/**
 * Bootstrap for WP Defender.
 *
 * @package WP_Defender
 */

namespace WP_Defender;

use WP_Defender\Traits\Defender_Bootstrap;
use WP_Defender\Component\Config\Config_Hub_Helper;

/**
 * Class Bootstrap
 */
class Bootstrap {

	use Defender_Bootstrap;

	/**
	 * Activation.
	 */
	public function activation_hook(): void {
		$this->activation_hook_common();
	}

	/**
	 * Load all modules.
	 */
	public function init_modules(): void {
		$this->init_modules_common();
		$this->init_wpmudev_dashnotice();
	}

	/**
	 * Initializes the WPMUDEV dash notice.
	 *
	 * @return void
	 */
	public function init_wpmudev_dashnotice(): void {
		if ( ! (bool) get_site_option( 'wp_defender_shown_activator' ) ) {
			return;
		}

		global $wpmudev_notices;
		$wpmudev_notices[] = array(
			'id'      => Config_Hub_Helper::WDP_ID,
			'name'    => defined( 'WP_DEFENDER_PRO' ) && WP_DEFENDER_PRO ? 'Defender Pro' : 'Defender',
			'screens' => array(
				'toplevel_page_wp-defender',
				'toplevel_page_wp-defender-network',
				'defender_page_wdf-settings',
				'defender_page_wdf-settings-network',
				'defender_page_wdf-logging',
				'defender_page_wdf-logging-network',
				'defender_page_wdf-hardener',
				'defender_page_wdf-hardener-network',
				'defender_page_wdf-scan',
				'defender_page_wdf-scan-network',
				'defender_page_wdf-ip-lockout',
				'defender_page_wdf-ip-lockout-network',
				'defender_page_wdf-waf',
				'defender_page_wdf-waf-network',
				'defender_page_wdf-2fa',
				'defender_page_wdf-2fa-network',
				'defender_page_wdf-advanced-tools',
				'defender_page_wdf-advanced-tools-network',
				'defender_page_wdf-notification',
				'defender_page_wdf-notification-network',
				'defender_page_wdf-expert-services',
				'defender_page_wdf-expert-services-network',
			),
		);
		/**
		 *  Load WPMUDEV dash notice.
		 *
		 * @noinspection PhpIncludeInspection
		 */
		include_once defender_path( 'extra/dash-notice/wpmudev-dash-notification.php' );
	}
}