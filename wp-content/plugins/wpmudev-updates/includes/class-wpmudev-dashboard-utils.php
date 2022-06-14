<?php
/**
 * Class that handles utility functionality.
 *
 * @link    https://wpmudev.com
 * @since   4.11.4
 * @author  Joel James <joel@incsub.com>
 * @package WPMUDEV_Dashboard_Utils
 */

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class WPMUDEV_Dashboard_Utils
 */
class WPMUDEV_Dashboard_Utils {

	/**
	 * WPMUDEV_Dashboard_Utils constructor.
	 *
	 * @since 4.11.4
	 */
	public function __construct() {
		// Load Dash plugin first whenever possible.
		add_filter( 'pre_update_option_active_plugins', array( $this, 'set_plugin_priority' ), 9999 );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'set_plugin_priority' ), 9999 );

		// Disable cron if required.
		add_action( 'plugins_loaded', array( $this, 'maybe_disable_cron' ) );
		// Handle admin action request.
		add_action( 'wp_ajax_wpmudev_dashboard_admin_request', array( $this, 'run_admin_request' ) );
		add_action( 'wp_ajax_nopriv_wpmudev_dashboard_admin_request', array( $this, 'run_admin_request' ) );
		// Clear staff flag on logout.
		add_action( 'wp_logout', array( $this, 'unset_staff_flag' ) );
	}

	/**
	 * Disable cron if possible.
	 *
	 * We are making an admin request only to process our actions.
	 * Don't let WP Cron to slow down the request.
	 *
	 * @since 4.11.7
	 * @return void
	 */
	public function maybe_disable_cron() {
		// Disable cron if possible.
		if ( $this->is_wpmudev_admin_request() && ! defined( 'DISABLE_WP_CRON' ) ) {
			define( 'DISABLE_WP_CRON', true );
		}
	}

	/**
	 * Set Dash plugin to load first by updating its position.
	 *
	 * This is the safest method than creating a MU plugin to get
	 * priority in plugin initialization order. Some plugins may change
	 * it, but that's okay.
	 *
	 * @param array $plugins Plugin list.
	 *
	 * @since 4.11.4
	 * @return array
	 */
	public function set_plugin_priority( $plugins ) {
		// Move to top.
		if ( isset( $plugins[ WPMUDEV_Dashboard::$basename ] ) ) {
			// Remove dash plugin.
			unset( $plugins[ WPMUDEV_Dashboard::$basename ] );

			// Set to first.
			return array_merge(
				array( WPMUDEV_Dashboard::$basename => time() ),
				$plugins
			);
		}

		return $plugins;
	}

	/**
	 * Make an self post request to wp-admin.
	 *
	 * Make an HTTP request to our own WP Admin to process admin side actions
	 * specifically hub sync or status updates which requires to be run on wp admin.
	 *
	 * @param array $data Request data.
	 *
	 * @since 4.11.6
	 *
	 * @uses  admin_url()
	 * @uses  wp_remote_post()
	 * @uses  wp_generate_password()
	 * @uses  set_site_transient()
	 * @uses  delete_site_transient()
	 *
	 * @return string|bool
	 */
	public function send_admin_request( $data = array() ) {
		// Create a random hash.
		$hash = md5( wp_generate_password() );
		// Create nonce.
		$nonce = wp_create_nonce( 'wpmudev_dashboard_admin_request' );

		// Set data in cache.
		set_site_transient(
			$hash,
			$data,
			120 // Expire it after 2 minutes in case we couldn't delete it.
		);

		// Request arguments.
		$args = array(
			'blocking'  => true,
			'timeout'   => 45,
			'sslverify' => false,
			'cookies'   => array(),
			'body'      => array(
				'action' => 'wpmudev_dashboard_admin_request',
				'nonce'  => $nonce,
				'hash'   => $hash,
			),
		);

		// Set cookies if required.
		if ( ! empty( $_COOKIE ) ) {
			foreach ( $_COOKIE as $name => $value ) {
				$args['cookies'][] = new WP_Http_Cookie( compact( 'name', 'value' ) );
			}
		}

		// Make post request.
		$response = wp_remote_post( admin_url( 'admin-ajax.php' ), $args );

		// Delete data after getting response.
		delete_site_transient( $hash );

		// If request not failed.
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			// Get response body.
			return wp_remote_retrieve_body( $response );
		}

		return false;
	}

	/**
	 * Handle the post request for processing admin request.
	 *
	 * After verification a hook is triggered so we can use it
	 * to perform admin actions.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function run_admin_request() {
		// Make sure required values are set.
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : ''; // phpcs:ignore
		$hash  = isset( $_POST['hash'] ) ? $_POST['hash'] : ''; // phpcs:ignore

		// Nonce and hash are required.
		if ( empty( $nonce ) || empty( $hash ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_params',
					'message' => __( 'Required parameters are missing', 'wpmudev' ),
				)
			);
		}

		// If nonce check failed.
		if ( ! wp_verify_nonce( $nonce, 'wpmudev_dashboard_admin_request' ) ) {
			wp_send_json_error(
				array(
					'code'    => 'nonce_failed',
					'message' => __( 'Admin request nonce check failed', 'wpmudev' ),
				)
			);
		}

		// Get request data from cache.
		$data = get_site_transient( $hash );

		// Make sure action and params are set.
		if ( false === $data ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_request',
					'message' => __( 'Invalid request.', 'wpmudev' ),
				)
			);
		}

		/**
		 * Process the admin request and send response.
		 *
		 * Always remember to send a json response using wp_send_json_error
		 * or wp_send_json_success.
		 *
		 * @param array $data Request data.
		 *
		 * @since 4.11.6
		 */
		do_action( 'wpmudev_dashboard_admin_request', $data );
	}

	/**
	 * Clear staff flag cookie on logout.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function unset_staff_flag() {
		setcookie( 'wpmudev_is_staff', '', 1 );
	}

	/**
	 * Check if current request is Dashboard's admin request.
	 *
	 * @since 4.11.7
	 *
	 * @return bool
	 */
	private function is_wpmudev_admin_request() {
		// Check if all data is set.
		$is_valid_request = isset( $_POST['action'], $_POST['nonce'], $_POST['hash'] ); // phpcs:ignore

		// Check if wpmudev request.
		return $is_valid_request && 'wpmudev_dashboard_admin_request' === $_POST['action']; // phpcs:ignore
	}

	/**
	 * Rename a folder to new name for backup.
	 *
	 * @param string $from Current folder name.
	 * @param string $to   New folder name.
	 *
	 * @since 4.11.9
	 *
	 * @return bool
	 */
	public function rename_plugin( $from, $to = '' ) {
		// Default backup name.
		$to = empty( $to ) ? $from . '-bak' : $to;

		// Rename plugin folder.
		return rename(
			WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $from,
			WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $to
		);
	}

	/**
	 * Check if a feature can be accessed.
	 *
	 * Currently only free memberships are being checked.
	 *
	 * @param string $feature Feature name.
	 *
	 * @since 4.11.9
	 *
	 * @return bool
	 */
	public function can_access_feature( $feature ) {
		$membership_type = WPMUDEV_Dashboard::$api->get_membership_status();

		// Items not allowed for free users.
		$free_disallow = array( 'plugins', 'support', 'whitelabel', 'translations' );

		return 'free' !== $membership_type || ! in_array( $feature, $free_disallow, true );
	}
}