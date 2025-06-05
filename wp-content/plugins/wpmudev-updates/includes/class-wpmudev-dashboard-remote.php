<?php
/**
 * The remote-module class.
 *
 * Manages all remote access from Hub to the local WordPress site.
 *
 * @link    https://wpmudev.com
 * @since   4.3.0
 * @package WPMUDEV_Dashboard
 */

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class WPMUDEV_Dashboard_Remote
 *
 * @since 4.3.0
 */
class WPMUDEV_Dashboard_Remote {

	/**
	 * Stores request timing information for debug logging
	 *
	 * @var int
	 */
	protected $timer = 0;

	/**
	 * Stores current action being processed
	 *
	 * @var string
	 */
	protected $current_action = '';

	/**
	 * Stores current action params being processed
	 *
	 * @var array
	 * @since 4.11.3
	 */
	protected $current_params = array();

	/**
	 * Stores registered remote access actions and their callbacks.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Set up the remote module.
	 *
	 * Here we load and initialize the API request from Hub.
	 *
	 * @since  4.0.0
	 * @access public
	 */
	public function __construct() {
		// Using priority because some plugins may initialize updates with low priority.
		add_action( 'init', array( $this, 'run_request' ), 999 );
		// Run action on wpmudev admin actions.
		add_action( 'wpmudev_dashboard_admin_request', array( $this, 'run_admin_action' ) );
	}

	/**
	 * Setup current request data.
	 *
	 * Set current action name and params to be processed.
	 * If action and params are invalid, we will die with json
	 * error message.
	 *
	 * @since  4.11.3
	 * @access protected
	 *
	 * @return void
	 */
	public function run_request() {
		// Do nothing if we don't.
		if ( ! $this->is_hub_request() ) {
			return;
		}

		// Register actions.
		$this->register_internal_actions();
		$this->register_plugin_actions();

		// Get the json data.
		$raw_json = file_get_contents( 'php://input' );

		// Get body.
		$body = json_decode( $raw_json );

		// Validate hash.
		$this->validate_request_hash( $_GET['wpmudev-hub'], $raw_json ); // phpcs:ignore

		// Action name is required.
		if ( ! isset( $body->action ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_params',
					'message' => __( 'The "action" parameter is missing', 'wpmudev' ),
				)
			);
		}

		// Params are required.
		if ( ! isset( $body->params ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_params',
					'message' => __( 'The "params" object is missing', 'wpmudev' ),
				)
			);
		}

		// Set request data.
		$this->timer          = microtime( true );
		$this->current_action = $body->action;
		$this->current_params = $body->params;

		// Now process the actions.
		if ( $this->is_admin_action( $body->action ) ) {
			// Process admin actions.
			$this->send_admin_request();
		} else {
			// Process normal actions.
			$this->process_action();
		}
	}

	/**
	 * Run admin side actions after registering all actions.
	 *
	 * @param array $data Request data.
	 *
	 * @since  4.11.6
	 * @access protected
	 *
	 * @return void
	 */
	public function run_admin_action( $data ) {
		if ( isset( $data['action'], $data['params'], $data['from'] ) && 'remote' === $data['from'] ) {
			// Register actions.
			$this->register_internal_actions();
			$this->register_plugin_actions();

			// Set request data.
			$this->timer          = microtime( true );
			$this->current_action = $data['action'];
			$this->current_params = $data['params'];

			// Now process the actions.
			$this->process_action();
		}
	}

	/**
	 * Make an self post request to wp-admin.
	 *
	 * Make an HTTP request to our own WP Admin to process admin side actions
	 * specifically update requests and hub sync request since most of the premium
	 * plugins and themes are initializing the update logic only in admin side of WP.
	 * This may not work in some servers if the request is timed out
	 * But that's the maximum we can do from Dash plugin.
	 *
	 * @since 4.11.6
	 *
	 * @uses  admin_url()
	 * @uses  wp_remote_post()
	 *
	 * @return void
	 */
	private function send_admin_request() {
		// Make post request.
		$response = WPMUDEV_Dashboard::$utils->send_admin_request(
			array(
				'from'   => 'remote',
				'action' => $this->current_action,
				'params' => $this->current_params,
			)
		);

		// If request not failed.
		if ( ! empty( $response ) ) {
			// Get response body.
			wp_send_json( json_decode( $response, true ) );
		} elseif ( false === $response ) { // In case if request failed.
			wp_send_json_error(
				array(
					'code'    => 'request_failed',
					'message' => __( 'Request failed.', 'wpmudev' ),
				)
			);
		}

		wp_send_json_error(
			array(
				'code'    => 'invalid_request',
				'message' => __( 'Invalid request.', 'wpmudev' ),
			)
		);
	}

	/**
	 * Run current request.
	 *
	 * First we will register all actions and then check if current action
	 * is a valid one. If not we will send a json error and die.
	 *
	 * @since 4.11.3
	 *
	 * @return void
	 */
	private function process_action() {
		// Continue only if valid action.
		if ( isset( $this->actions[ $this->current_action ] ) ) {
			// Log it if turned on.
			$this->maybe_log_request();

			// Execute request action.
			call_user_func(
				$this->actions[ $this->current_action ],
				$this->current_params,
				$this->current_action,
				$this
			);

			// Send success in case the callback didn't respond.
			$this->send_json_success();
		} else {
			// Invalid action.
			wp_send_json_error(
				array(
					'code'    => 'unregistered_action',
					'message' => __( 'This action is not registered. The required plugin is not installed, updated, or configured properly.', 'wpmudev' ),
				)
			);
		}
	}

	/**
	 * Validate the hash for the request.
	 *
	 * @param string $hash Hash from header.
	 * @param string $id   Request ID.
	 * @param string $json Json string.
	 *
	 * @since 4.11.1
	 *
	 * @return bool
	 */
	public function validate_hash( $hash, $id, $json ) {
		// Validation.
		if ( empty( $hash ) || empty( $id ) || empty( $json ) ) {
			return false;
		}

		// Get API key.
		$api_key = WPMUDEV_Dashboard::$api->get_key();

		// Combine ID and json string.
		$hash_string = $id . $json;

		// Generate hash.
		$new_hash = hash_hmac( 'sha256', $hash_string, $api_key );

		// Timing attack safe string comparison, PHP <5.6 compat added in WP 3.9.2.
		return hash_equals( $new_hash, $hash );
	}

	/**
	 * Validate the nonce for the request.
	 *
	 * Check nonce to prevent replay attacks.
	 *
	 * @param string $id   Request ID.
	 * @param string $json Request body json raw.
	 *
	 * @return bool
	 * @since 4.11.1
	 * @since 4.11.29 - added $json for further improve replay attack detection
	 *
	 */
	public function validate_nonce( $id, $json ) {
		// Validation.
		if ( empty( $id ) ) {
			return false;
		}

		if ( ! is_string( $json ) ) {
			$json = '';
		}

		// Get nonce from ID.
		list( $id, $timestamp ) = explode( '-', $id );

		// include the data in the prevention checks, it means identical data / json can not be replayed, but different data / json can be replayed -- which is the whole point of the nonce.
		$hashed_json = hash_hmac( 'sha256', $json, WPMUDEV_Dashboard::$api->get_key() );

		// Get saved nonce.
		$nonce = (float) WPMUDEV_Dashboard::$settings->get( sprintf( 'hub_nonce_%s', $hashed_json ), 'general', 0 );

		if ( floatval( $timestamp ) > $nonce ) {
			// If valid nonce, save it.
			WPMUDEV_Dashboard::$settings->set( sprintf( 'hub_nonce_%s', $hashed_json ), floatval( $timestamp ), 'general' );

			return true;
		}

		return false;
	}

	/**
	 * Check signature hash of the request.
	 *
	 * @param string $req_id         The request id as passed by Hub.
	 * @param string $json           The full json body that hash was created on.
	 * @param bool   $die_on_failure If set to false the function returns a bool.
	 *
	 * @since  4.0.0
	 * @access protected
	 *
	 * @return bool True on success.
	 */
	protected function validate_request_hash( $req_id, $json, $die_on_failure = true ) {
		if ( defined( 'WPMUDEV_IS_REMOTE' ) && ! WPMUDEV_IS_REMOTE ) {
			if ( $die_on_failure ) {
				wp_send_json_error(
					array(
						'code'    => 'remote_disabled',
						'message' => __( 'Remote calls are disabled in wp-config.php', 'wpmudev' ),
					)
				);
			} else {
				return false;
			}
		}

		if ( empty( $_SERVER['HTTP_WDP_AUTH'] ) ) {
			if ( $die_on_failure ) {
				wp_send_json_error(
					array(
						'code'    => 'missing_auth_header',
						'message' => __( 'Missing authentication header', 'wpmudev' ),
					)
				);
			} else {
				return false;
			}
		}

		// phpcs:ignore
		$hash = $_SERVER['HTTP_WDP_AUTH'];

		// Validate auth hash.
		$is_valid = $this->validate_hash( $hash, $req_id, $json );

		if ( ! $is_valid && $die_on_failure ) {
			wp_send_json_error(
				array(
					'code'    => 'incorrect_auth',
					'message' => __( 'Incorrect authentication', 'wpmudev' ),
				)
			);
		}

		// Check nonce to prevent replay attacks.
		if ( ! $this->validate_nonce( $req_id, $json ) ) {
			if ( $die_on_failure ) {
				wp_send_json_error(
					array(
						'code'    => 'nonce_failed',
						'message' => __( 'Nonce check failed', 'wpmudev' ),
					)
				);
			} else {
				return false;
			}
		}

		if ( ! defined( 'WPMUDEV_IS_REMOTE' ) ) {
			define( 'WPMUDEV_IS_REMOTE', $is_valid );
		}

		return $is_valid;
	}

	/**
	 * Registers a Hub api action and callback for it.
	 *
	 * @param string   $action   Action name.
	 * @param callable $callback The name of the function you wish to be called.
	 *
	 * @return void
	 */
	public function register_action( $action, $callback ) {
		$this->actions[ $action ] = $callback;
	}

	/**
	 * Get a list of registered Hub actions that can be called
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @return void
	 */
	public function action_registered( $params, $action ) {
		$actions = $this->actions;

		// Make class names human-readable.
		foreach ( $actions as $action => $callback ) {
			if ( is_array( $callback ) ) {
				$actions[ $action ] = array( get_class( $callback[0] ), $callback[1] );
			} elseif ( is_object( $callback ) ) {
				$actions[ $action ] = 'Closure';
			} else {
				$actions[ $action ] = trim( $callback ); // Cleans up lambda function names.
			}
		}

		$this->send_json_success( $actions );
	}

	/**
	 * Force a ping of the latest site status (plugins, themes, etc.)
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @return void
	 */
	public function action_sync( $params, $action ) {
		// Force flag.
		$force = ! empty( $params->force );
		// Simply refresh the membership details.
		WPMUDEV_Dashboard::$api->hub_sync( false, $force );

		$this->send_json_success();
	}

	/**
	 * Get the latest site status (plugins, themes, etc)
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @return void
	 */
	public function action_status( $params, $action ) {
		// Get status data.
		$data = WPMUDEV_Dashboard::$api->build_api_data( false );

		$this->send_json_success( $data );
	}

	/**
	 * Logout of this site, removing it from the Hub
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @return void
	 */
	public function action_logout( $params, $action ) {
		// Logout from dash.
		WPMUDEV_Dashboard::$site->logout( false );

		$this->send_json_success();
	}

	/**
	 * Activates a list of plugins and themes by pid or slug. Handles multiple, but should normally
	 * be called with only one package at a time.
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @return void
	 */
	public function action_activate( $params, $action ) {
		// Skip sync, hub remote calls are recorded locally.
		define( 'WPMUDEV_REMOTE_SKIP_SYNC', true );

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$errors    = array();
		$activated = array();

		// Process plugins.
		if ( isset( $params->plugins ) && is_array( $params->plugins ) ) {
			foreach ( $params->plugins as $plugin ) {
				if ( is_numeric( $plugin ) ) {
					// WPMUDEV plugin.
					$local    = WPMUDEV_Dashboard::$site->get_cached_projects( $plugin );
					$filename = $local['filename'];
				} else {
					$filename = $plugin;
				}

				// This checks if it's valid already.
				$result = activate_plugin( $filename, '', is_multisite() );
				if ( is_wp_error( $result ) ) {
					$errors[] = array(
						'file'    => $plugin,
						'code'    => $result->get_error_code(),
						'message' => $result->get_error_message(),
					);
				} else {
					WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();
					$activated[] = array( 'file' => $plugin );
				}
			}
		}

		// Process themes.
		if ( isset( $params->themes ) && is_array( $params->themes ) ) {
			foreach ( $params->themes as $theme ) {
				if ( is_numeric( $theme ) ) {
					// WPMUDEV themes.
					$local = WPMUDEV_Dashboard::$site->get_cached_projects( $theme );
					$slug  = $local['slug'];
				} else {
					$slug = $theme;
				}

				// wp_get_theme does not return an error for empty slugs.
				if ( empty( $slug ) ) {
					$slug = "wpmudev_theme_$theme";
				}

				// Check that this is a valid theme.
				$check_theme = wp_get_theme( $slug );
				if ( ! $check_theme->exists() ) {
					$errors[] = array(
						'file'    => $theme,
						'code'    => $check_theme->errors()->get_error_code(),
						'message' => $check_theme->errors()->get_error_message(),
					);
					continue;
				}

				if ( is_multisite() ) {
					// Allow theme network wide.
					$allowed_themes          = get_site_option( 'allowedthemes' );
					$allowed_themes[ $slug ] = true;
					update_site_option( 'allowedthemes', $allowed_themes );
				} else {
					switch_theme( $slug );
				}
				WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();
				$activated[] = array( 'file' => $theme );
			}
		}

		if ( count( $activated ) ) {
			$this->send_json_success( compact( 'activated', 'errors' ) );
		} else {
			$this->send_json_error( compact( 'activated', 'errors' ) );
		}
	}

	/**
	 * Deactivates a list of plugins and themes by pid or slug.
	 *
	 * Handles multiple, but should normally be called with only one package at a time.
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @return void
	 */
	public function action_deactivate( $params, $action ) {
		// Skip sync, hub remote calls are recorded locally.
		define( 'WPMUDEV_REMOTE_SKIP_SYNC', true );

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$errors      = array();
		$deactivated = array();

		// Process plugins.
		if ( isset( $params->plugins ) && is_array( $params->plugins ) ) {
			foreach ( $params->plugins as $plugin ) {
				if ( is_numeric( $plugin ) ) {
					// WPMUDEV plugin.
					$local    = WPMUDEV_Dashboard::$site->get_cached_projects( $plugin );
					$filename = $local['filename'];
				} else {
					$filename = $plugin;
				}

				// Check that it's a valid plugin.
				$valid = validate_plugin( $filename );
				if ( is_wp_error( $valid ) ) {
					$errors[] = array(
						'file'    => $plugin,
						'code'    => $valid->get_error_code(),
						'message' => $valid->get_error_message(),
					);
					continue;
				}

				deactivate_plugins( $filename, false, is_multisite() );
				// There is no return, so we always call it a success.
				WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();
				$deactivated[] = array( 'file' => $plugin );
			}
		}

		// Process themes.
		if ( isset( $params->themes ) && is_array( $params->themes ) ) {
			foreach ( $params->themes as $theme ) {
				if ( is_numeric( $theme ) ) {
					// WPMUDEV theme.
					$local = WPMUDEV_Dashboard::$site->get_cached_projects( $theme );
					$slug  = $local['slug'];
				} else {
					$slug = $theme;
				}

				// wp_get_theme does not return an error for empty slugs.
				if ( empty( $slug ) ) {
					$slug = "wpmudev_theme_$theme";
				}

				// Check that this is a valid theme.
				$check_theme = wp_get_theme( $slug );
				if ( ! $check_theme->exists() ) {
					$errors[] = array(
						'file'    => $theme,
						'code'    => $check_theme->errors()->get_error_code(),
						'message' => $check_theme->errors()->get_error_message(),
					);
					continue;
				}

				if ( is_multisite() ) {
					// Disallow theme network wide.
					$allowed_themes = get_site_option( 'allowedthemes' );
					unset( $allowed_themes[ $slug ] );
					update_site_option( 'allowedthemes', $allowed_themes );

					WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();
					$deactivated[] = array( 'file' => $theme );
				}
			}
		}

		if ( count( $deactivated ) ) {
			$this->send_json_success( compact( 'deactivated', 'errors' ) );
		} else {
			$this->send_json_error( compact( 'deactivated', 'errors' ) );
		}
	}

	/**
	 * Installs a list of plugins and themes by pid or slug.
	 *
	 * Handles multiple, but should normally be called with
	 * only one package at a time.
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @since 1.0.0
	 * @since 4.11.2 Added install by link option.
	 *
	 * @return void
	 */
	public function action_install( $params, $action ) {
		$errors       = array();
		$installed    = array();
		$only_wpmudev = true;

		// Set options.
		$options = array(
			// Activation is available only for plugins.
			'activate'  => ! empty( $params->is_activate ),
			// Overwrite if folder already exists.
			'overwrite' => ! isset( $params->overwrite ) || (bool) $params->overwrite,
		);

		// Process plugins.
		if ( isset( $params->plugins ) && is_array( $params->plugins ) ) {
			foreach ( $params->plugins as $plugin ) {
				// WPMUDEV plugin.
				if ( ! is_numeric( $plugin ) ) {
					$only_wpmudev = false;
				}
				// Install now.
				$success = WPMUDEV_Dashboard::$upgrader->install( $plugin, 'plugin', $options );
				// If successfully installed.
				if ( $success ) {
					$installed[] = array(
						'file' => $plugin,
						'log'  => WPMUDEV_Dashboard::$upgrader->get_log(),
					);
				} else {
					// Get error data.
					$error = WPMUDEV_Dashboard::$upgrader->get_error();
					// Set error response.
					$errors[] = array(
						'file'    => $plugin,
						'code'    => $error['code'],
						'message' => $error['message'],
						'log'     => WPMUDEV_Dashboard::$upgrader->get_log(),
					);
				}
			}
		}

		// Process themes.
		if ( isset( $params->themes ) && is_array( $params->themes ) ) {
			foreach ( $params->themes as $theme ) {
				// WPMUDEV theme @deprecated.
				if ( ! is_numeric( $theme ) ) {
					$only_wpmudev = false;
				}
				// Install now.
				$success = WPMUDEV_Dashboard::$upgrader->install( $theme, 'theme', $options );
				// Prepare success response.
				if ( $success ) {
					$installed[] = array(
						'file' => $theme,
						'log'  => WPMUDEV_Dashboard::$upgrader->get_log(),
					);
				} else {
					// Get the error data.
					$error = WPMUDEV_Dashboard::$upgrader->get_error();
					// Prepare error response.
					$errors[] = array(
						'file'    => $theme,
						'code'    => $error['code'],
						'message' => $error['message'],
						'log'     => WPMUDEV_Dashboard::$upgrader->get_log(),
					);
				}
			}
		}

		// If there is a non-dev product we need to sync still as those can't be recorded locally.
		if ( $only_wpmudev ) {
			// Skip sync, hub remote calls are recorded locally.
			define( 'WPMUDEV_REMOTE_SKIP_SYNC', true );
		}

		if ( count( $installed ) ) {
			// If at least one project installed.
			$this->send_json_success( compact( 'installed', 'errors' ) );
		} else {
			// Errors only :(.
			$this->send_json_error( compact( 'installed', 'errors' ) );
		}
	}

	/**
	 * Upgrades a list of plugins and themes by pid or slug.
	 *
	 * Handles multiple, but should normally be called with only one package at a time.
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @return void
	 */
	public function action_upgrade( $params, $action ) {
		// Skip sync, hub remote calls are recorded locally.
		define( 'WPMUDEV_REMOTE_SKIP_SYNC', true );

		$errors   = array();
		$upgraded = array();

		// Process plugins.
		if ( isset( $params->plugins ) && is_array( $params->plugins ) ) {
			foreach ( $params->plugins as $plugin ) {
				$pid     = is_numeric( $plugin ) ? $plugin : "plugin:{$plugin}";
				$success = WPMUDEV_Dashboard::$upgrader->upgrade( $pid );
				if ( $success ) {
					$upgraded[] = array(
						'file'        => $plugin,
						'log'         => WPMUDEV_Dashboard::$upgrader->get_log(),
						'new_version' => WPMUDEV_Dashboard::$upgrader->get_version(),
					);
				} else {
					$error    = WPMUDEV_Dashboard::$upgrader->get_error();
					$errors[] = array(
						'file'    => $plugin,
						'code'    => $error['code'],
						'message' => $error['message'],
						'log'     => WPMUDEV_Dashboard::$upgrader->get_log(),
					);
				}
			}
		}

		// Process themes.
		if ( isset( $params->themes ) && is_array( $params->themes ) ) {
			foreach ( $params->themes as $theme ) {
				$pid     = is_numeric( $theme ) ? $theme : "theme:{$theme}";
				$success = WPMUDEV_Dashboard::$upgrader->upgrade( $pid );
				if ( $success ) {
					$upgraded[] = array(
						'file'        => $theme,
						'log'         => WPMUDEV_Dashboard::$upgrader->get_log(),
						'new_version' => WPMUDEV_Dashboard::$upgrader->get_version(),
					);
				} else {
					$error    = WPMUDEV_Dashboard::$upgrader->get_error();
					$errors[] = array(
						'file'    => $theme,
						'code'    => $error['code'],
						'message' => $error['message'],
						'log'     => WPMUDEV_Dashboard::$upgrader->get_log(),
					);
				}
			}
		}

		if ( count( $upgraded ) ) {
			$this->send_json_success( compact( 'upgraded', 'errors' ) );
		} else {
			$this->send_json_error( compact( 'upgraded', 'errors' ) );
		}
	}

	/**
	 * Deletes a list of plugins and themes by pid or slug. Handles multiple, but should normally
	 * be called with only one package at a time. Logic copied from ajax-actions.php
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @return void
	 */
	public function action_delete( $params, $action ) {
		// Skip sync, hub remote calls are recorded locally.
		define( 'WPMUDEV_REMOTE_SKIP_SYNC', true );

		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		include_once ABSPATH . 'wp-admin/includes/theme.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';

		$errors  = array();
		$deleted = array();

		// Process plugins.
		if ( isset( $params->plugins ) && is_array( $params->plugins ) ) {
			// Should skip uninstall.
			$skip_uninstall = isset( $params->skip_uninstall_hook ) && (bool) $params->skip_uninstall_hook;

			foreach ( $params->plugins as $plugin ) {

				// Delete plugin.
				$result = WPMUDEV_Dashboard::$upgrader->delete_plugin( $plugin, $skip_uninstall );

				if ( true === $result ) {
					// Also refresh local data because reinstallation is not possible until the cache is refreshed.
					WPMUDEV_Dashboard::$site->refresh_local_projects( 'local' );
					$deleted[] = array( 'file' => $plugin );
				} else {
					$error = WPMUDEV_Dashboard::$upgrader->get_error();
					if ( isset( $error['code'], $error['message'] ) ) {
						$errors[] = array(
							'file'    => $plugin,
							'code'    => $error['code'],
							'message' => $error['message'],
						);
					} else {
						$errors[] = array(
							'file'    => $plugin,
							'code'    => 'unknown_error',
							'message' => __( 'Plugin could not be deleted.', 'wpmudev' ),
						);
					}
				}
			}
		}

		// Process themes.
		if ( isset( $params->themes ) && is_array( $params->themes ) ) {
			foreach ( $params->themes as $theme ) {
				if ( is_numeric( $theme ) ) {
					$local = WPMUDEV_Dashboard::$site->get_cached_projects( $theme );
					$slug  = $local['slug'];
				} else {
					$slug = $theme;
				}

				// wp_get_theme does not return an error for empty slugs.
				if ( empty( $slug ) ) {
					$slug = "wpmudev_theme_$theme";
				}

				// Check that this is a valid theme.
				$check_theme = wp_get_theme( $slug );
				if ( ! $check_theme->exists() ) {
					$errors[] = array(
						'file'    => $theme,
						'code'    => $check_theme->errors()->get_error_code(),
						'message' => $check_theme->errors()->get_error_message(),
					);
					continue;
				}

				// Check filesystem credentials. `delete_theme()` will bail otherwise.
				$url = wp_nonce_url( 'themes.php?action=delete&stylesheet=' . urlencode( $slug ), 'delete-theme_' . $slug );
				ob_start();
				$credentials = request_filesystem_credentials( $url );
				ob_end_clean();
				if ( false === $credentials || ! WP_Filesystem( $credentials ) ) {
					global $wp_filesystem;

					$error_code = 'fs_unavailable';
					$error      = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'wpmudev' );

					// Pass through the error from WP_Filesystem if one was raised.
					if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
						$error_code = $wp_filesystem->errors->get_error_code();
						$error      = esc_html( $wp_filesystem->errors->get_error_message() );
					}

					$errors[] = array(
						'file'    => $theme,
						'code'    => $error_code,
						'message' => $error,
					);
					continue;
				}

				$result = delete_theme( $slug );

				if ( is_wp_error( $result ) ) {
					$errors[] = array(
						'file'    => $theme,
						'code'    => $result->get_error_code(),
						'message' => $result->get_error_message(),
					);
					continue;
				} elseif ( false === $result ) {
					$errors[] = array(
						'file'    => $theme,
						'code'    => 'unknown_error',
						'message' => __( 'Theme could not be deleted.', 'wpmudev' ),
					);
					continue;
				}

				WPMUDEV_Dashboard::$site->schedule_shutdown_refresh();
				$deleted[] = array( 'file' => $theme );
			}
		}

		if ( count( $deleted ) ) {
			$this->send_json_success( compact( 'deleted', 'errors' ) );
		} else {
			$this->send_json_error( compact( 'deleted', 'errors' ) );
		}
	}

	/**
	 * Replace free version of a plugin with Pro version.
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @since 4.11.9
	 *
	 * @return void
	 */
	public function action_replace_free( $params, $action ) {
		$success = false;
		// Get project id.
		$project = isset( $params->project ) ? $params->project : '';

		if ( ! empty( $project ) ) {
			// Replace free version with Pro.
			$success = WPMUDEV_Dashboard::$site->maybe_replace_free_with_pro( $project, false );
		}

		if ( $success ) {
			$this->send_json_success( array( 'project' => $project ) );
		} else {
			$this->send_json_error(
				array(
					'project' => $project,
					'code'    => 'action_failed',
					'message' => __( 'Could not replace the plugin with Pro version.', 'wpmudev' ),
				)
			);
		}
	}

	/**
	 * Replace pro version of a plugin with free version.
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @since 4.11.9
	 *
	 * @return void
	 */
	public function action_replace_pro( $params, $action ) {
		$success = false;
		// Get project id.
		$project = isset( $params->project ) ? $params->project : '';

		if ( ! empty( $plugin ) ) {
			// Replace Pro version with free.
			$success = WPMUDEV_Dashboard::$site->maybe_replace_pro_with_free( $project, false );
		}

		if ( $success ) {
			$this->send_json_success( array( 'project' => $project ) );
		} else {
			$this->send_json_error(
				array(
					'project' => $project,
					'code'    => 'action_failed',
					'message' => __( 'Could not replace the plugin with free version.', 'wpmudev' ),
				)
			);
		}
	}

	/**
	 * Upgrades to the latest WP core version, major or minor.
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @since 4.4
	 *
	 * @return void
	 */
	public function action_core_upgrade( $params, $action ) {
		// Upgrade core WP.
		$success = WPMUDEV_Dashboard::$upgrader->upgrade_core();
		if ( $success ) {
			$this->send_json_success(
				array(
					'log'         => WPMUDEV_Dashboard::$upgrader->get_log(),
					'new_version' => WPMUDEV_Dashboard::$upgrader->get_version(),
				)
			);
		} else {
			$error = WPMUDEV_Dashboard::$upgrader->get_error();
			$this->send_json_error(
				array(
					'code'    => $error['code'],
					'message' => $error['message'],
					'data'    => array( 'log' => WPMUDEV_Dashboard::$upgrader->get_log() ),
				)
			);
		}
	}

	/**
	 * Enable/Disable WPMUDEV Analytics.
	 *
	 * @param object $params Parameters passed in json body.
	 * @param string $action The action name that was called.
	 *
	 * @since 4.6.1
	 *
	 * @return void
	 */
	public function action_analytics( $params, $action ) {
		if ( ! isset( $params->status ) ) {
			$this->send_json_error(
				array(
					'code'    => 'invalid_params',
					'message' => __( 'The "status" param is missing.', 'wpmudev' ),
				)
			);
		}

		switch ( $params->status ) {
			case 'enabled':
				$result = WPMUDEV_Dashboard::$api->analytics_enable();
				break;
			case 'disabled':
				$result = WPMUDEV_Dashboard::$api->analytics_disable();
				break;
			default:
				// Send error.
				$this->send_json_error(
					array(
						'code'    => 'invalid_params',
						'message' => __( 'Passed invalid value for param "status", it must be either "enabled" or "disabled"', 'wpmudev' ),
					)
				);
		}

		if ( isset( $result ) && is_wp_error( $result ) ) {
			$this->send_json_error(
				array(
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				)
			);
		}

		// set analytics status.
		WPMUDEV_Dashboard::$settings->set( 'enabled', ( 'enabled' === $params->status ), 'analytics' );

		// Send success.
		$this->send_json_success();
	}

	/**
	 * Enable or disable SSO.
	 *
	 * If the SSO is enabled for the first time,
	 * set the user id and sync to hub.
	 *
	 * @param object $params List of args.
	 * @param string $action Name of action.
	 *
	 * @since 4.11
	 *
	 * @return void
	 */
	public function action_sso( $params, $action ) {
		if ( ! isset( $params->status ) ) {
			$this->send_json_error(
				array(
					'code'    => 'invalid_params',
					'message' => __( 'The "status" param is missing.', 'wpmudev' ),
				)
			);
		}

		// Status can only be enabled or disabled.
		if ( ! in_array( $params->status, array( 'enabled', 'disabled' ), true ) ) {
			$this->send_json_error(
				array(
					'code'    => 'invalid_params',
					'message' => __( 'Passed invalid value for param "status", it must be either "enabled" or "disabled"', 'wpmudev' ),
				)
			);
		}

		// Enabled status from request.
		$enable_sso = 'enabled' === $params->status;
		// Current SSO status.
		$previous_sso = WPMUDEV_Dashboard::$settings->get( 'enabled', 'sso' );

		// Register the user to be logged in for SSO, only if the SSO was just enabled.
		if ( $enable_sso && ! $previous_sso ) {
			// Get an admin user ID for SSO.
			$user_id = WPMUDEV_Dashboard::$utils->get_admin_user_for_sso();

			WPMUDEV_Dashboard::$settings->set( 'userid', $user_id, 'sso' );
		}

		// Set SSO status.
		WPMUDEV_Dashboard::$settings->set( 'enabled', $enable_sso, 'sso' );

		// If the status of SSO is changed, sync to hub.
		if ( $enable_sso !== $previous_sso ) {
			// Also, force a hub-sync, since the SSO setting changed.
			WPMUDEV_Dashboard::$api->hub_sync( false, true );
		}

		// Send success.
		$this->send_json_success();
	}

	/**
	 * Register actions that are used by the Dashboard plugin.
	 *
	 * These are the internal actions which act as API endpoints
	 * between Dash plugin and Hub for communication.
	 *
	 * @return void
	 */
	protected function register_internal_actions() {
		$actions = array(
			'registered_actions' => 'action_registered',
			'sync'               => 'action_sync',
			'status'             => 'action_status',
			'logout'             => 'action_logout',
			'activate'           => 'action_activate',
			'deactivate'         => 'action_deactivate',
			'install'            => 'action_install',
			'upgrade'            => 'action_upgrade',
			'delete'             => 'action_delete',
			'core_upgrade'       => 'action_core_upgrade',
			'analytics'          => 'action_analytics',
			'sso'                => 'action_sso',
			'replace_pro'        => 'action_replace_pro',
			'replace_free'       => 'action_replace_free',
		);

		foreach ( $actions as $action => $callback ) {
			// Register action.
			$this->register_action( $action, array( $this, $callback ) );
		}
	}

	/**
	 * Registers custom Hub actions from other DEV plugins
	 *
	 * Other plugins should use the wdp_register_hub_action
	 * filter to add an item to the associative array as
	 * 'action_name' => 'callback'
	 *
	 * @return void
	 */
	protected function register_plugin_actions() {
		/**
		 * Registers a Hub api action and callback for it
		 *
		 * @param string   $action   Action name.
		 * @param callable $callback The name of the function you wish to be called.
		 */
		$actions = apply_filters( 'wdp_register_hub_action', array() );

		foreach ( $actions as $action => $callback ) {
			// Check action is not already registered and valid.
			if ( ! isset( $this->actions[ $action ] ) && is_callable( $callback ) ) {
				$this->register_action( $action, $callback );
			}
		}
	}

	/**
	 * Return success results for API to the hub
	 *
	 * @param mixed $data        Data to encode as JSON, then print and die.
	 * @param int   $status_code The HTTP status code to output, defaults to 200.
	 *
	 * @return void
	 */
	protected function send_json_success( $data = null, $status_code = null ) {
		// Log it if turned on.
		if ( $this->is_hub_request() && defined( 'WPMUDEV_API_DEBUG' ) && WPMUDEV_API_DEBUG ) {
			$req_time   = round( ( microtime( true ) - $this->timer ), 4 ) . 's';
			$req_status = is_null( $status_code ) ? 200 : $status_code;
			$log        = '[Hub API call response] %s %s %s %s';
			$log        .= "\n   Response: (success) %s\n";
			$msg        = sprintf(
				$log,
				$_GET['wpmudev-hub'], // phpcs:ignore
				$this->current_action,
				$req_status,
				$req_time,
				wp_json_encode( $data, JSON_PRETTY_PRINT )
			);
			error_log( $msg ); // phpcs:ignore
		}

		wp_send_json_success( $data, $status_code );
	}

	/**
	 * Return error results for API to the hub.
	 *
	 * @param mixed $data        Data to encode as JSON, then print and die.
	 * @param int   $status_code The HTTP status code to output, defaults to 200.
	 *
	 * @return void
	 */
	protected function send_json_error( $data = null, $status_code = null ) {
		// Log it if turned on.
		if ( $this->is_hub_request() && defined( 'WPMUDEV_API_DEBUG' ) && WPMUDEV_API_DEBUG ) {
			$req_time   = round( ( microtime( true ) - $this->timer ), 4 ) . 's';
			$req_status = is_null( $status_code ) ? 200 : $status_code;
			$log        = '[Hub API call response] %s %s %s %s';
			$log        .= "\n   Response: (error) %s\n";
			$msg        = sprintf(
				$log,
				$_GET['wpmudev-hub'], // phpcs:ignore
				$this->current_action,
				$req_status,
				$req_time,
				wp_json_encode( $data, JSON_PRETTY_PRINT )
			);
			error_log( $msg ); // phpcs:ignore
		}

		wp_send_json_error( $data, $status_code );
	}

	/**
	 * Check if current request is from Hub.
	 *
	 * Currently, we need this class only for API requests from
	 * Hub. So checking for a 'wpmudev-hub' param is useful to
	 * identify the request.
	 *
	 * @since  4.11.3
	 * @access protected
	 *
	 * @return bool
	 */
	protected function is_hub_request() {
		return ! empty( $_GET['wpmudev-hub'] ); // phpcs:ignore
	}

	/**
	 * Log current request details.
	 *
	 * If log is enabled, log the request time, action name
	 * and parameters to the error log.
	 *
	 * @since  4.11.3
	 * @access protected
	 *
	 * @return void
	 */
	protected function maybe_log_request() {
		if ( $this->is_hub_request() && defined( 'WPMUDEV_API_DEBUG' ) && WPMUDEV_API_DEBUG ) {
			$log = '[Hub API call] %s %s';
			$log .= "\n   Request params: %s\n";

			$msg = sprintf(
				$log,
				$_GET['wpmudev-hub'], // phpcs:ignore
				$this->current_action,
				wp_json_encode( $this->current_params, JSON_PRETTY_PRINT )
			);
			// Add error log.
			error_log( $msg ); // phpcs:ignore
		}
	}

	/**
	 * Check if an action is an admin action.
	 *
	 * Admin actions needs to be run in WP admin environment.
	 * Use `wpmudev_dashboard_remote_admin_actions` filter to
	 * add new actions to the admin actions list.
	 *
	 * @param string $action Action name.
	 *
	 * @since  4.11.3
	 * @access protected
	 *
	 * @return bool
	 */
	protected function is_admin_action( $action ) {
		$admin_actions = array(
			'status',
			'sync',
			'sso',
		);

		/**
		 * Filter to modify admin actions list.
		 *
		 * @param array $admin_actions Actions list.
		 *
		 * @since 4.11.3
		 */
		$admin_actions = apply_filters( 'wpmudev_dashboard_remote_admin_actions', $admin_actions );

		return in_array( $action, $admin_actions, true );
	}
}