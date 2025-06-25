<?php
/**
 * Class that handles ajax requests.
 *
 * @link    https://wpmudev.com
 * @since   4.11.6
 * @author  Joel James <joel@incsub.com>
 * @package WPMUDEV_Dashboard_Ajax
 */

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class WPMUDEV_Dashboard_Ajax
 */
class WPMUDEV_Dashboard_Ajax {

	/**
	 * Available action names.
	 *
	 * @var string[] $actions
	 */
	private $actions = array(
		'wdp-get-project',
		'wdp-project-activate',
		'wdp-project-deactivate',
		'wdp-project-update',
		'wdp-project-install',
		'wdp-project-install-activate',
		'wdp-project-install-upfront',
		'wdp-translation-update',
		'wdp-projectsearch',
		'wdp-usersearch',
		'wdp-sitesearch',
		'wdp-save-setting',
		'wdp-save-setting-bool',
		'wdp-save-setting-int',
		'wdp-show-popup',
		'wdp-changelog',
		'wdp-credentials',
		'wdp-analytics',
		'wdp-project-delete',
		'wdp-hub-sync',
		'wdp-project-upgrade-free',
		'wdp-login-success',
		'wdp-sso-status',
		'wdp-dismiss-highlights',
		'wdp-reset-settings',
		'wdp-dismiss-upsell',
		'wdp-extend-upsell',
	);

	/**
	 * Available nopriv action names.
	 *
	 * @var string[] $nopriv_actions
	 */
	private $nopriv_actions = array(
		'wdpunauth',
		'wdpsso_step1',
		'wdpsso_step2',
	);

	/**
	 * Available action names which can be bypassed.
	 *
	 * @var string[] $bypass_actions
	 */
	private $bypass_actions = array(
		'changelog',
		'analytics',
	);

	/**
	 * WPMUDEV_Dashboard_Ajax constructor.
	 *
	 * @since 4.11.6
	 */
	public function __construct() {
		// Register all ajax requests.
		foreach ( $this->actions as $action ) {
			add_action( "wp_ajax_$action", array( $this, 'process' ) );
		}

		// Register nopriv ajax actions.
		foreach ( $this->nopriv_actions as $action ) {
			add_action( "wp_ajax_$action", array( $this, 'nopriv_process' ) );
			add_action( "wp_ajax_nopriv_$action", array( $this, 'nopriv_process' ) );
		}

		// AUTO login ajax (nonce protected, it's called by our auto install server).
		add_action( 'wp_ajax_wdp-dashboard-autologin', array( $this, 'dashboard_autologin' ) );
	}

	/**
	 * Entry point for all ajax requests of the plugin.
	 *
	 * All ajax handlers point to this function instead of an individual
	 * callback function; this function validates the user before processing the
	 * actual request.
	 *
	 * @since  4.0.0
	 * @since  4.11.6
	 * @internal
	 */
	public function process() {
		// Make sure required items are found.
		if ( empty( $_REQUEST['action'] ) || empty( $_REQUEST['hash'] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Required field missing', 'wpmudev' ) )
			);
		}

		// Get action name.
		$action = str_replace( 'wdp-', '', $_REQUEST['action'] ); // phpcs:ignore
		// Get nonce.
		$nonce = $_REQUEST['hash']; // phpcs:ignore

		// Do nothing if the nonce is invalid.
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Something went wrong, please refresh the page and try again.', 'wpmudev' ) )
			);
		}

		// Do nothing if the user is not allowed to use the Dashboard. Exception for specific ajax actions.
		if ( ! in_array( $action, $this->bypass_actions, true ) && ! WPMUDEV_Dashboard::$site->allowed_user() ) {
			wp_send_json_error(
				array( 'message' => __( 'Sorry, you are not allowed to do this.', 'wpmudev' ) )
			);
		}

		// Method names should contain only underscores.
		$method = str_replace( '-', '_', $action );

		if ( method_exists( $this, $method ) ) {
			// Execute request action.
			call_user_func( array( $this, $method ) );
		} else {
			$this->send_json_error(
				array(
					'message' => sprintf(
					// translators: %s action name.
						__( 'Unknown action: %s', 'wpmudev' ),
						esc_html( $action )
					),
				)
			);
		}

		// When the method did not send a response assume error.
		wp_send_json_error(
			array( 'message' => __( 'Unexpected action, we could not handle it.', 'wpmudev' ) )
		);
	}

	/**
	 * Entry point for all public ajax requests of the plugin.
	 *
	 * All Ajax handlers point to this function instead of an individual
	 * callback function; These functions are available even when logged out.
	 *
	 * @since  4.0.0
	 * @internal
	 */
	public function nopriv_process() {
		// Do nothing if function was called incorrectly.
		if ( empty( $_REQUEST['action'] ) ) { // phpcs:ignore
			wp_send_json_error(
				array( 'message' => __( 'Required field missing', 'wpmudev' ) )
			);
		}

		// Get action name.
		$method = str_replace( '-', '_', $_REQUEST['action'] ); // phpcs:ignore

		if ( method_exists( $this, $method ) ) {
			// Execute request action.
			call_user_func( array( $this, $method ) );
		} else {
			$this->send_json_error(
				array(
					'message' => sprintf(
					// translators: %s action name.
						__( 'Unknown action: %s', 'wpmudev' ),
						esc_html( $_REQUEST['action'] ) // phpcs:ignore
					),
				)
			);
		}

		// When the method did not send a response assume error.
		wp_send_json_error();
	}

	/**
	 * Get a single project rendered html.
	 *
	 * @since 4.11.6
	 * @return void
	 */
	public function get_project() {
		if ( ! empty( $_REQUEST['pid'] ) ) { // phpcs:ignore
			WPMUDEV_Dashboard::$ui->render_project( intval( $_REQUEST['pid'] ) ); // phpcs:ignore
		}
	}

	/**
	 * Get a single project rendered html.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function check_updates() {
		WPMUDEV_Dashboard::$settings->set( 'refresh_profile', true, 'flags' );
		WPMUDEV_Dashboard::$api->refresh_projects_data();
		WPMUDEV_Dashboard::$site->refresh_local_projects( 'remote' );

		$this->send_json_success();
	}

	/**
	 * Activate a single project.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function project_activate() {
		$pid        = isset( $_REQUEST['pid'] ) ? intval( $_REQUEST['pid'] ) : 0; // phpcs:ignore
		$is_network = isset( $_REQUEST['is_network'] ) && 1 === intval( $_REQUEST['is_network'] ); // phpcs:ignore

		if ( ! empty( $pid ) ) {
			$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
			if ( empty( $local ) ) {
				$this->send_json_error( array( 'message' => __( 'Not installed', 'wpmudev' ) ) );
			}

			// Only plugins.
			if ( 'plugin' === $local['type'] ) {
				activate_plugins( $local['filename'], '', $is_network );
			}
			WPMUDEV_Dashboard::$site->clear_local_file_cache();
			WPMUDEV_Dashboard::$ui->render_project( $pid, false, false, true );
		}
	}

	/**
	 * Deactivate a single project.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function project_deactivate() {
		$pid        = isset( $_REQUEST['pid'] ) ? intval( $_REQUEST['pid'] ) : 0; // phpcs:ignore
		$is_network = isset( $_REQUEST['is_network'] ) && 1 === intval( $_REQUEST['is_network'] ); // phpcs:ignore

		if ( ! empty( $pid ) ) {
			$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
			if ( empty( $local ) ) {
				$this->send_json_error( array( 'message' => __( 'Not installed', 'wpmudev' ) ) );
			}

			if ( 'plugin' === $local['type'] && 119 !== $pid ) {
				deactivate_plugins( $local['filename'], '', $is_network );
			}

			WPMUDEV_Dashboard::$site->clear_local_file_cache();
			WPMUDEV_Dashboard::$ui->render_project( $pid, false, false, true );
		}
	}

	/**
	 * Update translation for a project.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function translation_update() {
		if ( isset( $_REQUEST['slug'] ) ) { // phpcs:ignore
			// Update translation.
			$success = WPMUDEV_Dashboard::$upgrader->upgrade_translation( $_REQUEST['slug'] ); // phpcs:ignore

			if ( $success ) {
				WPMUDEV_Dashboard::$site->clear_local_file_cache();
				$this->send_json_success();
			}

			$err = WPMUDEV_Dashboard::$upgrader->get_error();

			$this->send_json_error( $err );

		}
	}

	/**
	 * Install a project.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function project_install() {
		$pid = isset( $_REQUEST['pid'] ) ? intval( $_REQUEST['pid'] ) : 0; // phpcs:ignore

		if ( ! empty( $pid ) ) {
			$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
			if ( ! empty( $local ) ) {
				$this->send_json_error( array( 'message' => __( 'Already installed', 'wpmudev' ) ) );
			}

			if ( WPMUDEV_Dashboard::$site->maybe_replace_free_with_pro( $pid ) ) {
				WPMUDEV_Dashboard::$ui->render_project(
					$pid,
					false,
					'popup-after-install'
				);
			}
		}
	}

	/**
	 * Install and activate a project.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function project_install_activate() {
		$pid        = isset( $_REQUEST['pid'] ) ? intval( $_REQUEST['pid'] ) : 0; // phpcs:ignore
		$is_network = isset( $_REQUEST['is_network'] ) && 1 === intval( $_REQUEST['is_network'] ); // phpcs:ignore

		if ( ! empty( $pid ) ) {
			// Check if project is already installed.
			$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
			if ( empty( $local ) ) {
				// Install if not installed.
				if ( WPMUDEV_Dashboard::$site->maybe_replace_free_with_pro( $pid ) ) {
					// Get project data.
					$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
				}
			}

			// Can not continue.
			if ( empty( $local['filename'] ) ) {
				$this->send_json_error( array( 'message' => __( 'Could not install', 'wpmudev' ) ) );
			}

			// Activate the plugin.
			activate_plugins( $local['filename'], '', $is_network );
			// Clear cache.
			WPMUDEV_Dashboard::$site->clear_local_file_cache();
			// Render project.
			WPMUDEV_Dashboard::$ui->render_project( $pid, false, false, true );
		}
	}

	/**
	 * Update a project.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function project_update() {
		$pid = isset( $_REQUEST['pid'] ) ? intval( $_REQUEST['pid'] ) : 0; // phpcs:ignore

		if ( ! empty( $pid ) ) {
			$success = WPMUDEV_Dashboard::$upgrader->upgrade( $pid );

			if ( ! $success ) {
				$err = WPMUDEV_Dashboard::$upgrader->get_error();
				$this->send_json_error( $err );
			}

			WPMUDEV_Dashboard::$site->clear_local_file_cache();
			WPMUDEV_Dashboard::$ui->render_project( $pid );
		}
	}

	/**
	 * Delete a project.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function project_delete() {
		$pid = isset( $_REQUEST['pid'] ) ? intval( $_REQUEST['pid'] ) : 0; // phpcs:ignore

		if ( ! empty( $pid ) ) {
			if ( WPMUDEV_Dashboard::$upgrader->delete_plugin( $pid ) ) {
				WPMUDEV_Dashboard::$site->clear_local_file_cache();
				WPMUDEV_Dashboard::$ui->render_project( $pid, false );
			} else {
				$err = WPMUDEV_Dashboard::$upgrader->get_error();
				$this->send_json_error( $err );
			}
		}
	}

	/**
	 * Process user account search.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function usersearch() {
		$items = array();

		if ( ! empty( $_REQUEST['q'] ) ) { // phpcs:ignore
			$users = WPMUDEV_Dashboard::$site->get_potential_users( $_REQUEST['q'] ); // phpcs:ignore
			foreach ( $users as $user ) {
				$items[] = array(
					'id'      => $user->id,
					'thumb'   => $user->avatar,
					'label'   => sprintf(
						'<span class="name title">%1$s</span> <span class="email">(%2$s)</span>',
						$user->name,
						$user->email
					),
					'display' => $user->name . ' (' . $user->email . ')',
				);
			}
		}

		$this->send_json_success( $items );
	}

	/**
	 * Process site search.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function sitesearch() {
		$items = array();

		if ( is_multisite() ) {
			if ( ! empty( $_REQUEST['q'] ) ) { // phpcs:ignore
				$args = array(
					'search' => sanitize_text_field( $_REQUEST['q'] ), // phpcs:ignore
					'fields' => 'ids',
				);

				// Get settings.
				$settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
				// Exclude existing sites.
				if ( ! empty( $settings['labels_subsites'] ) ) {
					$args['site__not_in'] = (array) $settings['labels_subsites'];
				}

				// Get site ids.
				$sites = get_sites( $args );
				if ( ! empty( $sites ) ) {
					foreach ( $sites as $site_id ) {
						$items[] = array(
							'id'   => $site_id,
							'text' => str_replace( array( 'https://', 'http://' ), '', get_home_url( $site_id ) ),
						);
					}
				}
			}
		}

		$this->send_json_success( $items );
	}

	/**
	 * Process projects search.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function projectsearch() {
		$items = array();

		$urls = WPMUDEV_Dashboard::$ui->page_urls;

		if ( ! empty( $_REQUEST['q'] ) ) { // phpcs:ignore
			$projects = WPMUDEV_Dashboard::$site->find_projects_by_name( $_REQUEST['q'] ); // phpcs:ignore
			foreach ( $projects as $item ) {
				if ( 'plugin' === $item->type ) {
					$url     = $urls->plugins_url;
					$icon    = '<i class="dev-icon dev-icon-plugin"></i> ';
					$items[] = array(
						'id'    => $item->id,
						'thumb' => $item->logo,
						'label' => sprintf(
							'<a href="%3$s"><span class="name title">%1$s</span> <span class="desc">%2$s</span></a>',
							$icon . $item->name,
							$item->desc,
							$url . '#pid=' . $item->id
						),
					);
				}
			}
		}

		$this->send_json_success( $items );
	}

	/**
	 * Process and save boolean settings.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function save_setting_bool() {
		$this->save_setting( 'bool' );
	}

	/**
	 * Process and save int settings.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function save_setting_int() {
		$this->save_setting( 'int' );
	}

	/**
	 * Process and save settings.
	 *
	 * @param string $type Setting type.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function save_setting( $type = 'array' ) {
		if ( isset( $_REQUEST['name'], $_REQUEST['value'], $_REQUEST['group'] ) ) { // phpcs:ignore
			$name  = sanitize_html_class( $_REQUEST['name'] ); // phpcs:ignore
			$value = $_REQUEST['value']; // phpcs:ignore
			$group = empty( $_REQUEST['group'] ) ? false : $_REQUEST['group'];

			switch ( $type ) {
				case 'bool':
					if ( 'true' === $value || '1' === $value || 'on' === $value || 'yes' === $value ) {
						$value = true;
					} else {
						$value = false;
					}
					break;

				case 'int':
					$value = intval( $value );
					break;
				default:
					break;
			}

			WPMUDEV_Dashboard::$settings->set( $name, $value, $group );
		}

		$this->send_json_success();
	}

	/**
	 * Show project info popup.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function show_popup() {
		$pid  = isset( $_REQUEST['pid'] ) ? intval( $_REQUEST['pid'] ) : 0; // phpcs:ignore
		$type = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : ''; // phpcs:ignore

		if ( ! empty( $type ) && ! empty( $pid ) ) {
			WPMUDEV_Dashboard::$ui->show_popup( $type, $pid );
		}
	}

	/**
	 * Remember FTP credentials in cookie.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function credentials() {
		// Remember credentials for FTP update/installation, for 15 min.
		if ( WPMUDEV_Dashboard::$upgrader->remember_credentials() ) {
			$this->send_json_success();
		} else {
			$this->send_json_error();
		}
	}

	/**
	 * Get analytics data.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function analytics() {
		// Only if user has the access.
		if ( ! WPMUDEV_Dashboard::$site->user_can_analytics() ) {
			$this->send_json_error( array( 'message' => __( 'Unauthorized', 'wpmudev' ) ) );
		}

		// Allowed periods.
		$periods = array( 1, 7, 30, 90 );

		// Get the period value.
		$range = isset( $_REQUEST['range'] ) ? absint( $_REQUEST['range'] ) : 7; // phpcs:ignore
		$range = in_array( $range, $periods, true ) ? $range : 7;

		// Network flag.
		$network = isset( $_REQUEST['network'] ) && (bool) $_REQUEST['network']; // phpcs:ignore

		// Get analytics output type.
		$type = isset( $_REQUEST['type'] ) && 'filtered' === $_REQUEST['type'] ? 'filtered' : 'full'; // phpcs:ignore

		$filtered = array();

		// Get analytics data.
		if ( isset( $_REQUEST['filter_type'], $_REQUEST['filter_value'] ) ) { // phpcs:ignore
			$filtered = $this->get_analytics_filtered( $range, $network );
		}

		// If filtered data.
		if ( 'filtered' === $type ) {
			$data = $filtered;
		} else {
			// Get full data.
			$data = $this->get_analytics_full( $range, $network );
		}

		// Replace current data with filtered data.
		if ( 'full' === $type && ! empty( $filtered ) ) {
			$data['current_data'] = $filtered;
		}

		if ( ! empty( $data ) ) {
			$this->send_json_success( $data );
		} else {
			$this->send_json_error( array( 'message' => __( 'There was an API error, please try again.', 'wpmudev' ) ) );
		}
	}

	/**
	 * Get full analytics data for a selected period.
	 *
	 * @param int  $range   Range.
	 * @param bool $network Network flag.
	 *
	 * @since 4.11.6
	 *
	 * @return array
	 */
	private function get_analytics_full( $range, $network ) {
		// Blog id to filter.
		$blog_id = $network || ! is_multisite() ? 0 : get_current_blog_id();

		// Get the stats data.
		$data = WPMUDEV_Dashboard::$api->analytics_stats_overall( $range, $blog_id );

		// Set final data.
		return array(
			'overall_data' => isset( $data['overall'] ) ? $data['overall'] : array(),
			'current_data' => isset( $data['overall'] ) ? $data['overall'] : array(),
			'pages'        => isset( $data['pages'] ) ? $data['pages'] : array(),
			'authors'      => isset( $data['authors'] ) ? $data['authors'] : array(),
			'sites'        => isset( $data['sites'] ) ? $data['sites'] : array(),
			'autocomplete' => isset( $data['autocomplete'] ) ? $data['autocomplete'] : array(),
		);
	}

	/**
	 * Get filtered analytics data for the period.
	 *
	 * @param int $range Range.
	 *
	 * @since 4.11.6
	 *
	 * @return array
	 */
	private function get_analytics_filtered( $range ) {
		$data = array();

		// Get filtered data.
		if ( isset( $_REQUEST['filter_type'], $_REQUEST['filter_value'] ) ) { // phpcs:ignore
			$data = WPMUDEV_Dashboard::$api->analytics_stats_single( $range, $_REQUEST['filter_type'], $_REQUEST['filter_value'] ); // phpcs:ignore
		}

		return empty( $data ) ? array() : $data;
	}

	/**
	 * Sync site data with Hub using API.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function hub_sync() {
		$key = isset( $_REQUEST['key'] ) ? trim( $_REQUEST['key'] ) : ''; // phpcs:ignore

		if ( ! empty( $key ) ) {
			WPMUDEV_Dashboard::$api->set_key( $key );

			$result = WPMUDEV_Dashboard::$api->hub_sync( false, true );

			if ( ! $result || empty( $result['membership'] ) ) {

				WPMUDEV_Dashboard::$api->set_key( '' );

				if ( false === $result && ( ! isset( $result['limit_exceeded_with_hosting_sites'] ) || ! isset( $result['limit_exceeded_no_hosting_sites'] ) ) ) {
					$this->send_json_error(
						array(
							'redirect' => add_query_arg(
								array( 'connection_error' => '1' ),
								WPMUDEV_Dashboard::$ui->page_urls->dashboard_url
							),
						)
					);
				}

				if ( isset( $result['limit_exceeded_no_hosting_sites'] ) && $result['limit_exceeded_no_hosting_sites'] ) {
					$this->send_json_error(
						array(
							'redirect' => add_query_arg(
								array(
									'site_limit_exceeded'     => '1',
									'site_limit'              => $result['limit_data']['site_limit'],
									'available_hosting_sites' => ( $result['limit_data']['hosted_limit'] - $result['limit_data']['total_hosted'] ),
								),
								WPMUDEV_Dashboard::$ui->page_urls->dashboard_url
							),
						)
					);
				}

				$this->send_json_error(
					array(
						'redirect' => add_query_arg(
							array( 'invalid_key' => '1' ),
							WPMUDEV_Dashboard::$ui->page_urls->dashboard_url
						),
					)
				);
			} else {
				// Valid key.
				global $current_user;
				WPMUDEV_Dashboard::$settings->set( 'limit_to_user', $current_user->ID, 'general' );
				WPMUDEV_Dashboard::$api->refresh_profile();

				/***
				 * Action hook that run after login with WPMUDEV account is successful.
				 *
				 * @param int $user_id Current user ID.
				 *
				 * @since 4.11.2
				 */
				do_action( 'wpmudev_dashboard_after_login_success', $current_user->ID );
			}

			$this->send_json_success(
				array( 'redirect' => add_query_arg( 'view', 'sync-plugins', WPMUDEV_Dashboard::$ui->page_urls->dashboard_url ) )
			);
		}
	}

	/**
	 * Upgrade a free project.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function project_upgrade_free() {
		$pid = isset( $_REQUEST['pid'] ) ? intval( $_REQUEST['pid'] ) : 0; // phpcs:ignore

		if ( ! empty( $pid ) ) {
			if ( WPMUDEV_Dashboard::$site->maybe_replace_free_with_pro( $pid ) ) {
				$this->send_json_success();
			}
		}
	}

	/**
	 * Process login success action.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function login_success() {
		$this->send_json_success(
			array(
				'redirect' => add_query_arg(
					array(
						'view' => 'sync-plugins',
						'show' => 'success',
					),
					WPMUDEV_Dashboard::$ui->page_urls->dashboard_url
				),
			)
		);
	}

	/**
	 * Update SSO status.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function sso_status() {
		if ( ! is_null( $_REQUEST['sso'] ) && ! empty( $_REQUEST['ssoUserId'] ) ) { // phpcs:ignore
			WPMUDEV_Dashboard::$settings->set( 'enabled', absint( $_REQUEST['sso'] ), 'sso' ); // phpcs:ignore
			WPMUDEV_Dashboard::$settings->set( 'userid', absint( $_REQUEST['ssoUserId'] ), 'sso' ); // phpcs:ignore

			$this->send_json_success();
		}
	}

	/**
	 * Dismiss highlights modal dialog.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function dismiss_highlights() {
		// Set dismissal flag.
		WPMUDEV_Dashboard::$settings->set( 'highlights_dismissed', true, 'flags' );

		$this->send_json_success();
	}

	/**
	 * Reset plugin settings back to defaults.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function reset_settings() {
		// Reset settings.
		WPMUDEV_Dashboard::$settings->reset();
		// URL to redirect.
		$url = add_query_arg(
			array(
				'success'        => time() + 10,
				'success-action' => 'reset-settings',
			),
			WPMUDEV_Dashboard::$ui->page_urls->settings_url
		);

		$this->send_json_success(
			array(
				'redirect' => $url . '#data',
			)
		);
	}

	/**
	 * Dismiss upsell modal dialog.
	 *
	 * @since 4.11.15
	 *
	 * @return void
	 */
	public function dismiss_upsell() {
		// Set dismissal flag.
		WPMUDEV_Dashboard::$settings->set( 'upsell_dismissed', true, 'flags' );
		WPMUDEV_Dashboard::$settings->set( 'upsell_notice_time', time(), 'general' );

		$this->send_json_success();
	}

	/**
	 * Extend upsell modal dialog.
	 *
	 * @since 4.11.15
	 *
	 * @return void
	 */
	public function extend_upsell() {
		// Set extension flag.
		WPMUDEV_Dashboard::$settings->set( 'upsell_dismissed', false, 'flags' );
		// Show after 1 week.
		WPMUDEV_Dashboard::$settings->set( 'upsell_notice_time', strtotime( '+1 week' ), 'general' );

		$this->send_json_success();
	}

	/**
	 * Start authentication.
	 *
	 * Required POST params:
	 * - wdpunkey .. Temporary Auth Key from the DB.
	 * - staff    .. Name of the user who loggs in.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function wdpunauth() {
		WPMUDEV_Dashboard::$api->authenticate_remote_access();
	}

	/**
	 * Process authentication step 1.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function wdpsso_step1() {
		$redirect = isset( $_REQUEST['redirect'] ) ? urlencode( $_REQUEST['redirect'] ) : ''; // phpcs:ignore
		$nonce    = isset( $_REQUEST['nonce'] ) ? $_REQUEST['nonce'] : ''; // phpcs:ignore
		$jwttoken = isset( $_REQUEST['jwttoken'] ) ? $_REQUEST['jwttoken'] : ''; // phpcs:ignore
		$apikey   = isset( $_REQUEST['apikey'] ) ? $_REQUEST['apikey'] : ''; // phpcs:ignore
		$hubteam  = isset( $_REQUEST['hubteam'] ) ? $_REQUEST['hubteam'] : ''; // phpcs:ignore

		WPMUDEV_Dashboard::$api->authenticate_sso_access_step1( $redirect, $nonce, $jwttoken, $apikey, $hubteam );
	}

	/**
	 * Process authentication step 2.
	 *
	 * @since 4.11.6
	 *
	 * @return void
	 */
	public function wdpsso_step2() {
		$data = array(
			'incoming_hmac'  => $_REQUEST['outgoing_hmac'] ?? '',
			'token'          => $_REQUEST['token'] ?? '',
			'pre_sso_state'  => $_REQUEST['pre_sso_state'] ?? '',
			'redirect'       => $_REQUEST['redirect'] ?? '',
			'dev_user_id'    => (int) ( $_REQUEST['dev_user_id'] ?? '' ),
			'dev_user_email' => $_REQUEST['dev_user_email'] ?? '',
		);

		WPMUDEV_Dashboard::$api->authenticate_sso_access_step2( $data );
	}

	/**
	 * Autologin to dashboard plugin.
	 * - Hub sync
	 * - Auto upgrade free plugins to pro
	 *
	 * @since 4.11.6
	 */
	public function dashboard_autologin() {
		// nonce verifier.
		$auth_verify_nonce = wp_verify_nonce( ( isset( $_REQUEST['auth_nonce'] ) ? $_REQUEST['auth_nonce'] : '' ), 'auth_nonce' );
		if ( ! $auth_verify_nonce ) {
			$this->send_json_error(
				array(
					'type'    => 'invalid_auth',
					'message' => __( 'Invalid Authentication.', 'wpmudev' ),
				)
			);
		}

		// basic permissions ( even allowed_user have to have this cap anyway ).
		if ( ! current_user_can( ( is_multisite() ? 'manage_network_options' : 'manage_options' ) ) ) {
			$this->send_json_error(
				array(
					'type'    => 'invalid_permission',
					'message' => __( 'Invalid Permission.', 'wpmudev' ),
				)
			);
		}

		// Dash Allowed users only.
		if ( ! WPMUDEV_Dashboard::$site->allowed_user() ) {
			$this->send_json_error(
				array(
					'type'    => 'invalid_allow',
					'message' => __( 'Invalid Permission.', 'wpmudev' ),
				)
			);
		}

		$key               = isset( $_REQUEST['apikey'] ) ? trim( $_REQUEST['apikey'] ) : false;
		$skip_free_upgrade = isset( $_REQUEST['skip_upgrade_free_plugins'] ) ? true : false;

		if ( ! $key ) {
			$this->send_json_error(
				array(
					'type'    => 'invalid_key',
					'message' => __( 'Your API Key was invalid.', 'wpmudev' ),
				)
			);
		}

		$previous_key = '';
		if ( WPMUDEV_Dashboard::$api->has_key() ) {
			$previous_key = WPMUDEV_Dashboard::$api->get_key();
		}

		WPMUDEV_Dashboard::$api->set_key( $key );

		// When we auto install, we will also have the hub_sso_status param available to enable/disable SSO.
		if ( isset( $_REQUEST['hub_sso_status'] ) && ! is_null( $_REQUEST['hub_sso_status'] ) ) {
			WPMUDEV_Dashboard::$settings->set( 'enabled', absint( $_REQUEST['hub_sso_status'] ), 'sso' );
			if ( 1 === absint( $_REQUEST['hub_sso_status'] ) ) {
				WPMUDEV_Dashboard::$settings->set( 'userid', get_current_user_id(), 'sso' );
			}
		}

		$result = WPMUDEV_Dashboard::$api->hub_sync( false, true );
		if ( ! $result || empty( $result['membership'] ) ) {
			// Return to previous key to avoid logout.
			WPMUDEV_Dashboard::$api->set_key( $previous_key );

			if ( false === $result ) {
				$this->send_json_error(
					array(
						'type'    => 'connection_error',
						'message' => __( 'Your server had a problem connecting to WPMU DEV.', 'wpmudev' ),
					)
				);
			}
			$this->send_json_error(
				array(
					'type'    => 'invalid_key',
					'message' => __( 'Your API Key was invalid.', 'wpmudev' ),
				)
			);
		}

		// Valid key.
		global $current_user;
		WPMUDEV_Dashboard::$settings->set( 'limit_to_user', $current_user->ID, 'general' );
		WPMUDEV_Dashboard::$api->refresh_profile();

		// In case timeout use ?skip_upgrade_free_plugins.
		if ( $skip_free_upgrade ) {
			$this->send_json_success(
				array(
					'skip_upgrade_free_plugins' => true,
				)
			);
		}

		// Sync free plugins!, time execution will vary depends on installed plugins and server connection.
		$upgraded_plugins = array();
		$type             = WPMUDEV_Dashboard::$api->get_membership_status();
		if ( 'full' === $type || 'unit' === $type ) {
			$installed_free_projects = WPMUDEV_Dashboard::$site->get_installed_free_projects();

			foreach ( $installed_free_projects as $installed_free_project ) {
				$upgraded_plugin = array(
					'pid'         => $installed_free_project['id'],
					'name'        => $installed_free_project['name'],
					'is_upgraded' => false,
				);
				if ( WPMUDEV_Dashboard::$site->maybe_replace_free_with_pro( $installed_free_project['id'], false ) ) {
					$upgraded_plugin['is_upgraded'] = true;
				}

				$upgraded_plugins[] = $upgraded_plugin;
			}
		}

		$this->send_json_success(
			array(
				'skip_upgrade_free_plugins' => false,
				'upgrade_free_plugins'      => $upgraded_plugins,
			)
		);
	}

	/**
	 * Used by the Getting started wizard on WPMU DEV to programmatically login to the dashboard.
	 *
	 * @since 4.11.6
	 * @deprecated 4.11.17
	 */
	public function ajax_connect() {
		_deprecated_function( __METHOD__, '4.11.17' );
	}

	/**
	 * Clear all output buffers and send an JSON success response.
	 *
	 * @param mixed $data Data to return.
	 *
	 * @since 4.11.6
	 */
	private function send_json_success( $data = null ) {
		$this->send_json( true, $data );
	}

	/**
	 * Clear all output buffers and send an JSON error response.
	 *
	 * @param mixed $data Data to return.
	 *
	 * @since 4.11.6
	 */
	private function send_json_error( $data = null ) {
		$this->send_json( false, $data );
	}

	/**
	 * Clear all output buffers and send an JSON response.
	 *
	 * @param bool  $success Is success.
	 * @param mixed $data    Optional data to return to the Ajax request.
	 *
	 * @since 4.11.6
	 */
	private function send_json( $success = true, $data = null ) {
		while ( ob_get_level() ) {
			ob_get_clean();
		}

		$success ? wp_send_json_success( $data ) : wp_send_json_error( $data );
	}
}