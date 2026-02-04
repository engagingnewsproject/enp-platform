<?php
/**
 * Plugin Utility Functions for Ninja Forms Abilities
 *
 * This file contains all plugin settings-related execute callback functions.
 * These functions handle plugin configuration and settings management.
 *
 * @package NinjaForms
 * @subpackage Abilities\Utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ninja_forms_ability_get_plugin_settings( $input ) {
	try {
		// Get all settings values
		$all_settings = Ninja_Forms()->get_settings();

		// Add plugin version (from plugin metadata, not stored in settings)
		$all_settings['version'] = Ninja_Forms::VERSION;

		// Get settings groups (tabs) dynamically
		$groups = Ninja_Forms::config( 'PluginSettingsGroups' );

		// Initialize organized settings and mapping
		$organized_settings = array();
		$setting_to_group_map = array();

		foreach ( $groups as $group_id => $group_config ) {
			// Map group IDs to their config file names (handle special capitalization)
			$config_name_map = array(
				'general'   => 'General',
				'recaptcha' => 'ReCaptcha',
				'hcaptcha'  => 'Hcaptcha',
				'turnstile' => 'Turnstile',
				'advanced'  => 'Advanced',
			);

			$config_suffix = isset( $config_name_map[ $group_id ] ) ? $config_name_map[ $group_id ] : ucfirst( $group_id );

			// Load settings configuration for this group
			$group_settings_config = Ninja_Forms::config( 'PluginSettings' . $config_suffix );

			if ( ! $group_settings_config ) {
				continue;
			}

			// Organize settings by this group
			$group_label = strip_tags( $group_config['label'] ); // Remove HTML like help icons

			if ( ! isset( $organized_settings[ $group_label ] ) ) {
				$organized_settings[ $group_label ] = array();
			}

			// Add all settings from this group config (whether they have values or not)
			foreach ( $group_settings_config as $setting_key => $setting_config ) {
				// Skip 'prompt' type settings - these are internal helpers, not user-facing
				if ( isset( $setting_config['type'] ) && $setting_config['type'] === 'prompt' ) {
					continue;
				}

				// Skip 'show_welcome' setting - has inverse logic that's confusing
				if ( $setting_key === 'show_welcome' ) {
					continue;
				}

				// For 'html' type settings (buttons/actions), mark as action available
				if ( isset( $setting_config['type'] ) && $setting_config['type'] === 'html' ) {
					$organized_settings[ $group_label ][ $setting_key ] = '[Action Available]';
				} else {
					// Get value from stored settings
					$stored_value = isset( $all_settings[ $setting_key ] ) ? $all_settings[ $setting_key ] : null;

					// Checkbox fields: normalize null or empty string to 0 (unchecked)
					$checkbox_fields = array( 'delete_on_uninstall', 'disable_admin_notices', 'builder_dev_mode', 'load_legacy_submissions' );
					if ( in_array( $setting_key, $checkbox_fields, true ) && ( $stored_value === '' || $stored_value === null ) ) {
						$stored_value = 0;
					}

					// If no stored value, check for config default
					if ( $stored_value === null || $stored_value === '' ) {
						// Check for explicit 'value' in config
						if ( isset( $setting_config['value'] ) ) {
							$default_value = $setting_config['value'];
						} else {
							// Fallback defaults for settings without explicit 'value' field
							$known_defaults = array(
								'recaptcha_theme' => 'light',
							);
							$default_value = isset( $known_defaults[ $setting_key ] ) ? $known_defaults[ $setting_key ] : '';
						}

						// Special handling: some settings have empty string as value but meaningful labels
						$label_overrides = array(
							'opinionated_styles' => 'None',
						);
						if ( $default_value === '' && isset( $label_overrides[ $setting_key ] ) ) {
							$default_value = $label_overrides[ $setting_key ];
						}

						$organized_settings[ $group_label ][ $setting_key ] = $default_value;
					} else {
						$organized_settings[ $group_label ][ $setting_key ] = $stored_value;
					}
				}

				// Track this setting so we don't add it to "Other" later
				$setting_to_group_map[ $setting_key ] = true;
			}
		}

		// Add any remaining settings that weren't in a config group to "Other"
		foreach ( $all_settings as $key => $value ) {
			if ( ! isset( $setting_to_group_map[ $key ] ) ) {
				// Skip show_welcome here too
				if ( $key === 'show_welcome' ) {
					continue;
				}

				if ( ! isset( $organized_settings['Other'] ) ) {
					$organized_settings['Other'] = array();
				}
				$organized_settings['Other'][ $key ] = $value;
			}
		}

		// If specific keys requested, filter the organized settings
		if ( ! empty( $input['keys'] ) && is_array( $input['keys'] ) ) {
			$filtered_organized = array();
			foreach ( $organized_settings as $group_label => $group_settings ) {
				$filtered_group = array();
				foreach ( $group_settings as $key => $value ) {
					if ( in_array( $key, $input['keys'], true ) ) {
						$filtered_group[ $key ] = $value;
					}
				}
				if ( ! empty( $filtered_group ) ) {
					$filtered_organized[ $group_label ] = $filtered_group;
				}
			}
			$organized_settings = $filtered_organized;
			$message = sprintf( __( 'Retrieved %d plugin settings successfully.', 'ninja-forms' ), count( $input['keys'] ) );
		} else {
			$message = __( 'All plugin settings retrieved successfully.', 'ninja-forms' );
		}

		return array(
			'success'          => true,
			'message'          => $message,
			'settings'         => $all_settings, // Keep flat structure for backward compatibility
			'settings_by_group' => $organized_settings, // New organized structure
		);

	} catch ( Exception $e ) {
		return array(
			'success'  => false,
			'message'  => sprintf( __( 'Failed to retrieve settings: %s', 'ninja-forms' ), $e->getMessage() ),
			'settings' => array(),
		);
	}
}

function ninja_forms_ability_update_plugin_settings( $input ) {
	// Validate required input
	if ( ! isset( $input['settings'] ) || ! is_array( $input['settings'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Settings parameter is required and must be an object.', 'ninja-forms' ),
			'updated' => array(),
		);
	}

	$settings = $input['settings'];

	// If settings array is empty, return success with 0 updates
	if ( count( $settings ) === 0 ) {
		return array(
			'success' => true,
			'message' => __( 'Successfully updated 0 plugin settings.', 'ninja-forms' ),
			'updated' => array(),
		);
	}

	$updated = array();

	try {
		// Track if builder_dev_mode changed
		$old_builder_dev_mode = Ninja_Forms()->get_setting( 'builder_dev_mode', 0 );

		// Update each setting individually (with deferred save)
		foreach ( $settings as $key => $value ) {
			// Sanitize the value (preserve empty strings)
			if ( $value === '' ) {
				$sanitized_value = '';
			} else {
				$sanitized_value = sanitize_text_field( $value );
			}

			// Apply filter (matches dashboard behavior)
			$sanitized_value = apply_filters( 'ninja_forms_update_setting_' . $key, $sanitized_value );

			// Update setting with deferred save
			Ninja_Forms()->update_setting( $key, $sanitized_value, true );

			$updated[ $key ] = $sanitized_value;
		}

		// Handle special cases (matching dashboard Settings.php logic)

		// Currency symbol update
		if ( isset( $settings['currency'] ) ) {
			$currency = sanitize_text_field( $settings['currency'] );
			$currency_symbols = Ninja_Forms::config( 'CurrencySymbol' );
			$currency_symbol = ( isset( $currency_symbols[ $currency ] ) ) ? $currency_symbols[ $currency ] : '';
			Ninja_Forms()->update_setting( 'currency_symbol', $currency_symbol, true );
		}

		// Builder dev mode telemetry
		if ( isset( $settings['builder_dev_mode'] ) ) {
			$builder_dev_mode = sanitize_text_field( $settings['builder_dev_mode'] );
			$has_changed = ( $builder_dev_mode !== $old_builder_dev_mode );
			if ( $builder_dev_mode && $has_changed ) {
				Ninja_Forms()->dispatcher()->send( 'builder_dev_mode', $builder_dev_mode );
			}
		}

		// Opinionated styles telemetry
		if ( isset( $settings['opinionated_styles'] ) ) {
			if ( '' == $settings['opinionated_styles'] ) {
				Ninja_Forms()->dispatcher()->send( 'opinionated_styles_disabled', 'disabled' );
			}
		}

		// Save all settings to database at once
		Ninja_Forms()->update_settings();

		// Fire action for each setting (matches dashboard behavior)
		foreach ( $settings as $key => $value ) {
			$sanitized_value = sanitize_text_field( $value );
			do_action( 'ninja_forms_save_setting_' . $key, $sanitized_value );
		}

		$count = count( $updated );
		$message = sprintf(
			_n( 'Successfully updated %d plugin setting.', 'Successfully updated %d plugin settings.', $count, 'ninja-forms' ),
			$count
		);

		return array(
			'success' => true,
			'message' => $message,
			'updated' => $updated,
		);

	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Failed to update settings: %s', 'ninja-forms' ), $e->getMessage() ),
			'updated' => $updated,
		);
	}
}