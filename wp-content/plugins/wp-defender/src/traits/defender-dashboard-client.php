<?php
/**
 * Handle Dashboard based functionalities of WPMUDEV class.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

use WPMUDEV_Dashboard;
use WPMUDEV\Hub\Connector\API;
use WPMUDEV\Hub\Connector\Data;
use WP_Defender\Component\Config\Config_Hub_Helper;

trait Defender_Dashboard_Client {

	/**
	 * Get membership status.
	 *
	 * @return bool
	 */
	public function is_pro(): bool {
		return $this->get_apikey() !== false;
	}

	/**
	 * Check if user is a paid one in WPMU DEV.
	 *
	 * @return bool
	 */
	public function is_member(): bool {
		if (
			$this->is_dash_activated() && method_exists( WPMUDEV_Dashboard::$upgrader, 'user_can_install' )
		) {
			return WPMUDEV_Dashboard::$upgrader->user_can_install(
				Config_Hub_Helper::WDP_ID,
				true
			);
		}

		return false;
	}

	/**
	 * Check if user is a WPMU DEV admin.
	 *
	 * @return bool
	 * @since 2.6.3
	 */
	public function is_wpmu_dev_admin(): bool {
		if ( $this->is_dash_activated() && method_exists( 'WPMUDEV_Dashboard_Site', 'allowed_user' ) ) {
			return WPMUDEV_Dashboard::$site->allowed_user( get_current_user_id() );
		}

		return false;
	}

	/**
	 * Bring the plugin menu title.
	 *
	 * @return string Menu title.
	 */
	public function get_menu_title(): string {
		if ( $this->is_pro() ) {
			$menu_title = esc_html__( 'Defender Pro', 'wpdef' );
		} else {
			// Check if it's Pro but user logged the WPMU DEV Dashboard out.
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$menu_title = file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . WP_DEFENDER_PRO_PATH )
							&& is_plugin_active( WP_DEFENDER_PRO_PATH )
				? esc_html__( 'Defender Pro', 'wpdef' )
				: esc_html__( 'Defender', 'wpdef' );
		}

		return $menu_title;
	}

	/**
	 * Return icon svg image.
	 *
	 * @return string
	 */
	public function get_menu_icon(): string {
		ob_start();
		?>
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd"
					d="M9.99999 2.08899L3 4.21792V9.99502H9.99912V18.001H10C13.47 18.001 17 13.9231 17 11.0045V9.99501H9.99999V2.08899ZM10 0L1 2.73862V11.0045C1 15.1125 5.49 20 10 20C14.51 20 19 15.1225 19 11.0045V2.73862L10 0Z"
					fill="#F0F6FC"/>
		</svg>
		<?php
		$svg = ob_get_clean();

		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Check if WPMU DEV Dashboard plugin is activated.
	 *
	 * @return bool
	 * @since 3.4.0
	 */
	public function is_dash_activated(): bool {
		return class_exists( 'WPMUDEV_Dashboard' );
	}

	/**
	 * Checks if WPMU DEV Dashboard plugin is installed.
	 *
	 * @return bool
	 * @since 4.10.0
	 */
	public function is_dash_installed(): bool {
		return file_exists( WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php' );
	}

	/**
	 * Check if site is connected to HUB.
	 * Todo: after implementing the HCM, the Free version can get an API key if the site is connected to the Hub using
	 * HCM (without the Dashboard plugin). Does it make sense to use hub_connector_connected() method instead of
	 * the current one?
	 *
	 * @return bool
	 * @since 3.6.0 Added changes after the implementation of TFH on the hub.
	 * @since 3.4.0
	 */
	public function is_site_connected_to_hub(): bool {
		// The case if Pro version is activated, it is TFH account and a site is from 3rd party hosting.
		if ( WP_DEFENDER_PRO_PATH === DEFENDER_PLUGIN_BASENAME && $this->is_another_hosted_site_connected_to_tfh() ) {
			return ! empty( $this->get_api_key() );
		} else {
			$hub_site_id = $this->get_site_id();

			return ! empty( $hub_site_id ) && is_int( $hub_site_id );
		}
	}

	/**
	 * Check if HUB option is disabled, e.g. Global IP.
	 *
	 * @return bool
	 */
	public function is_disabled_hub_option(): bool {
		return ! $this->is_dash_activated() || ! $this->is_site_connected_to_hub();
	}

	/**
	 * Get remote access.
	 */
	public function get_remote_access() {
		// Use backward compatibility.
		if ( WPMUDEV_Dashboard::$version > '4.11.9' ) {
			return WPMUDEV_Dashboard::$settings->get( 'remote_access' );
		} else {
			return WPMUDEV_Dashboard::$site->get_option( 'remote_access' );
		}
	}
	/**
	 * Checks if the Hub connector is connected.
	 *
	 * @return bool True if connected, false otherwise.
	 */
	public static function get_status(): bool {
		return API::get()->is_logged_in();
	}

	/**
	 * Checks if Hub Connector is connected. If Dash plugin is not installed Hub connector can take over.
	 *
	 * @return bool
	 */
	public function hub_connector_connected(): bool {
		if ( $this->is_dash_activated() ) {
			$dash_api  = WPMUDEV_Dashboard::$api;
			$connected = (bool) $dash_api->has_key();
		} else {
			$connected = self::get_status();
		}

		return $connected;
	}

	/**
	 * Upgrade the method to get api key from Dashboard plugin or Hub Connector module.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		if ( $this->is_dash_activated() ) {
			$api_key = WPMUDEV_Dashboard::$api->get_key();
		} else {
			$api_key = API::get()->get_api_key();
		}

		return $api_key;
	}

	/**
	 * Get Membership type.
	 *
	 * @return string
	 */
	public function get_membership_type() {
		if ( $this->is_dash_activated() ) {
			return WPMUDEV_Dashboard::$api->get_membership_status();
		} elseif ( self::get_status() ) {
			return Data::get()->membership_type();
		}
		return 'free';
	}

	/**
	 * Logout from hub.
	 *
	 * @return array|bool|\WP_Error
	 */
	public function logout() {
		return API::get()->logout();
	}

	/**
	 * Check if site is connected to HUB via HCM or Dash.
	 *
	 * @return bool
	 */
	public function is_site_connected_to_hub_via_hcm_or_dash(): bool {
		return $this->hub_connector_connected() || self::get_status();
	}
}