<?php
/**
 * Class that handles settings functionality.
 *
 * @link    https://wpmudev.com
 * @since   4.11.10
 * @author  Joel James <joel@incsub.com>
 * @package WPMUDEV_Dashboard_Settings
 */

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class WPMUDEV_Dashboard_Settings
 */
class WPMUDEV_Dashboard_Settings {

	/**
	 * Returns the value of a plugin option.
	 *
	 * This can handle both grouped item and single options.
	 * If group argument is omitted it will get the non-grouped option.
	 *
	 * @param string $name    The option name.
	 * @param string $group   The group name (Optional for non grouped option).
	 * @param mixed  $default Optional. Set value to return if option not found.
	 *
	 * @since 4.11.10
	 *
	 * @return mixed The option value.
	 */
	public function get( $name, $group = false, $default = false ) {
		// Handle grouped options.
		if ( ! empty( $group ) ) {
			// Get option value.
			$value = $this->get_option( $group, array() );

			// Return value.
			return isset( $value[ $name ] ) ? $value[ $name ] : $default;
		}

		return $this->get_option( $name, $default );
	}

	/**
	 * Returns the value of a plugin option.
	 *
	 * The plugins option-prefix is automatically added to the option name.
	 * Use this function instead of direct access via get_site_option().
	 *
	 * @param string $name    The name of the option.
	 * @param mixed  $default Default value.
	 *
	 * @since 4.11.10
	 *
	 * @return mixed The option value.
	 */
	public function get_option( $name, $default = false ) {
		return get_site_option( 'wdp_un_' . $name, $default );
	}

	/**
	 * Updates the value of a single plugin option.
	 *
	 * Can update a single item of a group or event an option without
	 * any group.
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The new option value.
	 * @param string $group The group name (Optional for non grouped option).
	 *
	 * @since 4.11.10
	 *
	 * @return bool
	 */
	public function set( $name, $value, $group = false ) {
		// Handle grouped options.
		if ( ! empty( $group ) ) {
			// Get option value.
			$group_value = $this->get_option( $group, array() );
			// Merge values.
			$values = wp_parse_args(
				array( $name => $value ),
				$group_value
			);
		} else {
			$group  = $name;
			$values = $value;
		}

		$success = $this->set_option( $group, $values );

		if ( $success ) {
			/**
			 * Action hook to run after Dashboard option is updated.
			 *
			 * @since 4.11.22
			 *
			 * @param string $name  Name of the option.
			 * @param mixed  $value Value to update.
			 * @param string $group Group name.
			 */
			do_action( 'wpmudev_dashboard_settings_after_set', $name, $value, $group );
		}

		return $success;
	}

	/**
	 * Updates the value of a plugin option.
	 * The plugins option-prefix is automatically added to the option name.
	 *
	 * Use this function instead of direct access via update_site_option()
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The new option value.
	 *
	 * @since 4.11.10
	 *
	 * @return bool
	 */
	public function set_option( $name, $value ) {
		$success = update_site_option( 'wdp_un_' . $name, $value );

		if ( $success ) {
			/**
			 * Action hook to run after Dashboard option is updated.
			 *
			 * @since 4.11.22
			 *
			 * @param string $name  Name of the option.
			 * @param mixed  $value Value to update.
			 */
			do_action( 'wpmudev_dashboard_settings_after_set_option', $name, $value );
		}

		return $success;
	}

	/**
	 * Add a new plugin setting to the database.
	 *
	 * Can add a single item of a group or event an option without
	 * any group. If group name is omitted it will add as a single option.
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The new option value.
	 * @param string $group The group name (Optional for non grouped option).
	 *
	 * @since 4.11.10
	 *
	 * @return bool
	 */
	public function add( $name, $value, $group = false ) {
		// Get existing value for grouped option.
		if ( ! empty( $group ) ) {
			$existing = $this->get_option( $group, array() );

			// Already exist, don't add.
			if ( isset( $existing[ $name ] ) ) {
				return false;
			}

			// Add new value.
			$existing[ $name ] = $value;

			// Update option.
			return $this->set_option( $group, $value );
		}

		return $this->add_option( $name, $value );
	}

	/**
	 * Add a new single plugin setting to the database.
	 *
	 * The plugins option-prefix is automatically added to the option name.
	 * This function will only save the value if the option does not exist yet!
	 * Use this function instead of direct access via add_site_option().
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The new option value.
	 *
	 * @since 4.11.10
	 *
	 * @return bool
	 */
	public function add_option( $name, $value ) {
		return add_site_option( 'wdp_un_' . $name, $value );
	}

	/**
	 * Returns the value of a plugin transient.
	 *
	 * The plugins option-prefix is automatically added to the transient name.
	 * Use this function instead of direct access via get_site_transient().
	 *
	 * @param string $name   The transient name.
	 * @param bool   $prefix Optional. Set to false to not prefix the name.
	 *
	 * @since 4.11.10
	 *
	 * @return mixed The transient value.
	 */
	public function get_transient( $name, $prefix = true ) {
		$key = $prefix ? 'wdp_un_' . $name : $name;

		// Transient name cannot be longer than 167 characters
		// 150 is being safe
		$key = substr( $key, 0, 150 );

		return get_site_transient( $key );
	}

	/**
	 * Updates the value of a plugin transient.
	 *
	 * The plugins option-prefix is automatically added to the transient name.
	 * Use this function instead of direct access via update_site_option().
	 *
	 * @param string $name       The transient name.
	 * @param mixed  $value      The new transient value.
	 * @param int    $expiration Time until expiration. Default: No expiration.
	 * @param bool   $prefix     Optional. Set to false to not prefix the name.
	 *
	 * @since 4.11.10
	 *
	 * @return bool
	 */
	public function set_transient( $name, $value, $expiration = 0, $prefix = true ) {
		$key = $prefix ? 'wdp_un_' . $name : $name;

		// Transient name cannot be longer than 167 characters
		// 150 is being safe
		$key = substr( $key, 0, 150 );

		// Fix to prevent WP from hashing PHP objects.
		delete_site_transient( $key );

		if ( null !== $value ) {
			return set_site_transient( $key, $value, $expiration );
		}

		return false;
	}

	/**
	 * Initialize all plugin options in the DB during activation.
	 *
	 * This function is called by the `activate_plugin` plugin in the main
	 * plugin file. Do not call this on every page load.
	 *
	 * @since 4.11.10
	 *
	 * @return void
	 */
	public function init() {
		// Get the default settings.
		$defaults = $this->defaults();

		foreach ( $defaults as $name => $value ) {
			// This is a grouped option.
			if ( is_array( $value ) ) {
				$update = false;
				// Get existing value.
				$existing = $this->get_option( $name, array() );
				// Go through each item and add if not found.
				foreach ( $value as $sub_name => $sub_value ) {
					if ( ! isset( $existing[ $sub_name ] ) ) {
						// Adding new value, so needs update.
						$update = true;
						// Set new value.
						$existing[ $sub_name ] = null === $sub_value ? '' : $sub_value;
					}
				}

				// If needs an update.
				if ( $update ) {
					$this->set_option( $name, $existing );
				}
			} else {
				$value = null === $value ? '' : $value;
				// Add only if doesn't exist.
				$this->add_option( $name, $value );
			}
		}
	}

	/**
	 * Reset all settings back to default.
	 *
	 * This will reset setting items found in the WPMUDEV_Dashboard_Settings::defaults() list.
	 * If the default value of a field is null we will skip it from reset.
	 *
	 * @since 4.11.10
	 *
	 * @return void
	 */
	public function reset() {
		// Get the default settings.
		$defaults = $this->defaults();

		foreach ( $defaults as $name => $value ) {
			// This is a grouped option.
			if ( is_array( $value ) ) {
				// Get existing value.
				$values = $this->get_option( $name, array() );
				// Go through each item and add if not found.
				foreach ( $value as $sub_name => $sub_value ) {
					if ( null !== $sub_value ) {
						// Set default value.
						$values[ $sub_name ] = $sub_value;
					}
				}

				// Update with new values.
				$this->set_option( $name, $values );
			} else {
				if ( null !== $value ) {
					$this->set_option( $name, $value );
				}
			}
		}
	}

	/**
	 * Get the default settings values.
	 *
	 * This should be used to init and rest settings.
	 * To register new options use `wpmudev_dashboard_settings_defaults` filter.
	 * Only items registered here will be used for init and reset actions.
	 * If the value is null during reset it will be skipped.
	 *
	 * @since 4.11.10
	 *
	 * @return array
	 */
	public function defaults() {
		$settings = array(
			// Bigger options.
			'remote_access'                 => '',
			'updates_data'                  => null,
			'profile_data'                  => '',
			'updates_available'             => array(),
			'translation_updates_available' => array(),
			'notifications'                 => array(),
			// Whitelabel options.
			'whitelabel'                    => array(
				'enabled'                  => false,
				'branding_enabled'         => false,
				'branding_enabled_subsite' => false,
				'branding_type'            => 'default',
				'branding_image'           => '',
				'branding_image_id'        => 0,
				'branding_image_link'      => '',
				'footer_enabled'           => false,
				'footer_text'              => '',
				'labels_enabled'           => false,
				'labels_config'            => false,
				'labels_config_selected'   => '',
				'labels_networkwide'       => true,
				'labels_subsites'          => array(),
				'doc_links_enabled'        => false,
			),
			// Analytics options.
			'analytics'                     => array(
				'enabled'    => false,
				'tracker'    => '',
				'site_id'    => '',
				'script_url' => '',
				'metrics'    => array(
					'pageviews',
					'unique_pageviews',
					'page_time',
					'visits',
					'bounce_rate',
					'exit_rate',
				),
				'role'       => 'administrator',
			),
			// SSO.
			'sso'                           => array(
				'enabled'        => false,
				'userid'         => false,
				'previous_token' => '',
				'active_token'   => '',
			),
			'flags'                         => array(
				'refresh_remote'              => false,
				'refresh_profile'             => false,
				'redirected_v4'               => false,
				'highlights_dismissed'        => true,
				'first_setup'                 => false,
				'autoupdate_dashboard'        => true,
				'enable_auto_translation'     => false,
				'uninstall_preserve_settings' => true,
				'uninstall_keep_data'         => true,
			),
			// Data settings on uninstall.
			'data'                          => array(
				'preserve_settings' => true,
				'keep_data'         => true,
			),
			// Other small options.
			'general'                       => array(
				'last_run_updates'     => 0,
				'last_run_profile'     => 0,
				'last_run_sync'        => 0,
				'last_run_translation' => 0,
				'staff_notes'          => '',
				'translation_locale'   => 'en_US',
				'version'              => WPMUDEV_Dashboard::$version,
				'limit_to_user'        => '',
				'auth_user'            => null,
				'connected_admin'      => 0,
			),
		);

		/**
		 * Filter to modify default settings.
		 *
		 * @param array $settings Default settings.
		 *
		 * @since 4.11.10
		 */
		return apply_filters( 'wpmudev_dashboard_settings_defaults', $settings );
	}

	/**
	 * Get multiple options into single array assoc.
	 *
	 * This function will match expectation structure,
	 * Array returned from this function should be predictable.
	 *
	 * @param array $options Optional array assoc with setting name as key.
	 *
	 * @since 4.6.0
	 * @since 4.11.10 Moved to new class and renamed.
	 *
	 * @return array
	 */
	public function as_array( $options = array() ) {
		$settings = array();

		foreach ( $options as $name => $item ) {
			// Make sure all required properties set.
			$item = wp_parse_args(
				$item,
				array(
					'option'  => '',
					'group'   => false,
					'type'    => false,
					'default' => false,
				)
			);

			// If option name is not given use default value.
			if ( empty( $item['option'] ) ) {
				$settings[ $name ] = $this->sanitize( $item['default'], $item['type'] );
				continue;
			}

			// Get the sanitized value.
			$settings[ $name ] = $this->sanitize(
				$this->get( $item['option'], $item['group'], $item['default'] ),
				$item['type']
			);
		}

		return $settings;
	}

	/**
	 * Upgrade old settings to new grouped structure.
	 *
	 * A few items won't be upgraded because it is still in old
	 * structure without any group.
	 *
	 * @since 4.11.10
	 *
	 * @return void
	 */
	public function upgrade_41110() {
		$mapping = $this->deprecated_mappings();

		// Upgrade old options.
		foreach ( $mapping as $name => $item ) {
			// Get the old value.
			$value = $this->get_option( $name, null );
			// If value found.
			if ( null !== $value ) {
				// Delete old option.
				delete_site_option( 'wdp_un_' . $name );

				// Set group.
				$group = isset( $item['group'] ) ? $item['group'] : false;

				// Set new name.
				if ( isset( $item['name'] ) ) {
					$name = $item['name'];
				}

				// Set new option.
				$this->set( $name, $value, $group );
			}
		}

		// Unused options cleanup.
		$deprecated_options = array( 'farm133_themes', 'last_check_upfront' );

		foreach ( $deprecated_options as $option ) {
			delete_site_option( 'wdp_un_' . $option );
		}
	}

	/**
	 * Get the mapping for a deprecated option name.
	 *
	 * This is here only for backward compatibility.
	 *
	 * @param string $name Field name.
	 *
	 * @since      4.11.10
	 * @deprecated 4.11.10 For backward compatibility only.
	 *
	 * @return array
	 */
	public function deprecated_get_field_mapping( $name ) {
		$mapping = $this->deprecated_mappings();

		$mapped = array(
			'name'  => $name,
			'group' => false,
		);

		// If old option has new structure.
		if ( isset( $mapping[ $name ] ) ) {
			if ( isset( $mapping[ $name ]['name'] ) ) {
				$mapped['name'] = $mapping[ $name ]['name'];
			}

			if ( isset( $mapping[ $name ]['group'] ) ) {
				$mapped['group'] = $mapping[ $name ]['group'];
			}
		}

		return $mapped;
	}

	/**
	 * Get the mapping for deprecated option names.
	 *
	 * This is here for backward compatibility. Few of our plugins
	 * still use the old option names.
	 * If some of the options are not found here, which means they are
	 * still in old form.
	 *
	 * @since      4.11.10
	 * @deprecated 4.11.10 For backward compatibility only.
	 *
	 * @return array
	 */
	private function deprecated_mappings() {
		return array(
			'limit_to_user'                       => array(
				'group' => 'general',
			),
			'last_run_updates'                    => array(
				'group' => 'general',
			),
			'last_run_profile'                    => array(
				'group' => 'general',
			),
			'last_run_sync'                       => array(
				'group' => 'general',
			),
			'staff_notes'                         => array(
				'group' => 'general',
			),
			'translation_locale'                  => array(
				'group' => 'general',
			),
			'auth_user'                           => array(
				'group' => 'general',
			),
			'version'                             => array(
				'group' => 'general',
			),
			'redirected_v4'                       => array(
				'group' => 'flags',
			),
			'refresh_remote_flag'                 => array(
				'group' => 'flags',
			),
			'refresh_profile_flag'                => array(
				'group' => 'flags',
			),
			'autoupdate_dashboard'                => array(
				'group' => 'flags',
			),
			'enable_auto_translation'             => array(
				'group' => 'flags',
			),
			'highlights_dismissed'                => array(
				'group' => 'flags',
			),
			'first_setup'                         => array(
				'group' => 'flags',
			),
			'data_preserve_settings'              => array(
				'name'  => 'uninstall_preserve_settings',
				'group' => 'flags',
			),
			'data_keep_data'                      => array(
				'name'  => 'uninstall_keep_data',
				'group' => 'flags',
			),
			'whitelabel_enabled'                  => array(
				'name'  => 'enabled',
				'group' => 'whitelabel',
			),
			'whitelabel_branding_enabled'         => array(
				'name'  => 'branding_enabled',
				'group' => 'whitelabel',
			),
			'whitelabel_branding_enabled_subsite' => array(
				'name'  => 'branding_enabled_subsite',
				'group' => 'whitelabel',
			),
			'whitelabel_branding_type'            => array(
				'name'  => 'branding_type',
				'group' => 'whitelabel',
			),
			'whitelabel_branding_image'           => array(
				'name'  => 'branding_image',
				'group' => 'whitelabel',
			),
			'whitelabel_branding_image_id'        => array(
				'name'  => 'branding_image_id',
				'group' => 'whitelabel',
			),
			'whitelabel_branding_image_link'      => array(
				'name'  => 'branding_image_link',
				'group' => 'whitelabel',
			),
			'whitelabel_footer_enabled'           => array(
				'name'  => 'footer_enabled',
				'group' => 'whitelabel',
			),
			'whitelabel_footer_text'              => array(
				'name'  => 'footer_text',
				'group' => 'whitelabel',
			),
			'whitelabel_labels_enabled'           => array(
				'name'  => 'labels_enabled',
				'group' => 'whitelabel',
			),
			'whitelabel_labels_config'            => array(
				'name'  => 'labels_config',
				'group' => 'whitelabel',
			),
			'whitelabel_labels_config_selected'   => array(
				'name'  => 'labels_config_selected',
				'group' => 'whitelabel',
			),
			'whitelabel_labels_networkwide'       => array(
				'name'  => 'labels_networkwide',
				'group' => 'whitelabel',
			),
			'whitelabel_labels_subsites'          => array(
				'name'  => 'labels_subsites',
				'group' => 'whitelabel',
			),
			'whitelabel_doc_links_enabled'        => array(
				'name'  => 'doc_links_enabled',
				'group' => 'whitelabel',
			),
			'analytics_enabled'                   => array(
				'name'  => 'enabled',
				'group' => 'analytics',
			),
			'analytics_tracker'                   => array(
				'name'  => 'tracker',
				'group' => 'analytics',
			),
			'analytics_site_id'                   => array(
				'name'  => 'site_id',
				'group' => 'analytics',
			),
			'analytics_metrics'                   => array(
				'name'  => 'metrics',
				'group' => 'analytics',
			),
			'analytics_role'                      => array(
				'name'  => 'role',
				'group' => 'analytics',
			),
			'enable_sso'                          => array(
				'name'  => 'enabled',
				'group' => 'sso',
			),
			'sso_userid'                          => array(
				'name'  => 'userid',
				'group' => 'sso',
			),
			'previous_sso_token'                  => array(
				'name'  => 'previous_token',
				'group' => 'sso',
			),
			'active_sso_token'                    => array(
				'name'  => 'active_token',
				'group' => 'sso',
			),
		);
	}

	/**
	 * Sanitize given value to match a type.
	 *
	 * If not one of possible type, value won't be sanitized.
	 *
	 * @param mixed  $value Value to sanitize.
	 * @param string $type  Type of value.
	 *
	 * @since 4.11.10
	 *
	 * @return mixed
	 */
	private function sanitize( $value, $type = false ) {
		switch ( $type ) {
			case 'boolean':
				$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
				break;
			case 'string':
				// String expected, when its `empty`(NULL, false, etc) or its not string lets return empty string.
				if ( empty( $value ) || ! is_string( $value ) ) {
					$value = '';
				}
				break;
			case 'numeric':
				// numeric expected, its safe to return "0.7" even the explicit type is string
				// since PHP will auto coerce the type naturally.
				if ( ! is_numeric( $value ) ) {
					$value = 0;
				}
				break;
			case 'array':
				if ( ! is_array( $value ) ) {
					// Only array please.
					$value = empty( $value ) ? array() : (array) $value;
				}
				break;
		}

		return $value;
	}
}