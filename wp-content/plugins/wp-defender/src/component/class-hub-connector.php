<?php
/**
 * Hub Connector class.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WPMUDEV\Hub\Connector;
use Calotes\Base\Component;
use WP_Defender\Traits\Defender_Dashboard_Client;

/**
 * Handles the functionality related to the Hub Connector module.
 *
 * @since 4.10.0
 */
class Hub_Connector extends Component {
	use Defender_Dashboard_Client;

	/**
	 * The identifier for the WP Defender plugin in the Hub.
	 *
	 * @const string
	 */
	public const PLUGIN_IDENTIFIER = 'defender';

	/**
	 * The action name used for the Hub connection.
	 *
	 * @const string
	 */
	public const CONNECTION_ACTION = 'hub_connection';

	/**
	 * Checks if the Hub connector should render its UI.
	 *
	 * Verifies the nonce from the global-ip page to determine if the Hub connector should render its UI.
	 *
	 * @return bool The nonce value on success, false on failure.
	 */
	public static function should_render() {
		return ! self::get_status() && wp_verify_nonce( defender_get_data_from_request( '_def_nonce', 'g' ), self::CONNECTION_ACTION );
	}

	/**
	 * Initialize the Hub Connector module and set its options.
	 *
	 * The `extra/hub-connector/connector.php` file is required, and the options are set for the Hub Connector module.
	 *
	 * @return void
	 */
	public function init() {
		require_once defender_path( 'extra/hub-connector/connector.php' );
		$options = array(
			'screens'    => array(
				'toplevel_page_wp-defender',
				'toplevel_page_wp-defender-network',
				'defender_page_wdf-ip-lockout',
				'defender_page_wdf-ip-lockout-network',
				'defender-pro_page_wdf-ip-lockout',
				'defender-pro_page_wdf-ip-lockout-network',
			),
			'extra_args' => array(
				'register' => array(
					'connect_ref' => self::PLUGIN_IDENTIFIER,
					'utm_medium'  => 'plugin',
					'utm_source'  => self::PLUGIN_IDENTIFIER,
				),
			),
		);
		// Get advanced params.
		$page = defender_get_data_from_request( 'page', 'g' );
		$view = defender_get_data_from_request( 'view', 'g' );

		$result = $this->maybe_summary_box_trigger( $view );
		// Get advanced params.
		$res = $this->get_utm_tags( $page, $result['view'], $result['is_summary'] );
		if ( ! empty( $res['utm_campaign'] ) ) {
			$options['extra_args']['register']['utm_campaign'] = $res['utm_campaign'];
		}
		if ( ! empty( $res['utm_content'] ) ) {
			$options['extra_args']['register']['utm_content'] = $res['utm_content'];
		}

		Connector::get()->set_options( self::PLUGIN_IDENTIFIER, $options );
	}

	/**
	 * Gets the label for the button to connect the site to the Hub.
	 *
	 * @return string The label for the button.
	 */
	public function get_button_label(): string {
		if ( $this->is_dash_installed() && ! $this->is_dash_activated() ) {
			return __( 'Activate WPMU DEV Dashboard', 'wpdef' );
		}
		if ( $this->is_dash_activated() ) {
			return __( 'Log in to WPMU DEV', 'wpdef' );
		}
		return __( 'Connect site for Full protection', 'wpdef' );
	}

	/**
	 * Modify text string vars.
	 *
	 * @param array  $texts  Vars.
	 * @param string $plugin Plugin identifier.
	 *
	 * @return array
	 */
	public static function customize_text_vars( $texts, $plugin ): array {
		if ( self::PLUGIN_IDENTIFIER === $plugin ) {
			$feature_name                 = self::get_feature_name();
			$texts['create_account_desc'] = sprintf(
				/* translators: 1. Feature name. 2. Opened tag. 3. Closed tag. */
				esc_html__( 'Create a free account to connect your site to WPMU DEV and activate %1$s. %2$sIt`s fast, seamless, and free%3$s.', 'wpdef' ),
				'<strong>' . __( 'Defender - ', 'wpdef' ) . $feature_name . '</strong>',
				'<i>',
				'</i>'
			);
			$texts['login_desc'] = sprintf(
				/* translators: %s: Feature name. */
				esc_html__( 'Log in with your WPMU DEV account credentials to activate %s.', 'wpdef' ),
				$feature_name
			);
		}

		return $texts;
	}

	/**
	 * Get feature's name.
	 *
	 * @return string
	 */
	public static function get_feature_name(): string {
		$feature_name = defender_get_data_from_request( 'module_name', 'g' );
		if ( ! empty( $feature_name ) ) {
			return $feature_name;
		}

		$view = defender_get_data_from_request( 'view', 'g' );
		switch ( $view ) {
			case 'blocklist':
				return __( 'Custom IP Allow/Block list', 'wpdef' );
			default:
				return __( 'AntiBot Global Firewall', 'wpdef' );
		}
	}

	/**
	 * Get UTM tags.
	 *
	 * @param string $page The page to load.
	 * @param string $view The view to load.
	 * @param bool   $is_summary Is this from the Summary section? Default false.
	 *
	 * @return array
	 */
	private function get_utm_tags( string $page = '', string $view = '', bool $is_summary = false ): array {
		$utm_campaign = '';
		$utm_content  = '';
		if ( ! empty( $page ) ) {
			switch ( $page ) {
				// There are buttons on notice or widget on the Dashboard page.
				case 'wp-defender':
					$utm_content  = 'hub-connector';
					$utm_campaign = ( 'dashboard' === $view )
						? 'defender_dashboard_firewall_antibot'
						: 'defender_onboarding_antibot';
					break;
				case 'wdf-ip-lockout':
					$utm_content = 'hub-connector';
					if ( $is_summary ) {
						$utm_campaign = 'defender_firewall_antibot_summary';
					} else {
						$utm_campaign = ( 'blocklist' === $view )
							? 'defender_firewall_centralip'
							: 'defender_firewall_antibot';
					}
					break;
				default:
					break;
			}
		}

		return array(
			'utm_campaign' => $utm_campaign,
			'utm_content'  => $utm_content,
		);
	}

	/**
	 * Update data if a trigger is the Summary section.
	 *
	 * @param string $view The view to load.
	 *
	 * @return array
	 */
	private function maybe_summary_box_trigger( string $view ): array {
		return array(
			'view'       => 'summary-box' === $view ? 'global-ip' : $view,
			'is_summary' => 'summary-box' === $view,
		);
	}

	/**
	 * Retrieve the Hub connector URL.
	 *
	 * @param string $page Optional. The page to load. Default is empty string.
	 * @param string $view Optional. The view to load. Default is empty string.
	 *
	 * @return string
	 */
	public function get_url( string $page = '', string $view = '' ): string {
		if ( $this->is_dash_activated() ) {
			$args = array(
				'page'       => 'wpmudev',
				'utm_source' => self::PLUGIN_IDENTIFIER,
				'utm_medium' => 'plugin',
			);

			$result = $this->maybe_summary_box_trigger( $view );
			// Get advanced params.
			$res = $this->get_utm_tags( $page, $result['view'], $result['is_summary'] );
			if ( ! empty( $res['utm_campaign'] ) ) {
				$args['utm_campaign'] = $res['utm_campaign'];
			}
			if ( ! empty( $res['utm_content'] ) ) {
				$args['utm_content'] = $res['utm_content'];
			}

			return add_query_arg( $args, network_admin_url( 'admin.php' ) );
		}

		$query = array(
			'_def_nonce' => wp_create_nonce( self::CONNECTION_ACTION ),
		);
		if ( ! empty( $page ) ) {
			$query['page'] = $page;
		}
		if ( ! empty( $view ) ) {
			$query['view'] = $view;
		}

		return add_query_arg( $query, network_admin_url( 'admin.php' ) );
	}
}