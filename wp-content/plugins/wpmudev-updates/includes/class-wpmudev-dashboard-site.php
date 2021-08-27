<?php
/**
 * Site module.
 * Manages all access to the local WordPress site;
 * For example: Storing and fetching settings, activating plugins, etc.
 *
 * @since   4.0.0
 * @package WPMUDEV_Dashboard
 */

/**
 * The site-module class.
 */
class WPMUDEV_Dashboard_Site {

	/**
	 * URL to the Dashboard plugin; used to load images, etc.
	 *
	 * @var string (URL)
	 */
	public $plugin_url = '';

	/**
	 * Name of the Dashboard plugin directory (relative to wp-content/plugins)
	 *
	 * @var string (Path)
	 */
	public $plugin_dir = '';

	/**
	 * Full path to the plugin directory
	 *
	 * @var string (Path)
	 */
	public $plugin_path = '';

	/**
	 * The PID of the Upfront root theme.
	 * Upfront is required for our modern themes. This is used to automatically
	 * install Upfront when required.
	 *
	 * @var int (Project ID)
	 */
	public $id_upfront = 938297;

	/**
	 * The PID of the Upfront builder plugin.
	 * Upfront is required for this plugin to function. This is used to automatically
	 * install Upfront when required.
	 *
	 * @var int (Project ID)
	 */
	public $id_upfront_builder = 1107287;

	/**
	 * The PID of our "133 Theme Pack" package.
	 * This package needs some special treatment; since it contains many themes
	 * we need to update the package when only one of those themes changed.
	 *
	 * @var int (Project ID)
	 */
	public $id_farm133_themes = 128;

	/**
	 * This is the highest Project-ID of the legacy themes: If the theme has an
	 * higher ID it means that it requires Upfront.
	 *
	 * @var int (Project ID)
	 */
	public $id_legacy_themes = 237;

	/**
	 * Allows specific private ajax actions to work for non-allowed users
	 *
	 * @var array Ajax actions that non-allowed users can access
	 */
	protected $ajax_allowed_bypasses = array();

	/**
	 * Flag that is tripped to schedule api refresh right before display output (avoid multiple)
	 *
	 * @var bool
	 */
	protected static $_refresh_updates_flag = false;

	/**
	 * Flag that is tripped to schedule api refresh at the end of the page load (avoid multiple)
	 *
	 * @var bool
	 */
	protected static $_refresh_shutdown_flag = false;

	/**
	 * Caches the modified theme-updates transient.
	 *
	 * @var array
	 */
	protected static $_cache_themeupdates = false;

	/**
	 * Caches the modified plugin-updates transient.
	 *
	 * @var array
	 */
	protected static $_cache_pluginupdates = false;

	/**
	 * Caches the modified plugin-translation transient.
	 *
	 * @var array
	 */
	protected static $_cache_translationupdates = false;

	/**
	 * Stores return values of get_project_info()
	 *
	 * @var array
	 */
	protected static $_cache_project_info = false;

	/**
	 * The noticeid of the SSO notice
	 *
	 * @var int (Task ID's last 4 digits)
	 */
	protected $_sso_notice_id = 2927;

	/**
	 * Set up the Site module. Here we load and initialize the settings.
	 *
	 * @since 4.0.0
	 *
	 * @param string $main_file Path to the plugins main file.
	 *
	 * @internal
	 */
	public function __construct( $main_file ) {
		$this->init_flags();

		// Prepare module settings.
		$this->plugin_url  = trailingslashit( plugins_url( '', $main_file ) );
		$this->plugin_dir  = dirname( plugin_basename( $main_file ) );
		$this->plugin_path = trailingslashit( dirname( $main_file ) );

		// Process any actions triggered by the UI (e.g. save data).
		add_action( 'current_screen', array( $this, 'process_actions' ) );

		// Process ajax actions.
		$ajax_actions = array(
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
		);
		foreach ( $ajax_actions as $action ) {
			add_action( "wp_ajax_$action", array( $this, 'process_ajax' ) );
		}

		// AUTO login ajax (no nonce, its called by our auto install server)
		add_action( 'wp_ajax_wdp-dashboard-autologin', array( $this, 'dashboard_autologin' ) );

		$this->ajax_allowed_bypasses = array( 'changelog', 'analytics' );

		$nopriv_ajax_actions = array(
			'wdpunauth',
			'wdpsso_step1',
			'wdpsso_step2',
		);
		foreach ( $nopriv_ajax_actions as $action ) {
			add_action( "wp_ajax_$action", array( $this, 'nopriv_process_ajax' ) );
			add_action( "wp_ajax_nopriv_$action", array( $this, 'nopriv_process_ajax' ) );
		}

		add_action( 'wp_ajax_wdpun-connect', array( $this, 'ajax_connect' ) );

		// Check for compatibility issues and display a notification if needed.
		add_action(
			'admin_init',
			array( $this, 'compatibility_warnings' )
		);

		add_action( 'update-core.php', array( $this, 'refresh_local_projects_wrapper' ), 99 );
		add_action( 'load-plugins.php', array( $this, 'refresh_local_projects_wrapper' ), 99 );
		add_action( 'load-update.php', array( $this, 'refresh_local_projects_wrapper' ), 99 );
		add_action( 'load-update-core.php', array( $this, 'refresh_local_projects_wrapper' ), 99 );
		add_action( 'load-themes.php', array( $this, 'refresh_local_projects_wrapper' ), 99 );
		// really only used when hacking version num.
		add_action( 'load-plugin-editor.php', array( $this, 'refresh_local_projects_wrapper' ), 99 );
		add_action( 'load-theme-editor.php', array( $this, 'refresh_local_projects_wrapper' ), 99 );

		// Refresh after upgrade/install.
		add_action(
			'upgrader_process_complete',
			array( $this, 'after_local_files_changed' ),
			10,
			999
		);
		add_action(
			'set_site_transient_update_plugins',
			array( $this, 'schedule_shutdown_refresh' )
		);
		add_action(
			'set_site_transient_update_themes',
			array( $this, 'schedule_shutdown_refresh' )
		);
		add_filter(
			'delete_site_transient_update_themes',
			array( $this, 'schedule_shutdown_refresh' )
		); // runs when a theme is deleted

		// refresh after plugin/theme is activated/deactivated/deleted
		add_action( 'activated_plugin', array( $this, 'schedule_shutdown_refresh' ) );
		add_action( 'deactivated_plugin', array( $this, 'schedule_shutdown_refresh' ) );
		add_action( 'deleted_plugin', array( $this, 'schedule_shutdown_refresh' ) );
		if ( is_multisite() ) {
			add_action( 'update_site_option_allowedthemes', array( $this, 'schedule_shutdown_refresh' ) ); // network enable/disable
		}
		if ( is_main_site() ) {
			add_action( 'after_switch_theme', array( $this, 'schedule_shutdown_refresh' ) ); // per site activation
		}

		add_action( 'shutdown', array( $this, 'shutdown_refresh' ) );

		// Add WPMUDEV projects to the WP updates list.
		add_filter(
			'site_transient_update_plugins',
			array( $this, 'filter_plugin_update_count' )
		);

		add_filter(
			'site_transient_update_themes',
			array( $this, 'filter_theme_update_count' )
		);

		// Override the theme/plugin-installation API of WordPress core.
		add_filter(
			'plugins_api',
			array( $this, 'filter_plugin_update_info' ),
			101,
			3 // Run later to work with bad autoupdate plugins.
		);
		add_filter(
			'themes_api',
			array( $this, 'filter_plugin_update_info' ),
			101,
			3 // Run later to work with bad autoupdate plugins.
		);

		// Hook up our own plugin changelog iframe.
		add_action(
			'install_plugins_pre_plugin-information',
			array( $this, 'install_plugin_information' ),
			0
		);

		/*
		// Schedule event that collects data about active plugins/themes.
		Not finished because of low priority.

		if ( is_multisite() && is_main_site() ) {
			if ( ! wp_next_scheduled( 'wpmudev_scheduled_project_status' ) ) {
				wp_schedule_event( time(), 'twicedaily', 'wpmudev_scheduled_project_status' );
			}

			add_action(
				'wpmudev_scheduled_project_status',
				array( $this, 'refresh_blog_project_status' )
			);
		} elseif ( wp_next_scheduled( 'wpmudev_scheduled_project_status' ) ) {
			// In case the cron job was already installed in a sub-site...
			wp_clear_scheduled_hook( 'wpmudev_scheduled_project_status' );
		}
		*/

		// Whitelabel-ing plugin(s) pages
		add_action(
			'admin_enqueue_scripts',
			array( $this, 'whitelabel_plugin_admin_pages' )
		);

		// Tracking code for analytics module
		add_action( 'wp_footer', array( $this, 'analytics_tracking_code' ) );

		// Show friendly error in the login screen, if SSO is disabled or user is not logged in the Dashboard.
		add_filter( 'login_message', array( $this, 'show_sso_friendly_error' ) );

		/**
		 * Run custom initialization code for the Site module.
		 *
		 * @since  4.0.0
		 * @var  WPMUDEV_Dashboard_Site The dashboards Site module.
		 */
		do_action( 'wpmudev_dashboard_site_init', $this );

		// show sso notice
		add_action(
			'all_admin_notices',
			array( $this, 'sso_enable_notice' ),
			999
		);

		// prepare the notice template for SSO.
		add_filter(
			'wpmudev_notice_template',
			array( $this, 'sso_notice_template' ),
			10,
			2
		);

		// hide notice on subsite
		add_filter(
			'wpmudev_show_notice',
			array( $this, 'hide_sso_notice_on_subsite' ),
			10,
			2
		);

		add_filter(
			'ajax_query_attachments_args',
			array( $this, 'user_can_edit_branding_image' )
		);

		// Handle plugin update changes.
		add_action( 'wpmudev_dashboard_version_upgrade', array( $this, 'upgrade_plugin' ) );

		// Setup first time actions.
		add_action( 'wpmudev_dashboard_first_activation', array( $this, 'first_time_actions' ) );
	}

	/**
	 * Initialize all plugin options in the DB during activation.
	 * This function is called by the `activate_plugin` plugin in the main
	 * plugin file.
	 *
	 * Note:
	 * Function contains a complete list of all used Dashboard settings.
	 *
	 * @since  4.0.0
	 * @since  4.5.3 Add whitelabel options
	 *
	 * @param  string $action Can be set to 'reset' to overwrite all plugin
	 *                        options with the initial value (as if plugin was just
	 *                        installed for the first time).
	 *                        Any other value will only add the option if it's missing.
	 */
	public function init_options( $action = 'init' ) {
		// Initialize the plugin options stored in the WP Database.
		$options = array(
			'limit_to_user'                     => '',
			'remote_access'                     => '',
			'refresh_remote_flag'               => 0,
			'refresh_profile_flag'              => 0,
			'updates_data'                      => null,
			'profile_data'                      => '',
			'farm133_themes'                    => '',
			'updates_available'                 => '',
			'last_run_updates'                  => 0,
			'last_run_profile'                  => 0,
			'last_check_upfront'                => 0,
			'staff_notes'                       => '',
			'redirected_v4'                     => 0, // We want to redirect all users after first v4 activation!
			'autoupdate_dashboard'              => 1,
			'notifications'                     => array(),
			// 'blog_active_projects' => array(), // Only used on multisite. Not finished.
			'auth_user'                         => null, // NULL means: Ignore during 'reset' action.
			'highlights_dismissed'              => true,
			'version'                           => WPMUDEV_Dashboard::$version,
			'first_setup'                       => false,
			// Whitelabel options.
			'whitelabel_enabled'                => false,
			'whitelabel_branding_enabled'       => false,
			'whitelabel_branding_type'          => 'default',
			'whitelabel_branding_image'         => '',
			'whitelabel_branding_image_link'    => '',
			'whitelabel_footer_enabled'         => false,
			'whitelabel_footer_text'            => '',
			'whitelabel_labels_enabled'         => false,
			'whitelabel_labels_config'          => false,
			'whitelabel_labels_config_selected' => '',
			'whitelabel_labels_networkwide'     => true,
			'whitelabel_labels_subsites'        => array(),
			'whitelabel_doc_links_enabled'      => false,
			// Analytics options.
			'analytics_enabled'                 => false,
			'analytics_role'                    => 'administrator',
		);

		foreach ( $options as $key => $default_val ) {
			if ( 'reset' == $action && null !== $default_val ) {
				// Reset plugin to initial state.
				$this->set_option( $key, $default_val );
			} else {
				// Do not reset, just add if missing.
				if ( null === $default_val ) {
					$default_val = '';
				}
				$this->add_option( $key, $default_val );
			}
		}
	}


	/*
	 * *********************************************************************** *
	 * *     INTERNAL HELPER FUNCTIONS
	 * *********************************************************************** *
	 */


	/**
	 * Defines missing const flags with default values.
	 * This saves us from checking `if ( defined( ... ) )` all the time.
	 *
	 * Complete list of all supported Dashboard constants:
	 *
	 * - WPMUDEV_APIKEY .. Default: (undefined)
	 *     Define a static API key that cannot be changed via Dashboard.
	 *     If this constant is used then login/logout functions are not
	 *     available in the UI.
	 *
	 * - WPMUDEV_LIMIT_TO_USER .. Default: ''
	 *     Additional users that can access the Dashboard (comma separated).
	 *     This constant will override the user-list that can be defined on
	 *     the plugins Settings tab.
	 *
	 * - WPMUDEV_DISABLE_REMOTE_ACCESS .. Default: false
	 *     Set to true to disable the plugins external access functions.
	 *
	 * - WPMUDEV_MENU_LOCATION .. Default: '3.012'
	 *     Position of the WPMUDEV menu-item in the admin menu.
	 *
	 * - WPMUDEV_NO_AUTOACTIVATE .. Default: false
	 *     Default behavior of Install button is install + activate.
	 *     Set to true to only install the plugin, no activation.
	 *     (only for plugins on single-site)
	 *
	 * - WPMUDEV_CUSTOM_API_SERVER .. Default: false
	 *     Custom API Server from which to get membership details, etc.
	 *
	 * - WPMUDEV_API_SSLVERIFY .. Default: true
	 *     Set to false if you are having ssl errors connecting to our API (insecure).
	 *
	 * - WPMUDEV_API_UNCOMPRESSED .. Default: false
	 *     Set to true so API calls request uncompressed response values.
	 *
	 * - WPMUDEV_API_AUTHORIZATION .. Default: false
	 *     If the custom API Server needs some kind of authentication.
	 *
	 * - WPMUDEV_API_DEBUG .. Default: false
	 *     If set to true then all API calls are logged in the WordPress
	 *     logfile. This will only work if WP_DEBUG is enabled as well.
	 *
	 * - WPMUDEV_API_DEBUG_ALL .. Default: false
	 *     Adds more details to the output of WPMUDEV_API_DEBUG.
	 *
	 * - WPMUDEV_IS_REMOTE .. Default: (undefined)
	 *     This flag is set by the ajax handler after verifying an incoming
	 *     request from the remote dashboard. True means: The request is
	 *     authorized and will be processed.
	 *
	 * - WPMUDEV_DISABLE_SSO .. Default: false
	 *     Set to true to disable SSO from the Hub.
	 *
	 *     To disable all remote calls add this in wp-config (not recommended):
	 *     define( 'WPMUDEV_IS_REMOTE', false );
	 *
	 * @since  4.0.0
	 * @internal
	 */
	protected function init_flags() {
		// Do not initialize: WPMUDEV_APIKEY!
		// Do not initialize: WPMUDEV_IS_REMOTE!
		$flags = array(
			'WPMUDEV_LIMIT_TO_USER'         => false,
			'WPMUDEV_DISABLE_REMOTE_ACCESS' => false,
			'WPMUDEV_MENU_LOCATION'         => '3.012',
			'WPMUDEV_NO_AUTOACTIVATE'       => false,
			'WPMUDEV_CUSTOM_API_SERVER'     => false,
			'WPMUDEV_API_UNCOMPRESSED'      => false,
			'WPMUDEV_API_SSLVERIFY'         => true,
			'WPMUDEV_API_AUTHORIZATION'     => false,
			'WPMUDEV_API_DEBUG'             => false,
			'WPMUDEV_API_DEBUG_ALL'         => false,
			'WPMUDEV_DISABLE_SSO'           => false,
		);

		foreach ( $flags as $flag => $default_val ) {
			if ( ! defined( $flag ) ) {
				define( $flag, $default_val );
			}
		}
	}

	/**
	 * Upgrade plugin settings after the update.
	 *
	 * @param string $old_version Old version.
	 *
	 * @since  4.11
	 */
	public function upgrade_plugin( $old_version ) {
		// If upgrading to 4.11.
		if ( version_compare( $old_version, '4.11', '<' ) ) {
			// If branding enabled, branding type is custom.
			if ( $this->get_option( 'whitelabel_branding_enabled' ) ) {
				$this->set_option( 'whitelabel_branding_type', 'custom' );
			}
		}

		// If upgrading to 4.11.2.
		if ( version_compare( $old_version, '4.11.2', '<' ) ) {
			$enabled = (bool) $this->get_option( 'autoupdate_dashboard' );
			// Sync Dash auto update to WP.
			WPMUDEV_Dashboard::$upgrader->change_wp_auto_update( $enabled );
		}
	}

	/**
	 * Perform first activation actions.
	 *
	 * On first activation if we are on our hosting,
	 * enable Analytics and Uptime monitor by default.
	 *
	 * @since 4.11.2
	 */
	public function first_time_actions() {
		// On our hosting, if it's first time activation enable few services.
		if ( defined( 'WPMUDEV_HOSTING_SITE_ID' ) || isset( $_SERVER['WPMUDEV_HOSTED'] ) ) {
			// If analytics allowed.
			if ( WPMUDEV_Dashboard::$api->is_analytics_allowed() ) {
				// Attempt to enable analytics.
				if ( WPMUDEV_Dashboard::$api->analytics_enable() ) {
					// Enabled.
					$this->set_option( 'analytics_enabled', true );
				}
			}
		}
	}

	/**
	 * Process actions when page loads.
	 *
	 * @since  4.0.0
	 * @internal
	 *
	 * @param  WP_Screen $current_screen The current_screen object.
	 */
	public function process_actions( $current_screen ) {
		$no_nonce = array(
			'check-updates',
		);

		// Remove the "Changes saved" message when user refreshes the browser window.
		if ( empty( $_POST ) ) {
			$err = isset( $_GET['failed'] ) ? intval( $_GET['failed'] ) : false;
			$ok  = isset( $_GET['success'] ) ? intval( $_GET['success'] ) : false;

			if ( ( $ok && $ok < time() ) || ( $err && $err < time() ) ) {
				$url = esc_url_raw(
					remove_query_arg( array( 'success', 'failed', 'wpmudev_msg', 'success-action', 'failed-action' ) )
				);
				header( 'X-Redirect-From: SITE process_actions top' );
				wp_safe_redirect( $url );
				exit;
			}
		}

		// Do nothing when the current page is NOT a WPMU DEV menu item.
		if ( ! strpos( $current_screen->base, 'page_wpmudev' ) ) {
			return;
		}

		// Do nothing if either action or nonce is missing.
		if ( empty( $_REQUEST['action'] ) ) {
			// if ( isset( $_GET['success-action'] ) || isset( $_GET['failed-action'] ) ) {
			// $url = esc_url_raw(
			// remove_query_arg( array( 'success-action', 'failed-action' ) )
			// );
			// header( 'X-Redirect-From: SITE process_actions top' );
			// wp_safe_redirect( $url );
			// exit;
			// }

			return;
		}

		$action = $_REQUEST['action'];

		// Skip the nonce-check if the action does not require it.
		if ( ! in_array( $action, $no_nonce ) ) {
			if ( empty( $_REQUEST['hash'] ) ) {
				return;
			}
			$nonce = $_REQUEST['hash'];

			// Do nothing if the nonce is invalid.
			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				return;
			}
		}

		// Do nothing if the user is not allowed to use the Dashboard.
		if ( ! $this->allowed_user() ) {
			return;
		}

		$res      = $this->_process_action( $action );
		$redirect = remove_query_arg( array( 'action', 'hash', 'success', 'failed', 'success-action', 'failed-action' ) );

		switch ( $res ) {
			case 'OK':
				$redirect = add_query_arg( 'success', 10 + time(), $redirect );
				$redirect = add_query_arg( 'success-action', $action, $redirect );
				break;

			case 'ERR':
				$redirect = add_query_arg( 'failed', 10 + time(), $redirect );
				$redirect = add_query_arg( 'failed-action', $action, $redirect );
				break;

			case 'SILENT':
			default:
				// Nothing.
				break;
		}

		if ( $redirect ) {
			header( 'X-Redirect-From: SITE process_actions bottom' );
			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit;
		}
	}

	/**
	 * Internal processing function to execute specific actions upon page load.
	 * When this function is called we already confirmed that the user is
	 * permitted to use the dashboard and that a correct nonce was supplied.
	 *
	 * @since  4.0.0
	 * @internal
	 *
	 * @param  string $action The action to execute.
	 *
	 * @return string Either OK|SOK|ERR|SILENT.
	 *         OK     .. Action successful. Display "Saved" message.
	 *         ERR    .. Action failed. Display "Failed" message.
	 *         SILENT .. Do not display any message.
	 */
	protected function _process_action( $action ) {
		do_action( 'wpmudev_dashboard_action-' . $action );
		$success = 'SILENT';
		$type    = WPMUDEV_Dashboard::$api->get_membership_type();

		switch ( $action ) {
			// Tab: Support
			// Function Grant support access.
			case 'remote-grant':
				if ( ! is_wpmudev_member() && 'unit' !== $type ) {
					$success = false;
				} else {
					$success = WPMUDEV_Dashboard::$api->enable_remote_access( 'start' );
				}

				break;

			// Tab: Support
			// Function Revoke support access.
			case 'remote-revoke':
				if ( ! current_user_can( 'edit_users' ) ) {
					$success = false;
				} else {
					$success = WPMUDEV_Dashboard::$api->revoke_remote_access();
				}
				break;

			// Tab: Support
			// Function Extend support access.
			case 'remote-extend':
				if ( ! is_wpmudev_member() && 'unit' !== $type ) {
					$success = false;
				} else {
					$success = WPMUDEV_Dashboard::$api->enable_remote_access( 'extend' );
				}
				break;

			// Tab: Support
			// Function Save notes for support staff.
			case 'staff-note':
				$notes = '';
				if ( isset( $_REQUEST['notes'] ) ) {
					$notes = stripslashes( $_REQUEST['notes'] );
				}
				WPMUDEV_Dashboard::$site->set_option( 'staff_notes', $notes );
				// un silent message
				$success = true;
				break;

			// Tab: Settings
			// Function Add new admin users for Dashboard.
			case 'admin-add':
				if ( ! empty( $_REQUEST['users'] ) ) { // phpcs:ignore
					// Empty the list first.
					WPMUDEV_Dashboard::$site->set_option( 'limit_to_user', array() );
					$user_ids = (array) $_REQUEST['users']; // phpcs:ignore
					// Current user should always be there in the list.
					if ( ! in_array( get_current_user_id(), $user_ids, true ) ) {
						$user_ids[] = get_current_user_id();
					}
					// Add each users to the list.
					foreach ( $user_ids as $user_id ) {
						WPMUDEV_Dashboard::$site->add_allowed_user( $user_id );
					}
					$success = true;
				}
				break;

			// Tab: Settings
			// Function Remove other admin user for Dashboard.
			case 'admin-remove':
				if ( ! empty( $_REQUEST['user'] ) ) {
					$user_id = (int) $_REQUEST['user'];
					// Do not let self delete.
					if ( get_current_user_id() !== $user_id ) {
						$success = WPMUDEV_Dashboard::$site->remove_allowed_user( $user_id );
					}
				}
				break;

			// Tab: Plugins
			// Function to check for updates again.
			case 'check-updates':
				WPMUDEV_Dashboard::$site->set_option( 'refresh_profile_flag', 1 );
				WPMUDEV_Dashboard::$api->refresh_projects_data();
				WPMUDEV_Dashboard::$site->refresh_local_projects( 'remote' );

				// un silent message
				$success = true;
				break;

			// Tab: Settings
			// Function to setup whitelabel.
			case 'whitelabel-setup':
				$status = isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : '';// wpcs CSRF ok. already validated on process_ajax
				switch ( $status ) {
					case 'activate':
						$this->set_option( 'whitelabel_enabled', true );
						// un silent message
						$success = true;
						break;
					case 'deactivate':
						$this->set_option( 'whitelabel_enabled', false );
						// un silent message
						$success = true;
						break;
					case 'site-remove':
						if ( ! empty( $_REQUEST['site'] ) ) {
							// Get the site id.
							$site_id = (int) $_REQUEST['site'];
							// Get whitelabel settings.
							$settings = $this->get_whitelabel_settings();
							// Get already added sites.
							$sites = empty( $settings['labels_subsites'] ) ? array() : (array) $settings['labels_subsites'];
							// Delete current item if already exist.
							if ( in_array( $site_id, $sites ) ) {
								$key = array_search( $site_id, $sites );
								unset( $sites[ $key ] );
							}
							// Update new list.
							$success = $this->set_option( 'whitelabel_labels_subsites', $sites );
						}
						break;
					case 'settings':
						$setting_data = $_REQUEST; // phpcs:ignore
						$options_map  = array(
							'branding_enabled'         => array(
								'option_name' => 'whitelabel_branding_enabled',
								'default'     => false,
							),
							'branding_type'            => array(
								'option_name' => 'whitelabel_branding_type',
								'default'     => 'default',
							),
							'branding_enabled_subsite' => array(
								'option_name'   => 'branding_enabled_subsite',
								'expected_type' => 'boolean',
								'default'       => false,
							),
							'branding_image'           => array(
								'option_name' => 'whitelabel_branding_image',
								'default'     => '',
							),
							'branding_image_id'        => array(
								'option_name' => 'whitelabel_branding_image_id',
								'default'     => '',

							),
							'branding_image_link'      => array(
								'option_name' => 'whitelabel_branding_image_link',
								'default'     => '',

							),
							'footer_enabled'           => array(
								'option_name' => 'whitelabel_footer_enabled',
								'default'     => false,
							),
							'footer_text'              => array(
								'option_name' => 'whitelabel_footer_text',
								'default'     => '',
							),
							'labels_enabled'           => array(
								'option_name' => 'whitelabel_labels_enabled',
								'default'     => false,
							),
							'labels_config'            => array(
								'option_name' => 'whitelabel_labels_config',
								'default'     => array(),
							),
							'labels_config_selected'   => array(
								'option_name' => 'whitelabel_labels_config_selected',
								'default'     => '',
							),
							'labels_networkwide'       => array(
								'option_name' => 'whitelabel_labels_networkwide',
								'default'     => true,
							),
							'labels_subsites'          => array(
								'option_name' => 'whitelabel_labels_subsites',
								'default'     => array(),
							),
							'doc_links_enabled'        => array(
								'option_name' => 'whitelabel_doc_links_enabled',
								'default'     => false,
							),
						);

						$labels_defaults = array(
							'name'      => '',
							'icon_type' => 'default',
						);

						// Set branding enabled value.
						$setting_data['branding_enabled'] = isset( $setting_data['branding_type'] ) && 'default' !== $setting_data['branding_type'];

						$allowed_tags = array(
							'a'      => array(
								'href'   => array(),
								'title'  => array(),
								'target' => array(),
							),
							'b'      => array(),
							'i'      => array(),
							'strong' => array(),
						);

						foreach ( $options_map as $key => $value ) {
							if ( ! isset( $value['option_name'] ) || empty( $value['option_name'] ) ) {
								continue;
							}
							$option_value = isset( $value['default'] ) ? $value['default'] : false;
							if ( isset( $setting_data[ $key ] ) ) {
								if ( is_string( $option_value ) ) {
									if ( 'footer_text' === $key ) {
										$option_value = wp_kses( wp_unslash( $setting_data[ $key ] ), $allowed_tags );
									} else {
										$option_value = sanitize_text_field( wp_unslash( $setting_data[ $key ] ) );
									}
								} elseif ( is_bool( $option_value ) ) {
									$option_value = filter_var( $setting_data[ $key ], FILTER_VALIDATE_BOOLEAN );
								} elseif ( is_array( $option_value ) ) {
									if ( 'labels_config' === $key ) {
										$config_values = array();
										foreach ( $setting_data[ $key ] as $plugin => $config ) {
											if ( is_array( $config ) ) {
												// Make sure the format.
												$values = wp_parse_args( $config, $labels_defaults );
												// Set values.
												$config_values[ $plugin ] = array_map( 'sanitize_text_field', $values );
											}
										}
										$option_value = $config_values;
									} elseif ( 'labels_subsites' === $key ) {
										$option_value = array_map( 'intval', $setting_data[ $key ] );
									}
								}
							}

							$this->set_option( $value['option_name'], $option_value );

						}
						// un silent message
						$success = true;
						break;
					default:
						$success = false;
						break;
				}
				break;

			// Function to setup analytics.
			case 'analytics-setup':
				$status = isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : ''; // wpcs CSRF ok. already validated on process_ajax
				switch ( $status ) {
					case 'activate':
						$success = WPMUDEV_Dashboard::$api->analytics_enable();
						if ( $success ) {
							$this->set_option( 'analytics_enabled', true );
						}
						break;
					case 'deactivate':
						$success = WPMUDEV_Dashboard::$api->analytics_disable();
						if ( $success ) {
							$this->set_option( 'analytics_enabled', false );
						}
						break;
					case 'settings':
						$option_value = isset( $_REQUEST['analytics_role'] ) && get_role( $_REQUEST['analytics_role'] ) ? $_REQUEST['analytics_role']
							: 'administrator'; // wpcs CSRF ok. already validated on process_ajax
						$this->set_option( 'analytics_role', sanitize_text_field( $option_value ) );

						$metrics = isset( $_REQUEST['analytics_metrics'] ) && is_array( $_REQUEST['analytics_metrics'] ) ? $_REQUEST['analytics_metrics'] : array();
						$this->set_option( 'analytics_metrics', $metrics );

						$success = true;
						break;
					default:
						$success = false;
						break;
				}
				break;

			// setup translation updates
			case 'translation-setup':
				$locale = empty( $_REQUEST['selected_locale'] ) ? 'en_US' : $_REQUEST['selected_locale'];

				$enable_auto_translation = isset( $_REQUEST['enable_auto_translation'] ) ? absint( $_REQUEST['enable_auto_translation'] ) : 0;

				$this->set_option( 'enable_auto_translation', $enable_auto_translation );

				$prev_locale = WPMUDEV_Dashboard::$site->get_option( 'translation_locale' );

				$this->set_option( 'translation_locale', $locale );

				// hub-sync to check prev locale
				if ( $prev_locale !== $locale ) {
					// Also, force a hub-sync, since the translation setting changed.
					WPMUDEV_Dashboard::$api->calculate_translation_upgrades( true );
				}
				$success = true;
				break;

			// setup autoupdate dashboard
			case 'autoupdate-dashboard':
				$status = isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : ''; // wpcs CSRF ok. already validated on process_ajax
				switch ( $status ) {
					case 'settings':
						$auto_update = isset( $_REQUEST['autoupdate_dashboard'] ) ? filter_var( $_REQUEST['autoupdate_dashboard'], FILTER_VALIDATE_BOOLEAN ) : false;
						$this->set_option( 'autoupdate_dashboard', $auto_update );

						$enable_sso   = isset( $_REQUEST['enable_sso'] ) ? absint( $_REQUEST['enable_sso'] ) : 0;
						$previous_sso = $this->get_option( 'enable_sso', true );

						// Register the user to be logged in for SSO, only if the SSO was just enabled.
						if ( $enable_sso && ! $previous_sso ) {
							$sso_userid = get_current_user_id();
							$this->set_option( 'sso_userid', $sso_userid );
						}

						$this->set_option( 'enable_sso', $enable_sso );

						if ( ( $enable_sso && ! $previous_sso ) || ( ! $enable_sso && $previous_sso ) ) {
							// Also, force a hub-sync, since the SSO setting changed.
							WPMUDEV_Dashboard::$api->hub_sync( false, true );
						}

						$success = true;
						break;
					default:
						$success = false;
						break;
				}

				break;
			default:
				$success = false;
				break;
		}

		if ( true === $success ) {
			$success = 'OK';
		} elseif ( false === $success ) {
			$success = 'ERR';
		}

		return $success;
	}

	/**
	 * Entry point for all Ajax requests of the plugin.
	 *
	 * All Ajax handlers point to this function instead of an individual
	 * callback function; this function validates the user before processing the
	 * actual request.
	 *
	 * @since  4.0.0
	 * @internal
	 */
	public function process_ajax() {
		ob_start();

		/*
		 * Do nothing if function was called incorrectly.
		 *
		 * Note: `hash` is a normal wp-nonce.
		 *       We just use name="hash" instead of name="_wpnonce"
		 */
		if ( empty( $_REQUEST['action'] ) || empty( $_REQUEST['hash'] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Required field missing', 'wpmudev' ) )
			);
		}

		$action = str_replace( 'wdp-', '', $_REQUEST['action'] );
		$nonce  = $_REQUEST['hash'];
		// Do nothing if the nonce is invalid.
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Something went wrong, please refresh the page and try again.', 'wpmudev' ) )
			);
		}

		// Do nothing if the user is not allowed to use the Dashboard. Exception for specific ajax actions
		if ( ! in_array( $action, $this->ajax_allowed_bypasses ) && ! $this->allowed_user() ) {
			wp_send_json_error(
				array( 'message' => __( 'Sorry, you are not allowed to do this.', 'wpmudev' ) )
			);
		}

		$this->_process_ajax( $action, false );

		// When the _projess_ajax function did not send a response assume error.
		wp_send_json_error(
			array( 'message' => __( 'Unexpected action, we could not handle it.', 'wpmudev' ) )
		);
	}

	/**
	 * Entry point for all PUBLIC Ajax requests of the plugin.
	 *
	 * All Ajax handlers point to this function instead of an individual
	 * callback function; These functions are available even when logged out.
	 *
	 * @since  4.0.0
	 * @internal
	 */
	public function nopriv_process_ajax() {
		ob_start();

		// Do nothing if function was called incorrectly.
		if ( empty( $_REQUEST['action'] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Required field missing', 'wpmudev' ) )
			);
			exit;
		}

		$action = $_REQUEST['action'];

		$this->_process_ajax( $action, true );

		// When the _projess_ajax function did not send a response assume error.
		wp_send_json_error();
	}

	/**
	 * Internal processing function to execute specific ajax actions.
	 * When this function is called we already confirmed that the user is
	 * permitted to use the dashboard.
	 *
	 * @since  4.0.0
	 * @internal
	 *
	 * @param  string $action       The action to execute.
	 * @param  bool   $allow_guests If true, then only public ajax-actions are
	 *                              processed (which use a special authentication method) but
	 *                              logged-in-only actions are skipped for security reasons.
	 */
	protected function _process_ajax( $action, $allow_guests = false ) {
		$pid        = 0;
		$pids       = array();
		$is_network = false;

		if ( isset( $_REQUEST['pid'] ) ) {
			$pid = $_REQUEST['pid'];
		} elseif ( isset( $_REQUEST['pids'] ) ) {
			$pids = json_decode( stripslashes( $_REQUEST['pids'] ) );
		}

		// Those actions are ONLY available for logged-in admin users.
		if ( ! $allow_guests ) {
			if ( isset( $_REQUEST['is_network'] ) ) {
				$is_network = ( 1 == intval( $_REQUEST['is_network'] ) );
			}

			switch ( $action ) {
				case 'get-project':
					if ( $pid ) {
						WPMUDEV_Dashboard::$ui->render_project( $pid );
					}
					break;

				case 'check-updates':
					WPMUDEV_Dashboard::$site->set_option( 'refresh_profile_flag', 1 );
					WPMUDEV_Dashboard::$api->refresh_projects_data();
					WPMUDEV_Dashboard::$site->refresh_local_projects( 'remote' );
					$this->send_json_success();
					break;

				case 'project-activate':
					if ( $pid ) {
						$local = $this->get_cached_projects( $pid );
						if ( empty( $local ) ) {
							$this->send_json_error( array( 'message' => __( 'Not installed' ) ) );
						}
						$other_pids = false;

						if ( 'plugin' == $local['type'] ) {
							activate_plugins( $local['filename'], '', $is_network );
						} elseif ( 'theme' == $local['type'] ) {
							if ( $is_network ) {
								// Allow theme network wide.
								$allowed_themes                   = get_site_option( 'allowedthemes' );
								$allowed_themes[ $local['slug'] ] = true;
								update_site_option( 'allowedthemes', $allowed_themes );
							} else {
								// We only activate themes on single-sites.
								$old_theme = $this->get_active_wpmu_theme();
								if ( $old_theme ) {
									$other_pids = array( $old_theme );
								}
								switch_theme( $local['slug'] );
							}
						}
						$this->clear_local_file_cache();
						WPMUDEV_Dashboard::$ui->render_project( $pid, $other_pids, false, true );
					}
					break;
				case 'translation-update':
					// we work with slug on translation update.
					$pid = $_REQUEST['slug'];
					if ( $pid ) {
						$success = WPMUDEV_Dashboard::$upgrader->upgrade_translation( $pid );

						if ( $success ) {
							$this->clear_local_file_cache();
							$this->send_json_success();
						}

						$err = WPMUDEV_Dashboard::$upgrader->get_error();

						$this->send_json_error( $err );

					}
					break;

				case 'project-deactivate':
					if ( $pid ) {
						$local = $this->get_cached_projects( $pid );
						if ( empty( $local ) ) {
							$this->send_json_error( array( 'message' => __( 'Not installed' ) ) );
						}

						if ( 'plugin' == $local['type'] ) {
							deactivate_plugins( $local['filename'], '', $is_network );
						} elseif ( 'theme' == $local['type'] ) {
							if ( $is_network ) {
								// Disallow theme network wide.
								$allowed_themes = get_site_option( 'allowedthemes' );
								unset( $allowed_themes[ $local['slug'] ] );
								update_site_option( 'allowedthemes', $allowed_themes );
							}
						}

						$this->clear_local_file_cache();
						WPMUDEV_Dashboard::$ui->render_project( $pid, false, false, true );
					}
					break;

				case 'project-install':
					if ( $pid ) {
						$local = $this->get_cached_projects( $pid );
						if ( ! empty( $local ) ) {
							$this->send_json_error( array( 'message' => __( 'Already installed' ) ) );
						}

						if ( $this->maybe_replace_free_with_pro( $pid ) ) {
							WPMUDEV_Dashboard::$ui->render_project(
								$pid,
								false,
								'popup-after-install'
							);
						}
					}
					break;

				case 'project-install-activate':
					if ( $pid ) {
						// Check if project is already installed.
						$local = $this->get_cached_projects( $pid );
						if ( empty( $local ) ) {
							// Install if not installed.
							if ( $this->maybe_replace_free_with_pro( $pid ) ) {
								// Get project data.
								$local = $this->get_cached_projects( $pid );
							}
						}

						// Can not continue.
						if ( empty( $local['filename'] ) ) {
							$this->send_json_error( array( 'message' => __( 'Could not install' ) ) );
						}

						// Activate the plugin.
						activate_plugins( $local['filename'], '', $is_network );
						// Clear cache.
						$this->clear_local_file_cache();
						// Render project.
						WPMUDEV_Dashboard::$ui->render_project( $pid, false, false, true );
					}
					break;

				// @deprecated
				case 'project-install-upfront':
					if ( ! $this->is_upfront_installed() ) {
						$id_upfront = $this->id_upfront;

						$success = WPMUDEV_Dashboard::$upgrader->install( $id_upfront, 'theme' );

						if ( ! $success ) {
							$err = WPMUDEV_Dashboard::$upgrader->get_error();
							wp_send_json_error( $err );
						}
					}

					if ( $pid ) {
						$local = $this->get_cached_projects( $pid );
						WPMUDEV_Dashboard::$ui->render_project(
							$pid,
							false,
							'popup-after-install-upfront'
						);
					}
					break;

				case 'project-update':
					if ( $pid ) {
						$success = WPMUDEV_Dashboard::$upgrader->upgrade( $pid );

						if ( ! $success ) {
							$err = WPMUDEV_Dashboard::$upgrader->get_error();
							$this->send_json_error( $err );
						}

						$this->clear_local_file_cache();
						WPMUDEV_Dashboard::$ui->render_project( $pid );
					}
					break;

				case 'usersearch':
					$items = array();
					if ( ! empty( $_REQUEST['q'] ) ) {
						$users = $this->get_potential_users( $_REQUEST['q'] );
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
					break;

				case 'sitesearch':
					$items = array();
					if ( is_multisite() ) {
						if ( ! empty( $_REQUEST['q'] ) ) {
							$args = array(
								'search' => sanitize_text_field( $_REQUEST['q'] ),
								'fields' => 'ids',
							);

							// Get settings.
							$settings = $this->get_whitelabel_settings();
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
					break;

				case 'projectsearch':
					$items = array();
					$urls  = WPMUDEV_Dashboard::$ui->page_urls;
					if ( ! empty( $_REQUEST['q'] ) ) {
						$projects = $this->find_projects_by_name( $_REQUEST['q'] );
						foreach ( $projects as $item ) {
							if ( 'theme' == $item->type ) {
								$url  = $urls->themes_url;
								$icon = '<i class="dev-icon dev-icon-theme"></i> ';
							} elseif ( 'plugin' == $item->type ) {
								$url  = $urls->plugins_url;
								$icon = '<i class="dev-icon dev-icon-plugin"></i> ';
							}
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
					$this->send_json_success( $items );
					break;

				case 'save-setting':
				case 'save-setting-bool':
				case 'save-setting-int':
					if ( ! empty( $_REQUEST['name'] ) && isset( $_REQUEST['value'] ) ) {
						$name  = sanitize_html_class( $_REQUEST['name'] );
						$value = $_REQUEST['value'];

						switch ( $action ) {
							case 'save-setting-bool':
								if ( 'true' == $value
									 || '1' == $value
									 || 'on' == $value
									 || 'yes' == $value
								) {
									$value = true;
								} else {
									$value = false;
								}
								break;

							case 'save-setting-int':
								$value = intval( $value );
								break;
							default:
								break;
						}

						WPMUDEV_Dashboard::$site->set_option( $name, $value );
					}
					$this->send_json_success();
					break;

				case 'show-popup':
					if ( ! empty( $_REQUEST['type'] ) ) {
						$type = $_REQUEST['type'];
						WPMUDEV_Dashboard::$ui->show_popup( $type, $pid );
					}
					break;

				case 'credentials':
					// Remember credentials for FTP update/installation, for 15 min.
					if ( WPMUDEV_Dashboard::$upgrader->remember_credentials() ) {
						$this->send_json_success();
					} else {
						$this->send_json_error();
					}
					break;

				case 'analytics':
					if ( ! $this->user_can_analytics() ) {
						$this->send_json_error( array( 'message' => __( 'Unauthorized', 'wpmudev' ) ) );
					}

					$data = WPMUDEV_Dashboard::$api->analytics_stats_single( $_REQUEST['range'], $_REQUEST['type'], $_REQUEST['filter'] );
					if ( $data ) {
						$this->send_json_success( $data );
					} else {
						$this->send_json_error( array( 'message' => __( 'There was an API error, please try again.', 'wpmudev' ) ) );
					}
					break;

				case 'project-delete':
					if ( $pid ) {
						if ( WPMUDEV_Dashboard::$upgrader->delete_plugin( $pid ) ) {
							$this->clear_local_file_cache();

							WPMUDEV_Dashboard::$ui->render_project(
								$pid,
								false
							);
						} else {
							$err = WPMUDEV_Dashboard::$upgrader->get_error();
							$this->send_json_error( $err );
						}
					}
					break;

				case 'hub-sync':
					if ( ! empty( $_REQUEST['key'] ) ) {
						$key = trim( $_REQUEST['key'] );
						$sso = false;
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
												'site_limit_exceeded' => '1',
												'site_limit' => $result['limit_data']['site_limit'],
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
							// valid key
							global $current_user;
							WPMUDEV_Dashboard::$site->set_option( 'limit_to_user', $current_user->ID );
							WPMUDEV_Dashboard::$api->refresh_profile();

							if ( $sso ) {
								// Since we auto install, we need to associate SSO with the correct user.
								WPMUDEV_Dashboard::$site->set_option( 'sso_userid', $current_user->ID );
							}

							/***
							 * Action hook that run after login with WPMUDEV account is successful.
							 *
							 * @param int $user_id Current user ID.
							 *
							 * @since 4.11.2
							 */
							do_action( 'wpmudev_dashboard_after_login_success', $current_user->ID );
						}
						$installed_free_projects = WPMUDEV_Dashboard::$site->get_installed_free_projects();
						$url                     = add_query_arg(
							array( 'view' => 'sync-plugins' ),
							WPMUDEV_Dashboard::$ui->page_urls->dashboard_url
						);

						if ( empty( $installed_free_projects ) ) {
							$url = WPMUDEV_Dashboard::$ui->page_urls->dashboard_url;
							if ( is_wpmudev_single_member() || is_wpmudev_member() ) {
								$url .= '#sync-plugins';
							}
						}

						$this->send_json_success(
							array(
								'redirect' => $url,
							)
						);
					}
					break;

				case 'project-upgrade-free':
					if ( $pid ) {
						if ( $this->maybe_replace_free_with_pro( $pid ) ) {
							$this->send_json_success();
						}
					}
					break;

				case 'login-success':
					$url = WPMUDEV_Dashboard::$ui->page_urls->dashboard_url;
					if ( $pid ) {
						$pid = implode( ',', $pid );
						$url = add_query_arg(
							array( 'updated-plugins' => $pid ),
							$url
						);
					}
					$this->send_json_success(
						array(
							'redirect' => $url . '#sync-plugins',
						)
					);
					break;

				case 'sso-status':
					if ( ! is_null( $_REQUEST['sso'] ) && ! empty( $_REQUEST['ssoUserId'] ) ) {
						WPMUDEV_Dashboard::$site->set_option( 'enable_sso', absint( $_REQUEST['sso'] ) );
						WPMUDEV_Dashboard::$site->set_option( 'sso_userid', absint( $_REQUEST['ssoUserId'] ) );
						$this->send_json_success();
					}
					break;

				case 'dismiss-highlights':
					// Set dismissal flag.
					WPMUDEV_Dashboard::$site->set_option( 'highlights_dismissed', true );
					$this->send_json_success();
					break;

				default:
					$this->send_json_error(
						array(
							'message' => sprintf(
								__( 'Unknown action: %s', 'wpmudev' ),
								esc_html( $action )
							),
						)
					);
					break;
			}
		}

		// Those actions are available for logged-in users AND guests.
		if ( $allow_guests ) {
			switch ( $action ) {
				case 'wdpunauth':
					/*
					 * Required POST params:
					 * - wdpunkey .. Temporary Auth Key from the DB.
					 * - staff    .. Name of the user who loggs in.
					 */
					WPMUDEV_Dashboard::$api->authenticate_remote_access();
					break;

				case 'wdpsso_step1':
					$redirect = isset( $_REQUEST['redirect'] ) ? urlencode( $_REQUEST['redirect'] ) : '';
					$nonce    = isset( $_REQUEST['nonce'] ) ? $_REQUEST['nonce'] : '';
					$jwttoken = isset( $_REQUEST['jwttoken'] ) ? $_REQUEST['jwttoken'] : '';
					$apikey   = isset( $_REQUEST['apikey'] ) ? $_REQUEST['apikey'] : '';
					$hubteam  = isset( $_REQUEST['hubteam'] ) ? $_REQUEST['hubteam'] : '';
					WPMUDEV_Dashboard::$api->authenticate_sso_access_step1( $redirect, $nonce, $jwttoken, $apikey, $hubteam );
					break;

				case 'wdpsso_step2':
					$incoming_hmac = isset( $_REQUEST['outgoing_hmac'] ) ? $_REQUEST['outgoing_hmac'] : '';
					$token         = isset( $_REQUEST['token'] ) ? $_REQUEST['token'] : '';
					$pre_sso_state = isset( $_REQUEST['pre_sso_state'] ) ? $_REQUEST['pre_sso_state'] : '';
					$redirect      = isset( $_REQUEST['redirect'] ) ? $_REQUEST['redirect'] : '';

					WPMUDEV_Dashboard::$api->authenticate_sso_access_step2( $incoming_hmac, $token, $pre_sso_state, $redirect );
					break;

				default:
					$this->send_json_error(
						array(
							'message' => sprintf(
								__( 'Unknown action: %s', 'wpmudev' ),
								esc_html( $action )
							),
						)
					);
					break;
			}
		}
	}

	/**
	 * Used by the Getting started wizard on WPMU DEV to programatically login to the dashboard
	 *
	 * @param $_REQUEST ['apikey']
	 */
	public function ajax_connect() {

		// check permissions
		if ( ! current_user_can( 'manage_network_options' ) ) {
			$this->send_json_error( 'No permissions' );
		}

		WPMUDEV_Dashboard::$api->set_key( trim( $_REQUEST['apikey'] ) );
		$result = WPMUDEV_Dashboard::$api->hub_sync( false, true );
		if ( ! $result || empty( $result['membership'] ) ) {
			// Don't logout at this point!
			WPMUDEV_Dashboard::$api->set_key( '' );
			if ( false === $result ) {
				$this->send_json_error( WPMUDEV_Dashboard::$api->api_error );
			} else {
				$this->send_json_error( __( 'Your API Key was invalid. Please try again.', 'wpmudev' ) );
			}
		} else {
			// You did it! Login was successful :)
			// The current user is our new hero-user with Dashboard access.
			WPMUDEV_Dashboard::$site->set_option( 'limit_to_user', get_current_user_id() );
			WPMUDEV_Dashboard::$api->refresh_profile();
			// User is logged in: First redirect is done.
			WPMUDEV_Dashboard::$site->set_option( 'redirected_v4', 1 );

			$this->send_json_success();
		}
	}

	/**
	 * Clear all output buffers and send an JSON reponse to an Ajax request.
	 *
	 * @since  4.0.0
	 *
	 * @param  mixed $data Optional data to return to the Ajax request.
	 */
	public function send_json_success( $data = null ) {
		while ( ob_get_level() ) {
			ob_get_clean();
		}

		wp_send_json_success( $data );
	}

	/**
	 * Clear all output buffers and send an JSON reponse to an Ajax request.
	 *
	 * @since  4.0.0
	 *
	 * @param  mixed $data Optional data to return to the Ajax request.
	 */
	public function send_json_error( $data = null ) {
		while ( ob_get_level() ) {
			ob_get_clean();
		}

		wp_send_json_error( $data );
	}


	/*
	 * *********************************************************************** *
	 * *     PUBLIC INTERFACE FOR OTHER MODULES
	 * *********************************************************************** *
	 */


	/**
	 * Returns the value of a plugin option.
	 * The plugins option-prefix is automatically added to the option name.
	 *
	 * Use this function instead of direct access via get_site_option()
	 *
	 * @since  4.0.0
	 *
	 * @param  string $name    The option name.
	 * @param  bool   $prefix  Optional. Set to false to not prefix the name.
	 * @param  mixed  $default Optional. Set value to return if option not found.
	 *
	 * @return mixed The option value.
	 */
	public function get_option( $name, $prefix = true, $default = false ) {
		if ( $prefix ) {
			$key = 'wdp_un_' . $name;
		} else {
			$key = $name;
		}

		return get_site_option( $key, $default );
	}

	/**
	 * Updates the value of a plugin option.
	 * The plugins option-prefix is automatically added to the option name.
	 *
	 * Use this function instead of direct access via update_site_option()
	 *
	 * @since  4.0.0
	 *
	 * @param  string $name  The option name.
	 * @param  mixed  $value The new option value.
	 *
	 * @return bool
	 */
	public function set_option( $name, $value ) {
		$key = 'wdp_un_' . $name;
		return update_site_option( $key, $value );
	}

	/**
	 * Add a new plugin setting to the database.
	 * The plugins option-prefix is automatically added to the option name.
	 *
	 * This function will only save the value if the option does not exist yet!
	 *
	 * Use this function instead of direct access via add_site_option()
	 *
	 * @since  4.0.0
	 *
	 * @param  string $name  The option name.
	 * @param  mixed  $value The new option value.
	 */
	public function add_option( $name, $value ) {
		$key = 'wdp_un_' . $name;

		/*
		Intentionally NO use of the `option_cache` variable because we cannot
		guarantee that the $value is actually saved to DB (it only is saved
		when the option does not exist yet)
		*/

		$value = add_site_option( $key, $value );
	}

	/**
	 * Returns the value of a plugin transient.
	 * The plugins option-prefix is automatically added to the transient name.
	 *
	 * Use this function instead of direct access via get_site_transient()
	 *
	 * @since  4.0.0
	 *
	 * @param  string $name   The transient name.
	 * @param  bool   $prefix Optional. Set to false to not prefix the name.
	 *
	 * @return mixed The transient value.
	 */
	public function get_transient( $name, $prefix = true ) {
		if ( $prefix ) {
			$key = 'wdp_un_' . $name;
		} else {
			$key = $name;
		}

		// Transient name cannot be longer than 45 characters.
		$key = substr( $key, 0, 45 );

		return get_site_transient( $key );
	}

	/**
	 * Updates the value of a plugin transient.
	 * The plugins option-prefix is automatically added to the transient name.
	 *
	 * Use this function instead of direct access via update_site_option()
	 *
	 * @since  4.0.0
	 *
	 * @param  string $name       The transient name.
	 * @param  mixed  $value      The new transient value.
	 * @param  int    $expiration Time until expiration. Default: No expiration.
	 */
	public function set_transient( $name, $value, $expiration = 0 ) {
		// Transient name cannot be longer than 45 characters.
		$key = substr( 'wdp_un_' . $name, 0, 45 );

		// Fix to prevent WP from hashing PHP objects.
		delete_site_transient( $key );

		if ( null !== $value ) {
			set_site_transient( $key, $value, $expiration );
		}
	}

	/**
	 * Returns a usermeta value of the current user.
	 *
	 * @since  4.0.0
	 *
	 * @param  string $name The meta-key.
	 *
	 * @return mixed The meta-value.
	 */
	public function get_usermeta( $name ) {
		$user_id = get_current_user_id();

		$value = get_user_meta( $user_id, $name, true );

		return $value;
	}

	/**
	 * Updates a usermeta value of the current user.
	 *
	 * @since  4.0.0
	 *
	 * @param  string $name  The transient name.
	 * @param  mixed  $value The new transient value.
	 */
	public function set_usermeta( $name, $value ) {
		$user_id = get_current_user_id();

		update_user_meta( $user_id, $name, $value );
	}

	/**
	 * Log out current WPMUDEV user from the local site and erase all cached
	 * data.
	 * After logout, the user is redirected to the login page.
	 *
	 * @since  4.0.5
	 *
	 * @param  bool $redirect If set to false, the user will not be redirected
	 *                        to the login page. Used for remote logout via ajax handler.
	 */
	public function logout( $redirect = true ) {
		// Prevent infinite loops...
		if ( ! WPMUDEV_Dashboard::$api->has_key() ) {
			return false;
		}

		WPMUDEV_Dashboard::$api->revoke_remote_access();
		// Attempt to disable only if enabled.
		if ( $this->get_option( 'analytics_enabled' ) ) {
			WPMUDEV_Dashboard::$api->analytics_disable();
		}
		$this->init_options( 'reset' );
		WPMUDEV_Dashboard::$api->set_key( '' );
		WPMUDEV_Dashboard::$api->hub_sync( false, true ); // force a sync so that site is removed from user's hub.

		if ( $redirect ) {
			// Directly redirect to login page.
			$urls = WPMUDEV_Dashboard::$ui->page_urls;
			WPMUDEV_Dashboard::$ui->redirect_to( $urls->dashboard_url );
		}
	}

	/**
	 * Converts the given date-time string or timestmap from GMT to the local
	 * WordPress timezone.
	 *
	 * @since  4.0.0
	 *
	 * @param  string|int $time Either a date-time expression or timestamp.
	 *
	 * @return int The timestamp in local WordPress timezone.
	 */
	public function to_localtime( $time ) {
		if ( is_numeric( $time ) ) {
			$gmt_timestamp = intval( $time );
		} else {
			$gmt_timestamp = strtotime( $time );
		}

		if ( ! $time ) {
			return 0;
		}

		$string = date( 'Y-m-d H:i:s', $gmt_timestamp );
		$tz     = get_option( 'timezone_string' ); // In Multisite networks this option is from the main blog.

		if ( $tz ) {
			$datetime = date_create( $string, new DateTimeZone( 'UTC' ) );
			if ( ! $datetime ) {
				return 0;
			}
			$datetime->setTimezone( new DateTimeZone( $tz ) );
			$localtime = strtotime( $datetime->format( 'Y-m-d H:i:s' ) );
		} else {
			if ( ! preg_match( '#([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})#', $string, $matches ) ) {
				return 0;
			}

			$gmt_offset = get_option( 'gmt_offset' ); // In Multisite networks this option is from the main blog.

			$string_time = gmmktime( $matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1] );
			$localtime   = $string_time + $gmt_offset * HOUR_IN_SECONDS;
		}

		return $localtime;
	}

	/**
	 * The proper way to get the array of locally installed products from cache.
	 *
	 * @since  1.0.0
	 *
	 * @param  int $project_id Optional. If set then a single project array
	 *                         will be returned. Default: Return full project list.
	 *
	 * @return array
	 */
	public function get_cached_projects( $project_id = null ) {
		$projects = $this->get_transient( 'local_projects' );

		if ( ! $projects || ! is_array( $projects ) ) {
			// Set param to true to avoid infinite loop.
			$projects = $this->scan_fs_local_projects();
			if ( is_array( $projects ) ) {
				// Save to be able to check for changes later.
				$this->set_transient(
					'local_projects',
					$projects,
					5 * MINUTE_IN_SECONDS
				);
			}
		}

		if ( $project_id ) {
			if ( isset( $projects[ $project_id ] ) ) {
				$res = $projects[ $project_id ];
			} else {
				$res = false;
			}
		} else {
			$res = $projects;
		}

		return $res;
	}

	/**
	 * Returns lots of details about the specified project.
	 *
	 * @since  4.0.0
	 *
	 * @param  int  $pid        The Project ID.
	 * @param  bool $fetch_full Optional. If true, then even potentially
	 *                          time-consuming preparation is done.
	 *                          e.g. load changelog via API.
	 *
	 * @return object Details about the project.
	 */
	public function get_project_info( $pid, $fetch_full = false ) {
		$pid              = intval( $pid );
		$is_network_admin = is_multisite(); // If multisite we only ever do things in network admin
		$urls             = WPMUDEV_Dashboard::$ui->page_urls;

		if ( ! is_array( self::$_cache_project_info ) ) {
			self::$_cache_project_info = array();
		}

		// build data if it's not cached or we need changelog and the changelog is missing from cache
		if ( ! isset( self::$_cache_project_info[ $pid ] ) || ( $fetch_full && ! count( self::$_cache_project_info[ $pid ]->changelog ) ) ) {
			$res = (object) array(
				'pid'                 => $pid,
				'type'                => '', // Possible: 'plugin' or 'theme'.
				'special'             => false, // Possible: false, 'dropin' or 'muplugin'.
				'name'                => '', // Project name.
				'path'                => '', // Full path to main project file.
				'filename'            => '', // Filename, relative to plugins/themes dir.
				'slug'                => '', // Slug used for updates.
				'version_latest'      => '0.0',
				'version_installed'   => '0.0',
				'has_update'          => false, // Is new version available?
				'can_update'          => false, // User has permission to update?
				'can_activate'        => false, // User has permission to activate/deactivate?
				'can_autoupdate'      => false, // If plugin should auto-update?
				'is_compatible'       => true, // Site has all requirements to install project?
				'incompatible_reason' => '', // If is_compatible is false.
				'need_upfront'        => false, // Only used by themes.
				'is_installed'        => false, // Installed on current site?
				'is_active'           => false, // WordPress state, i.e. plugin activated?
				'is_hidden'           => false, // Projects can be hidden via API.
				'is_licensed'         => false, // User has license to use this project?
				'default_order'       => 0,
				'downloads'           => 0,
				'popularity'          => 0,
				'release_stamp'       => 0,
				'update_stamp'        => 0,
				'info'                => '',
				'url'                 => (object) array(
					'instructions'     => '',
					'config'           => '',
					'activate'         => '',
					'deactivate'       => '',
					'install'          => '',
					'update'           => '',
					'download'         => '',
					'website'          => '',
					'thumbnail'        => '',
					'thumbnail_square' => '',
					'icon'             => '',
					'video'            => '',
					'infos'            => '',
				),
				'changelog'           => array(),
				'features'            => array(),
				'tags'                => array(),
				'screenshots'         => array(),
				'free_version_slug'   => '',
			);

			$remote = WPMUDEV_Dashboard::$api->get_project_data( $pid );
			if ( empty( $remote ) ) {
				self::$_cache_project_info[ $pid ] = false;

				return false;
			}
			$local           = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
			$system_projects = WPMUDEV_Dashboard::$site->get_system_projects();

			// General details.
			$res->type           = ( 'theme' === $remote['type'] ? 'theme' : 'plugin' );
			$res->name           = $remote['name'];
			$res->info           = strip_tags( $remote['short_description'] );
			$res->version_latest = $remote['version'];
			$res->features       = $remote['features'];
			$res->default_order  = isset( $remote['_order'] ) ? intval( $remote['_order'] ) : 0;
			$res->downloads      = intval( $remote['downloads'] );
			$res->popularity     = intval( $remote['popularity'] );
			$res->release_stamp  = intval( $remote['released'] );
			$res->update_stamp   = intval( $remote['updated'] );

			// Project tags.
			if ( 'plugin' === $res->type ) {
				$tags = WPMUDEV_Dashboard::$ui->tags_data( 'plugin' );
			} else {
				$tags = WPMUDEV_Dashboard::$ui->tags_data( 'theme' );
			}
			foreach ( $tags as $tid => $tag ) {
				if ( ! in_array( $pid, $tag['pids'] ) ) {
					continue;
				}
				$res->tags[ $tid ] = $tag['name'];
			}

			// Status details.
			$res->can_update  = WPMUDEV_Dashboard::$upgrader->user_can_install( $pid );
			$res->is_licensed = WPMUDEV_Dashboard::$upgrader->user_can_install( $pid, true );

			if ( 'theme' == $res->type ) {
				$res->need_upfront = $this->is_upfront_theme( $pid );
			} elseif ( $this->id_upfront_builder == $pid ) { // the upfront builder plugin requires Upfront theme
				$res->need_upfront = true;
			}

			$res->is_installed = WPMUDEV_Dashboard::$upgrader->is_project_installed( $pid );

			$res->can_autoupdate = ( '1' == $remote['autoupdate'] ); // this has nothing to do with permissions, just project capability
			$res->is_compatible  = WPMUDEV_Dashboard::$upgrader->is_project_compatible( $pid, $incompatible_reason );

			// Plugin can be active, even if not licensed:
			// E.g. it was installed and then the admin logged out from WPMU DEV
			// Dashboard, or the API-Key was changed from the Hub, ...
			if ( $res->is_installed ) {
				if ( ! empty( $local['name'] ) ) {
					$res->name = $local['name'];
				}
				if ( 'muplugin' === $local['type'] ) {
					$res->special = $local['type'];
				} elseif ( 'dropin' === $local['type'] ) {
					$res->special = $local['type'];
				}
				$res->path              = $local['path'];
				$res->filename          = $local['filename'];
				$res->slug              = $local['slug'];
				$res->version_installed = $local['version'];
				$res->has_update        = WPMUDEV_Dashboard::$upgrader->is_update_available( $pid );

				if ( 'plugin' === $res->type ) {
					if ( ! function_exists( 'is_plugin_active' ) ) {
						include_once ABSPATH . 'wp-admin/includes/plugin.php';
					}

					if ( $is_network_admin ) {
						$res->is_active = is_plugin_active_for_network( $res->filename );
					} else {
						$res->is_active = is_plugin_active( $res->filename );
					}
				} elseif ( 'theme' === $res->type ) {
					if ( $is_network_admin ) {
						$allowed_themes = get_site_option( 'allowedthemes' );
						$res->is_active = ! empty( $allowed_themes[ $res->slug ] );
					} else {
						$res->is_active = get_option( 'stylesheet' ) === $res->slug;
					}
				}
			}
			if ( in_array( $pid, $system_projects ) ) {
				// Hardcoded by plugin, those are always hidden!
				$res->is_hidden = true;
			} elseif ( $res->is_installed ) {
				// Installed projects are always visible.
				$res->is_hidden = false;
			} elseif ( 'theme' === $res->type && $this->is_legacy_theme( $pid ) ) {
				// Hide all non-installed legacy themes.
				$res->is_hidden = true;
			} else {
				// Project is not installed, then use flag from API.
				$res->is_hidden = ! $remote['active'];
			}
			if ( 'plugin' === $res->type ) {
				if ( $res->special ) {
					// MU-Plugins and Dropins cannot be (de)activated.
					$res->can_activate = false;
				} elseif ( $is_network_admin ) {
					$res->can_activate = current_user_can( 'manage_network_plugins' );
				} else {
					$res->can_activate = current_user_can( 'activate_plugins' );
				}
			} elseif ( 'theme' === $res->type ) {
				if ( $is_network_admin ) {
					$res->can_activate = current_user_can( 'manage_network_themes' );
				} else {
					$res->can_activate = current_user_can( 'switch_themes' );
				}
			}

			// URLs.
			$res->url->website = esc_url( $remote['url'] );
			if ( ! empty( $remote['thumbnail_large'] ) ) {
				$res->url->thumbnail = esc_url( $remote['thumbnail_large'] );
			} else {
				$res->url->thumbnail = esc_url( $remote['thumbnail'] );
			}
			if ( ! empty( $remote['thumbnail_square'] ) ) {
				$res->url->thumbnail_square = esc_url( $remote['thumbnail_square'] );
			} else {
				$res->url->thumbnail_square = esc_url( $remote['thumbnail'] );
			}
			// Project icon.
			if ( ! empty( $remote['icon'] ) ) {
				$res->url->icon = esc_url( $remote['icon'] );
			} else {
				$res->url->icon = $res->url->thumbnail_square;
			}
			$res->url->video        = esc_url( $remote['video'] );
			$res->url->instructions = WPMUDEV_Dashboard::$api->rest_url( 'usage/' . $pid );

			if ( $res->is_active ) {
				if ( 'plugin' == $res->type ) {
					if ( $is_network_admin && ! empty( $remote['ms_config_url'] ) ) {
						$res->url->config = esc_url( network_admin_url( $remote['ms_config_url'] ) );
					} elseif ( ! $is_network_admin && ! empty( $remote['wp_config_url'] ) ) {
						$res->url->config = esc_url( admin_url( $remote['wp_config_url'] ) );
					}
				}
			}

			$res->url->install  = WPMUDEV_Dashboard::$upgrader->auto_install_url( $pid );
			$res->url->download = esc_url( $remote['url'] );
			if ( ! $res->special ) {
				// I.e. only if plugin is no dropin/muplugin.
				$res->url->update = WPMUDEV_Dashboard::$upgrader->auto_update_url( $pid );
			}
			if ( ! $res->is_compatible ) {
				switch ( $incompatible_reason ) {
					case 'multisite':
						$res->incompatible_reason = __( 'Requires Multisite', 'wpmudev' );
						break;

					case 'buddypress':
						$res->incompatible_reason = __( 'Requires BuddyPress', 'wpmudev' );
						break;

					default:
						$res->incompatible_reason = __( 'Incompatible', 'wpmudev' );
						break;
				}
			}

			// When not logged in, the project-ID is passed as URL param!
			$pid_sep = WPMUDEV_Dashboard::$api->has_key() ? '#' : '&';

			if ( 'plugin' == $res->type ) {
				$res->url->infos = $urls->plugins_url . $pid_sep . 'pid=' . $pid;

				$res->url->deactivate = 'plugins.php?action=deactivate&plugin=' . urlencode( $res->filename );
				$res->url->activate   = 'plugins.php?action=activate&plugin=' . urlencode( $res->filename );

				if ( $is_network_admin ) {
					$res->url->deactivate = network_admin_url( $res->url->deactivate );
					$res->url->activate   = network_admin_url( $res->url->activate );
				} else {
					$res->url->deactivate = admin_url( $res->url->deactivate );
					$res->url->activate   = admin_url( $res->url->activate );
				}
				$res->url->deactivate = wp_nonce_url( $res->url->deactivate, 'deactivate-plugin_' . $res->filename );
				$res->url->activate   = wp_nonce_url( $res->url->activate, 'activate-plugin_' . $res->filename );
			} elseif ( 'theme' == $res->type ) {
				$res->url->infos = $urls->themes_url . $pid_sep . 'pid=' . $pid;

				if ( $is_network_admin ) {
					/*
					 * In Network-Admin following theme-actions are disabled:
					 * - Activate
					 * - Configure
					 */
					$res->url->activate = false;
					$res->url->config   = false;
				} else {
					$res->url->activate = wp_nonce_url(
						'themes.php?action=activate&template=' . urlencode( $res->filename ) . '&stylesheet=' . urlencode( $res->filename ),
						'switch-theme_' . $res->filename
					);
					if ( $res->need_upfront ) {
						$res->url->config = home_url( '/?editmode=true' );
					} else {
						$return_url       = urlencode( WPMUDEV_Dashboard::$ui->page_urls->themes_url );
						$res->url->config = admin_url( 'customize.php?return=' . $return_url );
					}
				}
			}
			$res->screenshots = $remote['screenshots'];

			$res->free_version_slug = $remote['free_version_slug'];
			// Temporary fix for Branda and Beehive missing or having invalid free version slugs.
			if ( 9135 === $pid ) {
				$res->free_version_slug = 'branda-white-labeling/ultimate-branding.php';
			}
			if ( 51 === $pid ) {
				$res->free_version_slug = 'beehive-analytics/beehive-analytics.php';
			}
			if ( 2097296 === $pid ) {
				$res->free_version_slug = 'forminator/forminator.php';
			}
			// Temporary fix end.

			// Performance: Only fetch changelog if needed.
			if ( $fetch_full ) {
				$res->changelog = WPMUDEV_Dashboard::$api->get_changelog(
					$pid,
					$res->version_latest
				);

			}

			self::$_cache_project_info[ $pid ] = $res;
		}

		// Following flags are not cached.
		if ( self::$_cache_project_info[ $pid ] && is_object( self::$_cache_project_info[ $pid ] ) ) {
			self::$_cache_project_info[ $pid ]->is_network_admin = $is_network_admin;
		} else {
			self::$_cache_project_info[ $pid ] = false;
		}

		return self::$_cache_project_info[ $pid ];
	}

	/**
	 * Returns a list of all installed 133-theme-pack themes.
	 *
	 * @since  1.0.0
	 * @return array|false
	 */
	public function get_farm133_themepack() {
		return $this->get_option( 'farm133_themes' );
	}

	/**
	 * Check if a given theme project id is an Upfront theme.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $project_id The project to check.
	 *
	 * @return bool
	 */
	public function is_upfront_theme( $project_id ) {
		if ( $project_id == $this->id_upfront ) {
			return false;
		}
		if ( $project_id <= $this->id_legacy_themes ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if a given theme project id is a legacy theme.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $project_id The project to check.
	 *
	 * @return bool
	 */
	public function is_legacy_theme( $project_id ) {
		if ( $project_id > $this->id_legacy_themes ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if root Upfront project is installed.
	 *
	 * @since  3.0.0
	 * @return bool
	 */
	public function is_upfront_installed() {
		$data = $this->get_cached_projects( $this->id_upfront );

		return ( ! empty( $data ) );
	}

	/**
	 * Check if a child Upfront project is installed.
	 *
	 * @since  3.0.0
	 * @return bool
	 */
	public function is_upfront_theme_installed() {
		$result         = false;
		$local_projects = $this->get_cached_projects();

		foreach ( $local_projects as $project_id => $project ) {
			// Quit on first theme installed greater than legacy threshold.
			if ( 'theme' == $project['type'] && $this->is_upfront_theme( $project_id ) ) {
				$result = true;
				break;
			}
		}

		return $result;
	}

	/**
	 * Return the currently active WPMUDEV theme.
	 * If current theme is no WPMUDEV theme, the function returns false.
	 *
	 * Only works on single-site installations!
	 *
	 * @since  4.0.0
	 * @return bool
	 */
	public function get_active_wpmu_theme() {
		$result           = false;
		$is_network_admin = is_multisite() && ( is_network_admin() || ! empty( $_REQUEST['is_network'] ) );

		// Network-installations do not support this function.
		if ( $is_network_admin ) {
			return $result;
		}

		$local_projects = $this->get_cached_projects();
		$current        = get_option( 'stylesheet' );
		foreach ( $local_projects as $project_id => $project ) {
			if ( 'theme' == $project['type'] && $project['slug'] == $current ) {
				$result = $project_id;
				break;
			}
		}

		return $result;
	}

	/**
	 * Checks if the current user is in the list of allowed users of the Dashboard.
	 * Allows for multiple users allowed in define, e.g. in this format:
	 *
	 * <code>
	 *  define("WPMUDEV_LIMIT_TO_USER", "1, 10, 15");
	 * </code>
	 *
	 * @since  1.0.0
	 *
	 * @param  int $user_id Optional. If empty then the current user-ID is used.
	 *
	 * @return bool
	 */
	public function allowed_user( $user_id = null ) {
		// If this is a remote call from WPMUDEV remote dashboard then allow.
		if ( ! $user_id && defined( 'WPMUDEV_IS_REMOTE' ) && WPMUDEV_IS_REMOTE ) {
			return true;
		}

		// Balk if this is called too early.
		if ( ! $user_id && ! did_action( 'set_current_user' ) ) {
			return false;
		}

		if ( empty( $user_id ) ) {
			/*
			 * @todo calling this too soon bugs out in some wp installs
			 * http://wpmudev.com/forums/topic/urgenti-lost-permission-after-upgrading#post-227543
			 */
			$user_id = get_current_user_id();
		}

		$allowed = $this->get_allowed_users( true );

		return in_array( $user_id, $allowed );
	}

	/**
	 * Grant access to the WPMU DEV Dashboard to a new admin user.
	 *
	 * @since  4.0.0
	 *
	 * @param  int $user_id The user to add.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function add_allowed_user( $user_id ) {
		$user = get_userdata( $user_id );

		if ( ! $user || ! is_a( $user, 'WP_User' ) ) {
			// User not found.
			return false;
		}

		$need_cap = 'manage_options';
		if ( is_multisite() ) {
			$need_cap = 'manage_network_options';
		}

		if ( ! $user->has_cap( $need_cap ) ) {
			// User is no admin.
			return false;
		}

		$allowed = WPMUDEV_Dashboard::$site->get_option( 'limit_to_user', true, array() );
		$allowed = (array) $allowed;

		if ( in_array( $user_id, $allowed ) ) {
			// User was already added.
			return false;
		}

		$allowed[] = $user_id;
		WPMUDEV_Dashboard::$site->set_option( 'limit_to_user', $allowed );

		return true;
	}

	/**
	 * Remove access to the WPMU DEV Dashboard from another admin user.
	 *
	 * @since  4.0.0
	 *
	 * @param  int $user_id The user to remove.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function remove_allowed_user( $user_id ) {
		$allowed = WPMUDEV_Dashboard::$site->get_option( 'limit_to_user' );
		if ( empty( $allowed ) || ! is_array( $allowed ) ) {
			// The allowed-list is still empty.
			return false;
		}

		$key = array_search( $user_id, $allowed );
		if ( false === $key ) {
			// User not found in the allowed-list.
			return false;
		}

		unset( $allowed[ $key ] );
		$allowed = array_values( $allowed );
		WPMUDEV_Dashboard::$site->set_option( 'limit_to_user', $allowed );

		return true;
	}

	/**
	 * Get a human readable list of users with allowed permissions for the
	 * Dashboard.
	 *
	 * @since  1.0.0
	 *
	 * @param  bool $id_only Return only user-IDs or full usernames.
	 *
	 * @return array|bool
	 */
	public function get_allowed_users( $id_only = false ) {
		$result = false;

		if ( WPMUDEV_LIMIT_TO_USER ) {
			// Hardcoded list of users.
			if ( is_array( WPMUDEV_LIMIT_TO_USER ) ) {
				$allowed = WPMUDEV_LIMIT_TO_USER;
			} else {
				$allowed = explode( ',', WPMUDEV_LIMIT_TO_USER );
				$allowed = array_map( 'trim', $allowed );
			}
			$allowed = array_map( 'intval', $allowed );
		} else {
			$changed = false;

			// Default: Allow users based on DB settings.
			$allowed = WPMUDEV_Dashboard::$site->get_option( 'limit_to_user' );
			if ( $allowed ) {
				if ( ! is_array( $allowed ) ) {
					$allowed = array( $allowed );
					$changed = true;
				}
			} else {
				// If not set, then add current user as allowed user.
				$cur_user_id = get_current_user_id();
				if ( $cur_user_id ) {
					$allowed = array( $cur_user_id );
					$changed = true;
				} else {
					$allowed = array();
				}
			}

			// Sanitize allowed users after login to Dashboard, so we can
			// react to changes in the user capabilities.
			if ( ! empty( $allowed ) && WPMUDEV_Dashboard::$api->has_key() ) {
				$need_cap = 'manage_options';
				if ( is_multisite() ) {
					$need_cap = 'manage_network_options';
				}

				// Remove invalid users from the allowed-users-list.
				foreach ( $allowed as $key => $user_id ) {
					$user = get_userdata( $user_id );
					if ( ! $user || ! is_a( $user, 'WP_User' ) ) {
						unset( $allowed[ $key ] );
						$changed = true;
					} elseif ( ! $user->has_cap( $need_cap ) ) {
						unset( $allowed[ $key ] );
						$changed = true;
					}
				}

				if ( $changed ) {
					WPMUDEV_Dashboard::$site->set_option( 'limit_to_user', $allowed );
				}
			}
		}

		if ( $id_only ) {
			$result = $allowed;
		} else {
			$result = array();
			foreach ( $allowed as $user_id ) {
				if ( $user_info = get_userdata( $user_id ) ) {
					$result[] = array(
						'id'           => $user_id,
						'name'         => $user_info->display_name,
						'email'        => $user_info->user_email,
						'first_name'   => $user_info->user_firstname,
						'last_name'    => $user_info->user_lastname,
						'username'     => $user_info->user_login,
						'is_me'        => get_current_user_id() == $user_id,
						'profile_link' => get_edit_user_link( $user_id ),
					);
				}
			}
		}

		return $result;
	}

	/**
	 * Get the list of admin users available to add.
	 *
	 * Get all admin capable users which is not added yet
	 * to list.
	 *
	 * @since 4.11.2
	 *
	 * @return array
	 */
	public function get_available_users() {
		$result = array();

		// Get already allowed users.
		$allowed = $this->get_allowed_users( true );

		// We need only IDs for now.
		$args = array( 'fields' => 'ID' );

		// Exclude already allowed users.
		if ( ! empty( $allowed ) ) {
			$args['exclude'] = $allowed;
		}

		// To get from all blogs on multisite.
		if ( is_multisite() ) {
			$args['blog_id'] = 0;
		}

		// Required capability.
		$need_cap = is_multisite() ? 'manage_network_options' : 'manage_options';

		// Get user IDs.
		$users = get_users( $args );

		if ( ! empty( $users ) ) {
			foreach ( $users as $user_id ) {
				// Only admin capable users.
				if ( ! user_can( $user_id, $need_cap ) ) {
					continue;
				}

				// Include user details.
				$user_info = get_userdata( $user_id );
				if ( ! empty( $user_info ) ) {
					$result[] = array(
						'id'         => $user_id,
						'name'       => $user_info->display_name,
						'first_name' => $user_info->user_firstname,
						'last_name'  => $user_info->user_lastname,
						'email'      => $user_info->user_email,
						'username'   => $user_info->user_login,
						'is_me'      => get_current_user_id() === $user_id,
					);
				}
			}
		}

		return $result;
	}

	/**
	 * Returns a list of users with manage_options capability.
	 *
	 * The currently logged in user is excluded from the return value, since
	 * this user is not a potentialy but an actualy allowed user.
	 *
	 * @since  4.0.0
	 *
	 * @param  string $filter Optional. Filter by user name.
	 *
	 * @return array List of user-details
	 */
	protected function get_potential_users( $filter ) {
		global $wpdb;

		/*
		 * We build a custom SQL here so we can also get users that are not
		 * assigned to a specific blog but only have access to the network
		 * admin (on multisites).
		 */
		$sql    = "
		SELECT
			u.ID as id,
			u.display_name,
			m_fn.meta_value as first_name,
			m_ln.meta_value as last_name
		FROM {$wpdb->users} u
			LEFT JOIN {$wpdb->usermeta} m_fn ON m_fn.user_id=u.ID AND m_fn.meta_key='first_name'
			LEFT JOIN {$wpdb->usermeta} m_ln ON m_ln.user_id=u.ID AND m_ln.meta_key='last_name'
		WHERE
			u.ID != %d
			AND (u.display_name LIKE %s OR m_fn.meta_value LIKE %s OR m_ln.meta_value LIKE %s OR u.user_email LIKE %s)
		";
		$filter = '%' . $filter . '%';
		$sql    = $wpdb->prepare(
			$sql,
			get_current_user_id(),
			$filter,
			$filter,
			$filter,
			$filter
		);

		// Now we have a list of all users, no matter which blog they belong to.
		$res = $wpdb->get_results( $sql );

		$need_cap = 'manage_options';
		if ( is_multisite() ) {
			$need_cap = 'manage_network_options';
		}

		$items = array();
		// Filter users by capabilty.
		foreach ( $res as $item ) {
			$user = get_userdata( $item->id );
			if ( ! $user || ! is_a( $user, 'WP_User' ) ) {
				continue;
			}
			if ( ! $user->has_cap( $need_cap ) ) {
				continue;
			}
			if ( $this->allowed_user( $user->ID ) ) {
				continue;
			}

			$items[] = (object) array(
				'id'         => $user->ID,
				'name'       => $user->display_name,
				'first_name' => $user->user_firstname,
				'last_name'  => $user->user_lastname,
				'email'      => $user->user_email,
				'avatar'     => get_avatar_url( $user->ID ),
			);
		}

		return $items;
	}

	/**
	 * Returns a list of projects that match the specified name.
	 *
	 * @since  4.0.0
	 *
	 * @param  string $filter Optional. Filter by project name.
	 *
	 * @return array List of project-details
	 */
	protected function find_projects_by_name( $filter ) {
		$data     = WPMUDEV_Dashboard::$api->get_projects_data();
		$projects = $data['projects'];

		// Remove legacy themes.
		foreach ( $projects as $key => $project ) {
			if ( 'theme' != $project['type'] ) {
				continue;
			}
			if ( WPMUDEV_Dashboard::$site->is_legacy_theme( $project['id'] ) ) {
				unset( $projects[ $key ] );
			}
		}

		$items = array();

		foreach ( $projects as $item ) {
			$data = $this->get_project_info( $item['id'] );

			if ( $data->is_hidden ) {
				continue;
			}
			if ( false === stripos( $data->name, $filter ) ) {
				continue;
			}

			$items[] = (object) array(
				'id'        => $data->pid,
				'name'      => $data->name,
				'desc'      => $data->info,
				'logo'      => $data->url->thumbnail,
				'type'      => $data->type,
				'installed' => $data->is_installed,
			);
		}

		return $items;
	}

	/**
	 * Detect if this is a development site running on a private/loopback IP
	 *
	 * @return bool
	 */
	public function is_localhost() {
		$loopbacks = array( '127.0.0.1', '::1' );
		if ( in_array( $_SERVER['REMOTE_ADDR'], $loopbacks ) ) {
			return true;
		}

		if ( ! filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check user permissions to see if we can install this project.
	 *
	 * @since  1.0.0
	 *
	 * @param  int  $project_id   The project to check.
	 * @param  bool $only_license Skip permission check, only validate license.
	 *
	 * @return bool
	 */
	public function user_can_install( $project_id, $only_license = false ) {
		$data              = WPMUDEV_Dashboard::$api->get_projects_data();
		$membership_type   = WPMUDEV_Dashboard::$api->get_membership_type();
		$licensed_projects = WPMUDEV_Dashboard::$api->get_membership_projects();

		if ( 'unit' === $membership_type ) {
			foreach ( $licensed_projects as $p ) {
				$is_allowed = intval( $project_id ) === $p;
				if ( $is_allowed ) {
					return true;
				}
			}
		}

		// Basic check if we have valid data.
		if ( empty( $data['projects'] ) ) {
			return false;
		}
		if ( empty( $data['projects'][ $project_id ] ) ) {
			return false;
		}

		$project = $data['projects'][ $project_id ];

		if ( ! $only_license ) {
			if ( ! $this->allowed_user() ) {
				return false;
			}
			if ( ! WPMUDEV_Dashboard::$upgrader->can_auto_install( $project['type'] ) ) {
				return false;
			}
		}

		$is_upfront = WPMUDEV_Dashboard::$site->id_upfront == $project_id;
		$package    = isset( $project['package'] ) ? $project['package'] : '';
		$access     = false;

		if ( 'full' == $membership_type ) {
			// User has full membership.
			$access = true;
		} elseif ( 'single' == $membership_type && $licensed_projects == $project_id ) {
			// User has single membership for the requested project.
			$access = true;
		} elseif ( 'free' == $project['paid'] ) {
			// It's a free project. All users can install this.
			$access = true;
		} elseif ( 'lite' == $project['paid'] ) {
			// It's a lite project. All users can install this.
			$access = true;
		} elseif ( 'single' == $membership_type && $package && $package == $licensed_projects ) {
			// A packaged project that the user bought.
			$access = true;
		} elseif ( $is_upfront && 'single' == $membership_type ) {
			// User wants to get Upfront parent theme.
			$access = true;
		}

		return $access;
	}

	/**
	 * Returns a list of internal/hidden/deprecated projects.
	 *
	 * @since  4.0.0
	 * @return array
	 */
	public function get_system_projects() {
		$list = array(
			// Upfront parent is hidden.
			WPMUDEV_Dashboard::$site->id_upfront,
		);

		return $list;
	}

	/*
	 * *********************************************************************** *
	 * *     INTERNAL ACTION HANDLERS
	 * *********************************************************************** *
	 */


	/**
	 * Check for any compatibility issues or important updates and display a
	 * notice if found.
	 *
	 * @since  4.0.0
	 */
	public function compatibility_warnings() {
		if ( $this->is_upfront_theme_installed() && ! $this->is_upfront_installed() ) {
			// Upfront child theme is installed but not parent theme is missing:
			// Only display this on the WP Dashboard page.
			$upfront = $this->get_project_info( $this->id_upfront );

			if ( is_object( $upfront ) ) {
				do_action(
					'wpmudev_override_notice',
					__( '<b>The Upfront parent theme is missing!</b><br>Please install it to use your Upfront child themes', 'wpmudev' ),
					'<a href="' . $upfront->url->install . '" class="button button-primary">Install Upfront</a>'
				);
			}
		} elseif ( $this->is_upfront_installed() ) {
			$upfront = $this->get_project_info( $this->id_upfront );

			if ( is_object( $upfront ) && $upfront->has_update ) {
				// Upfront update is available:
				// Only display this message in the WPMUDEV Themes page!
				add_action(
					'wpmudev_dashboard_notice-themes',
					array( $this, 'notice_upfront_update' )
				);
			}
		}
	}

	/**
	 * Display a notification on the Themes page.
	 *
	 * @since  4.0.3
	 */
	public function notice_upfront_update() {
		$upfront_url = '#update=' . $this->id_upfront;
		$message     = sprintf(
			'<b>%s</b><br>%s',
			__( 'Awesome news for Upfront', 'wpmudev' ),
			__( 'We have a new version of Upfront for you! Install it right now to get all the latest improvements and features.', 'wpmudev' )
		);

		$cta = sprintf(
			'<span data-project="%s">
			<a href="%s" class="button show-project-update">Update Upfront</a>
			</span>',
			$this->id_upfront,
			$upfront_url
		);

		do_action( 'wpmudev_override_notice', $message, $cta );

		WPMUDEV_Dashboard::$notice->setup_message();
	}

	/**
	 * Intercept the default WP iframe that displays a plugins changelog details
	 * and render our own changelog layout if we detect a WPMUDEV project.
	 *
	 * @since  4.0.5
	 */
	public function install_plugin_information() {
		global $tab;

		if ( empty( $_REQUEST['plugin'] ) ) {
			return;
		}
		if ( 'plugin-information' != $tab ) {
			return;
		}

		$search_for = $_REQUEST['plugin'];
		$projects   = $this->get_cached_projects();
		$project_id = false;
		foreach ( $projects as $id => $item ) {
			if ( $_REQUEST['plugin'] == $item['slug'] ) {
				$project_id = $id;
				break;
			}
		}

		if ( $project_id ) {
			WPMUDEV_Dashboard::$ui->wp_popup_changelog( $project_id );
		}
	}

	/**
	 * Does a filesystem scan for local plugins/themes and caches it. If any
	 * changes found it will trigger remote api check and calculate upgrades as well.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $check Either 'local' or 'remote'. Local will only scan
	 *                       the local FS for changes. Remote will also query the API
	 *                       and schedule updates.
	 *
	 * @return array
	 */
	public function refresh_local_projects( $check = 'remote' ) {
		// 1. Scan local FS to find projects.
		$local_projects = $this->scan_fs_local_projects();

		// 2. Load the cached project-list from DB.
		$saved_local_projects = $this->get_cached_projects();

		// Check for changes.
		$md5_db = md5( json_encode( $saved_local_projects ) );
		$md5_fs = md5( json_encode( $local_projects ) );

		if ( 'remote' == $check || $md5_db != $md5_fs ) {
			self::$_cache_themeupdates       = false;
			self::$_cache_pluginupdates      = false;
			self::$_cache_translationupdates = false;

			$this->set_transient(
				'local_projects',
				$local_projects,
				5 * MINUTE_IN_SECONDS
			);
			$this->set_option( 'updates_available', false );

			// check if is manual check for updates
			if ( isset( $_REQUEST['action'] ) && 'check-updates' === $_REQUEST['action'] ) {
				// force hubsync
				$full_sync = true;
			} else {
				$full_sync = false;
			}

			WPMUDEV_Dashboard::$api->hub_sync( $local_projects, $full_sync );

			// Recalculate upgrades with current/updated data.
			WPMUDEV_Dashboard::$api->calculate_upgrades( $local_projects );
		}

		return $local_projects;
	}

	/**
	 * Used to call refresh_local_projects from a hook (strip passed arguments)
	 *
	 * @since    1.0.0
	 * @internal Action hook
	 */
	public function refresh_local_projects_wrapper() {
		if ( self::$_refresh_updates_flag || isset( $_GET['force-check'] ) ) {
			self::$_refresh_updates_flag  = false;
			self::$_refresh_shutdown_flag = false;
			WPMUDEV_Dashboard::$api->refresh_projects_data();
			$this->refresh_local_projects( 'remote' );
		} else {
			$this->refresh_local_projects( 'local' );
		}
	}

	/**
	 * A scheduled event that runs twicedaily on multisite networks. This
	 * function loops through all blogs and stores a list of active WPMUDEV
	 * plugins/themes in a sitemeta value.
	 * This information is used by API::refresh_membership_data() to send an
	 * accurate list of active projects to the API server.
	 *
	 * @todo   We do not want to use site-loops. Find a different way!
	 *
	 * @since  4.0.8
	 */
	public function refresh_blog_project_status() {
		/*
		What a shame. We need to find a more efficient way than this...

		// No need for caching on single-sites.
		if ( ! is_multisite() ) { return; }

		// No point in doing this for large networks (more than 10.000 sites).
		if ( wp_is_large_network() ) { return; }

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php' ;
		}

		$local_projects = WPMUDEV_Dashboard::$site->get_cached_projects();
		$active_projects = array();

		// First filter out network-wide-actived plugins.
		foreach ( $local_projects as $pid => $item ) {
			if ( 'theme' == $item['type'] ) { continue; }
			if ( is_plugin_active_for_network( $item['filename'] ) ) {
				unset( $local_projects[ $pid ] );
			}
		}

		// We "only" scan the first 1000 sites, not whole network.
		$scan_sites = wp_get_sites(
			array(
				'archived' => false,
				'spam' => false,
				'deleted' => false,
				'limit' => 1000,
			)
		);

		foreach ( $scan_sites as $site ) {
			switch_to_blog( $site['blog_id'] );
			if ( ! count( $local_projects ) ) {
				// Yay! All our plugins are active, no more checks needed...
				break;
			}
			foreach ( $local_projects as $pid => $item ) {
				if ( 'theme' == $item['type'] ) {
					$theme = wp_get_theme();
					$slug = dirname( $item['filename'] );
					if ( $theme->stylesheet == $slug || $theme->template == $slug ) {
						$active_projects[ $pid ] = true;
						unset( $local_projects[ $pid ] );
					}
				} else {
					if ( is_plugin_active( $item['filename'] ) ) {
						$active_projects[ $pid ] = true;
						unset( $local_projects[ $pid ] );
					}
				}
			}
			restore_current_blog();
		}

		// Now save the list with active projects of first 1000 active blogs!
		WPMUDEV_Dashboard::$site->set_option(
			'blog_active_projects',
			$active_projects
		);
		*/
	}

	/**
	 * This handler is called right before starting an upgrade/installation
	 * process, it makes sure that the upgrader will get uncached and
	 * up-to-date details.
	 *
	 * @since  4.1.0
	 */
	public function clear_local_file_cache() {
		self::$_cache_project_info = false;
		$this->set_transient( 'local_projects', false );
	}

	/**
	 * This handler is called right after a plugin was installed or updated.
	 * It instructs the dashboard to flush all caches (i.e. filesystem is
	 * scanned again, the transient is re-generated, ...)
	 *
	 * @since  4.0.7
	 */
	public function after_local_files_changed() {
		self::$_cache_themeupdates  = false;
		self::$_cache_pluginupdates = false;
		$this->clear_local_file_cache();
	}

	/**
	 * Something happened that we need to send data to DEV, so schedule it to run on shutdown hook
	 *
	 * @since  4.2
	 */
	public function schedule_shutdown_refresh() {
		self::$_refresh_shutdown_flag = true;
	}

	/**
	 * Sends latest data to DEV if schedule at end of page load
	 */
	public function shutdown_refresh() {
		if ( self::$_refresh_shutdown_flag && ! defined( 'WPMUDEV_REMOTE_SKIP_SYNC' ) ) {
			WPMUDEV_Dashboard::$site->refresh_local_projects( 'remote' );
		}
	}

	/**
	 * Scans all folder locations and compiles a list of WPMU DEV plugins and
	 * themes and header data.
	 * Also saves 133 theme pack themes into option for later use.
	 *
	 * @since  1.0.0
	 * @internal
	 * @return array Local projects
	 */
	protected function scan_fs_local_projects() {
		$projects = array();

		// ----------------------------------------------------------------------------------
		// Plugins directory.
		// ----------------------------------------------------------------------------------
		$plugins_root = WP_PLUGIN_DIR;
		if ( empty( $plugins_root ) ) {
			$plugins_root = ABSPATH . 'wp-content/plugins';
		}

		$items = $this->find_project_files( $plugins_root, '.php', true );
		foreach ( $items as $item ) {
			if ( isset( $projects[ $item['pid'] ] ) ) {
				continue;
			}

			$item['type']             = 'plugin';
			$projects[ $item['pid'] ] = $item;
		}

		// ----------------------------------------------------------------------------------
		// mu-plugins directory.
		// ----------------------------------------------------------------------------------
		$mu_plugins_root = WPMU_PLUGIN_DIR;
		if ( empty( $mu_plugins_root ) ) {
			$mu_plugins_root = ABSPATH . 'wp-content/mu-plugins';
		}

		$items = $this->find_project_files( $mu_plugins_root, '.php', false );
		foreach ( $items as $item ) {
			if ( isset( $projects[ $item['pid'] ] ) ) {
				continue;
			}

			$item['type']             = 'muplugin';
			$projects[ $item['pid'] ] = $item;
		}

		// ----------------------------------------------------------------------------------
		// wp-content directory.
		// ----------------------------------------------------------------------------------
		$content_plugins_root = WP_CONTENT_DIR;
		if ( empty( $content_plugins_root ) ) {
			$content_plugins_root = ABSPATH . 'wp-content';
		}

		$items = $this->find_project_files( $content_plugins_root, '.php', false );
		foreach ( $items as $item ) {
			if ( isset( $projects[ $item['pid'] ] ) ) {
				continue;
			}

			$item['type']             = 'dropin';
			$projects[ $item['pid'] ] = $item;
		}

		// ----------------------------------------------------------------------------------
		// Themes directory.
		// ----------------------------------------------------------------------------------
		$themes_root = WP_CONTENT_DIR . '/themes';
		if ( empty( $themes_root ) ) {
			$themes_root = ABSPATH . 'wp-content/themes';
		}

		$items = $this->find_project_files( $themes_root, '.css', true );

		foreach ( $items as $item ) {
			// Skip 133-Farm-Pack themes.
			if ( $item['pid'] == $this->id_farm133_themes ) {
				continue;
			}

			// Skip child themes.
			if ( false !== strpos( $item['filename'], '-child' ) ) {
				continue;
			}

			$item['type']             = 'theme';
			$item['slug']             = basename( dirname( $item['path'] ) );
			$projects[ $item['pid'] ] = $item;
		}

		$farm133_themes = $this->scan_fs_farm133_themes();

		if ( count( $farm133_themes ) ) {
			$farm133_project                      = reset( $farm133_themes );
			$projects[ $this->id_farm133_themes ] = $farm133_project;
		}

		return $projects;
	}

	/**
	 * Scan the WP themes directory for all 133-Farm-Pack themes and cache the
	 * result in DB option.
	 *
	 * @since  1.0.0
	 * @return array List with all farm133 themes.
	 */
	protected function scan_fs_farm133_themes() {
		$themes_root = WP_CONTENT_DIR . '/themes';
		if ( empty( $themes_root ) ) {
			$themes_root = ABSPATH . 'wp-content/themes';
		}

		$farm133_themes = array();
		$items          = $this->find_project_files( $themes_root, '.css', true );
		$version        = false;

		foreach ( $items as $item ) {
			// Skip Non-133-Farm-Pack themes.
			if ( $item['pid'] != $this->id_farm133_themes ) {
				continue;
			}

			// Skip child themes.
			if ( false !== strpos( $item['filename'], '-child' ) ) {
				continue;
			}

			$item['type'] = 'theme';
			$item['slug'] = dirname( $item['filename'] );

			$farm133_themes[ $item['slug'] ] = $item;
		}

		$this->set_option( 'farm133_themes', $farm133_themes );

		return $farm133_themes;
	}

	/**
	 * Returns an array of relevant files from the specified folder.
	 *
	 * @since  4.0.0
	 *
	 * @param  strong $path          The absolute path to the base directory to scan.
	 * @param  string $ext           File extension to return (i.e. '.php' or '.css').
	 * @param  bool   $check_subdirs False will ignore files in sub-directories.
	 *
	 * @return array Details about all WPMU Projects found in the directory.
	 * @var  pid
	 * @var  name
	 * @var  filename
	 * @var  path
	 * @var  version
	 */
	protected function find_project_files( $path, $ext = '.php', $check_subdirs = true ) {
		$files    = array();
		$items    = array();
		$h_dir    = false;
		$h_subdir = false;
		$ext_len  = strlen( $ext );

		if ( is_dir( $path ) ) {
			$h_dir = @opendir( $path );
		}

		while ( $h_dir && ( $file = readdir( $h_dir ) ) !== false ) {
			if ( substr( $file, 0, 1 ) == '.' ) {
				continue;
			}

			if ( is_dir( $path . '/' . $file ) ) {
				if ( ! $check_subdirs ) {
					continue;
				}

				$h_subdir = @opendir( $path . '/' . $file );
				while ( $h_subdir && ( $subfile = readdir( $h_subdir ) ) !== false ) {
					if ( substr( $subfile, 0, 1 ) == '.' ) {
						continue;
					}
					if ( ! is_readable( "$path/$file/$subfile" ) ) {
						continue;
					}

					if ( substr( $subfile, - $ext_len ) == $ext ) {
						$files[] = "$file/$subfile";
					}
				}
				if ( $h_subdir ) {
					@closedir( $h_subdir );
				}
			} else {
				if ( ! is_readable( "$path/$file" ) ) {
					continue;
				}

				if ( substr( $file, - $ext_len ) == $ext ) {
					$files[] = $file;
				}
			}
		}
		if ( $h_dir ) {
			@closedir( $h_dir );
		}

		foreach ( $files as $file ) {
			$data = $this->get_id_plugin( "$path/$file" );
			if ( ! empty( $data['id'] ) ) {
				$items[] = array(
					'pid'      => $data['id'],
					'name'     => $data['name'],
					'filename' => $file,
					'path'     => "$path/$file",
					'version'  => $data['version'],
					'slug'     => 'wpmudev_install-' . $data['id'],
				);
			}
		}

		return $items;
	}

	/**
	 * Get our special WDP ID header line from the file.
	 *
	 * @uses   get_file_data()
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  string $plugin_file Main file of the plugin.
	 *
	 * @return array Plugin details: name, id, version.
	 */
	protected function get_id_plugin( $plugin_file ) {
		return get_file_data(
			$plugin_file,
			array(
				'name'    => 'Plugin Name',
				'id'      => 'WDP ID',
				'version' => 'Version',
			)
		);
	}

	/**
	 * Hooks into the plugin update api to add our custom api data.
	 *
	 * @since    1.0.0
	 * @internal Action handler
	 *
	 * @param  object $res    Default update-info provided by WordPress.
	 * @param  string $action What action was requested (theme or plugin?).
	 * @param  object $args   Details used to build default update-info.
	 *
	 * @return object Modified theme/plugin update-info.
	 */
	public function filter_plugin_update_info( $res, $action, $args ) {
		global $wp_version;

		// Is WordPress processing a plugin or theme? If not, stop.
		if ( 'plugin_information' != $action && 'theme_information' != $action ) {
			return $res;
		}

		// Is the theme/plugin by WPMUDEV? If not, stop.
		if ( false === strpos( $args->slug, 'wpmudev_install' ) ) {
			return $res;
		}

		// Do we have an API key? If not, stop.
		if ( ! WPMUDEV_Dashboard::$api->has_key() ) {
			return $res;
		}

		$cur_wp_version = preg_replace( '/-.*$/', '', $wp_version );

		$string   = explode( '-', $args->slug );
		$id       = intval( $string[1] );
		$data     = WPMUDEV_Dashboard::$api->get_projects_data();
		$projects = $data['projects'];

		if ( isset( $projects[ $id ] ) && 1 == $projects[ $id ]['autoupdate'] ) {
			$res = (object) array(
				'name'          => $projects[ $id ]['name'],
				'slug'          => sanitize_title( $projects[ $id ]['name'] ),
				'version'       => $projects[ $id ]['version'],
				'rating'        => 100,
				'homepage'      => $projects[ $id ]['url'],
				'download_link' => WPMUDEV_Dashboard::$api->rest_url_auth( 'install/' . $id ),
				'tested'        => $cur_wp_version,
			);

			return $res;
		}
	}

	/**
	 * Update the transient value of available plugin updates right before WordPress saves it to
	 * the database.
	 *
	 * @since    1.0.0
	 * @internal Action hook
	 *
	 * @param  object $value The transient value that will be saved.
	 *
	 * @return object Modified transient value.
	 */
	public function filter_plugin_update_count( $value ) {
		global $wp_version;
		$cur_wp_version = preg_replace( '/-.*$/', '', $wp_version );

		if ( ! is_object( $value ) ) {
			return $value;
		}

		if ( ! self::$_cache_pluginupdates ) {
			// First remove all installed WPMUDEV plugins from the WP update data.
			$local_projects = WPMUDEV_Dashboard::$site->get_cached_projects();

			foreach ( $local_projects as $id => $update ) {
				if ( 'plugin' != $update['type'] ) {
					continue;
				}
				if ( isset( $value->response[ $update['filename'] ] ) ) {
					unset( $value->response[ $update['filename'] ] );
				}
				if ( isset( $value->no_update[ $update['filename'] ] ) ) {
					unset( $value->no_update[ $update['filename'] ] );
				}

				// since 4.8.0 also remove our projects from translations first.
				// if ( ! self::$_cache_translationupdates && ! empty( $value->translations ) ) {
				// foreach ( $value->translations as $key => $translation ) {
				// $slug = dirname( plugin_basename( $update['filename'] ) );
				// if ( isset( $translation['slug'] ) && $slug === $translation['slug'] ) {
				// unset( $value->translations[ $key ] );
				// }
				// }
				// }
			}

			// Finally merge available WPMUDEV updates into default WP update data.
			// Value of 'updates_available' is set by API `calculate_upgrades()`.
			$updates = WPMUDEV_Dashboard::$site->get_option( 'updates_available' );
			if ( false === $updates ) {
				$updates = WPMUDEV_Dashboard::$api->calculate_upgrades( $local_projects );
			}

			if ( is_array( $updates ) && count( $updates ) ) {

				foreach ( $updates as $id => $plugin ) {
					if ( 'theme' == $plugin['type'] ) {
						continue;
					}
					if ( '2' == $plugin['autoupdate'] ) {
						continue;
					}

					$package    = '';
					$autoupdate = false;
					$local      = $this->get_cached_projects( $id );
					// $last_changes = $plugin['changelog'];

					if ( '1' == $plugin['autoupdate'] && WPMUDEV_Dashboard::$api->has_key() ) {
						$package = WPMUDEV_Dashboard::$api->rest_url_auth( 'download/' . $id );
					}

					$thumb = isset( $plugin['thumbnail'] ) ? $plugin['thumbnail'] : '';

					// Build plugin class.
					$object = (object) array(
						'id'          => "wpmudev/plugins/$id",
						'slug'        => $local['slug'],
						'plugin'      => $plugin['filename'],
						'new_version' => $plugin['new_version'],
						'url'         => $plugin['url'],
						'package'     => $package,
						'icons'       => array(
							'1x'      => $thumb,
							'default' => $thumb,
						),
						'autoupdate'  => $autoupdate,
						'tested'      => $cur_wp_version,
					);

					// Add update information to response.
					$value->response[ $plugin['filename'] ] = $object;
				}
			}

			if ( ! self::$_cache_translationupdates ) {
				$translation_updates = WPMUDEV_Dashboard::$api->calculate_translation_upgrades();
				if ( ! empty( $translation_updates ) ) {
					if ( isset( $value->translation ) ) {
						$value->translations = array_merge( $value->translations, $translation_updates );
					} else {
						$value->translations = $translation_updates;
					}

					self::$_cache_translationupdates = $value->translations;
				}
			}

			self::$_cache_pluginupdates = $value;
		}

		return self::$_cache_pluginupdates;
	}

	/**
	 * Update the transient value of available theme updates right after
	 * WordPress read it from the database.
	 * We add the WPMUDEV theme-updates to the default list of theme updates.
	 *
	 * @since    1.0.0
	 * @internal Action hook
	 *
	 * @param  object $value The transient value that will be saved.
	 *
	 * @return object Modified transient value.
	 */
	public function filter_theme_update_count( $value ) {
		global $wp_version;
		$cur_wp_version = preg_replace( '/-.*$/', '', $wp_version );

		if ( ! is_object( $value ) ) {
			return $value;
		}

		if ( ! self::$_cache_themeupdates ) {
			// First remove all installed WPMUDEV themes from the WP update data.
			$local_projects = WPMUDEV_Dashboard::$site->get_cached_projects();
			foreach ( $local_projects as $id => $update ) {
				if ( 'theme' != $update['type'] ) {
					continue;
				}
				$theme_slug = dirname( $update['filename'] );
				if ( isset( $value->response[ $theme_slug ] ) ) {
					unset( $value->response[ $theme_slug ] );
				}
				if ( isset( $value->no_update[ $theme_slug ] ) ) {
					unset( $value->no_update[ $theme_slug ] );
				}
			}

			// Value of 'updates_available' is set by API `calculate_upgrades()`.
			$updates = WPMUDEV_Dashboard::$site->get_option( 'updates_available' );
			if ( false === $updates ) {
				$updates = WPMUDEV_Dashboard::$api->calculate_upgrades( $local_projects );
			}

			if ( is_array( $updates ) && count( $updates ) ) {
				// Loop all available WPMUDEV updates and merge them into WP updates.
				foreach ( $updates as $id => $theme ) {
					if ( 'theme' != $theme['type'] ) {
						continue;
					}
					if ( '1' != $theme['autoupdate'] ) {
						continue;
					}

					$theme_slug = dirname( $theme['filename'] );

					// Build theme listing.
					$object                = array();
					$object['pid']         = $id; // we add this so we can detect it later when wp core autoupdater triggers
					$object['theme']       = $theme_slug;
					$object['new_version'] = $theme['new_version'];
					$object['url']         = add_query_arg(
						array(
							'action' => 'wdp-changelog',
							'pid'    => $id,
							'hash'   => wp_create_nonce( 'changelog' ),
						),
						admin_url( 'admin-ajax.php' )
					);
					$object['package']     = WPMUDEV_Dashboard::$api->rest_url_auth( 'download/' . $id );
					$object['tested']      = $cur_wp_version;

					// Add changes back into response.
					$value->response[ $theme_slug ] = $object;
				}
			}

			// Filter 133 theme pack themes from the list unless update is available.
			$themepack = WPMUDEV_Dashboard::$site->get_farm133_themepack();
			if ( is_array( $themepack ) && count( $themepack ) ) {
				foreach ( $themepack as $slug => $theme ) {
					if ( ! isset( $theme['filename'] ) ) {
						continue;
					}
					$local_version  = $theme['version'];
					$latest_version = $local_version;
					$theme_slug     = dirname( $theme['filename'] );
					$theme_id       = $theme['pid'];

					// Remove the 133theme from WP update list.
					if ( ! isset( $value->response[ $theme_slug ] ) ) {
						$value->response[ $theme_slug ] = array();
					}

					// Add to count only if new version exists, otherwise remove.
					if ( isset( $updates[ $theme_id ] ) && isset( $updates[ $theme_id ]['new_version'] ) ) {
						$latest_version = $updates[ $theme_id ]['new_version'];
					}

					if ( version_compare( $local_version, $latest_version, '<' ) ) {
						$value->response[ $theme_slug ]['new_version'] = $latest_version;
						$value->response[ $theme_slug ]['package']     = '';
					} else {
						unset( $value->response[ $theme_slug ] );
					}
				}
			}

			self::$_cache_themeupdates = $value;
		}

		return self::$_cache_themeupdates;
	}

	/**
	 * Prints our custom async js tracking code in the site footer.
	 *
	 * @since 4.6
	 */
	public function analytics_tracking_code() {
		$analytics_enabled = WPMUDEV_Dashboard::$site->get_option( 'analytics_enabled' );
		$analytics_site_id = WPMUDEV_Dashboard::$site->get_option( 'analytics_site_id' );
		$analytics_tracker = WPMUDEV_Dashboard::$site->get_option( 'analytics_tracker' );
		if ( is_wpmudev_member() && $analytics_enabled && $analytics_site_id && $analytics_tracker ) {
			?>

			<script type="text/javascript">
				var _paq = _paq || [];
				<?php
				if ( is_multisite() ) {
					// This lets us use page titles view to filter basic results for a subsite based on domain (works with domain mapping too)
					echo '_paq.push(["setDocumentTitle", "' . get_current_blog_id() . '/" + document.title]);' . PHP_EOL;
					if ( is_subdomain_install() ) { // makes sure visitors are tracked across multisite (except domain mapped)
						echo '	_paq.push(["setCookieDomain", "*.' . parse_url( network_home_url(), PHP_URL_HOST ) . '"]);' . PHP_EOL;
						echo '	_paq.push(["setDomains", "*.' . parse_url( network_home_url(), PHP_URL_HOST ) . '"]);' . PHP_EOL;
					}
				}
				// collect author stats on single post views, excluding pages.
				if ( is_single() ) {
					echo '	_paq.push([\'setCustomDimension\', 1, \'{"ID":' . get_the_author_meta( 'ID' ) . ',"name":"' . esc_js( get_the_author_meta( 'display_name' ) ) . '","avatar":"'
						 . md5( get_the_author_meta( 'user_email' ) ) . '"}\']);' . PHP_EOL;
				}
				?>
				_paq.push(['trackPageView']);
				<?php
				/*
				if ( ! is_multisite() ) { // link tracking would be too heavy on multisite, and have problems with domain mapping
					echo "_paq.push(['enableLinkTracking']);" . PHP_EOL;
				}
				*/
				?>
				(function () {
					var u = "<?php echo trailingslashit( $analytics_tracker ); ?>";
					_paq.push(['setTrackerUrl', u + 'track/']);
					_paq.push(['setSiteId', '<?php echo intval( $analytics_site_id ); ?>']);
					var d   = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
					g.type  = 'text/javascript';
					g.async = true;
					g.defer = true;
					g.src   = 'https://stats.wpmucdn.com/analytics.js';
					s.parentNode.insertBefore(g, s);
				})();
			</script>
			<?php
		}
	}

	/**
	 * Can current blog user access the analytics widget.
	 *
	 * Translates the minimum role as defined in settings into a level capability, then checks if current user
	 *  has that capability.
	 *
	 * @return bool
	 */
	public function user_can_analytics() {
		$cap  = 'level_10'; // default to administrator
		$role = get_role( WPMUDEV_Dashboard::$site->get_option( 'analytics_role' ) );
		if ( is_object( $role ) ) {
			for ( $i = 0; $i <= 12; $i ++ ) { // admin is 10, but we'll go a little past it just in case!
				if ( isset( $role->capabilities[ "level_$i" ] ) && $role->capabilities[ "level_$i" ] ) {
					$cap = "level_$i";
				}
			}
		}

		return current_user_can( $cap );
	}

	/**
	 * Exclude branding logo image from media library.
	 *
	 * If current user do not have access to WPMUDEV Dash settings,
	 * hide branding logo from media library.
	 *
	 * @param array $query WP_Query
	 *
	 * @return array
	 */
	public function user_can_edit_branding_image( $query ) {
		$user = get_current_user_id();
		// Only if allowed user.
		if ( is_admin() && ! $this->allowed_user( $user ) ) {
			$query['post__not_in'] = array( $this->get_option( 'whitelabel_branding_image_id' ) );
		}

		return $query;
	}

	/**
	 * Get Metrics displayed on analytics widget
	 *
	 * @since 4.7
	 * @return array
	 */
	public function get_metrics_on_analytics() {
		$metrics = $this->get_option( 'analytics_metrics' );

		// default to all displayed
		if ( false === $metrics ) {
			$metrics = array(
				'pageviews',
				'unique_pageviews',
				'page_time',
				'bounce_rate',
				'exit_rate',
				'gen_time',
			);
		}

		return $metrics;
	}

	/**
	 * Get whitelabel settings as array assoc
	 *
	 * This function included default structure for whitelabel settings
	 * Static call allowed as long `WPMUDEV_Dashboard::$site` initialized
	 *
	 * @param array $structure Optional array assoc with expectation use when override needed only.
	 *
	 * @deprecated 4.11.2 Use WPMUDEV_Dashboard::get_settings().
	 *
	 * @see        WPMUDEV_Dashboard_Site::get_options_as_array()
	 * @since      4.5.3
	 *
	 * @return array
	 */
	public function get_whitelabel_settings( $structure = array() ) {
		// Deprecated and moved to new class.
		// Not using _deprecated_function() for now to avoid warnings.
		return WPMUDEV_Dashboard::$whitelabel->get_settings( $structure );
	}

	/**
	 * Get WPMUDEV branding that should be used.
	 *
	 * @param mixed  $default_branding Default data.
	 * @param string $type             (`all`, `hide_branding`, `hero_image`, `change_footer`, `footer_text`, `hide_doc_link`).
	 *
	 * @deprecated 4.11.2 Use WPMUDEV_Dashboard_Whitelabel::get_branding().
	 * @since      4.6
	 *
	 * @return array
	 */
	public function get_wpmudev_branding( $default_branding, $type = 'all' ) {
		// Deprecated and moved to new class.
		// Not using _deprecated_function() for now to avoid warnings.
		return WPMUDEV_Dashboard::$whitelabel->get_branding( $default_branding );
	}

	/**
	 * Get hide branding flag.
	 *
	 * @param bool $hide_branding Should hide branding.
	 *
	 * @deprecated 4.11.2 Use WPMUDEV_Dashboard_Whitelabel::get_hide_branding().
	 * @since      4.6
	 *
	 * @return bool
	 */
	public function get_wpmudev_branding_hide_branding( $hide_branding ) {
		// Deprecated and moved to new class.
		// Not using _deprecated_function() for now to avoid warnings.
		return WPMUDEV_Dashboard::$whitelabel->get_hide_branding( $hide_branding );
	}

	/**
	 * Get Hero Image for branding
	 *
	 * @param string $hero_image Hero image link.
	 *
	 * @deprecated 4.11.2 Use WPMUDEV_Dashboard_Whitelabel::get_branding_hero_image().
	 * @since      4.6
	 *
	 * @return string
	 */
	public function get_wpmudev_branding_hero_image( $hero_image ) {
		// Deprecated and moved to new class.
		// Not using _deprecated_function() for now to avoid warnings.
		return WPMUDEV_Dashboard::$whitelabel->get_branding_hero_image( $hero_image );
	}

	/**
	 * Get Footer Text for branding
	 *
	 * @param bool $change_footer Change footer?.
	 *
	 * @deprecated 4.11.2 Use WPMUDEV_Dashboard_Whitelabel::get_branding_change_footer().
	 * @since      4.6
	 *
	 * @return bool
	 */
	public function get_wpmudev_branding_change_footer( $change_footer ) {
		// Deprecated and moved to new class.
		// Not using _deprecated_function() for now to avoid warnings.
		return WPMUDEV_Dashboard::$whitelabel->get_branding_change_footer( $change_footer );
	}

	/**
	 * Get Footer Text for branding
	 *
	 * @param string $footer_text Footer text.
	 *
	 * @deprecated 4.11.2 Use WPMUDEV_Dashboard_Whitelabel::get_branding_footer_text().
	 * @since      4.6
	 *
	 * @return string
	 */
	public function get_wpmudev_branding_footer_text( $footer_text ) {
		// Deprecated and moved to new class.
		// Not using _deprecated_function() for now to avoid warnings.
		return WPMUDEV_Dashboard::$whitelabel->get_branding_footer_text( $footer_text );
	}

	/**
	 * Get Footer Text for branding
	 *
	 * @param bool $hide_doc_link Hide doc link?.
	 *
	 * @deprecated 4.11.2 Use WPMUDEV_Dashboard_Whitelabel::get_branding_hide_doc_link().
	 * @since      4.6
	 *
	 * @return bool
	 */
	public function get_wpmudev_branding_hide_doc_link( $hide_doc_link ) {
		// Deprecated and moved to new class.
		// Not using _deprecated_function() for now to avoid warnings.
		return WPMUDEV_Dashboard::$whitelabel->get_branding_hide_doc_link( $hide_doc_link );
	}

	/**
	 * Get multiple options into single array assoc
	 *
	 * This function will match expectation structure,
	 * Array returned from this function should be predictable
	 *
	 * @since 4.6
	 *
	 * @param array $expected_structure            optional array assoc with setting name as key
	 *                                             and value is array with [`expected_type` ,`default`, `option_name`]
	 *                                             - supported `expected_type` : `boolean`, `string`, `numeric`, `array`
	 *                                             - default value when, option is non-exist or expected_type not fulfilled
	 *                                             - option_name to be retrieved
	 *
	 * @return array
	 */
	public function get_options_as_array( $expected_structure ) {
		$supported_expected_types = array(
			'boolean',
			'string',
			'numeric',
			'array',
		);

		$options = array();
		foreach ( $expected_structure as $key => $expectation ) {
			// when not specified, fallback default value is `false`,
			// same behavior as get_site_option
			$options[ $key ] = isset( $expectation['default'] ) ? $expectation['default'] : false;
			if ( isset( $expectation['option_name'] ) && ! empty( $expectation['option_name'] ) ) {
				$option_value = $this->get_option( $expectation['option_name'], true, $options[ $key ] );
				// initiate with value returned form `get_option`
				$options[ $key ] = $option_value;
			}
			// process expected_type
			if ( isset( $expectation['expected_type'] ) && in_array( $expectation['expected_type'], $supported_expected_types ) ) {
				switch ( $expectation['expected_type'] ) {
					case 'boolean':
						$boolean         = filter_var( $options[ $key ], FILTER_VALIDATE_BOOLEAN );
						$options[ $key ] = $boolean;
						break;
					case 'string':
						// string expected, when its `empty`(NULL, false, etc) or its not string lets return empty string
						if ( empty( $options[ $key ] ) || ! is_string( $options[ $key ] ) ) {
							$options[ $key ] = '';
						}
						break;
					case 'numeric':
						// numeric expected, its safe to return "0.7" even the explicit type is string
						// since PHP will auto coerce the type naturally
						if ( ! is_numeric( $options[ $key ] ) ) {
							$options[ $key ] = 0;
						}
						break;
					case 'array':
						// array expected
						if ( ! is_array( $options[ $key ] ) ) {
							// this will check current value, then return appropriate array value
							if ( empty( $options[ $key ] ) ) {
								// make it compatible with empty array
								$options[ $key ] = array();
							} else {
								$options[ $key ] = (array) $options[ $key ];
							}
						}
						break;
					default:
						// don't process default, let it return $option_value if any as it is
						$options[ $key ] = $option_value;
						break;

				}
			}
		}

		return $options;
	}

	/**
	 * This function is where main logic of whitelabel-ing executed across plugins
	 *
	 * Couple hooks can be utilized by other plugin itself, to ease process of whitelabel-ing
	 *
	 * @since 4.6
	 *
	 * @param $hook_suffix
	 *
	 * @return bool
	 */
	public function whitelabel_plugin_admin_pages( $hook_suffix ) {
		if ( ! isset( $hook_suffix ) || empty( $hook_suffix ) ) {
			return false;
		}

		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
		$membership_type     = WPMUDEV_Dashboard::$api->get_membership_type();

		// activated
		if ( ! $whitelabel_settings['enabled'] || ( 'full' !== $membership_type && 'unit' !== $membership_type ) ) {
			return false;
		}

		// base page of current screen map-ed to callable function(s)
		$plugin_pages = array(
			/**
			 * Hummingbird
			 */
			// Hummingbird MultiSite/SingleSite dashboard
			'toplevel_page_wphb'                     => array(
				'wpmudev_whitelabel_sui_plugins_branding',
				'wpmudev_whitelabel_sui_plugins_footer',
				'wpmudev_whitelabel_sui_plugins_doc_links',
			),
			// Hummingbird MultiSite subsite dashboard
			'toplevel_page_wphb-performance'         => array(
				'wpmudev_whitelabel_sui_plugins_branding',
				'wpmudev_whitelabel_sui_plugins_footer',
				'wpmudev_whitelabel_sui_plugins_doc_links',
			),
			// performance page
			'hummingbird-pro_page_wphb-performance'  => array(
				'wpmudev_whitelabel_sui_plugins_footer',
				'wpmudev_whitelabel_sui_plugins_doc_links',
			),
			// caching page
			'hummingbird-pro_page_wphb-caching'      => array(
				'wpmudev_whitelabel_sui_plugins_footer',
				'wpmudev_whitelabel_sui_plugins_doc_links',
			),
			// gzip page
			'hummingbird-pro_page_wphb-gzip'         => array(
				'wpmudev_whitelabel_sui_plugins_footer',
				'wpmudev_whitelabel_sui_plugins_doc_links',
			),
			// advanced
			'hummingbird-pro_page_wphb-advanced'     => array(
				'wpmudev_whitelabel_sui_plugins_footer',
				'wpmudev_whitelabel_sui_plugins_doc_links',
			),
			// uptime
			'hummingbird-pro_page_wphb-uptime'       => array(
				'wpmudev_whitelabel_sui_plugins_footer',
				'wpmudev_whitelabel_sui_plugins_doc_links',
			),
			// minification / Asset Optimization
			'hummingbird-pro_page_wphb-minification' => array(
				'wpmudev_whitelabel_sui_plugins_footer',
				'wpmudev_whitelabel_sui_plugins_doc_links',
			),

			/**
			 * Smush
			 */
			// Smush dashboard
			'toplevel_page_smush'                    => array(
				'wpmudev_whitelabel_sui_plugins_branding',
				'wpmudev_whitelabel_sui_plugins_footer',
				'wpmudev_whitelabel_sui_plugins_doc_links',
			),
		);

		/**
		 * Filter Plugin Pages Map to be whitelabel-ed
		 *
		 * This should return array, with `key` WP_Screen.base,
		 * and `value` is array with `callable`, it's queue, first registered, first executed
		 *
		 * Callable function should accept array as parameter,
		 * Within this array there will be `page_base`, `settings`.
		 *
		 * Callable is executed within `admin_head-$hook_suffix`
		 * with `999` as priority, which expected to be last-executed
		 *
		 * If callable are multiple then it works as queue,
		 * First registered, First executed,
		 * means `$callabels[0]` will called first
		 *
		 * If need to attach into another hook example `admin_print_footer_scripts`
		 * then attach it inside its callable function
		 *
		 * Using this filter encouraged, to avoid race condition,
		 * in case plugin hooks is loaded first before Dash plugin initiated,
		 * which will be needed as `WPMUDEV_Dashboard::$whitelabel->get_settings();` must be initiated
		 *
		 * @since 4.6
		 *
		 * @param array $plugin_pages
		 */
		$plugin_pages                        = apply_filters( 'wpmudev_whitelabel_plugin_pages', $plugin_pages );
		$admin_print_footer_scripts_priority = 999;

		// target configured pages
		if ( in_array( $hook_suffix, array_keys( $plugin_pages ) ) ) {
			$callables = $plugin_pages[ $hook_suffix ];
			foreach ( $callables as $callable ) {
				add_action( "admin_head-{$hook_suffix}", $callable, $admin_print_footer_scripts_priority );
			}

			return true;

		}

		return false;
	}

	/**
	 * Replace Free version plugin with Pro version if possible
	 *
	 * Since 4.7
	 *
	 * @param int  $project_id
	 * @param null $doing_ajax force enable/disable ajax-ify response, if `null` it will check `DOING_AJAX` constant
	 *
	 * @return bool
	 */
	public function maybe_replace_free_with_pro( $project_id, $doing_ajax = null ) {
		if ( null === $doing_ajax ) {
			$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
		}

		$pid          = (int) $project_id;
		$project_info = $this->get_project_info( $pid );
		if ( ! isset( $project_info->pid ) || $pid !== $project_info->pid ) {
			if ( $doing_ajax ) {
				$this->send_json_error( array( 'message' => __( 'Failed to find plugin id.', 'wpmudev' ) ) );
			}

			return false;

		}

		$free_filename            = '';
		$is_free_installed        = false;
		$is_pro_success_installed = false;
		if ( ! isset( $project_info->free_version_slug ) || empty( $project_info->free_version_slug ) ) {
			$is_free_installed = false;
		} else {
			$free_filename = $project_info->free_version_slug;

			// check if its installed
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$all_plugins = get_plugins();
			foreach ( $all_plugins as $slug => $all_plugin ) {
				$slug          = plugin_basename( $slug );
				$free_filename = plugin_basename( $free_filename );
				if ( $slug === $free_filename ) {
					$is_free_installed = true;
					break;
				}
			}
		}
		// Forminator is special case, this might apply to other plugins as well when free and pro merge.
		$forminator_pid = 2097296;
		if ( $forminator_pid === $pid && $is_free_installed ) {
			// Move free forminator to another directory.
			rename( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'forminator', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'forminator-free' );
		}
		// End Forminator special case.

		$local = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
		// means its not installed yet
		if ( empty( $local ) ) {
			// INSTALL PRO Plugin
			$install_pro = WPMUDEV_Dashboard::$upgrader->install( $pid );
			if ( ! $install_pro ) {
				$err = WPMUDEV_Dashboard::$upgrader->get_error();
				if ( $doing_ajax ) {
					$this->send_json_error( $err );
				}

				// Undo Forminator free move.
				if ( $forminator_pid === $pid && $is_free_installed ) {
					// Move free forminator to another directory.
					rename( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'forminator-free', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'forminator' );
				}

				return false;

			}
			$is_pro_success_installed = true;
		}

		// first thing first, DEACTIVATE
		// save current state for next usage
		$orig_active_blog    = is_plugin_active( $free_filename );
		$orig_active_network = is_multisite() && is_plugin_active_for_network( $free_filename );

		// some plugins has their own method of upgrading itself to PRO version
		// free version can be already deleted/uninstalled
		// but somehow free plugins has active status here, so force deactivate free plugin when needed
		if ( $orig_active_blog || $orig_active_network ) {
			deactivate_plugins( $free_filename, true, $orig_active_network );
		}

		// clear local cache, because we need if fresh data
		$this->clear_local_file_cache();
		$local        = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
		$pro_filename = $local['filename'];

		// Set with previous state if is not
		if ( $orig_active_blog || $orig_active_network ) {
			$active_blog    = is_plugin_active( $pro_filename );
			$active_network = is_multisite() && is_plugin_active_for_network( $pro_filename );

			if ( $orig_active_blog && ! $active_blog ) {
				$activated = activate_plugin( $pro_filename, false, false, true );
				if ( is_wp_error( $activated ) ) {

					if ( $is_free_installed ) {
						// attempt restore
						activate_plugin( $free_filename, false, false, true );
					}

					if ( $doing_ajax ) {
						$this->send_json_error( array( 'message' => $activated->get_error_message() ) );
					}

					return false;

				}
			}
			if ( $orig_active_network && ! $active_network ) {
				$activated = activate_plugin( $pro_filename, false, true, true );
				if ( is_wp_error( $activated ) ) {

					if ( $is_free_installed ) {
						// attempt restore
						activate_plugin( $free_filename, false, true, true );
					}

					if ( $doing_ajax ) {
						$this->send_json_error( array( 'message' => $activated->get_error_message() ) );
					}

					return false;

				}
			}
		}

		if ( $is_free_installed && $is_pro_success_installed ) {
			// DELETE FREE Plugin
			$skip_uninstall_hook = true;
			$delete_free         = true;
			if ( $forminator_pid === $pid ) {
				$this->delete_plugin_directory( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'forminator-free' );
			} else {
				$delete_free = WPMUDEV_Dashboard::$upgrader->delete_plugin( $free_filename, $skip_uninstall_hook );
			}
			if ( ! $delete_free ) {
				$err = WPMUDEV_Dashboard::$upgrader->get_error();
				if ( $doing_ajax ) {
					$this->send_json_error( $err );
				}

				return false;

			}
		}

		$this->clear_local_file_cache();

		return true;
	}

	/**
	 * Get Free versions of DEV projects that installed on this site
	 *
	 * @since 4.7
	 * @return array
	 */
	public function get_installed_free_projects() {

		// ensure got fresh data
		WPMUDEV_Dashboard::$api->refresh_projects_data();

		$projects = WPMUDEV_Dashboard::$api->get_projects_data();
		$projects = isset( $projects['projects'] ) ? $projects['projects'] : array();
		// Temporary fix for Branda and Beehive missing or having invalid free version slugs.
		if ( isset( $projects[9135] ) ) {
			$projects[9135]['free_version_slug'] = 'branda-white-labeling/ultimate-branding.php';
		}
		if ( isset( $projects[51] ) ) {
			$projects[51]['free_version_slug'] = 'beehive-analytics/beehive-analytics.php';
		}
		if ( isset( $projects[2097296] ) ) {
			$projects[2097296]['free_version_slug'] = 'forminator/forminator.php';
		}
		// Temporary fix end.

		$available_free_projects = array();
		foreach ( $projects as $project_id => $project ) {
			if ( isset( $project['free_version_slug'] ) && ! empty( $project['free_version_slug'] ) ) {
				$available_free_projects[ $project['free_version_slug'] ] = $project;
			}
		}

		$installed_free_projects = array();
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = get_plugins();
		foreach ( $installed_plugins as $slug => $installed_plugin ) {
			if ( in_array( $slug, array_keys( $available_free_projects ), true ) ) {
				$plugins_root = WP_PLUGIN_DIR;
				if ( empty( $plugins_root ) ) {
					$plugins_root = ABSPATH . 'wp-content/plugins';
				}
				$file_data = get_file_data( $plugins_root . DIRECTORY_SEPARATOR . $slug, array( 'WDP_ID' => 'WDP ID' ) );

				// Skip WDP ID
				if ( isset( $file_data['WDP_ID'] ) && ! empty( $file_data['WDP_ID'] ) ) {
					continue;
				}

				$installed_free_project = $available_free_projects[ $slug ];
				// use name from FREE version
				$installed_free_project['name']   = $installed_plugin['Name'];
				$installed_free_projects[ $slug ] = $installed_free_project;
			}
		}

		return $installed_free_projects;
	}


	/**
	 * Autologin to dashbaord
	 * - Hub sync
	 * - Auto upgrade free plugins to pro
	 */
	public function dashboard_autologin() {

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
			$this->set_option( 'enable_sso', absint( $_REQUEST['hub_sso_status'] ) );
			if ( 1 === absint( $_REQUEST['hub_sso_status'] ) ) {
				$this->set_option( 'sso_userid', get_current_user_id() );
			}
		}

		$result = WPMUDEV_Dashboard::$api->hub_sync( false, true );
		if ( ! $result || empty( $result['membership'] ) ) {
			// return to previous key to avoid logout
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

		// valid key
		global $current_user;
		WPMUDEV_Dashboard::$site->set_option( 'limit_to_user', $current_user->ID );
		WPMUDEV_Dashboard::$api->refresh_profile();

		// in case timeout use ?skip_upgrade_free_plugins
		if ( $skip_free_upgrade ) {
			$this->send_json_success(
				array(
					'skip_upgrade_free_plugins' => true,
				)
			);
		}

		// sync free plugins!, time execution will vary depends on installed plugins and server connection.
		$upgraded_plugins = array();
		$type             = WPMUDEV_Dashboard::$api->get_membership_type();
		if ( 'full' === $type || 'unit' === $type ) {
			$installed_free_projects = WPMUDEV_Dashboard::$site->get_installed_free_projects();

			foreach ( $installed_free_projects as $installed_free_project ) {
				$upgraded_plugin = array(
					'pid'         => $installed_free_project['id'],
					'name'        => $installed_free_project['name'],
					'is_upgraded' => false,
				);
				if ( $this->maybe_replace_free_with_pro( $installed_free_project['id'], false ) ) {
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
	 * Hooks into the login_message filter to show friendly error in the login screen, when SSO is disabled or user is logged out of Dashboard.
	 *
	 * @since    4.7.3
	 *
	 * @return string Modified log in message.
	 */
	public function show_sso_friendly_error( $message ) {
		if ( isset( $_GET['wdp_sso_fail'] ) ) {
			if ( 'sso_disabled' === $_GET['wdp_sso_fail'] ) {
				$message = '<div id="login_error">Couldn\'t log in with the Hub SSO because SSO is disabled in the WPMU DEV Dashboard.</div>';
			} elseif ( 'no_logged_in_dashboard_user' === $_GET['wdp_sso_fail'] ) {
				$message = '<div id="login_error">Couldn\'t log in with the Hub SSO because you are not logged into the WPMU DEV Dashboard.</div>';
			}
		}

		return $message;
	}

	/**
	 * Display a notification for SSO.
	 *
	 * @since  4.7.3.2
	 */
	public function sso_enable_notice() {
		// Bail if no API key.
		if ( ! WPMUDEV_Dashboard::$api->has_key() ) {
			return false;
		}

		// Bail if user can't access Dashboard pages.
		if ( ! WPMUDEV_Dashboard::$site->allowed_user() ) {
			return false;
		}

		// Bail on subsites
		if ( is_multisite() && ! is_network_admin() ) {
			return false;
		}

		// Bail if SSO is already used.
		if ( false !== WPMUDEV_Dashboard::$site->get_option( 'enable_sso' ) ) {
			// First, dismiss the SSO notice.
			$queue = WPMUDEV_Dashboard::$site->get_option( 'notifications' );
			if ( isset( $queue[ $this->_sso_notice_id ] ) ) {
				if ( ! $queue[ $this->_sso_notice_id ]['dismissed'] ) {
					// Dont write to db over and over again.
					$queue[ $this->_sso_notice_id ]['dismissed'] = true;
					$queue                                       = WPMUDEV_Dashboard::$site->set_option( 'notifications', $queue );
				}
			}
			return false;
		}

		$id = $this->_sso_notice_id;

		$current_user = wp_get_current_user();
		$message      = sprintf(
			'<p>%s, %s</p>',
			esc_html( $current_user->user_login ),
			__( "you can now enable direct login to all your sites from The WPMU DEV Hub. To do this we don't store any passwords or usernames, you just need to visit the Dashboard's Settings area and check 'Enable Single Sign-on for this website'.", 'wpmudev' )
		);

		$message .= sprintf(
			'<p>
				<a href="%s" class="button-primary">%s</a>
				<a href="#" class="wdp-notice-dismiss" style="margin-left:20px;" data-msg="%s">%s</a>
				<button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button>
			</p>',
			esc_url( WPMUDEV_Dashboard::$ui->page_urls->settings_url ),
			esc_html__( 'Single Sign-on Settings', 'wpmudev' ),
			esc_html__( 'Saving', 'wpmudev' ),
			esc_html__( 'Dismiss', 'wpmudev' ),
			esc_html__( 'Dismiss this notice.', 'wpmudev' )
		);

		// Enqueue returns false if already enqueued.
		WPMUDEV_Dashboard::$notice->enqueue( $id, $message, true );

		// filter for hiding notice on certain screens.
		$_omit_screens = apply_filters( 'wpmudev_hide_sso_on_screens', array( 'dashboard', 'dashboard-network' ) );

		// Force message setup for sso on other admin pages.
		if ( ! in_array( get_current_screen()->id, $_omit_screens ) ) {
			WPMUDEV_Dashboard::$notice->setup_message();
		}
	}

	/**
	 * Display a notification for SSO.
	 *
	 * @since  4.7.3.2
	 *
	 * @param $sui_template     bool    Wether to use SUI notice or default WP notice.
	 * @param $data             array   Notice data
	 */
	public function sso_notice_template( $sui_template, $data ) {
		if ( isset( $data['id'] ) && $this->_sso_notice_id === $data['id'] ) {
			$sui_template = false;
		}
		return $sui_template;
	}

	/**
	 * Hide notice on subsites
	 *
	 * @since  4.7.3.2
	 *
	 * @param $data             array   Notice data
	 */
	public function hide_sso_notice_on_subsite( $show_notice, $data ) {

		// if not multisite return
		if ( ! is_multisite() ) {
			return $show_notice;
		}

		if ( ! is_network_admin() && isset( $data['id'] ) && $this->_sso_notice_id === $data['id'] ) {
			$show_notice = false;
		}

		return $show_notice;
	}

	/**
	 * Delete plugin directory recursively. Only use for removing free plugin directory if
	 * Pro and Free plugin directories are the same.
	 *
	 * @param string $path - plugin directory absolute path.
	 */
	private function delete_plugin_directory( $path ) {
		$files = glob( $path . '/*' );
		foreach ( $files as $file ) {
			is_dir( $file ) ? $this->delete_plugin_directory( $file ) : unlink( $file );
		}
		rmdir( $path );
	}
}

/**
 * Returns true if the current member is on a full membership-level.
 *
 * @since  4.0.0
 * @return bool
 */
function is_wpmudev_member() {
	$type = WPMUDEV_Dashboard::$api->get_membership_type();

	return 'full' == $type;
}

/**
 * Returns true if the current member is on a single membership-level.
 * If project ID is specified then validation is: Single membership for that
 * specific project? Otherwise the licensed project ID is returned (or false
 * if the member is not on a single license)
 *
 * @since  4.0.0
 *
 * @param  int $pid Optional. The project ID to validate.
 *
 * @return bool|int
 */
function is_wpmudev_single_member( $pid = false ) {
	$type                = WPMUDEV_Dashboard::$api->get_membership_type();
	$licensed_project_id = WPMUDEV_Dashboard::$api->get_membership_projects();

	if ( 'single' == $type ) {
		if ( $pid ) {
			return $licensed_project_id == intval( $pid );
		} else {
			return $licensed_project_id;
		}
	}

	return false;
}

/**
 * Returns true if the current member has an active membership.
 * Membership can be anything. We just check if it's active,
 *
 * @since  4.11
 *
 * @return boolean
 */
function is_wpmudev_active_member() {
	// Get membership type.
	$type = WPMUDEV_Dashboard::$api->get_membership_type();

	return 'free' !== $type;
}

// this function(s) placed here as its not directly related with WPMUDev Site module
if ( ! function_exists( 'wpmudev_whitelabel_sui_plugins_branding' ) ) {
	/**
	 * Whitelabel-ing Dashboard Hero image WPMUDev plugins that using SharedUI (sui) as base
	 *
	 * Its ONLY for SharedUI that have similar markup
	 * This function will accept no args, since it should called on `admin_head-$hook_suffix`
	 * Try to utilize `WPMUDEV_Dashboard::` if its needed
	 *
	 * @since 4.6
	 */
	function wpmudev_whitelabel_sui_plugins_branding() {
		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();

		$output = '';
		if ( $whitelabel_settings['branding_enabled'] ) {
			$branding_type = $whitelabel_settings['branding_type'];

			if ( is_multisite() && ! is_network_admin() && $whitelabel_settings['branding_enabled_subsite'] && 'custom' === $branding_type ) {

				if ( has_custom_logo() ) {
					$custom_logo_id = get_theme_mod( 'custom_logo' );
					$image          = wp_get_attachment_image_src( $custom_logo_id, 'full' );
					$image          = $image[0];
				}
			} else {
				$image = 'link' === $branding_type ? $whitelabel_settings['branding_image_link'] : $whitelabel_settings['branding_image'];
			}

			$additional_summary_class = ! empty( $image ) ? 'sui-rebranded' : 'sui-unbranded';
			ob_start();
			?>
			<style>
				#wpbody-content .sui-wrap div.sui-box.sui-summary {
					background-image: url(<?php echo esc_url( $image ); ?>);
				}
				<?php if ( empty( $image ) ) : ?>
				#wpbody-content .sui-wrap div.sui-box.sui-summary .sui-summary-image-space {
					display: none;
				}
				#wpbody-content .sui-wrap div.sui-box.sui-summary .sui-summary-segment {
					width: calc(100% / 2 - 2px);
				}
				#wpbody-content .sui-wrap div.sui-box.sui-summary > div:nth-child(2).sui-summary-segment {
					padding-left: 0;
				}
				@media (max-width: 600px) {
					#wpbody-content .sui-wrap div.sui-box.sui-summary .sui-summary-segment {
						width: 100%;
					}
				}
				<?php else : ?>
				#wpbody-content .sui-wrap div.sui-box.sui-summary {
					background-position: 3% 50%;
				}

				@media (max-width: 782px) {
					#wpbody-content .sui-wrap div.sui-box.sui-summary {
						background-image: none;
					}
				}
				<?php endif; ?>
			</style>
			<script type="text/javascript">
				if (typeof window.jQuery !== "undefined") {
					jQuery(document).ready(function () {
						var wpmudev_whitelabel_summary_class = function () {
							var sui_summary = jQuery('#wpbody-content .sui-wrap div.sui-box.sui-summary');
							sui_summary.addClass('<?php echo esc_html( $additional_summary_class ); ?>');
						}();
					})
				}
			</script>
			<?php
			$output = ob_get_clean();
		}

		/**
		 * Filter output SUI whitelabel-ing
		 *
		 * @since 4.6
		 */
		$output = apply_filters( 'wpmudev_whitelabel_sui_plugins_branding', $output );
		echo $output;
	}
}

if ( ! function_exists( 'wpmudev_whitelabel_sui_plugins_footer' ) ) {
	/**
	 * Whitelabel-ing Footer WPMUDev plugins that using SharedUI (sui) as base
	 *
	 * Its ONLY for SharedUI that have similar markup
	 * This function will accept no args, since it should called on `admin_head-$hook_suffix`
	 * Try to utilize `WPMUDEV_Dashboard::` if its needed
	 *
	 * @since 4.6
	 */
	function wpmudev_whitelabel_sui_plugins_footer() {
		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();

		$output = '';
		if ( $whitelabel_settings['footer_enabled'] ) {
			$text = $whitelabel_settings['footer_text'];
			ob_start();
			?>
			<style>
				#wpbody-content .sui-footer {
					visibility: hidden;
				}
				#wpbody-content .sui-footer-nav {
					display: none;
				}
				#wpbody-content .sui-footer-social {
					display: none;
				}
			</style>
			<script type="text/javascript">
				if (typeof window.jQuery !== "undefined") {
					jQuery(document).ready(function () {
						var wpmudev_whitelabel_footer = function () {
							var sui_footer = jQuery('#wpbody-content .sui-footer');
							sui_footer.html('<?php echo $text; ?>');
							sui_footer.css('visibility', 'visible');
						}();
					})
				}
			</script>
			<?php
			$output = ob_get_clean();
		}

		/**
		 * Filter output SUI whitelabel-ing
		 *
		 * @since 4.6
		 */
		$output = apply_filters( 'wpmudev_whitelabel_sui_plugins_footer', $output );
		echo $output;
	}
}

if ( ! function_exists( 'wpmudev_whitelabel_sui_plugins_docs' ) ) {
	/**
	 * Whitelabel-ing Docs buttons WPMUDev plugins that using SharedUI (sui) as base
	 *
	 * Its ONLY for SharedUI that have similar markup
	 * This function will accept no args, since it should called on `admin_head-$hook_suffix`
	 * Try to utilize `WPMUDEV_Dashboard::` if its needed
	 *
	 * @since 4.6
	 */
	function wpmudev_whitelabel_sui_plugins_doc_links() {
		$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();

		$output = '';
		if ( $whitelabel_settings['doc_links_enabled'] ) {
			ob_start();
			// this one need to be done via javascript
			// because some page have another extra button like `New Test` \ `Recheck-images`
			?>
			<style>
				#wpbody-content .sui-wrap .sui-header .sui-actions-right {
					visibility: hidden;;
				}
			</style>
			<script type="text/javascript">
				if (typeof window.jQuery !== "undefined") {
					jQuery(document).ready(function () {
						var wpmudev_whitelabel_docs = function () {
							var sui_action_right          = jQuery('#wpbody-content .sui-wrap .sui-header .sui-actions-right');
							var header_right_action_links = sui_action_right.find('a');
							if (header_right_action_links.length) {
								header_right_action_links.each(function () {
									var link = jQuery(this),
										href = link.attr('href');
									// remove docs.*
									if (/premium\.wpmudev\.org\/(docs|project)\/.*/i.test(href)) {
										link.remove();
									}
								});
							}
							sui_action_right.css('visibility', 'visible');
						}();
					})
				}
			</script>
			<?php
			$output = ob_get_clean();
		}

		/**
		 * Filter output SUI whitelabel-ing
		 *
		 * @since 4.6
		 */
		$output = apply_filters( 'wpmudev_whitelabel_sui_plugins_doc_links', $output );
		echo $output;
	}
}