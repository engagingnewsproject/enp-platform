<?php
/**
 * Handles folder renaming for Pro plugins.
 *
 * @since   4.11.13
 * @package WPMUDEV_Dashboard
 */

/**
 * Class WPMUDEV_Dashboard_Special_Upgrader
 */
class WPMUDEV_Dashboard_Special_Upgrader {

	/**
	 * Plugins with new folder names.
	 *
	 * @since 4.11.13
	 * @var array[] $plugins
	 */
	private $plugins = array(
		'google-analytics-async' => array(
			'version' => '3.3.15',
			'old'     => 'google-analytics-async/google-analytics-async.php',
			'new'     => 'beehive-analytics/beehive-analytics.php',
		),
	);

	/**
	 * Plugins with activation status for restore purpose.
	 *
	 * @since 4.11.13
	 * @var array[] $plugin_statuses
	 */
	private $plugin_statuses = array(
		'pro'  => array(),
		'free' => array(),
	);

	/**
	 * List of plugin folder names which is renamed by us.
	 *
	 * @since 4.11.13
	 * @var string[] $processed_plugins
	 */
	private $processed_plugins = array();

	/**
	 * Set up actions for the module.
	 *
	 * @since 4.11.13
	 * @internal
	 */
	public function __construct() {
		// Include WDP ID header.
		add_filter( 'extra_plugin_headers', array( $this, 'include_wdp_id_header' ) );

		// Store activation status for Pro.
		add_filter( 'upgrader_pre_install', array( $this, 'store_status_before_upgrade' ), -999, 2 );

		// Handle directory rename after upgrade/install.
		add_filter( 'upgrader_install_package_result', array( $this, 'maybe_rename' ) );

		// Reactivate renamed plugins.
		add_action( 'upgrader_process_complete', array( $this, 'maybe_reactivate' ), 10, 2 );

		// Hide activation link.
		add_filter( 'install_plugin_complete_actions', array( $this, 'hide_activation_button' ), 10, 3 );

		// Filter plugin data.
		add_filter( 'wpmudev_dashboard_upgrader_get_plugin_data', array( $this, 'set_plugin_data' ), 10, 2 );
	}

	/**
	 * Store plugin activation status before deactivating it for upgrade.
	 *
	 * @since 4.11.13
	 *
	 * @param bool|WP_Error $response The installation response before the installation has started.
	 * @param array         $plugin   Plugin package arguments.
	 *
	 * @return bool|WP_Error The original `$response` parameter or WP_Error.
	 */
	public function store_status_before_upgrade( $response, $plugin ) {
		// Need to continue only if not error or plugin is set.
		if ( ! is_wp_error( $response ) && ! empty( $plugin['plugin'] ) ) {
			// Get file and folder.
			$names = $this->get_plugin_structure( $plugin['plugin'] );
			if ( isset( $names['folder'], $this->plugins[ $names['folder'] ] ) ) {
				// Store activation status.
				$this->mark_status( $plugin['plugin'] );
			}
		}

		return $response;
	}

	/**
	 * Rename the upgrading/installing plugin.
	 *
	 * If the plugin is one of our Pro plugins, attempt to rename it to
	 * the new directory structure.
	 *
	 * @since 4.11.13
	 *
	 * @param array|WP_Error $result Installation result.
	 *
	 * @return array
	 */
	public function maybe_rename( $result ) {
		// If the destination is our plugin.
		if ( ! is_wp_error( $result ) && isset( $result['destination_name'], $this->plugins[ $result['destination_name'] ] ) ) {
			// Clear plugins cache to get the new version.
			wp_clean_plugins_cache( false );

			// Plugin version is not compatible.
			if ( ! $this->is_plugin_compatible( $result['destination_name'] ) ) {
				// Remove renamed version if required.
				$this->remove_renamed( $result['destination_name'] );

				return $result;
			}

			$options = $this->plugins[ $result['destination_name'] ];

			// Is free version installed.
			$free_installed = $this->is_free_installed( $options['new'] );
			// Is renamed Pro installed.
			$pro_installed = $this->is_pro_installed( $options['new'] );

			// Backup free versions.
			if ( $free_installed || $pro_installed ) {
				$this->mark_status( $options['new'], $free_installed ? 'free' : 'pro' );
				$this->backup_plugin( $options['new'] );
			}

			// Make sure to deactivate Pro before renaming.
			$this->deactivate_plugin( $options['old'] );

			// Get folder and file names.
			$old_names = $this->get_plugin_structure( $options['old'] );
			$new_names = $this->get_plugin_structure( $options['new'] );

			// Attempt to rename folder.
			if ( ! WPMUDEV_Dashboard::$utils->rename_plugin( $old_names['folder'], $new_names['folder'] ) ) {
				// Revert free version.
				if ( $free_installed || $pro_installed ) {
					$this->restore_plugin( $options['new'] );
					$this->restore_activation( $options['new'], $free_installed ? 'free' : 'pro' );
				}

				// Revert everything.
				$this->restore_activation( $options['old'] );

				return $result;
			}

			// Attempt to rename file if file names are different.
			if ( $old_names['file'] !== $new_names['file'] ) {
				// Failed to rename file.
				if ( ! WPMUDEV_Dashboard::$utils->rename_plugin(
					$new_names['folder'] . DIRECTORY_SEPARATOR . $old_names['file'],
					$new_names['folder'] . DIRECTORY_SEPARATOR . $new_names['file']
				) ) {
					// Revert folder renaming.
					WPMUDEV_Dashboard::$utils->rename_plugin( $new_names['folder'], $old_names['folder'] );
					// Revert free version.
					if ( $free_installed || $pro_installed ) {
						$this->restore_plugin( $options['new'] );
						$this->restore_activation( $options['new'], $free_installed ? 'free' : 'pro' );
					}
					// Revert Pro.
					$this->restore_activation( $options['old'] );

					return $result;
				}
			}

			// Delete free version backup.
			if ( $free_installed || $pro_installed ) {
				$this->delete_backup( $options['new'] );
			}

			// Replace names.
			$result = $this->replace_names( $result );

			// Add to processed list.
			$this->processed_plugins[ $options['old'] ] = $options['new'];
		}

		return $result;
	}

	/**
	 * Reactivate plugins after the upgrade/install process completed.
	 *
	 * @since 4.11.13
	 *
	 * @param WP_Upgrader $upgrade    WP_Upgrader instance.
	 * @param array       $hook_extra Array of bulk item update data.
	 *
	 * @return void
	 */
	public function maybe_reactivate( $upgrade, $hook_extra ) {
		// Should be plugin type.
		if ( isset( $hook_extra['type'] ) && 'plugin' === $hook_extra['type'] && ! empty( $this->processed_plugins ) ) {
			// Clear plugins cache to get the new version.
			wp_clean_plugins_cache( false );

			foreach ( $this->processed_plugins as $old => $new ) {
				// Activate new plugin if required.
				$this->restore_activation( $old, 'pro', $new );
				// Try to use free status if free is deleted for Pro.
				$this->restore_activation( $new, 'free' );
				// In case of renamed Pro was already active.
				$this->restore_activation( $new );
			}
		}
	}

	/**
	 * Hide activation button if WP can't find the new plugin file.
	 *
	 * This is a temporary solution only for uploading plugins via WP.
	 * WP doesn't have a filter to modify the result data set for upgrader skins.
	 * So we will have to hide the activation button to avoid file header error.
	 *
	 * @since 4.11.13
	 *
	 * @param string[] $install_actions Array of plugin action links.
	 * @param object   $api             Object containing WordPress.org API plugin data. Empty
	 *                                  for non-API installs, such as when a plugin is installed
	 *                                  via upload.
	 * @param string   $plugin_file     Path to the plugin file relative to the plugins directory.
	 *
	 * @return array
	 */
	public function hide_activation_button( $install_actions, $api, $plugin_file ) {
		// Hide activation if file is empty.
		if ( empty( $plugin_file ) ) {
			unset( $install_actions['activate_plugin'] );
			unset( $install_actions['network_activate'] );
		}

		return $install_actions;
	}

	/**
	 * Filter plugin data for Dashboard to use new plugin.
	 *
	 * @since 4.11.13
	 *
	 * @param array  $data Plugin data.
	 * @param string $file Plugin file.
	 *
	 * @return array
	 */
	public function set_plugin_data( $data, $file ) {
		// Check if it is found in processed plugins list.
		if ( isset( $this->processed_plugins[ $file ] ) ) {
			// Get plugin data.
			$plugin_data = $this->get_plugin_data( $this->processed_plugins[ $file ] );
			// If found return it.
			if ( ! empty( $plugin_data ) ) {
				return $plugin_data;
			}
		}

		return $data;
	}

	/**
	 * Replace old folder names with new.
	 *
	 * Once we rename a plugin folder, we need to replace it in the final
	 * result for WP to detect the changes.
	 *
	 * @since 4.11.13
	 *
	 * @param array $result Upgrade result.
	 *
	 * @return array
	 */
	private function replace_names( $result ) {
		// Get folder and file names.
		$old_names = $this->get_plugin_structure( $this->plugins[ $result['destination_name'] ]['old'] );
		$new_names = $this->get_plugin_structure( $this->plugins[ $result['destination_name'] ]['new'] );

		// Replace folder names with new.
		foreach ( array( 'destination', 'destination_name', 'remote_destination' ) as $field ) {
			if ( ! empty( $result[ $field ] ) ) {
				$result[ $field ] = str_replace( $old_names['folder'], $new_names['folder'], $result[ $field ] );
			}
		}

		return $result;
	}

	/**
	 * Retrieves the path to the file that contains the plugin info.
	 *
	 * This is an alternative for Plugin_Upgrader::plugin_info() to make sure to use
	 * renamed plugin directory.
	 *
	 * @since 4.11.24
	 *
	 * @param string|bool|WP_Error $result Upgrader result.
	 *
	 * @return string|false The full path to the main plugin file, or false.
	 */
	public function get_plugin_info_path( $result ) {
		// Should be an array.
		if ( ! is_array( $result ) ) {
			return false;
		}

		// Make sure destination name exists.
		if ( empty( $result['destination_name'] ) ) {
			return false;
		}

		// Ensure to pass with leading slash.
		$plugin = get_plugins( '/' . $result['destination_name'] );
		if ( empty( $plugin ) ) {
			return false;
		}

		// Assume the requested plugin is the first in the list.
		$plugin_files = array_keys( $plugin );

		return $result['destination_name'] . '/' . $plugin_files[0];
	}

	/**
	 * Remove renamed version if they are downgrading to old version.
	 *
	 * Do this only if required.
	 *
	 * @since 4.11.13
	 *
	 * @param string $folder Installing plugin folder.
	 *
	 * @return void
	 */
	private function remove_renamed( $folder ) {
		if ( isset( $this->plugins[ $folder ]['new'] ) ) {
			// Only if Pro is installed.
			if ( $this->is_pro_installed( $this->plugins[ $folder ]['new'] ) ) {
				// Get folder name.
				$names = $this->get_plugin_structure( $this->plugins[ $folder ]['new'] );
				// Deactivate first.
				$this->deactivate_plugin( $this->plugins[ $folder ]['new'] );
				// Delete plugin (Do not uninstall).
				$this->delete_folder( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $names['folder'] );
			}
		}
	}

	/**
	 * Make sure we get WDP ID in plugins data.
	 *
	 * NOTE: We NEED TO keep using WDP ID as the
	 * key, because that's how the WP filter works.
	 *
	 * @since 4.11.13
	 *
	 * @param array $headers Existing headers.
	 *
	 * @return array
	 */
	public function include_wdp_id_header( $headers ) {
		// Include WDP ID.
		$headers[] = 'WDP ID';

		return $headers;
	}

	/**
	 * Check if a plugin free version is installed.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin Plugin file.
	 *
	 * @return bool
	 */
	private function is_free_installed( $plugin ) {
		$data = $this->get_plugin_data( $plugin );

		return ! empty( $data ) && empty( $data['WDP ID'] );
	}

	/**
	 * Check if a plugin Pro version is installed.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin Plugin file.
	 *
	 * @return bool
	 */
	private function is_pro_installed( $plugin ) {
		$data = $this->get_plugin_data( $plugin );

		return ! empty( $data['WDP ID'] );
	}

	/**
	 * Get a plugin's current data.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin Plugin file.
	 *
	 * @return array
	 */
	private function get_plugin_data( $plugin ) {
		$plugins = get_plugins();

		return empty( $plugins[ $plugin ] ) ? array() : $plugins[ $plugin ];
	}

	/**
	 * Backup a plugin by renaming it.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin Plugin file.
	 *
	 * @return void
	 */
	private function backup_plugin( $plugin ) {
		// Split name and get folder info.
		$names = $this->get_plugin_structure( $plugin );

		if ( empty( $names['folder'] ) ) {
			return;
		}

		// Make sure to deactivate.
		$this->deactivate_plugin( $plugin );

		// Rename folder.
		WPMUDEV_Dashboard::$utils->rename_plugin( $names['folder'], $names['folder'] . '-old' );
	}

	/**
	 * Backup a plugin by renaming it.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin Plugin file.
	 *
	 * @return void
	 */
	private function restore_plugin( $plugin ) {
		// Split name and get folder info.
		$names = $this->get_plugin_structure( $plugin );

		if ( empty( $names['folder'] ) ) {
			return;
		}

		// Revert folder renaming.
		WPMUDEV_Dashboard::$utils->rename_plugin( $names['folder'] . '-old', $names['folder'] );
	}

	/**
	 * Delete a backed up plugin.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin Plugin file.
	 *
	 * @return void
	 */
	private function delete_backup( $plugin ) {
		// Split name and get folder info.
		$names = $this->get_plugin_structure( $plugin );

		if ( empty( $names['folder'] ) ) {
			return;
		}

		// Delete plugin.
		$this->delete_folder( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $names['folder'] . '-old' );
	}

	/**
	 * Mark a plugin's activation status for restoration purpose.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin Plugin file.
	 * @param string $type   pro or free.
	 *
	 * @return void
	 */
	private function mark_status( $plugin, $type = 'pro' ) {
		$this->plugin_statuses[ $type ][ $plugin ] = array(
			'active'         => is_plugin_active( $plugin ),
			'network_active' => is_plugin_active_for_network( $plugin ),
		);
	}

	/**
	 * Deactivate plugin on both single and network.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin Plugin file.
	 *
	 * @return void
	 */
	private function deactivate_plugin( $plugin ) {
		deactivate_plugins( $plugin, true );
		deactivate_plugins( $plugin, true, true );
	}

	/**
	 * Reactivate plugin if it was active before.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin      Plugin file.
	 * @param string $type        pro or free.
	 * @param string $to_activate Plugin to activate.
	 *
	 * @return void
	 */
	private function restore_activation( $plugin, $type = 'pro', $to_activate = null ) {
		// No need to continue if not found.
		if ( ! isset( $this->plugin_statuses[ $type ][ $plugin ] ) ) {
			return;
		}

		// Use different plugin for activation.
		$to_activate = null === $to_activate ? $plugin : $to_activate;

		// Reactivate on single site.
		if ( $this->plugin_statuses[ $type ][ $plugin ]['active'] ) {
			activate_plugin( $to_activate, false, false, true );
		}

		// Reactivate network-wide.
		if ( $this->plugin_statuses[ $type ][ $plugin ]['network_active'] ) {
			activate_plugin( $to_activate, false, true, true );
		}
	}

	/**
	 * Check if a plugin version is ready for renaming.
	 *
	 * @since 4.11.13
	 *
	 * @param string $folder Plugin folder.
	 *
	 * @return bool
	 */
	private function is_plugin_compatible( $folder ) {
		// If found in our temporary list.
		if ( isset( $this->plugins[ $folder ]['version'] ) ) {
			$plugins = get_plugins();

			// We need version.
			if ( ! isset( $plugins[ $this->plugins[ $folder ]['old'] ]['Version'] ) ) {
				return false;
			}

			// If compatible version.
			return version_compare(
				$plugins[ $this->plugins[ $folder ]['old'] ]['Version'],
				$this->plugins[ $folder ]['version'],
				'>='
			);
		}

		return false;
	}

	/**
	 * Get the plugin folder and file names.
	 *
	 * @since 4.11.13
	 *
	 * @param string $plugin Plugin basename.
	 *
	 * @return array
	 */
	private function get_plugin_structure( $plugin ) {
		// Explode the basename.
		$parts = explode( '/', $plugin );

		return array(
			'folder' => $parts[0],
			'file'   => $parts[1],
		);
	}

	/**
	 * Delete a folder and it's contents.
	 *
	 * @since 4.11.13
	 *
	 * @param string $path Folder path.
	 *
	 * @return void
	 */
	private function delete_folder( $path ) {
		$files = glob( $path . '/*' );
		foreach ( $files as $file ) {
			is_dir( $file ) ? $this->delete_folder( $file ) : unlink( $file );
		}
		rmdir( $path );
	}
}