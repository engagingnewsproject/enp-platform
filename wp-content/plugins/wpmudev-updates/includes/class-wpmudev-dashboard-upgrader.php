<?php
/**
 * Upgrader module.
 * Handles all plugin updates and installations.
 *
 * @package WPMUDEV_Dashboard
 * @since   4.1.0
 */

/**
 * The update/installation handler.
 */
class WPMUDEV_Dashboard_Upgrader {

	/**
	 * Stores the last error that happened during any upgrade/install process.
	 *
	 * @var array With elements 'code' and 'message'.
	 */
	protected $error = false;

	/**
	 * Stores the log from any upgrade/install process.
	 *
	 * @var array
	 */
	protected $log = false;

	/**
	 * Stores the new version after from any upgrade process.
	 *
	 * @var array
	 */
	protected $new_version = false;

	/**
	 * Tracks core update results during processing.
	 *
	 * @var array
	 * @access protected
	 */
	protected $update_results = array();

	/**
	 * Minimum PHP version required by WPMU DEV plugins.
	 *
	 * @var string
	 */
	public $min_php = '5.6';

	/**
	 * Special upgrader instance holder.
	 *
	 * @var WPMUDEV_Dashboard_Special_Upgrader
	 */
	protected $special_upgrader;

	/**
	 * Set up actions for the Upgrader module.
	 *
	 * @internal
	 * @since 4.1.0
	 */
	public function __construct() {
		// Enable auto updates for enabled projects.
		add_filter( 'auto_update_plugin', array( $this, 'maybe_auto_update' ), 10, 2 );
		add_filter( 'auto_update_theme', array( $this, 'maybe_auto_update' ), 10, 2 );

		// Apply FTP credentials to install/update plugins and themes.
		add_action( 'plugins_loaded', array( $this, 'apply_credentials' ) );

		// Handle upgrade request.
		add_action( 'wpmudev_dashboard_admin_request', array( $this, 'handle_upgrade_request' ) );

		global $wp_version;

		// Need this only in WP 5.5+ (https://wp.me/p2AvED-lgK).
		if ( version_compare( $wp_version, '5.5.0', '>=' ) ) {
			// Add auto update capability to Dash plugin.
			add_filter( 'all_plugins', array( $this, 'add_auto_update_support' ) );
			// Sync auto update option between Dash and WP.
			add_action( 'update_option_auto_update_plugins', array( $this, 'sync_wp_to_dash' ), 10, 2 );
			add_action( 'update_option_wdp_un_autoupdate_dashboard', array( $this, 'sync_dash_to_wp' ), 10, 2 );
			add_action( 'update_site_option_auto_update_plugins', array( $this, 'sync_auto_update_network' ), 10, 3 );
			add_action( 'update_site_option_wdp_un_autoupdate_dashboard', array( $this, 'sync_auto_update_network' ), 10, 3 );
		}

		// Init special upgrader.
		$this->special_upgrader = new WPMUDEV_Dashboard_Special_Upgrader();
	}

	/**
	 * Add auto update UI support for Dash plugin.
	 *
	 * @param array $plugins Plugins list.
	 *
	 * @since 4.11.2
	 *
	 * @return array
	 */
	public function add_auto_update_support( $plugins ) {
		// Add auto update support.
		if ( isset( $plugins[ WPMUDEV_Dashboard::$basename ] ) ) {
			$plugins[ WPMUDEV_Dashboard::$basename ]['update-supported'] = true;
		}

		return $plugins;
	}

	/**
	 * Sync auto update enable/disable between WP and Dash in multisite.
	 *
	 * @param string $option    Name of the network option.
	 * @param mixed  $value     Current value of the network option.
	 * @param mixed  $old_value Old value of the network option.
	 *
	 * @since 4.11.2
	 *
	 * @return void
	 */
	public function sync_auto_update_network( $option, $value, $old_value ) {
		if ( 'auto_update_plugins' === $option ) {
			$this->sync_wp_to_dash( $old_value, $value );
		} elseif ( 'wdp_un_autoupdate_dashboard' === $option ) {
			$this->sync_dash_to_wp( $old_value, $value );
		}
	}

	/**
	 * Sync auto update change from WP to Dash.
	 *
	 * To make the auto update management compatible with WP
	 * we need to sync the enable/disable from WP plugins page
	 * with Dash and from Dash to WP.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value     The new option value.
	 *
	 * @since 4.11.2
	 *
	 * @return void
	 */
	public function sync_wp_to_dash( $old_value, $value ) {
		// Dash plugin file name.
		$filename = WPMUDEV_Dashboard::$basename;

		// Get Dashboard auto update enabled status.
		$dash_auto_enabled = (bool) WPMUDEV_Dashboard::$settings->get( 'autoupdate_dashboard', 'flags' );
		// Check if Dash is available in WP auto update list.
		$auto_enabled = in_array( $filename, $value, true );
		// Both are not same, then update.
		if ( $dash_auto_enabled !== $auto_enabled ) {
			// Change Dash to same as WP.
			WPMUDEV_Dashboard::$settings->set( 'autoupdate_dashboard', $auto_enabled, 'flags' );
		}
	}

	/**
	 * Sync Dash plugin auto update to WP.
	 *
	 * To make the auto update management compatible with WP
	 * we need to sync the enable/disable from WP plugins page
	 * with Dash and from Dash to WP.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value     The new option value.
	 *
	 * @since 4.11.2
	 *
	 * @return void
	 */
	public function sync_dash_to_wp( $old_value, $value ) {
		// Sync to WP.
		$this->change_wp_auto_update( (bool) $value );
	}

	/**
	 * Change WP auto update list to include/exclude Dash.
	 *
	 * @param bool $enable Is auto update enabled.
	 *
	 * @since 4.11.2
	 *
	 * @return void
	 */
	public function change_wp_auto_update( $enable = true ) {
		// Get auto update enabled plugins.
		$auto_updates = (array) get_site_option( 'auto_update_plugins', array() );
		// Check if Dash is enabled.
		$auto_enabled = in_array( WPMUDEV_Dashboard::$basename, $auto_updates, true );
		// If not already same.
		if ( $enable !== $auto_enabled ) {
			if ( $enable ) {
				// Enable Dash.
				$auto_updates[] = WPMUDEV_Dashboard::$basename;
				$auto_updates   = array_unique( $auto_updates );
			} else {
				// Disable Dash.
				$auto_updates = array_diff( $auto_updates, array( WPMUDEV_Dashboard::$basename ) );
			}

			// Update WP auto update list.
			update_site_option( 'auto_update_plugins', $auto_updates );
		}
	}

	/**
	 * Reset PHP opcache
	 *
	 * @since 4.9.3
	 */
	public function wp_opcache_reset() {
		if ( ! function_exists( 'opcache_reset' ) ) {
			return;
		}

		if ( ! empty( ini_get( 'opcache.restrict_api' ) ) && strpos( __FILE__, ini_get( 'opcache.restrict_api' ) ) !== 0 ) {
			return;
		}

		opcache_reset();
	}

	/**
	 * Captures core update results from hook, only way to get them
	 *
	 * @param $results
	 */
	public function capture_core_update_results( $results ) {
		$this->update_results = $results;
	}

	/**
	 * Checks if an installed project is the latest version or if an update
	 * is available.
	 *
	 * @param int $project_id The project-ID.
	 *
	 * @since  4.0.0
	 * @return bool True means there is an update (local project is outdated)
	 */
	public function is_update_available( $project_id ) {
		if ( ! $this->is_project_installed( $project_id ) ) {
			return false;
		}

		$local         = WPMUDEV_Dashboard::$site->get_cached_projects( $project_id );
		$local_version = $local['version'];

		$remote         = WPMUDEV_Dashboard::$api->get_project_data( $project_id );
		$remote_version = $remote['version'];

		return version_compare( $local_version, $remote_version, 'lt' );
	}

	/**
	 * Checks if a certain project is localy installed.
	 *
	 * @param int $project_id The project to check.
	 *
	 * @since  4.0.0
	 * @return bool True if the project is installed.
	 */
	public function is_project_installed( $project_id ) {
		$data = WPMUDEV_Dashboard::$site->get_cached_projects( $project_id );

		return ( ! empty( $data ) );
	}

	/**
	 * Get the nonced admin url for installing a given project.
	 *
	 * @param int $project_id The project to install.
	 *
	 * @since 1.0.0
	 * @return string|bool Generated admin url for installing the project.
	 */
	public function auto_install_url( $project_id ) {
		// Download possible?
		if ( ! WPMUDEV_Dashboard::$api->has_key() ) {
			return false;
		}

		$data    = WPMUDEV_Dashboard::$api->get_projects_data();
		$project = WPMUDEV_Dashboard::$api->get_project_data( $project_id );

		// Valid project ID?
		if ( empty( $project ) ) {
			return false;
		}

		// Already installed?
		if ( $this->is_project_installed( $project_id ) ) {
			return false;
		}

		// Auto-update possible for this project?
		if ( empty( $project['autoupdate'] ) ) {
			return false;
		}
		if ( 1 != $project['autoupdate'] ) {
			return false;
		}

		// User can install the project (license and tech requirements)?
		if ( ! $this->user_can_install( $project_id ) ) {
			return false;
		}
		if ( ! $this->is_project_compatible( $project_id ) ) {
			return false;
		}

		// All good, create the download URL.
		$url = false;
		if ( 'plugin' == $project['type'] ) {
			$url = wp_nonce_url(
				self_admin_url( "update.php?action=install-plugin&plugin=wpmudev_install-$project_id" ),
				"install-plugin_wpmudev_install-$project_id"
			);
		} elseif ( 'theme' == $project['type'] ) {
			$url = wp_nonce_url(
				self_admin_url( "update.php?action=install-theme&theme=wpmudev_install-$project_id" ),
				"install-theme_wpmudev_install-$project_id"
			);
		}

		return $url;
	}

	/**
	 * Get the nonced admin url for updating a given project.
	 *
	 * @param int $project_id The project to install.
	 *
	 * @since 1.0.0
	 * @return string|bool Generated admin url for updating the project.
	 */
	public function auto_update_url( $project_id ) {
		// Download possible?
		if ( ! WPMUDEV_Dashboard::$api->has_key() ) {
			return false;
		}

		$project = WPMUDEV_Dashboard::$api->get_project_data( $project_id );

		// Valid project ID?
		if ( empty( $project ) ) {
			return false;
		}

		// Already installed?
		if ( ! $this->is_project_installed( $project_id ) ) {
			return false;
		}

		$local = WPMUDEV_Dashboard::$site->get_cached_projects( $project_id );
		if ( empty( $local ) ) {
			return false;
		}

		// Auto-update possible for this project?
		if ( empty( $project['autoupdate'] ) ) {
			return false;
		}
		if ( 1 != $project['autoupdate'] ) {
			return false;
		}

		// User can install the project (license and tech requirements)?
		if ( ! $this->user_can_install( $project_id ) ) {
			return false;
		}
		if ( ! $this->is_project_compatible( $project_id ) ) {
			return false;
		}

		// All good, create the update URL.
		$url = false;
		if ( 'plugin' == $project['type'] ) {
			$update_file = $local['filename'];
			$url         = wp_nonce_url(
				self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $update_file ),
				'upgrade-plugin_' . $update_file
			);
		} elseif ( 'theme' == $project['type'] ) {
			$update_file = $local['slug'];
			$url         = wp_nonce_url(
				self_admin_url( 'update.php?action=upgrade-theme&theme=' . $update_file ),
				'upgrade-theme_' . $update_file
			);
		}

		return $url;
	}

	/**
	 * Check user permissions to see if we can install this project.
	 *
	 * @param int  $project_id   The project to check.
	 * @param bool $only_license Skip permission check, only validate license.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function user_can_install( $project_id, $only_license = false ) {
		$data              = WPMUDEV_Dashboard::$api->get_projects_data();
		$membership_type   = WPMUDEV_Dashboard::$api->get_membership_status();
		$licensed_projects = WPMUDEV_Dashboard::$api->get_membership_projects();
		$excluded_projects = WPMUDEV_Dashboard::$api->get_excluded_projects();

		if ( 'unit' === $membership_type ) {
			foreach ( $licensed_projects as $p ) {
				$is_allowed = intval( $project_id ) === $p;
				if ( $is_allowed ) {
					return true;
				}
			}
		}

		if ( in_array( intval( $project_id ), $excluded_projects, true ) ) {
			return false;
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
			if ( ! WPMUDEV_Dashboard::$site->allowed_user() && ! current_user_can( 'edit_plugins' ) ) {
				return false;
			}
			// if ( ! $this->can_auto_install( $project['type'] ) ) { return false; }
		}

		$is_upfront = WPMUDEV_Dashboard::$site->id_upfront == $project_id;
		$package    = isset( $project['package'] ) ? $project['package'] : '';
		$access     = false;

		if ( 'full' === $membership_type ) {
			// User has full membership.
			$access = true;
		} elseif ( 'single' === $membership_type && $licensed_projects == $project_id ) {
			// User has single membership for the requested project.
			$access = true;
		} elseif ( 'free' === $project['paid'] ) {
			// It's a free project. All users can install this.
			$access = true;
		} elseif ( 'lite' === $project['paid'] ) {
			// It's a lite project. All users can install this.
			$access = true;
		} elseif ( 'single' === $membership_type && $package && $package == $licensed_projects ) {
			// A packaged project that the user bought.
			$access = true;
		} elseif ( $is_upfront && 'single' === $membership_type ) {
			// User wants to get Upfront parent theme.
			$access = true;
		} elseif ( 'free' === $membership_type && in_array( intval( $project_id ), $licensed_projects, true ) ) {
			// TFH user with plugin access.
			$access = true;
		}

		return $access;
	}

	/**
	 * Check whether this project is compatible with the current install based
	 * on requirements from API.
	 *
	 * @param int    $project_id The project to check.
	 * @param string $reason     If incompatible the reason is stored in this
	 *                           output-parameter.
	 *
	 * @since  1.0.0
	 * @return bool True if the project is compatible with current site.
	 */
	public function is_project_compatible( $project_id, &$reason = '' ) {
		$data   = WPMUDEV_Dashboard::$api->get_projects_data();
		$reason = '';

		if ( empty( $data['projects'][ $project_id ] ) ) {
			return false;
		}

		// Get project data.
		$project = $data['projects'][ $project_id ];

		// Minimum required PHP version.
		$requires_min_php = empty( $project['requires_min_php'] ) ? $this->min_php : $project['requires_min_php'];

		// Skip if minimum required PHP version is not found.
		if ( version_compare( PHP_VERSION, $requires_min_php, '<' ) ) {
			$reason = 'php';

			return false;
		}

		if ( empty( $project['requires'] ) ) {
			$reason = 'unknown requirements';

			return false;
		}

		// Skip multisite only products if not compatible.
		if ( 'ms' == $project['requires'] && ! is_multisite() ) {
			$reason = 'multisite';

			return false;
		}

		// Skip BuddyPress only products if not active.
		if ( 'bp' == $project['requires'] && ! defined( 'BP_VERSION' ) ) {
			$reason = 'buddypress';

			return false;
		}

		return true;
	}

	/**
	 * Can plugins be automatically installed? Checks filesystem permissions
	 * and WP configuration to determine.
	 *
	 * @param string $type Either plugin or theme.
	 *
	 * @since  1.0.0
	 * @return bool True means that projects can be downloaded automatically.
	 */
	public function can_auto_install( $type ) {
		$writable = false;

		if ( ! function_exists( 'get_filesystem_method' ) ) {
			include_once ABSPATH . '/wp-admin/includes/file.php';
		}

		// Are we dealing with direct access FS?
		if ( 'direct' == get_filesystem_method() ) {
			if ( 'plugin' == $type ) {
				$root = WP_PLUGIN_DIR;
			} elseif ( 'language' === $type ) {
				$root = is_dir( WP_LANG_DIR ) ? WP_LANG_DIR : WP_CONTENT_DIR;
			} else {
				$root = WP_CONTENT_DIR . '/themes';
			}

			$writable = is_writable( $root );
		}

		// If we don't have write permissions, do we have FTP settings?
		if ( ! $writable ) {
			$writable = defined( 'FTP_USER' )
			            && defined( 'FTP_PASS' )
			            && defined( 'FTP_HOST' );
		}

		// Lastly, if no other option worked, do we have SSH settings?
		if ( ! $writable ) {
			$writable = defined( 'FTP_USER' )
			            && defined( 'FTP_PUBKEY' )
			            && defined( 'FTP_PRIKEY' );
		}

		return $writable;
	}

	/**
	 * Read FTP credentials from the POST data and store them in a httponly
	 * cookie, with expiration 15 mintues.
	 *
	 * @since  1.0.0
	 * @return bool True on success.
	 */
	public function remember_credentials() {
		if ( ! isset( $_POST['ftp_user'] ) ) {
			return false;
		}
		if ( ! isset( $_POST['ftp_pass'] ) ) {
			return false;
		}
		if ( ! isset( $_POST['ftp_host'] ) ) {
			return false;
		}

		// Store user + host in DB so we have correct default values next time.
		$credentials             = (array) get_option(
			'ftp_credentials',
			array(
				'hostname' => '',
				'username' => '',
			)
		);
		$credentials['hostname'] = $_POST['ftp_host'];
		$credentials['username'] = $_POST['ftp_user'];
		update_option( 'ftp_credentials', $credentials );

		// Prepare and set the httponly cookie for next 15 minutes.
		$cookie_data = array(
			urlencode( $_POST['ftp_user'] ),
			urlencode( $_POST['ftp_pass'] ),
			urlencode( $_POST['ftp_host'] ),
		);
		$expire      = time() + 900; // 15minutes * 60seconds.

		$secure_cookie = 'https' === wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );

		return setcookie(
			COOKIEHASH . '-dev_ftp_data',
			implode( '&', $cookie_data ),
			$expire,
			COOKIEPATH,
			COOKIE_DOMAIN,
			$secure_cookie,
			true
		);
	}

	/**
	 * If we have a cookie with FTP credentials we will apply them here so
	 * WordPress can use them to install/update plugins.
	 *
	 * @since  1.0.0
	 */
	public function apply_credentials() {
		$secure_cookie = 'https' === wp_parse_url( get_option( 'home' ), PHP_URL_SCHEME );
		$cookie_name   = COOKIEHASH . '-dev_ftp_data';
		if ( empty( $_COOKIE[ $cookie_name ] ) ) {
			return;
		}

		$cookie_data = explode( '&', $_COOKIE[ $cookie_name ] );
		if ( 3 != count( $cookie_data ) ) {
			// Clear invalid cookie!
			setcookie(
				$cookie_name,
				'',
				1,
				COOKIEPATH,
				COOKIE_DOMAIN,
				$secure_cookie,
				true
			);

			return;
		}

		// Set the const values so WP can use them.
		if ( ! defined( 'FTP_USER' ) ) {
			define( 'FTP_USER', urldecode( $cookie_data[0] ) );
		}
		if ( ! defined( 'FTP_PASS' ) ) {
			define( 'FTP_PASS', urldecode( $cookie_data[1] ) );
		}
		if ( ! defined( 'FTP_HOST' ) ) {
			define( 'FTP_HOST', urldecode( $cookie_data[2] ) );
		}
	}

	/**
	 * Checks requirements, install-status, etc before upgrading the specific
	 * WPMU DEV project. Returns the project slug for upgrader.
	 *
	 * @param int $pid Project ID.
	 *
	 * @since  1.0.0
	 * @return array|bool Details about the project needed by upgrade().
	 */
	protected function prepare_dev_upgrade( $pid ) {
		$resp = array(
			'slug'     => 'wpmudev_install-' . $pid,
			'filename' => '',
			'type'     => '',
		);

		// Refresh local project cache before the update starts.
		WPMUDEV_Dashboard::$site->refresh_local_projects( 'local' );
		$local_projects = WPMUDEV_Dashboard::$site->get_cached_projects();

		// Now make sure that the project is updated, no matter what!
		WPMUDEV_Dashboard::$api->calculate_upgrades( $local_projects, $pid );

		if ( ! $this->is_project_installed( $pid ) ) {
			$this->set_error( $pid, 'UPG.01', __( 'Project not installed', 'wpmudev' ) );

			return false;
		}

		$project          = WPMUDEV_Dashboard::$site->get_project_info( $pid );

		if ( ! $project->is_compatible && ! empty( $project->incompatible_reason ) ) {
			$this->set_error( $pid, 'INS.09', sprintf( __( 'Incompatible: %s', 'wpmudev' ), $project->incompatible_reason ) );

			return false;
		}

		$resp['type']     = $project->type;
		$resp['filename'] = $project->filename;

		return $resp;
	}

	/**
	 * Handle upgrade of a single item (plugin/theme).
	 *
	 * Download and install a single plugin/theme update.
	 * A lot of logic is borrowed from ajax-actions.php.
	 *
	 * @param string $file Item file name.
	 * @param string $type Type (plugin/theme).
	 *
	 * @since 4.11.1 Moved to separate method.
	 *
	 * @return array
	 */
	private function process_upgrade( $file, $type ) {
		$response = array(
			'error'       => array(),
			'success'     => false,
			'log'         => false,
			'new_version' => false,
		);

		// Make sure all required files are loaded.
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Skin class.
		$skin = new WP_Ajax_Upgrader_Skin();

		switch ( $type ) {
			case 'plugin':
				// Update the update transient.
				wp_update_plugins();

				// Store the activation status.
				$active_blog    = is_plugin_active( $file );
				$active_network = is_multisite() && is_plugin_active_for_network( $file );

				// Plugin upgrader class.
				$upgrader = new Plugin_Upgrader( $skin );
				// Run the upgrade process.
				$result = $upgrader->bulk_upgrade( array( $file ) );

				/*
				 * Note: The following plugin activation is an intended and
				 * needed step. During upgrade() WordPress deactivates the
				 * plugin network- and site-wide. By default the user would
				 * see a upgrade-results page with the option to activate the
				 * plugin again. We skip that screen and restore original state.
				 */
				if ( $active_blog ) {
					activate_plugin( $file, false, false, true );
				}
				if ( $active_network ) {
					activate_plugin( $file, false, true, true );
				}
				break;

			case 'theme':
				// Update the update transient.
				wp_update_themes();

				// Theme upgrader class.
				$upgrader = new Theme_Upgrader( $skin );
				// Run the upgrade process.
				$result = $upgrader->bulk_upgrade( array( $file ) );
				break;

			default:
				// Return error for other types.
				$response['error']['code']    = 'UPG.08';
				$response['error']['message'] = __( 'Invalid upgrade call', 'wpmudev' );

				return $response;
		}

		// Reset cache.
		$this->wp_opcache_reset();

		// Set the upgrade log.
		$response['log'] = $skin->get_upgrade_messages();

		// Handle different types of errors.
		if ( is_wp_error( $skin->result ) ) {
			if ( in_array( $skin->result->get_error_code(), array( 'remove_old_failed', 'mkdir_failed_ziparchive' ), true ) ) {
				$response['error']['code']    = 'UPG.10';
				$response['error']['message'] = $skin->get_error_messages();
			} else {
				$response['error']['code']    = 'UPG.04';
				$response['error']['message'] = $skin->result->get_error_message();
			}

			return $response;
		} elseif ( in_array( $skin->get_errors()->get_error_code(), array( 'remove_old_failed', 'mkdir_failed_ziparchive' ), true ) ) {
			$response['error']['code']    = 'UPG.10';
			$response['error']['message'] = $skin->get_error_messages();

			return $response;
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$response['error']['code']    = 'UPG.09';
			$response['error']['message'] = $skin->get_error_messages();

			return $response;
		} elseif ( false === $result ) {
			global $wp_filesystem;

			$response['error']['code']    = 'UPG.05';
			$response['error']['message'] = __( 'Unable to connect to the filesystem. Please confirm your credentials', 'wpmudev' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$response['error']['message'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			return $response;
		} elseif ( is_array( $result ) && ! empty( $result[ $file ] ) ) {
			// Upgrade is success. Yay!.
			$response['success'] = true;
			// Get the new version.
			if ( 'plugin' === $type ) {
				/**
				 * Filter to set new plugin version number.
				 *
				 * If you return something other than empty, we won't check for plugin data imagining
				 * that the data is already given.
				 *
				 * @since 4.11.13
				 *
				 * @param array  $plugin_data Plugin data.
				 * @param string $file        Plugin file.
				 */
				$plugin_data = apply_filters( 'wpmudev_dashboard_upgrader_get_plugin_data', array(), $file );

				if ( empty( $plugin_data ) ) {
					// Get new plugin data.
					$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $file );
				}

				// Set new plugin version.
				$response['new_version'] = $plugin_data['Version'];
			} else {
				// Get theme data.
				$theme = wp_get_theme( $file );
				// Set new version.
				$response['new_version'] = $theme->get( 'Version' );
			}

			// API call to inform wpmudev site about the change,
			// as it's a single we can let it do that at the end to avoid multiple pings.
			WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();

			return $response;
		}

		// An unhandled error occurred.
		$response['error']['code']    = 'UPG.06';
		$response['error']['message'] = __( 'Update failed for an unknown reason', 'wpmudev' );

		return $response;
	}

	/**
	 * Download and install a single plugin/theme update.
	 *
	 * A lot of logic is borrowed from ajax-actions.php
	 *
	 * @param int|string $pid The project ID or a plugin slug.
	 *
	 * @since  4.0.0
	 *
	 * @return bool True on success.
	 */
	public function upgrade( $pid ) {
		$this->clear_error();
		$this->clear_log();
		$this->clear_version();

		// Is a WPMU DEV project?
		$is_dev = is_numeric( $pid );

		if ( $is_dev ) {
			$pid = (int) $pid;
			// Prepare required data for WPMUDEV projects.
			$infos = $this->prepare_dev_upgrade( $pid );
			if ( ! $infos ) {
				return false;
			}

			// Get file name and type.
			$type     = $infos['type'];
			$filename = 'theme' === $type ? dirname( $infos['filename'] ) : $infos['filename'];
		} elseif ( is_string( $pid ) ) {
			// No need to check if the plugin exists/is installed. WP will check it.
			list( $type, $filename ) = explode( ':', $pid );
		} else {
			// Can not continue.
			$this->set_error( $pid, 'UPG.07', __( 'Invalid upgrade call', 'wpmudev' ) );

			return false;
		}

		// Permission check.
		if ( ! $this->can_auto_install( $type ) ) {
			$this->set_error( $pid, 'UPG.10', __( 'Insufficient filesystem permissions', 'wpmudev' ) );

			return false;
		}

		// For plugins_api/themes_api.
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		include_once ABSPATH . 'wp-admin/includes/theme-install.php';
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		include_once ABSPATH . 'wp-admin/includes/theme.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';

		/*
		 * Set before the update:
		 * WP will refresh local cache via action-hook before the install()
		 * method is finished. That refresh call must scan the FS again.
		 */
		if ( $is_dev ) {
			WPMUDEV_Dashboard::$site->clear_local_file_cache();
		}

		// Upgrade the item.
		$result = $this->process_upgrade( $filename, $type );

		// If failed, try premium upgrade.
		if ( empty( $result['success'] ) ) {
			$result = $this->premium_upgrade_request( $filename, $type );
		}

		// Set the upgrade log.
		$this->log = empty( $result['log'] ) ? false : $result['log'];

		// Both methods failed.
		if ( empty( $result['success'] ) ) {
			$this->set_error( $pid, $result['error']['code'], $result['error']['message'] );

			return false;
		} else {
			// Success. Yay!.
			$this->new_version = $result['new_version'];

			return true;
		}
	}

	/**
	 * Premium plugin/theme upgrade for compatibility.
	 *
	 * Make an HTTP request to our own WP Admin to process
	 * update request since most of the premium plugins and
	 * themes are initializing the update logic only in admin
	 * side of WP.
	 * This may not work in some servers if the request is timed out
	 * But that's the maximum we can do from Dash plugin.
	 *
	 * @param string $file Item file name.
	 * @param string $type Type (plugin/theme).
	 *
	 * @since 4.11.7
	 *
	 * @uses  admin_url()
	 * @uses  wp_remote_post()
	 *
	 * @return array
	 */
	private function premium_upgrade_request( $file, $type ) {
		// Make post request.
		$response = WPMUDEV_Dashboard::$utils->send_admin_request(
			array(
				'action' => 'upgrade',
				'from'   => 'upgrader',
				'file'   => $file,
				'type'   => $type,
			)
		);

		// If request not failed.
		if ( ! empty( $response ) ) {
			// Get response body.
			$response = json_decode( $response, true );

			if ( isset( $response['success'] ) ) {
				if ( empty( $response['error'] ) ) {
					return array(
						'success'     => true,
						'new_version' => $response['new_version'],
						'log'         => $response['log'],
					);
				} else {
					return array(
						'success' => false,
						'log'     => $response['log'],
						'error'   => $response['error'],
					);
				}
			}
		}

		return array(
			'success' => false,
			'log'     => false,
			'error'   => array(
				'code'    => 'UPG.13',
				'message' => __( 'Update failed for an unknown reason', 'wpmudev' ),
			),
		);
	}

	/**
	 * Handle the post request for upgrade.
	 *
	 * This is being used to add compatibility for premium plugins/themes
	 * updates which runs properly only on WP admin side.
	 *
	 * @param array $data Request data.
	 *
	 * @since 4.11.7
	 *
	 * @return void
	 */
	public function handle_upgrade_request( $data ) {
		// Only if all values are set.
		if (
			isset( $data['type'], $data['file'], $data['from'], $data['action'] )
			&& 'upgrader' === $data['from']
			&& 'upgrade' === $data['action']
		) {
			// Skip sync, hub remote calls are recorded locally.
			if ( ! defined( 'WPMUDEV_REMOTE_SKIP_SYNC' ) ) {
				define( 'WPMUDEV_REMOTE_SKIP_SYNC', true );
			}

			// All good. Process the request.
			wp_send_json( $this->process_upgrade( $data['file'], $data['type'] ) );
		}
	}

	/**
	 * Download and install a plugin translation files.
	 *
	 * A lot of logic is borrowed from ajax-actions.php
	 *
	 * @param string $slug Plugin slugs to upgrade translations.
	 *
	 * @since  4.8.0
	 * @return bool True on success.
	 */
	public function upgrade_translation( $slug = '' ) {

		$translations = $this->wp_format_translation_updates( $slug );
		if ( empty( $translations ) ) {
			$this->set_error( $slug, 'TUPG.01', __( 'WPMU Dev translations upto date', 'wpmudev' ) );

			return false;
		}

		if ( ! $this->can_auto_install( 'language' ) ) {
			$this->set_error( $slug, 'TUPG.02', __( 'Insufficient filesystem permissions', 'wpmudev' ) );

			return false;
		}

		// for updating translations
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Language_Pack_Upgrader( $skin );
		$result   = false;
		$success  = false;

		$result = $upgrader->bulk_upgrade( $translations );

		$this->log = $skin->get_upgrade_messages();

		if ( is_wp_error( $skin->get_errors() ) && ! $skin->result ) {
			$this->set_error( $slug, 'TUPG.03', $skin->get_errors()->get_error_message() );

			return false;
		} elseif ( false === $result ) {
			global $wp_filesystem;

			$error = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'wpmudev' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$error = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			$this->set_error( $slug, 'TUPG.04', $error );

			return false;
		} elseif ( $result ) { // this is success!

			// API call to inform wpmudev site about the change, as it's a single we can let it do that at the end to avoid multiple pings
			//WPMUDEV_Dashboard::$api->calculate_translation_upgrades( true );

			return true;
		}

		// An unhandled error occurred.
		$this->set_error( $slug, 'TUPG.05', __( 'Update failed for an unknown reason.', 'wpmudev' ) );

		return false;
	}

	/**
	 * Retrieves a list of all language updates available.
	 *
	 * @param  $slug string Slug of the plugin that we are to update
	 *
	 * @since 4.8.0
	 *
	 * @return object[] Array of translation objects that have available updates.
	 */
	public function wp_format_translation_updates( $slug = '' ) {
		$updates      = array();
		$translations = WPMUDEV_Dashboard::$settings->get( 'translation_updates_available' );

		// if no translations avaialbe return empty
		if ( empty( $translations ) ) {
			return array();
		}

		// if empty slug return all available.
		if ( empty( $slug ) ) {
			foreach ( $translations as $key => $value ) {
				$updates[] = (object) $value;
			}
		} else {
			foreach ( $translations as $key => $value ) {
				if ( $value['slug'] === $slug ) {
					$updates[] = (object) $value;
					break;
				}
			}
		}

		return $updates;
	}

	/**
	 * Install a new plugin or theme.
	 *
	 * A lot of logic is borrowed from ajax-actions.php.
	 *
	 * @param int    $pid      The project ID.
	 * @param string $type     plugin or theme.
	 * @param array  $options  Options.
	 *
	 * @since  4.0.0
	 *
	 * @return bool True on success.
	 */
	public function install( $pid, $type = 'plugin', $options = array() ) {
		$this->clear_error();
		$this->clear_log();

		$slug = '';
		$link = '';

		// Is a WPMU DEV project?
		$is_dev = is_numeric( $pid );

		if ( $is_dev ) {
			$pid = (int) $pid;

			// Plugin is already installed.
			if ( $this->is_project_installed( $pid ) ) {
				$this->set_error( $pid, 'INS.01', __( 'Already installed', 'wpmudev' ) );

				return false;
			}

			// Get project data.
			$project = WPMUDEV_Dashboard::$site->get_project_info( $pid );
			// Invalid project.
			if ( ! $project ) {
				$this->set_error( $pid, 'INS.04', __( 'Invalid project', 'wpmudev' ) );

				return false;
			}

			$slug = 'wpmudev_install-' . $pid;
			$type = $project->type;

			if ( ! $this->can_auto_install( $type ) ) {
				$this->set_error( $pid, 'INS.09', __( 'Insufficient filesystem permissions', 'wpmudev' ) );

				return false;
			}

			// Check if project is compatible.
			if ( ! $project->is_compatible && ! empty( $project->incompatible_reason ) ) {
				$this->set_error( $pid, 'INS.09', sprintf( __( 'Incompatible: %s', 'wpmudev' ), $project->incompatible_reason ) );

				return false;
			}

			// Make sure Upfront is available before an upfront theme or plugin is installed.
			if ( $project->need_upfront && ! WPMUDEV_Dashboard::$site->is_upfront_installed() ) {
				$this->install( WPMUDEV_Dashboard::$site->id_upfront );
			}
		} elseif ( is_string( $pid ) ) {
			// If the string is a URL.
			if ( filter_var( $pid, FILTER_VALIDATE_URL ) ) {
				$link = esc_url_raw( $pid );
			} else {
				// Don't worry, pid is the file name.
				$slug = ( 'plugin' === $type && false !== strpos( $pid, '/' ) ) ? dirname( $pid ) : $pid;
			}
		} else {
			$this->set_error( $pid, 'INS.07', __( 'Invalid upgrade call', 'wpmudev' ) );

			return false;
		}

		// For plugins_api/themes_api..
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		include_once ABSPATH . 'wp-admin/includes/theme-install.php';
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		include_once ABSPATH . 'wp-admin/includes/theme.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';

		$skin = new WP_Ajax_Upgrader_Skin();

		/*
		 * Set before the update:
		 * WP will refresh local cache via action-hook before the install()
		 * method is finished. That refresh call must scan the FS again.
		 */
		if ( $is_dev ) {
			WPMUDEV_Dashboard::$site->clear_local_file_cache();
		}

		// Overwrite existing folder.
		if ( ! empty( $options['overwrite'] ) ) {
			add_filter( 'upgrader_package_options', array( $this, 'add_overwrite_option' ) );
		}

		switch ( $type ) {
			case 'plugin':
				// If the link is not provided.
				if ( empty( $link ) ) {
					// Save on a bit of bandwidth.
					$api = plugins_api(
						'plugin_information',
						array(
							'slug'   => sanitize_key( $slug ),
							'fields' => array( 'sections' => false ),
						)
					);

					if ( is_wp_error( $api ) ) {
						$this->set_error( $pid, 'INS.02', $api->get_error_message() );
						// Remove temporary filter.
						$this->remove_overwrite_filter();

						return false;
					}

					// Get download link.
					$link = $api->download_link;
				}

				// Install the plugin.
				$upgrader = new Plugin_Upgrader( $skin );
				$result   = $upgrader->install( $link );

				// If installed and activation is required.
				if ( ! empty( $options['activate'] ) && true === $result ) {
					$plugin = $this->special_upgrader->get_plugin_info_path( $upgrader->skin->result );
					// Plugin file found.
					if ( ! empty( $plugin ) ) {
						/**
						 * Filter hook to change plugin silent activation.
						 *
						 * @since 4.11.20
						 *
						 * @param bool $silent Should silence activation?.
						 */
						$silent_activation = apply_filters( 'wpmudev_dashboard_plugin_install_silent_activation', false );

						// Activate the plugin.
						$activated = activate_plugin( $plugin, false, is_multisite(), $silent_activation );
						// If error in activation.
						if ( is_wp_error( $activated ) ) {
							$this->set_error( $pid, 'INS.10', $activated->get_error_message() );
							// Remove temporary filter.
							$this->remove_overwrite_filter();

							return false;
						}
					}
				}
				break;

			case 'theme':
				if ( empty( $link ) ) {
					// Save on a bit of bandwidth.
					$api = themes_api(
						'theme_information',
						array(
							'slug'   => sanitize_key( $slug ),
							'fields' => array( 'sections' => false ),
						)
					);

					if ( is_wp_error( $api ) ) {
						$this->set_error( $pid, 'INS.02', $api->get_error_message() );
						// Remove temporary filter.
						$this->remove_overwrite_filter();

						return false;
					}

					// Get download link.
					$link = $api->download_link;
				}

				// Install theme.
				$upgrader = new Theme_Upgrader( $skin );
				$result   = $upgrader->install( $link );
				break;

			default:
				$this->set_error( $pid, 'INS.08', __( 'Invalid upgrade call', 'wpmudev' ) );
				// Remove temporary filter.
				$this->remove_overwrite_filter();

				return false;
		}

		// Remove temporary filter.
		$this->remove_overwrite_filter();

		$this->log = $skin->get_upgrade_messages();
		if ( is_wp_error( $result ) ) {
			if ( 'mkdir_failed_ziparchive' === $skin->$result->get_error_code() ) {
				$this->set_error( $pid, 'INS.09', $skin->get_error_messages() );
			} else {
				$this->set_error( $pid, 'INS.05', $result->get_error_message() );
			}

			return false;
		} elseif ( is_wp_error( $skin->result ) ) {
			$this->set_error( $pid, 'INS.03', $skin->result->get_error_message() );

			return false;
		} elseif ( 'mkdir_failed_ziparchive' === $skin->get_errors()->get_error_code() ) {
			$this->set_error( $pid, 'INS.09', $skin->get_error_messages() );

			return false;
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$this->set_error( $pid, 'INS.06', $skin->get_error_messages() );

			return false;
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$error = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'wpmudev' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$error = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			$this->set_error( $pid, 'INS.08', $error );

			return false;
		}

		// API call to inform wpmudev site about the change,
		// as it's a single we can let it do that at the end to avoid multiple pings.
		WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();

		return true;
	}

	/**
	 * Upgrade WP Core to latest version
	 *
	 * A lot of logic is borrowed from WP_Automatic_Updater
	 *
	 * @since  4.4
	 * @return bool True on success.
	 */
	public function upgrade_core() {
		global $wp_version, $wpdb;

		$this->clear_error();
		$this->clear_log();
		$this->clear_version();

		/**
		 * mimic @see wp_maybe_auto_update()
		 */
		include_once ABSPATH . 'wp-admin/includes/admin.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		add_action( 'automatic_updates_complete', array( $this, 'capture_core_update_results' ) );

		add_filter( 'auto_update_core', '__return_true', 99999 ); // temporarily allow core autoupdates
		add_filter( 'allow_major_auto_core_updates', '__return_true', 99999 ); // temporarily allow core autoupdates
		add_filter( 'allow_minor_auto_core_updates', '__return_true', 99999 ); // temporarily allow core autoupdates
		add_filter( 'auto_update_core', '__return_true', 99999 ); // temporarily allow core autoupdates
		add_filter( 'auto_update_theme', '__return_false', 99999 );
		add_filter( 'auto_update_plugin', '__return_false', 99999 );

		// TODO don't send email for successful updates
		// apply_filters( 'auto_core_update_send_email', true, $type, $core_update, $result )

		$upgrader = new WP_Automatic_Updater();

		/* ---- these checks are already run later, but we run them now so we can capture detailed errors --- */

		if ( $upgrader->is_disabled() || ( defined( 'WP_AUTO_UPDATE_CORE' ) && false === WP_AUTO_UPDATE_CORE ) ) {
			$this->set_error(
				'core',
				'autoupdates_disabled',
				sprintf(
					__(
						'You have disabled automatic core updates via define( \'WP_AUTO_UPDATE_CORE\', false ); in your wp-config.php or a filter. Remove that code to allow updating core by Automate or disable "WordPress Core" in your Automate settings. %1$sContact support%2$s if you need further assistance.',
						'wpmudev'
					),
					'<a href="https://wpmudev.com/hub/support/#get-support">',
					'</a>'
				)
			);

			return false;
		}

		// Used to see if WP_Filesystem is set up to allow unattended updates.
		$skin = new Automatic_Upgrader_Skin();
		if ( ! $skin->request_filesystem_credentials( false, ABSPATH, false ) ) {
			$this->set_error( 'core', 'fs_unavailable', __( 'Could not access filesystem.', 'wpmudev' ) ); // this string is from core translation

			return false;
		}

		if ( $upgrader->is_vcs_checkout( ABSPATH ) ) {
			$this->set_error( 'core', 'is_vcs_checkout', __( 'Automatic core updates are disabled when WordPress is checked out from version control.', 'wpmudev' ) );

			return false;
		}

		wp_version_check(); // Check for Core updates
		$updates = get_site_transient( 'update_core' );
		if ( ! $updates || empty( $updates->updates ) ) {
			return false;
		}

		$auto_update = false;
		foreach ( $updates->updates as $update ) {
			if ( 'autoupdate' != $update->response ) {
				continue;
			}

			if ( ! $auto_update || version_compare( $update->current, $auto_update->current, '>' ) ) {
				$auto_update = $update;
			}
		}

		if ( ! $auto_update ) {
			$this->set_error( 'core', 'update_unavailable', __( 'No WordPress core updates appear available.', 'wpmudev' ) );

			return false;
		}

		// compatiblity
		$php_compat = version_compare( phpversion(), $auto_update->php_version, '>=' );
		if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && empty( $wpdb->is_mysql ) ) {
			$mysql_compat = true;
		} else {
			$mysql_compat = version_compare( $wpdb->db_version(), $auto_update->mysql_version, '>=' );
		}

		if ( ! $php_compat || ! $mysql_compat ) {
			$this->set_error( 'core', 'incompatible', __( 'The new version of WordPress is incompatible with your PHP or MySQL version.', 'wpmudev' ) );

			return false;
		}

		// If this was a critical update failure last try, cannot update.
		$skip         = false;
		$failure_data = get_site_option( 'auto_core_update_failed' );
		if ( $failure_data ) {
			if ( ! empty( $failure_data['critical'] ) ) {
				$skip = true;
			}

			// Don't claim we can update on update-core.php if we have a non-critical failure logged.
			if ( $wp_version == $failure_data['current'] && false !== strpos( $auto_update->current, '.1.next.minor' ) ) {
				$skip = true;
			}

			// Cannot update if we're retrying the same A to B update that caused a non-critical failure.
			// Some non-critical failures do allow retries, like download_failed.
			if ( empty( $failure_data['retry'] ) && $wp_version == $failure_data['current'] && $auto_update->current == $failure_data['attempted'] ) {
				$skip = true;
			}

			if ( $skip ) {
				$this->set_error( 'core', 'previous_failure', __( 'There was a previous failure with this update. Please update manually instead.', 'wpmudev' ) );

				return false;
			}
		}

		// this is the only reason left this would fail
		if ( ! Core_Upgrader::should_update_to_version( $auto_update->current ) ) {
			$this->set_error(
				'core',
				'autoupdates_disabled',
				sprintf(
					__(
						'You have disabled automatic core updates via define( \'WP_AUTO_UPDATE_CORE\', false ); in your wp-config.php or a filter. Remove that code to allow updating core by Automate or disable "WordPress Core" in your Automate settings. %1$sContact support%2$s if you need further assistance.',
						'wpmudev'
					),
					'<a href="https://wpmudev.com/hub/support/#get-support">',
					'</a>'
				)
			);

			return false;
		}

		/* -------------------------- */

		// ok we are good to give it a try
		$upgrader->run();

		// check populated var from hook
		if ( ! empty( $this->update_results['core'] ) ) {
			$update_result = $this->update_results['core'][0];

			$result    = $update_result->result;
			$this->log = $update_result->messages;

			// yay we did it!
			if ( ! is_wp_error( $result ) ) {
				$this->new_version = $result;

				// API call to inform wpmudev site about the change, as it's a single we can let it do that at the end to avoid multiple pings
				WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();

				return true;
			}

			$error_code = $result->get_error_code();
			$error_msg  = $result->get_error_message();

			// if a rollback was run and errored append that to message.
			if ( $error_code === 'rollback_was_required' && is_wp_error( $result->get_error_data()->rollback ) ) {
				$rollback_result = $result->get_error_data()->rollback;
				$error_msg       .= ' Rollback: ' . $rollback_result->get_error_message();
			}

			$this->set_error( 'core', $error_code, $error_msg );

			return false;
		}

		// An unhandled error occurred.
		$this->set_error( 'core', 'unknown_failure', __( 'Update failed for an unknown reason.', 'wpmudev' ) );

		return false;
	}

	/**
	 * This function checks if the specified project is configured for automatic
	 * upgrade in the background (without telling the user about the upgrade).
	 *
	 * If auto-upgrade is enabled then we enable it in the filter
	 *
	 * For dashboard it respects the setting "Enable
	 * automatic updates of WPMU DEV plugin" on the Manage page is enabled.
	 *
	 * @param bool   $should_update Whether this item should be autoupdated
	 * @param object $item          Plugin or Theme object
	 *
	 * @since  4.4
	 *
	 * @return boolean $should_update
	 */
	public function maybe_auto_update( $should_update, $item ) {

		if ( isset( $item->pid ) ) { // DEV themes have this set
			$project_id = $item->pid;
		} elseif ( ! empty( $item->slug ) && false !== strpos( $item->slug, 'wpmudev_install-' ) ) {
			// get the project_id
			list( , $project_id ) = explode( '-', $item->slug );
		} else {
			// Do nothing, not a DEV project
			return $should_update;
		}

		/*
		 * List of projects that will be automatically upgraded when the above
		 * flag is enabled.
		 */
		$auto_update_projects = apply_filters(
			'wpmudev_project_auto_update_projects',
			array(
				119, // WPMUDEV dashboard.
			)
		);

		if ( 119 == $project_id && ! WPMUDEV_Dashboard::$settings->get( 'autoupdate_dashboard', 'flags' ) ) {
			// Do nothing, auto-update is disabled for Dashboard plugin!
			return $should_update;
		}

		if ( in_array( $project_id, $auto_update_projects ) ) {
			return true;
		}

		return $should_update;
	}

	/**
	 * Stores the specific error details.
	 *
	 * @param string $pid     The PID that was installed/updated.
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 *
	 * @since 4.1.0
	 *
	 */
	public function set_error( $pid, $code, $message ) {
		$this->error = array(
			'pid'     => $pid,
			'code'    => $code,
			'message' => $message,
		);

		if ( defined( 'WPMUDEV_API_DEBUG' ) && WPMUDEV_API_DEBUG ) {
			error_log(
				sprintf( 'WPMU DEV Upgrader error: %s - %s.', $code, $message )
			);
		}
	}

	/**
	 * Clears the current error flag.
	 *
	 * @since  4.1.0
	 */
	public function clear_error() {
		$this->error = false;
	}

	/**
	 * Returns the current error details, or false if no error is set.
	 *
	 * @since  4.1.0
	 * @return false|array Either the error details or false (no error).
	 */
	public function get_error() {
		return $this->error;
	}

	/**
	 * Clears the current log.
	 *
	 * @since  4.3.0
	 */
	public function clear_log() {
		$this->log = false;
	}

	/**
	 * Returns the current log details, or false if no log is set.
	 *
	 * @since  4.3.0
	 * @return false|array Either the log details or false (no error).
	 */
	public function get_log() {
		return $this->log;
	}

	/**
	 * Clears the last updated version.
	 *
	 * @since  4.3.0
	 */
	public function clear_version() {
		$this->new_version = false;
	}

	/**
	 * Returns the current log details, or false if no log is set.
	 *
	 * @since  4.3.0
	 * @return false|array Either the log details or false (no error).
	 */
	public function get_version() {
		return $this->new_version;
	}

	/**
	 * Delete Plugin, used internally
	 *
	 * @param int|string $pid                 The project ID or plugin filename.
	 * @param bool       $skip_uninstall_hook to avoid data deleted on uninstall
	 *
	 * @since  4.7
	 *
	 * @return bool True on success.
	 */
	public function delete_plugin( $pid, $skip_uninstall_hook = false ) {
		$this->clear_error();
		$this->clear_log();

		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';

		// Is a WPMU DEV project?
		$is_dev = is_numeric( $pid );

		if ( $is_dev ) {
			$pid = (int) $pid;

			if ( ! $this->is_project_installed( $pid ) ) {
				$this->set_error( $pid, 'DEL.01', __( 'Plugin not installed', 'wpmudev' ) );

				return false;
			}
			$local    = WPMUDEV_Dashboard::$site->get_cached_projects( $pid );
			$filename = $local['filename'];
		} else {
			$filename = $pid;
		}

		$filename = plugin_basename( sanitize_text_field( $filename ) );

		// Check that it's a valid plugin
		$valid = validate_plugin( $filename );
		if ( is_wp_error( $valid ) ) {
			$this->set_error( $pid, 'DEL.09', $valid->get_error_message() );

			return false;
		}

		// Check activation status.
		if ( is_plugin_active( $filename ) ) {
			if ( is_multisite() ) {
				$this->set_error( $pid, 'DEL.02', __( 'This plugin is active on a subsite. Try again after deactivating it there.', 'wpmudev' ) );
			} else {
				$this->set_error( $pid, 'DEL.02', __( 'This plugin is active. Try again after deactivating it.', 'wpmudev' ) );
			}

			return false;
		}

		if ( is_multisite() && is_plugin_active_for_network( $filename ) ) {
			$this->set_error( $pid, 'DEL.02', __( 'This plugin is network-active. Try again after deactivating it.', 'wpmudev' ) );

			return false;
		}

		// Check filesystem credentials. `delete_plugins()` will bail otherwise.
		$url = wp_nonce_url( 'plugins.php?action=delete-selected&verify-delete=1&checked[]=' . $filename, 'bulk-plugins' );
		ob_start();
		$credentials = request_filesystem_credentials( $url );
		ob_end_clean();
		if ( false === $credentials || ! WP_Filesystem( $credentials ) ) {
			global $wp_filesystem;

			$error_code = 'DEL.03';
			$error      = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'wpmudev' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$error_code = $wp_filesystem->errors->get_error_code();
				$error      = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			$this->set_error( $pid, $error_code, $error );

			return false;
		}

		// skip uninstall hook if asked to
		if ( $skip_uninstall_hook ) {
			// uninstall hook available
			if ( is_uninstallable_plugin( $filename ) ) {
				/**
				 * @see is_uninstallable_plugin()
				 */
				$uninstallable_plugins = (array) get_option( 'uninstall_plugins' );
				if ( isset( $uninstallable_plugins[ $filename ] ) ) {
					unset( $uninstallable_plugins[ $filename ] );
					update_option( 'uninstall_plugins', $uninstallable_plugins );
				}

				if ( file_exists( WP_PLUGIN_DIR . '/' . dirname( $filename ) . '/uninstall.php' ) ) {
					/** @var WP_Filesystem_Base $wp_filesystem */
					global $wp_filesystem;
					if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
						$wp_filesystem->delete( WP_PLUGIN_DIR . '/' . dirname( $filename ) . '/uninstall.php', false, 'f' );
					}
				}
			}

			// one recheck
			if ( is_uninstallable_plugin( $filename ) ) {
				$this->set_error( $pid, 'DEL.07', __( 'Plugin Uninstall hook could not be removed.', 'wpmudev' ) );
			}
		}

		/*
		 * Set before the update:
		 * WP will refresh local cache via action-hook before the install()
		 * method is finished. That refresh call must scan the FS again.
		 */
		WPMUDEV_Dashboard::$site->clear_local_file_cache();
		$result = delete_plugins( array( $filename ) );

		if ( true === $result ) {
			wp_clean_plugins_cache( false );
			WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();

			return true;
		} elseif ( is_wp_error( $result ) ) {
			if ( 'could_not_remove_plugin' === $skin->$result->get_error_code() ) {
				$this->set_error( $pid, 'DEL.10', $skin->get_error_messages() );
			} else {
				$this->set_error( $pid, $result->get_error_code(), $result->get_error_message() );
			}

			return false;
		} else {
			$this->set_error( $pid, 'DEL.05', __( 'Plugin could not be deleted.', 'wpmudev' ) );

			return false;
		}
	}

	/**
	 * Set flag to overwrite plugins if folder already exists.
	 *
	 * @param array $options Installation options.
	 *
	 * @since 4.11.6
	 *
	 * @return array
	 */
	public function add_overwrite_option( $options ) {
		// Make sure we are overwriting existing plugin.
		$options['abort_if_destination_exists'] = false;

		return $options;
	}

	/**
	 * Remove temporary filter we added to overwrite existing folders.
	 *
	 * @since 4.11.6
	 */
	private function remove_overwrite_filter() {
		// Remove temporary filter.
		remove_filter( 'upgrader_package_options', array( $this, 'add_overwrite_option' ) );
	}
}