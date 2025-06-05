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
		// Make sure SSO is valid.
		add_action( 'wpmudev_after_remove_allowed_user', array( $this, 'recheck_sso_user' ) );
		// Do hub sync if server properties change.
		add_action( 'admin_init', array( $this, 'sync_on_site_info_change' ) );

		// Disable delete for connected admin.
		add_filter( 'user_row_actions', array( $this, 'maybe_remove_delete_action' ), 1, 2 );
		add_action( 'delete_user', array( $this, 'maybe_abort_admin_delete' ) );
		add_action( 'wpmu_delete_user', array( $this, 'maybe_abort_admin_delete' ) );
		// Set connected admin.
		add_action( 'wpmudev_dashboard_settings_after_set', array( $this, 'handle_permission_changes' ) );
	}

	/**
	 * Disable cron if possible.
	 *
	 * We are making an admin request only to process our actions.
	 * Don't let WP Cron to slow down the request.
	 *
	 * @since 4.11.7
	 *
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
	 * @since 4.11.4
	 *
	 * @param array $plugins Plugin list.
	 *
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
	 * @since 4.11.6
	 *
	 * @uses  admin_url()
	 * @uses  wp_remote_post()
	 * @uses  wp_generate_password()
	 * @uses  set_site_transient()
	 * @uses  delete_site_transient()
	 *
	 * @param array $data Request data.
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
				// string is expected by WpOrg\Requests\Cookie class https://incsub.atlassian.net/browse/WDD-548 ( continuation of wp_remote_post )
				if ( ! is_string( $value ) ) {
					continue;
				}
				$args['cookies'][] = new WP_Http_Cookie(
					array(
						'name'  => $name,
						'value' => $value,
					)
				);
			}
		}

		/**
		 * Override default requests arguments for Utility - send_admin_request.
		 *
		 * @param array $args Default args.
		 * @param array $data Data that being sent in send_admin_request.
		 *
		 * @since  4.11.29
		 *
		 */
		$args = apply_filters( "wpmudev_utils_send_admin_request_args", $args, $data );

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
		 * @since 4.11.6
		 *
		 * @param array $data Request data.
		 *
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
	 * Make sure the user ID is valid for SSO.
	 *
	 * @since 4.11.18
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public function recheck_sso_user( $user_id ) {
		$sso_user_id = WPMUDEV_Dashboard::$settings->get( 'userid', 'sso' );
		// If the removed user id is matching sso user id.
		if ( (int) $sso_user_id === (int) $user_id ) {
			$new_sso_user_id = $this->get_admin_user_for_sso();
			// Set new user id for SSO.
			WPMUDEV_Dashboard::$settings->set( 'userid', $new_sso_user_id, 'sso' );
		}
	}

	/**
	 * Get a admin user id for SSO.
	 *
	 * @since 4.11.18
	 *
	 * @return int
	 */
	public function get_admin_user_for_sso() {
		$user_id = get_current_user_id();
		// If we couldn't find a user.
		if ( empty( $user_id ) ) {
			$users = WPMUDEV_Dashboard::$site->get_allowed_users( true );
			if ( ! empty( $users[0] ) ) {
				$user_id = $users[0];
			}

			// Still empty?.
			if ( empty( $user_id ) ) {
				// Let's get an admin user now.
				$users = WPMUDEV_Dashboard::$site->get_available_users();
				if ( ! empty( $users[0] ) ) {
					$user_id = $users[0]->ID;
				}
			}
		}

		return $user_id;
	}

	/**
	 * Get user id of admin who connected site with Hub.
	 *
	 * This may not be accurate. As a fallback we return first admin from the
	 * allowed admins list.
	 *
	 * @since 4.11.22
	 *
	 * @return int
	 */
	public function get_connected_admin_id() {
		// Get connected admin user id.
		$user_id = WPMUDEV_Dashboard::$settings->get( 'connected_admin', 'general', 0 );

		// If connected admin is not found, use SSO admin id.
		if ( empty( $user_id ) ) {
			$user_id = WPMUDEV_Dashboard::$settings->get( 'userid', 'sso', 0 );
		}

		// If SSO user id is not set, get the first admin from allowed admins list.
		if ( empty( $user_id ) ) {
			$users = WPMUDEV_Dashboard::$site->get_allowed_users( true );
			if ( ! empty( $users[0] ) ) {
				$user_id = $users[0];
			}
		}

		return empty( $user_id ) ? 0 : (int) $user_id;
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
	 * Check if current page is Dashboard's admin page.
	 *
	 * @since 4.11.15
	 *
	 * @return bool
	 */
	public function is_wpmudev_admin_page() {
		$screen = get_current_screen();

		// All dashboard page ids starts with wpmudev.
		return isset( $screen->parent_base ) && 'wpmudev' === $screen->parent_base;
	}

	/**
	 * Rename a folder to new name for backup.
	 *
	 * @since 4.11.9
	 *
	 * @param string $to   New folder name.
	 *
	 * @param string $from Current folder name.
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
	 * @since 4.11.9
	 *
	 * @param string $feature Feature name.
	 *
	 * @return bool
	 */
	public function can_access_feature( $feature ) {
		$is_hosted_third_party = WPMUDEV_Dashboard::$api->is_hosted_third_party();
		$membership_type       = WPMUDEV_Dashboard::$api->get_membership_status();

		// Items not allowed for free users.
		$free_disallow = array( 'plugins', 'support', 'whitelabel', 'translations' );

		return ( 'free' !== $membership_type && ! $is_hosted_third_party ) || ! in_array( $feature, $free_disallow, true );
	}

	/**
	 * Get site information.
	 *
	 * Get site and server properties to show in Hub widget.
	 *
	 * @since 4.11.19
	 *
	 * @return bool
	 */
	public function get_site_info() {
		global $wp_version;

		$php_memory = '';

		// No fatal errors please.
		if (
			class_exists( 'WP_Site_Health' )
			&& method_exists( 'WP_Site_Health', 'get_instance' )
			&& property_exists( 'WP_Site_Health', 'php_memory_limit' )
		) {
			$php_memory = WP_Site_Health::get_instance()->php_memory_limit;
		}

		// Prepare info.
		$info = array(
			'wp_version'   => $wp_version,
			'php_version'  => phpversion(),
			'wp_debug'     => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'php_memory'   => $php_memory,
			'is_multisite' => is_multisite(),
		);

		// Add site health data.
		$info = $this->set_site_health_issue_counts( $info );

		/**
		 * Filter hook to modify site info data.
		 *
		 * @since 4.11.19
		 *
		 * @param array $info Info.
		 */
		return apply_filters( 'wpmudev_dashboard_get_site_info', $info );
	}

	/**
	 * Set site health data.
	 *
	 * @param array $info Info data.
	 *
	 * @since 4.11.19
	 *
	 * @return array
	 */
	public function set_site_health_issue_counts( $info = array() ) {
		// Get site health issues count.
		$issues = get_transient( 'health-check-site-status-result' );
		if ( ! empty( $issues ) ) {
			$issues = json_decode( $issues, true );
		}

		// Add all issues count separately.
		$info['good_issues_count']        = $issues['good'] ?? 0;
		$info['recommended_issues_count'] = $issues['recommended'] ?? 0;
		$info['critical_issues_count']    = $issues['critical'] ?? 0;
		// For backward compatibility.
		$info['issues_total'] = $info['recommended_issues_count'] + $info['critical_issues_count'];

		return $info;
	}

	/**
	 * Do a hub sync when site info changes.
	 *
	 * @since 4.11.19
	 *
	 * @return void
	 */
	public function sync_on_site_info_change() {
		// Get previous info.
		$previous = WPMUDEV_Dashboard::$settings->get( 'site_info', 'general', array() );
		// Get current site info.
		$current = $this->get_site_info();
		if ( $current !== $previous ) {
			// Do hub sync to update on Hub.
			WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();

			WPMUDEV_Dashboard::$settings->set( 'site_info', $current, 'general' );

			/**
			 * Action hook to trigger on site info change.
			 *
			 * @since 4.11.19
			 *
			 * @param array $previous Previous info.
			 * @param array $current  Current info.
			 */
			do_action( 'wpmudev_dashboard_site_info_changed', $previous, $current );
		}
	}

	/**
	 * Remove delete row action for main admin user.
	 *
	 * @since 4.11.22
	 *
	 * @param array   $actions     Actions.
	 * @param WP_User $user_object User object.
	 *
	 * @return array
	 */
	public function maybe_remove_delete_action( $actions, $user_object ) {
		$admin_id = WPMUDEV_Dashboard::$utils->get_connected_admin_id();

		// Remove if main admin.
		if ( isset( $user_object->ID ) && $user_object->ID === $admin_id ) {
			unset( $actions['delete'] );
		}

		return $actions;
	}

	/**
	 * Abort user delete if main admin is being deleted.
	 *
	 * @since 4.11.22
	 *
	 * @param int $user_id ID of the user to delete.
	 *
	 * @return void
	 */
	public function maybe_abort_admin_delete( $user_id ) {
		$admin_id = WPMUDEV_Dashboard::$utils->get_connected_admin_id();
		// Get user object.
		$user = get_userdata( $user_id );

		// Abort if main admin.
		if ( $user instanceof WP_User && $user_id === $admin_id ) {
			wp_die(
				sprintf(
				/* translators: %s: is for name */
					__( 'Sorry, you are not allowed to delete user: <strong>%s</strong>.', 'wpmudev' ),
					$user->user_login
				)
			);
		}
	}

	/**
	 * When allowed users list is changed, set connected admin.
	 *
	 * @since 4.11.22
	 *
	 * @param string $key Updated option key.
	 *
	 * @return void
	 */
	public function handle_permission_changes( $key ) {
		if ( 'limit_to_user' !== $key ) {
			return;
		}

		// Get current allowed admins list.
		$allowed_users = (array) WPMUDEV_Dashboard::$settings->get( 'limit_to_user', 'general', array() );
		// Get current connected admin.
		$connected_admin = WPMUDEV_Dashboard::$settings->get( 'connected_admin', 'general', 0 );

		// Set connected admin if required.
		if ( ! empty( $allowed_users[0] ) && ( empty( $connected_admin ) || ! in_array( $connected_admin, $allowed_users, true ) ) ) {
			WPMUDEV_Dashboard::$settings->set( 'connected_admin', $allowed_users[0], 'general' );
		}
	}
}