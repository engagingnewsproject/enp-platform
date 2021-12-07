<?php

namespace WP_Defender;

use WP_Defender\Model\Scan as Model_Scan;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Model\Setting\Security_Headers;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Component\Legacy_Versions;
use WP_Defender\Component\Backup_Settings;

class Upgrader {

	/**
	 * Migrate old security headers from security tweaks. Trigger it once time
	 */
	public function migrate_security_headers() {
		$model   = wd_di()->get( Security_Headers::class );
		$new_key = $model->table;
		$option  = get_site_option( $new_key );

		if ( empty( $option ) ) {
			//Part of Security tweaks data
			$old_key      = 'wd_hardener_settings';
			$old_settings = get_site_option( $old_key );
			if ( ! is_array( $old_settings ) ) {
				$old_settings = json_decode( $old_settings, true );
				if ( is_array( $old_settings ) && isset( $old_settings['data'] ) && ! empty( $old_settings['data'] ) ) {
					//Exists 'X-Frame-Options'
					if ( isset( $old_settings['data']['sh_xframe'] ) && ! empty( $old_settings['data']['sh_xframe'] ) ) {
						$header_data = $old_settings['data']['sh_xframe'];

						$mode = ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) )
							? strtolower( $header_data['mode'] )
							: false;
						/**
						 * Directive ALLOW-FROM is deprecated. If header directive is ALLOW-FROM then set 'sameorigin'.
						 * @since 2.5.0
						 */
						if ( 'allow-from' === $mode ) {
							$model->sh_xframe_mode = 'sameorigin';
						} elseif ( in_array( $mode, array( 'sameorigin', 'deny' ), true ) ) {
							$model->sh_xframe_mode = $mode;
						}
						$model->sh_xframe = true;
					}

					//Exists 'X-XSS-Protection'
					if ( isset( $old_settings['data']['sh_xss_protection'] ) && ! empty( $old_settings['data']['sh_xss_protection'] ) ) {
						$header_data = $old_settings['data']['sh_xss_protection'];

						if ( isset( $header_data['mode'] )
							&& ! empty( $header_data['mode'] )
							&& in_array( $header_data['mode'], array( 'sanitize', 'block' ), true )
						) {
							$model->sh_xss_protection_mode = $header_data['mode'];
							$model->sh_xss_protection      = true;
						}
					}

					//Exists 'X-Content-Type-Options'
					if ( isset( $old_settings['data']['sh_content_type_options'] ) && ! empty( $old_settings['data']['sh_content_type_options'] ) ) {
						$header_data = $old_settings['data']['sh_content_type_options'];

						if ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) ) {
							$model->sh_content_type_options_mode = $header_data['mode'];
							$model->sh_content_type_options      = true;
						}
					}

					//Exists 'Strict Transport'
					if ( isset( $old_settings['data']['sh_strict_transport'] ) && ! empty( $old_settings['data']['sh_strict_transport'] ) ) {
						$header_data = $old_settings['data']['sh_strict_transport'];

						if ( isset( $header_data['hsts_preload'] ) && ! empty( $header_data['hsts_preload'] ) ) {
							$model->hsts_preload = (int) $header_data['hsts_preload'];
						}
						if ( isset( $header_data['include_subdomain'] ) && ! empty( $header_data['include_subdomain'] ) ) {
							$model->include_subdomain = in_array(
								$header_data['include_subdomain'],
								array( 'true', '1', 1 ),
								true
							) ? 1 : 0;
						}
						if ( isset( $header_data['hsts_cache_duration'] ) && ! empty( $header_data['hsts_cache_duration'] ) ) {
							$model->hsts_cache_duration = $header_data['hsts_cache_duration'];
						}
						$model->sh_strict_transport = true;
					}

					//Exists 'Referrer Policy'
					if ( isset( $old_settings['data']['sh_referrer_policy'] ) && ! empty( $old_settings['data']['sh_referrer_policy'] ) ) {
						$header_data = $old_settings['data']['sh_referrer_policy'];

						if ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) ) {
							$model->sh_referrer_policy_mode = $header_data['mode'];
							$model->sh_referrer_policy      = true;
						}
					}

					//Exists 'Feature-Policy'
					if ( isset( $old_settings['data']['sh_feature_policy'] ) && ! empty( $old_settings['data']['sh_feature_policy'] ) ) {
						$header_data = $old_settings['data']['sh_feature_policy'];

						if ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) ) {
							$mode                          = strtolower( $header_data['mode'] );
							$model->sh_feature_policy_mode = $mode;
							if ( 'origins' === $mode && isset( $header_data['values'] ) && ! empty( $header_data['values'] ) ) {
								//The values differ from the values of the 'X-Frame-Options' key, because they may be array.
								if ( is_array( $header_data['values'] ) ) {
									$model->sh_feature_policy_urls = implode( PHP_EOL, $header_data['values'] );
									//otherwise
								} elseif ( is_string( $header_data['values'] ) ) {
									$urls                          = explode( ' ', $header_data['values'] );
									$model->sh_feature_policy_urls = implode( PHP_EOL, $urls );
								}
							}
							$model->sh_feature_policy = true;
						}
					}
					// Save.
					$model->save();
				}
			}
		}
	}

	/**
	 *
	 * If user upgrade from an older version to latest version
	 *
	 * @param $current_version
	 */
	public function maybe_show_new_features( $current_version ) {
		if ( false === $current_version ) {
			//do nothing
			return;
		}

		// Set the version where we have added the new feature.
		// Update it when you want to show modal on a specific version.
		if ( version_compare( $current_version, '2.4', '<' ) ) {
			update_site_option( 'wd_show_new_feature', true );
		}
	}

	/**
	 *
	 * Migrate configs for latest versions.
	 * @since 2.4
	 *
	 * @param $current_version
	 */
	public function migrate_configs( $current_version ) {
		if (
			version_compare( $current_version, '2.2', '>=' )
			&& version_compare( $current_version, '2.4', '<' )
		) {
			$config_component = wd_di()->get( Backup_Settings::class );
			$prev_data        = $config_component->backup_data();
			if ( empty( $prev_data ) ) {
				return;
			}
			$adapter       = wd_di()->get( \WP_Defender\Component\Config\Config_Adapter::class );
			$migrated_data = $adapter->upgrade( $prev_data );
			$config_component->restore_data( $migrated_data, true );
			// Hide Onboard page
			update_site_option( 'wp_defender_shown_activator', true );

			$configs = $config_component->get_configs();
			if ( ! empty( $configs ) ) {
				foreach ( $configs as $k => $config ) {
					if (
						$config_component->verify_config_data( $config )
						&& ! $config_component->check_for_new_structure( $config['configs'] )
					) {
						$new_data            = $config;
						$new_data['configs'] = $adapter->upgrade( $config['configs'] );

						/**
						 * Import config 'strings' and the active tag if a config has it.
						 */
						if ( isset( $config['is_active'] ) ) {
							$new_data['is_active'] = $config['is_active'];
						}
						$new_data['strings'] = $config_component->import_module_strings( $new_data );
						//Update config data
						update_site_option( $k, $new_data );
						continue;
					}
				}
			}
		}
		// For older versions we do not use old models, e.g. for version < 2.2. So the default values will be used.
	}

	private function upgrade_2_4_2() {
		// Update Scan settings.
		$model_settings = wd_di()->get( Scan_Settings::class );
		$key            = $model_settings->table;
		$option         = get_site_option( $key );
		if ( ! empty( $option ) && ! is_array( $option ) ) {
			$old_settings = json_decode( $option, true );
			if ( is_array( $old_settings ) && isset( $old_settings['max_filesize'] ) ) {
				$model_settings->filesize = (int) $old_settings['max_filesize'];
				$model_settings->save();
			}
		}
		// Update 'reminder_duration' value inside the Security Key tweak.
		$old_settings = get_site_option( 'wd_hardener_settings' );
		if ( ! empty( $old_settings ) && ! is_array( $old_settings ) ) {
			$old_settings  = json_decode( $old_settings, true );
			$tweak_sec_key = wd_di()->get( \WP_Defender\Component\Security_Tweaks\Security_Key::class );

			if ( is_array( $old_settings ) && isset( $old_settings['data']['securityReminderDuration'] )
				&& in_array( $old_settings['data']['securityReminderDuration'], $tweak_sec_key->reminder_frequencies(), true )
			) {
				$tweak_sec_key->update_option( 'reminder_duration', $old_settings['data']['securityReminderDuration'] );
			}
		}
	}

	/**
	 *
	 * Migrate value of scan setting from 'integrity_check' to 'check_core'.
	 *
	 * @since 2.4.7
	 * @return void
	 */
	private function migrate_scan_integrity_check() {
		$model             = new Scan_Settings();
		$model->check_core = (bool) $model->integrity_check;
		$model->save();
	}

	/**
	 * Run an upgrade/installation.
	 *
	 * @return void
	 */
	public function run() {
		// Sometimes multiple requests comes at the same time.
		// So we will only count the web requests.
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) {
			return;
		}

		$db_version = get_site_option( 'wd_db_version' );
		if ( empty( $db_version ) ) {
			update_site_option( 'wd_db_version', DEFENDER_DB_VERSION );
			// Add Black Friday notice.
			update_site_option( 'wp_defender_show_black_friday', true );
			return;
		}

		if ( DEFENDER_DB_VERSION === $db_version ) {
			return;
		}

		$this->maybe_show_new_features( $db_version );
		$this->migrate_configs( $db_version );

		if ( version_compare( $db_version, '2.2.9', '<' ) ) {
			$this->migrate_security_headers();
		}
		if ( version_compare( $db_version, '2.4.2', '<' ) ) {
			$this->upgrade_2_4_2();
		}
		if ( version_compare( $db_version, '2.4.7', '<' ) ) {
			$this->index_database();
			$this->migrate_scan_integrity_check();
		}
		if ( version_compare( $db_version, '2.4.10', '<' ) ) {
			$this->upgrade_2_4_10();
		}
		if ( version_compare( $db_version, '2.5.0', '<' ) ) {
			$this->upgrade_2_5_0();
		}
		if ( version_compare( $db_version, '2.5.2', '<' ) ) {
			$this->upgrade_2_5_2();
		}
		if ( version_compare( $db_version, '2.5.4', '<' ) ) {
			$this->upgrade_2_5_4();
		}
		if ( version_compare( $db_version, '2.5.6', '<' ) ) {
			$this->upgrade_2_5_6();
		}
		if ( version_compare( $db_version, '2.6.0', '<' ) ) {
			$this->upgrade_2_6_0();
		}
		if ( version_compare( $db_version, '2.6.1', '<' ) ) {
			$this->upgrade_2_6_1();
		}
		if ( version_compare( $db_version, '2.6.2', '<' ) ) {
			$this->upgrade_2_6_2();
		}

		defender_no_fresh_install();
		// Don't run any function below this line.
		update_site_option( 'wd_db_version', DEFENDER_DB_VERSION );
	}

	/**
	 * Index necessary columns.
	 * Sometimes this function call twice that's why we have to check index already exists or not.
	 * `dbDelta` not work on `ALTER TABLE` query so we had to use $wpdb->query()
	 *
	 * @since 2.4.7
	 * @return void
	 */
	private function index_database() {
		global $wpdb;
		$wpdb->hide_errors();

		$this->add_index_to_defender_email_log( $wpdb );
		$this->add_index_to_defender_audit_log( $wpdb );
		$this->add_index_to_defender_scan_item( $wpdb );
		$this->add_index_to_defender_lockout_log( $wpdb );
		$this->add_index_to_defender_lockout( $wpdb );
	}

	/**
	 * Add index to defender_email_log
	 *
	 * @param $wpdb
	 * @since 2.4.7
	 */
	private function add_index_to_defender_email_log( $wpdb ) {
		$table  = $wpdb->base_prefix . 'defender_email_log';
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'source';", ARRAY_A );

		if ( is_array( $result ) ) {
			return;
		}

		$sql = "ALTER TABLE {$table} ADD INDEX `source` (`source`);";
		$wpdb->query( $sql );
	}

	/**
	 * Add index to defender_audit_log
	 *
	 * @param $wpdb
	 * @since 2.4.7
	 */
	private function add_index_to_defender_audit_log( $wpdb ) {
		$table  = $wpdb->base_prefix . 'defender_audit_log';
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'event_type';", ARRAY_A );

		if ( is_array( $result ) ) {
			return;
		}

		$sql = "ALTER TABLE {$table}
				ADD INDEX `event_type` (`event_type`),
				ADD INDEX `action_type` (`action_type`),
				ADD INDEX `user_id` (`user_id`),
				ADD INDEX `context` (`context`),
				ADD INDEX `ip` (`ip`);";
		$wpdb->query( $sql );
	}

	/**
	 * Add index to defender_scan_item
	 *
	 * @param $wpdb
	 * @since 2.4.7
	 */
	private function add_index_to_defender_scan_item( $wpdb ) {
		$table  = $wpdb->base_prefix . 'defender_scan_item';
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'type';", ARRAY_A );

		if ( is_array( $result ) ) {
			return;
		}

		$sql = "ALTER TABLE {$table} ADD INDEX `type` (`type`), ADD INDEX `status` (`status`);";
		$wpdb->query( $sql );
	}

	/**
	 * Add index to defender_lockout_log
	 *
	 * @param $wpdb
	 * @since 2.4.7
	 */
	private function add_index_to_defender_lockout_log( $wpdb ) {
		$table  = $wpdb->base_prefix . 'defender_lockout_log';
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'ip';", ARRAY_A );

		if ( is_array( $result ) ) {
			return;
		}

		$sql = "ALTER TABLE {$table} ADD INDEX `ip` (`ip`), ADD INDEX `type` (`type`), ADD INDEX `tried` (`tried`);";
		$wpdb->query( $sql );
	}

	/**
	 * Add index to defender_lockout.
	 *
	 * @param $wpdb
	 * @since 2.4.7
	 */
	private function add_index_to_defender_lockout( $wpdb ) {
		$table  = $wpdb->base_prefix . 'defender_lockout';
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'ip';", ARRAY_A );

		if ( is_array( $result ) ) {
			return;
		}

		$sql = "ALTER TABLE {$table}
				ADD INDEX `ip` (`ip`),
				ADD INDEX `status` (`status`),
				ADD INDEX `attempt` (`attempt`),
				ADD INDEX `attempt_404` (`attempt_404`);";
		$wpdb->query( $sql );
	}

	/**
	 * Upgrade to 2.4.10
	 * @since 2.4.10
	 */
	private function upgrade_2_4_10() {
		$service         = wd_di()->get( Backup_Settings::class );
		$configs         = Config_Hub_Helper::get_configs( $service );
		$deprecated_keys = array(
			//reason: updated or removed some tweak slugs
			'security_key',
			'wp-rest-api',
			//reason: moved the security headers to a separate module
			'sh-referrer-policy',
			'sh-strict-transport',
			'sh-xframe',
			'sh-content-security',
			'sh-content-type-options',
			'sh-feature-policy',
			'sh-xss-protection',
		);
		foreach ( $configs as $key => $config ) {
			$is_updated = false;
			//Remove deprecated 'data' key inside Security tweaks.
			if ( isset( $config['configs']['security_tweaks']['data'] ) ) {
				unset( $configs[ $key ]['configs']['security_tweaks']['data'] );
				$is_updated = true;
			}
			//Remove deprecated keys in 'issues',
			if ( isset( $config['configs']['security_tweaks']['issues'] ) ) {
				foreach ( $config['configs']['security_tweaks']['issues'] as $iss_key => $issue ) {
					if ( in_array( $issue, $deprecated_keys, true ) ) {
						unset( $configs[ $key ]['configs']['security_tweaks']['issues'][ $iss_key ] );
						$is_updated = true;
					}
				}
			}
			//in 'ignore',
			if ( isset( $config['configs']['security_tweaks']['ignore'] ) ) {
				foreach ( $config['configs']['security_tweaks']['ignore'] as $ign_key => $issue ) {
					if ( in_array( $issue, $deprecated_keys, true ) ) {
						unset( $configs[ $key ]['configs']['security_tweaks']['ignore'][ $ign_key ] );
						$is_updated = true;
					}
				}
			}
			//and in 'fixed'.
			if ( isset( $config['configs']['security_tweaks']['fixed'] ) ) {
				foreach ( $config['configs']['security_tweaks']['fixed'] as $fix_key => $issue ) {
					if ( in_array( $issue, $deprecated_keys, true ) ) {
						unset( $configs[ $key ]['configs']['security_tweaks']['fixed'][ $fix_key ] );
						$is_updated = true;
					}
				}
			}

			if ( $is_updated ) {
				update_site_option( $key, $configs[ $key ] );
				Config_Hub_Helper::update_on_hub( $configs[ $key ] );
				if ( isset( $config['is_active'] ) ) {
					$model         = new \WP_Defender\Model\Setting\Security_Tweaks();
					$model->issues = $configs[ $key ]['configs']['security_tweaks']['issues'];
					$model->ignore = $configs[ $key ]['configs']['security_tweaks']['ignore'];
					$model->fixed  = $configs[ $key ]['configs']['security_tweaks']['fixed'];
					$model->save();
				}
			}
		}
	}

	/**
	 * Upgrade to 2.5.0.
	 * @since 2.5.0
	 */
	private function upgrade_2_5_0() {
		$model = wd_di()->get( Security_Headers::class );
		//Directive ALLOW-FROM is deprecated. If header directive is ALLOW-FROM then set 'sameorigin'.
		if ( isset( $model->sh_xframe_mode ) && 'allow-from' === $model->sh_xframe_mode ) {
			$model->sh_xframe_mode = 'sameorigin';
			$model->save();
		}
		//Display a new feature about Pwned Passwords on Welcome modal
		update_site_option( 'wd_show_feature_password_pwned', true );
		/**
		 * Uncheck 'File change detection' option if there was checked only child 'Scan theme files' option and save
		 * settings. Also remove items for 'Scan theme files' without run Scan.
		*/
		//Step#1
		$scan_settings = new Scan_Settings();
		if (
			$scan_settings->integrity_check
			&& ! $scan_settings->check_core
			&& ! $scan_settings->check_plugins
		) {
			$scan_settings->integrity_check = false;
			$scan_settings->save();
		}
		//Step#2
		$scan_model = Model_Scan::get_active();
		if ( is_object( $scan_model ) ) {
			// Nothing changes.
			return;
		}
		$scan_model = Model_Scan::get_last();
		if ( is_object( $scan_model ) && ! is_wp_error( $scan_model ) ) {
			// Active items.
			$items = $scan_model->get_issues( Scan_Item::TYPE_THEME_CHECK, Scan_Item::STATUS_ACTIVE );
			foreach ( $items as $item ) {
				$scan_model->remove_issue( $item->id );
			}
			// Ignored items.
			$items = $scan_model->get_issues( Scan_Item::TYPE_THEME_CHECK, Scan_Item::STATUS_IGNORE );
			foreach ( $items as $item ) {
				$scan_model->remove_issue( $item->id );
			}
		}
	}
	/**
	 * Upgrade. Display a new feature about Reset Password on Welcome modal.
	 * @since 2.5.2
	 */
	private function upgrade_2_5_2() {
		update_site_option( 'wd_show_feature_password_reset', true );
	}

	/**
	 * Upgrade. Display a new feature about Google Recaptcha on Welcome modal.
	 * @since 2.5.4
	 */
	private function upgrade_2_5_4() {
		update_site_option( 'wd_show_feature_google_recaptcha', true );
	}

	private function force_nf_lockout_exclusions() {
		$nf_settings       = new \WP_Defender\Model\Setting\Notfound_Lockout();
		$allowlist         = $nf_settings->get_lockout_list( 'allowlist' );
		$default_allowlist = array( '.css', '.js', '.map' );
		$is_save           = false;
		if ( ! empty( $allowlist ) ) {
			foreach ( $default_allowlist as $item ) {
				if ( ! in_array( $item, $allowlist ) ) {
					$allowlist[] = $item;
					$is_save     = true;
				}
			}
			$nf_settings->whitelist = implode( "\n", $allowlist );
		} else {
			$nf_settings->whitelist = ".css\n.js\n.map";
			$is_save                = true;
		}
		// Save it.
		if ( $is_save ) {
			$nf_settings->save();
		}
	}

	/**
	 * Upgrade to 2.5.6.
	 * @since 2.5.6
	 */
	private function upgrade_2_5_6() {
		$adapted_component = wd_di()->get( Legacy_Versions::class );
		$issue_list        = $adapted_component->get_scan_issue_data();
		$ignored_list      = $adapted_component->get_scan_ignored_data();
		if ( ! empty( $issue_list ) || ! empty( $ignored_list ) ) {
			$adapted_component->migrate_scan_data( $issue_list, $ignored_list );
			$adapted_component->remove_old_scan_data( $issue_list, $ignored_list );
			$adapted_component->change_onboarding_status();
		}

		// Trigger recurring cron schedule for autogenerate security salt.
		$security_tweaks = wd_di()->get( Security_Tweaks::class );
		$security_tweaks->get_security_key()->cron_schedule();
		// Add some lockout extension to old installations forced.
		$this->force_nf_lockout_exclusions();
		// Display a new feature on Welcome modal.
		update_site_option( 'wd_show_feature_file_extensions', true );
	}

	/**
	 * Upgrade to 2.6.0.
	 * @since 2.6.0
	 */
	private function upgrade_2_6_0() {
		update_site_option( 'wd_show_feature_user_agent', true );
	}

	private function update_scan_error_send_body( $model ) {
		if (
			isset( $model->configs['template']['error']['body'] )
			&& ! empty( $model->configs['template']['error']['body'] )
		) {
			$needle = '{follow this link} and check the logs to see what casued the failure';
			if ( false !== stripos( $model->configs['template']['error']['body'], $needle ) ) {
				$model->configs['template']['error']['body'] = str_replace(
					$needle,
					'visit your site and run a manual scan',
					$model->configs['template']['error']['body']
				);
				$model->save();
			}
		}
	}

	/**
	 * Upgrade to 2.6.1.
	 * @since 2.6.1
	 */
	private function upgrade_2_6_1() {
		// Add the "What's new" modal.
		update_site_option( 'wd_show_feature_woo_recaptcha', true );
		// Update the title of the basic config.
		$config_component = wd_di()->get( Backup_Settings::class );
		$configs          = $config_component->get_configs();
		if ( ! empty( $configs ) ) {
			foreach ( $configs as $k => $config ) {
				if ( 0 === strcmp( $config['name'], __( 'Basic config', 'wpdef' ) ) ) {
					$config['name'] = __( 'Basic Config', 'wpdef' );

					update_site_option( $k, $config );
					delete_site_transient( Config_Hub_Helper::CONFIGS_TRANSIENT_KEY );
					break;
				}
			}
		}
		// Update scan emails for 'When failed to scan' option.
		$malware_notification = wd_di()->get( \WP_Defender\Model\Notification\Malware_Notification::class );
		$this->update_scan_error_send_body( $malware_notification );
		$malware_report = wd_di()->get( \WP_Defender\Model\Notification\Malware_Report::class );
		$this->update_scan_error_send_body( $malware_report );
	}

	/**
	 * Upgrade to 2.6.2.
	 * @since 2.6.2
	 */
	private function upgrade_2_6_2() {
		// Add the "What's new" modal.
		update_site_option( 'wd_show_feature_plugin_vulnerability', true );
		// Add Black Friday notice.
		update_site_option( 'wp_defender_show_black_friday', true );
	}
}
