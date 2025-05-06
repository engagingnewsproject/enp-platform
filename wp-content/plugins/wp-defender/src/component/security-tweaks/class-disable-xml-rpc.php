<?php
/**
 * Responsible for disabling XML-RPC functionality in WordPress.
 *
 * @package WP_Defender\Component\Security_Tweaks
 */

namespace WP_Defender\Component\Security_Tweaks;

use WP_Defender\Traits\IO;
use WP_Defender\Behavior\WPMUDEV;

/**
 * XML-RPC can be a security risk if not properly managed, and this class provides methods to disable it and ensure it
 *  remains disabled.
 */
class Disable_XML_RPC extends Abstract_Security_Tweaks {

	use IO;

	/**
	 * Unique identifier for the tweak.
	 *
	 * @var string $slug
	 */
	public string $slug = 'disable-xml-rpc';

	/**
	 * Indicates whether the issue has been resolved.
	 *
	 * @var bool
	 */
	public bool $resolved = false;

	/**
	 * This class contains everything relate to WPMUDEV.
	 *
	 * @var WPMUDEV|null
	 */
	private ?WPMUDEV $wpmudev;

	/**
	 * Class constructor to initialize the Disable_XML_RPC component.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->wpmudev = wd_di()->get( WPMUDEV::class );
	}

	/**
	 * This function processes the request to disable XML-RPC.
	 *
	 * @return bool Returns true if the function successfully executes.
	 */
	public function process(): bool {
		// Update the site option for the current instance of the class to "ON".
		update_site_option( $this->slug, 'ON' );

		// Return true to indicate that the function executed successfully.
		return true;
	}

	/**
	 * Reverts the XML-RPC toggle to the "off" state.
	 *
	 * @return bool Returns true if the function successfully executes.
	 */
	public function revert(): bool {
		// Set the XML-RPC toggle to "OFF".
		update_site_option( $this->slug, 'OFF' );

		// Return true to indicate that the function executed successfully.
		return true;
	}

	/**
	 * Shield up. It runs on every page load when xml-rpc is disabled.
	 *
	 * @return void
	 */
	public function shield_up(): void {
		// Check if this tweak has been enabled.
		$this->resolved = $this->check();
		// if this tweak is ON, block XML-RPC.
		if ( $this->resolved ) {
			$this->add_hooks();
		}
	}

	/**
	 * Queue hooks when this class init.
	 *
	 * @return void
	 */
	public function add_hooks(): void {
		// Disable XML-RPC methods that require authentication.
		add_filter( 'xmlrpc_enabled', '__return_false' );
		// Class used for handling XML-RPC requests.
		add_filter( 'wp_xmlrpc_server_class', array( $this, 'send_forbidden_response' ) );
		// Methods exposed by the XML-RPC server.
		add_filter( 'xmlrpc_methods', '__return_empty_array' );
	}

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check(): bool {
		return $this->is_tweak_enabled_in_site();
	}

	/**
	 * Re-check the XML-RPC status.
	 *
	 * @return bool
	 */
	public function recheck(): bool {
		// Check if XML-RPC is disabled on Defender (plugin) side.
		$plugin_xmlrpc_status = $this->is_tweak_enabled_in_site();
		// Check if user is using WPMUDEV hosting.
		if ( $this->wpmudev->is_wpmu_hosting() ) {
			return $this->determine_status( $plugin_xmlrpc_status, $this->is_tweak_enabled_in_server() ) !== 3;
		}
		// Return the status of XML-RPC.
		return $plugin_xmlrpc_status;
	}

	/**
	 * Check whether the tweak is enabled or not in the site.
	 *
	 * @return bool
	 */
	public function is_tweak_enabled_in_site(): bool {
		return get_site_option( $this->slug ) === 'ON';
	}

	/**
	 * Check whether the tweak is enabled or not in the server.
	 *
	 * @return bool
	 */
	public function is_tweak_enabled_in_server(): bool {
		// Check if the current site is hosted by WPMU DEV.
		if ( ! $this->wpmudev->is_wpmu_hosting() ) {
			// If not, return true to indicate that the tweak is enabled by default.
			return true;
		}

		$server_xmlrpc_status = defender_get_hosting_feature_state( 'xmlrpc_block' );
		if ( '' === $server_xmlrpc_status ) {
			// Something went wrong.
			return false;
		}
		// Return whether the XML-RPC is enabled or disabled based on the XML-RPC status.
		return ! $server_xmlrpc_status;
	}

	/**
	 * Determines the status of the XML-RPC feature based on plugin and hosting settings.
	 *
	 * @param bool $is_plugin_xmlrpc_enabled Indicates if the plugin's XML-RPC is enabled.
	 * @param bool $is_hosting_xmlrpc_enabled Indicates if the hosting's XML-RPC is enabled.
	 *
	 * @return int The status of the XML-RPC feature.
	 */
	public function determine_status( bool $is_plugin_xmlrpc_enabled, bool $is_hosting_xmlrpc_enabled ): int {
		if ( $is_plugin_xmlrpc_enabled && ! $is_hosting_xmlrpc_enabled ) {
			return 1; // Case 1: Defender ON, Hosting OFF.
		}

		if ( ! $is_plugin_xmlrpc_enabled && $is_hosting_xmlrpc_enabled ) {
			return 2; // Case 2: Defender OFF, Hosting ON.
		}

		if ( ! $is_plugin_xmlrpc_enabled && ! $is_hosting_xmlrpc_enabled ) {
			return 3; // Case 3: Defender OFF, Hosting OFF.
		}

		// If ON both sides then turn it OFF on Defender side.
		$this->revert();
		return 2; // Case 2: Defender OFF, Hosting ON.
	}

	/**
	 * Send a 403 Forbidden response and terminate the script.
	 *
	 * @return void
	 */
	public function send_forbidden_response(): void {
		http_response_code( 403 );
		exit( esc_html__( 'Forbidden', 'wpdef' ) );
	}

	/**
	 * Retrieve the tweak's label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return esc_html__( 'Disable XML-RPC', 'wpdef' );
	}

	/**
	 * Get the error reason.
	 *
	 * @return string
	 */
	public function get_error_reason(): string {
		return esc_html__( 'XML-RPC is currently enabled.', 'wpdef' );
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array(): array {
		// The message to display if the XML-RPC feature is disabled.
		$enabled = esc_html__( 'XML-RPC is disabled, great job!', 'wpdef' );
		// Determine if the XML-RPC feature is enabled on the server side.
		$in_server = $this->is_tweak_enabled_in_server();
		// Determine if the XML-RPC feature is enabled on the plugin side.
		$in_site = $this->is_tweak_enabled_in_site();

		// If the site is not hosted on WPMU DEV's hosting, return the summary data.
		if ( $this->wpmudev->is_wpmu_hosting() && $this->wpmudev->is_dash_activated() && $this->wpmudev->get_site_id() ) {
			// If the site is hosted on WPMU DEV's hosting, construct the URL to the hosting settings page.
			$url = 'https://wpmudev.com/hub2/site/' . $this->wpmudev->get_site_id() . '/hosting/tools#block-xml-rpc';

			// If the XML-RPC feature is enabled on the server side, construct the message to display.
			if ( $in_server ) {
				if ( $this->wpmudev->is_whitelabel_enabled() ) {
					$enabled = esc_html__( 'You can manage the XML-RPC settings in the Hosting Settings.', 'wpdef' );
				} else {
					$enabled = sprintf(
						/* translators: %1$s: link to admin page, %2$s: closing link tag. */
						esc_html__( 'You can manage XML-RPC settings from Hosting Settings by going to %1$sthis link%2$s.', 'wpdef' ),
						'<a target="_blank" href="' . $url . '">',
						'</a>'
					);
				}
			} elseif ( $this->wpmudev->is_whitelabel_enabled() ) {
				$enabled = esc_html__( 'XML-RPC is currently disabled on plugin side.', 'wpdef' );
			} else {
				// If the XML-RPC feature is disabled on the plugin side but enabled on the server side, construct the message to display.
				$enabled = sprintf(
					/* translators: %1$s: link to admin page, %2$s: closing link tag. */
					esc_html__( 'XML-RPC is currently disabled on plugin side, we suggest disabling it from server side by clicking on %1$sthis link%2$s.', 'wpdef' ),
					'<a target="_blank" href="' . $url . '">',
					'</a>'
				);
			}

			// Return the summary data.
			return array(
				'slug'             => $this->slug,
				'title'            => $this->get_label(),
				'errorReason'      => $this->get_error_reason(),
				'successReason'    => $enabled,
				'misc'             => array(
					'show_revert_button' => $this->determine_status( $in_site, $in_server ) !== 2,
				),
				'bulk_description' => esc_html__( 'In the past, there were security concerns with XML-RPC so we recommend making sure this feature is fully disabled if you don’t need it active. We will disable XML-RPC for you.', 'wpdef' ),
				'bulk_title'       => 'XML-RPC',
			);
		}

		return array(
			'slug'             => $this->slug,
			'title'            => $this->get_label(),
			'errorReason'      => $this->get_error_reason(),
			'successReason'    => $enabled,
			'misc'             => array(
				'show_revert_button' => $in_site,
			),
			'bulk_description' => esc_html__( 'In the past, there were security concerns with XML-RPC so we recommend making sure this feature is fully disabled if you don’t need it active. We will disable XML-RPC for you.', 'wpdef' ),
			'bulk_title'       => 'XML-RPC',
		);
	}
}