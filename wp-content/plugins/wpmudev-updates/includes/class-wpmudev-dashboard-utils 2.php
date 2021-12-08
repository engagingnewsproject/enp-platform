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
		// Handle admin action request.
		add_action( 'admin_post_nopriv_wpmudev_dashboard_admin_request', array( $this, 'run_admin_request' ) );
		// Clear staff flag on logout.
		add_action( 'wp_logout', array( $this, 'unset_staff_flag' ) );
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
	 * @param string $action Action name.
	 * @param string $from   From (remote or cron).
	 * @param array  $params Parameters.
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
	public function send_admin_request( $action, $from = 'remote', $params = array() ) {
		// Create a random hash.
		$hash = md5( wp_generate_password() );
		// Create nonce.
		$nonce = wp_create_nonce( 'wpmudev_dashboard_admin_request' );

		// Set data in cache.
		set_site_transient(
			$hash,
			array(
				'action' => $action,
				'params' => $params,
				'from'   => $from,
			),
			120 // Expire it after 2 minutes in case we couldn't delete it.
		);

		// Make post request.
		$response = wp_remote_post(
			admin_url( 'admin-post.php' ),
			array(
				'blocking' => true,
				'timeout'  => 15,
				'body'     => array(
					'action' => 'wpmudev_dashboard_admin_request',
					'nonce'  => $nonce,
					'hash'   => $hash,
				),
			)
		);

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
					'message' => __( 'Nonce check failed', 'wpmudev' ),
				)
			);
		}

		// Get request data from cache.
		$data = get_site_transient( $hash );

		// Make sure action and params are set.
		if ( ! isset( $data['action'], $data['params'], $data['from'] ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_request',
					'message' => __( 'Invalid request', 'wpmudev' ),
				)
			);
		}

		/**
		 * Run admin action after http request is processed.
		 *
		 * @param string $action Action name.
		 * @param array  $params Params.
		 * @param string $from   From (remote or cron).
		 *
		 * @since 4.11.6
		 */
		do_action(
			'wpmudev_dashboard_admin_action',
			$data['action'],
			$data['params'],
			$data['from']
		);
	}

	/**
	 * Clear staff flag cookie on logout.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function unset_staff_flag() {
		unset( $_COOKIE['wpmudev_is_staff'] );
	}
}