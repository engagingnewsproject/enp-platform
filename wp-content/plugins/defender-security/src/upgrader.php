<?php

namespace WP_Defender;

use WP_Defender\Component\Feature_Modal;
use WP_Defender\Model\Scan as Model_Scan;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Model\Setting\Security_Headers;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Model\Setting\Two_Fa as Two_Fa_Settings;
use WP_Defender\Component\Two_Fa as Two_Fa_Component;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Component\Legacy_Versions;
use WP_Defender\Component\Backup_Settings;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Notification\Malware_Notification;
use WP_Defender\Model\Notification\Tweak_Reminder;
use WP_Defender\Traits\User;

class Upgrader {
	use User;

	/**
	 * Migrate old security headers from security tweaks. Trigger it once time.
	 */
	public function migrate_security_headers() {
		$model   = wd_di()->get( Security_Headers::class );
		$new_key = $model->table;
		$option  = get_site_option( $new_key );

		if ( empty( $option ) ) {
			// Part of Security tweaks data.
			$old_key      = 'wd_hardener_settings';
			$old_settings = get_site_option( $old_key );
			if ( ! is_array( $old_settings ) ) {
				$old_settings = json_decode( $old_settings, true );
				if ( is_array( $old_settings ) && isset( $old_settings['data'] ) && ! empty( $old_settings['data'] ) ) {
					// Exist 'X-Frame-Options'.
					if ( isset( $old_settings['data']['sh_xframe'] ) && ! empty( $old_settings['data']['sh_xframe'] ) ) {
						$header_data = $old_settings['data']['sh_xframe'];

						$mode = ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) )
							? strtolower( $header_data['mode'] )
							: false;
						/**
						 * Directive ALLOW-FROM is deprecated. If header directive is ALLOW-FROM then set 'sameorigin'.
						 *
						 * @since 2.5.0
						 */
						if ( 'allow-from' === $mode ) {
							$model->sh_xframe_mode = 'sameorigin';
						} elseif ( in_array( $mode, array( 'sameorigin', 'deny' ), true ) ) {
							$model->sh_xframe_mode = $mode;
						}
						$model->sh_xframe = true;
					}

					// Exist 'X-XSS-Protection'.
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

					// Exist 'X-Content-Type-Options'.
					if ( isset( $old_settings['data']['sh_content_type_options'] ) && ! empty( $old_settings['data']['sh_content_type_options'] ) ) {
						$header_data = $old_settings['data']['sh_content_type_options'];

						if ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) ) {
							$model->sh_content_type_options_mode = $header_data['mode'];
							$model->sh_content_type_options      = true;
						}
					}

					// Exist 'Strict Transport'.
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

					// Exist 'Referrer Policy'.
					if ( isset( $old_settings['data']['sh_referrer_policy'] ) && ! empty( $old_settings['data']['sh_referrer_policy'] ) ) {
						$header_data = $old_settings['data']['sh_referrer_policy'];

						if ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) ) {
							$model->sh_referrer_policy_mode = $header_data['mode'];
							$model->sh_referrer_policy      = true;
						}
					}

					// Exist 'Feature-Policy'.
					if ( isset( $old_settings['data']['sh_feature_policy'] ) && ! empty( $old_settings['data']['sh_feature_policy'] ) ) {
						$header_data = $old_settings['data']['sh_feature_policy'];

						if ( isset( $header_data['mode'] ) && ! empty( $header_data['mode'] ) ) {
							$mode                          = strtolower( $header_data['mode'] );
							$model->sh_feature_policy_mode = $mode;
							if ( 'origins' === $mode && isset( $header_data['values'] ) && ! empty( $header_data['values'] ) ) {
								// The values differ from the values of the 'X-Frame-Options' key, because they may be an array.
								if ( is_array( $header_data['values'] ) ) {
									$model->sh_feature_policy_urls = implode( PHP_EOL, $header_data['values'] );
									// Otherwise.
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
	 * If user upgrade from an older version to the latest version.
	 *
	 * @param $current_version
	 */
	public function maybe_show_new_features( $current_version ) {
		if ( false === $current_version ) {
			// Do nothing.
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
	 *
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
			$config_component->restore_data( $migrated_data, 'migration' );
			// Hide Onboard page.
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
						// Update config data.
						update_site_option( $k, $new_data );
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
		// Sometimes multiple requests come at the same time. So we will only count the web requests.
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) {
			return;
		}

		$db_version = get_site_option( 'wd_db_version' );
		if ( empty( $db_version ) ) {
			update_site_option( 'wd_db_version', DEFENDER_DB_VERSION );
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
		if ( version_compare( $db_version, '2.7.0', '<' ) ) {
			$this->upgrade_2_7_0();
		}
		if ( version_compare( $db_version, '2.8.0', '<' ) ) {
			$this->upgrade_2_8_0();
		}
		if ( version_compare( $db_version, '2.8.1', '<' ) ) {
			$this->upgrade_2_8_1();
		}

		defender_no_fresh_install();
		// Don't run any function below this line.
		update_site_option( 'wd_db_version', DEFENDER_DB_VERSION );
	}

	/**
	 * Index necessary columns.
	 * Sometimes this function call twice that's why we have to check index already exists or not.
	 * `dbDelta` not work on `ALTER TABLE` query, so we had to use $wpdb->query().
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
	 * Add index to defender_email_log.
	 *
	 * @param $wpdb
	 * @since 2.4.7
	 */
	private function add_index_to_defender_email_log( $wpdb ) {
		$table = $wpdb->base_prefix . 'defender_email_log';
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'source';", ARRAY_A );

		if ( is_array( $result ) ) {
			return;
		}

		$sql = "ALTER TABLE {$table} ADD INDEX `source` (`source`);";
		$wpdb->query( $sql );
	}

	/**
	 * Add index to defender_audit_log.
	 *
	 * @param $wpdb
	 * @since 2.4.7
	 */
	private function add_index_to_defender_audit_log( $wpdb ) {
		$table = $wpdb->base_prefix . 'defender_audit_log';
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
	 * Add index to defender_scan_item.
	 *
	 * @param $wpdb
	 * @since 2.4.7
	 */
	private function add_index_to_defender_scan_item( $wpdb ) {
		$table = $wpdb->base_prefix . 'defender_scan_item';
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'type';", ARRAY_A );

		if ( is_array( $result ) ) {
			return;
		}

		$sql = "ALTER TABLE {$table} ADD INDEX `type` (`type`), ADD INDEX `status` (`status`);";
		$wpdb->query( $sql );
	}

	/**
	 * Add index to defender_lockout_log.
	 *
	 * @param $wpdb
	 * @since 2.4.7
	 */
	private function add_index_to_defender_lockout_log( $wpdb ) {
		$table = $wpdb->base_prefix . 'defender_lockout_log';
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
		$table = $wpdb->base_prefix . 'defender_lockout';
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
	 * Upgrade to 2.4.10.
	 *
	 * @since 2.4.10
	 */
	private function upgrade_2_4_10() {
		$service         = wd_di()->get( Backup_Settings::class );
		$configs         = Config_Hub_Helper::get_configs( $service );
		$deprecated_keys = array(
			// Reason: updated or removed some tweak slugs.
			'security_key',
			'wp-rest-api',
			// Reason: moved the security headers to a separate module.
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
			// Remove deprecated 'data' key inside Security tweaks.
			if ( isset( $config['configs']['security_tweaks']['data'] ) ) {
				unset( $configs[ $key ]['configs']['security_tweaks']['data'] );
				$is_updated = true;
			}
			// Remove deprecated keys in 'issues'.
			if ( isset( $config['configs']['security_tweaks']['issues'] ) ) {
				foreach ( $config['configs']['security_tweaks']['issues'] as $iss_key => $issue ) {
					if ( in_array( $issue, $deprecated_keys, true ) ) {
						unset( $configs[ $key ]['configs']['security_tweaks']['issues'][ $iss_key ] );
						$is_updated = true;
					}
				}
			}
			// In 'ignore'.
			if ( isset( $config['configs']['security_tweaks']['ignore'] ) ) {
				foreach ( $config['configs']['security_tweaks']['ignore'] as $ign_key => $issue ) {
					if ( in_array( $issue, $deprecated_keys, true ) ) {
						unset( $configs[ $key ]['configs']['security_tweaks']['ignore'][ $ign_key ] );
						$is_updated = true;
					}
				}
			}
			// In 'fixed'.
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
	 *
	 * @since 2.5.0
	 */
	private function upgrade_2_5_0() {
		$model = wd_di()->get( Security_Headers::class );
		// Directive ALLOW-FROM is deprecated. If header directive is ALLOW-FROM then set 'sameorigin'.
		if ( isset( $model->sh_xframe_mode ) && 'allow-from' === $model->sh_xframe_mode ) {
			$model->sh_xframe_mode = 'sameorigin';
			$model->save();
		}
		// Display a new feature about Pwned Passwords on Welcome modal.
		update_site_option( 'wd_show_feature_password_pwned', true );
		/**
		 * Uncheck 'File change detection' option if there was checked only child 'Scan theme files' option and save
		 * settings. Also remove items for 'Scan theme files' without run Scan.
		 */
		// Step#1.
		$scan_settings = new Scan_Settings();
		if (
			$scan_settings->integrity_check
			&& ! $scan_settings->check_core
			&& ! $scan_settings->check_plugins
		) {
			$scan_settings->integrity_check = false;
			$scan_settings->save();
		}
		// Step#2.
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
	 *
	 * @since 2.5.2
	 */
	private function upgrade_2_5_2() {
		update_site_option( 'wd_show_feature_password_reset', true );
	}

	/**
	 * Upgrade. Display a new feature about Google Recaptcha on Welcome modal.
	 *
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
	 *
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
	 *
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
	 *
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
		$malware_report = wd_di()->get( Malware_Report::class );
		$this->update_scan_error_send_body( $malware_report );
	}

	/**
	 * Upgrade to 2.6.2.
	 *
	 * @since 2.6.2
	 */
	private function upgrade_2_6_2() {
		// Add the "What's new" modal.
		update_site_option( 'wd_show_feature_plugin_vulnerability', true );
		// Add Black Friday notice.
		update_site_option( 'wp_defender_show_black_friday', true );
	}

	/**
	 * Update 2FA email template.
	 */
	private function update_2fa_send_body() {
		$model = wd_di()->get( Two_Fa_Settings::class );
		if ( isset( $model->email_body ) ) {
			$model->email_body = 'Hi {{display_name}},

Your temporary password is {{passcode}}. To finish logging in, copy and paste the temporary password into the Password field on the login screen.';
			$model->save();
		}
	}

	/**
	 * Update Malware Scan email template.
	 *
	 * @since 2.7.0
	 * @return void
	 */
	private function update_malware_scan_send_body() {
		$models = array(
			wd_di()->get( \WP_Defender\Model\Notification\Malware_Notification::class ),
			wd_di()->get( \WP_Defender\Model\Notification\Malware_Report::class ),
		);

		foreach ( $models as $model ) {
			$is_updated = false;
			if ( ! empty( $model->configs['template']['error']['body'] ) ) {
				$subject = $model->configs['template']['error']['body'];
				$subject = $this->update_malware_scan_send_body_common( $subject );
				$subject = str_replace( 'I couldn', 'We couldn', $subject );

				if ( $model->configs['template']['error']['body'] !== $subject ) {
					$is_updated = true;

					$model->configs['template']['error']['body'] = $subject;
				}
			}

			if ( ! empty( $model->configs['template']['found']['body'] ) ) {
				$subject = $model->configs['template']['found']['body'];
				$subject = $this->update_malware_scan_send_body_common( $subject );
				$subject = preg_replace(
					"/I(’|'|\\\')ve finished scanning \{SITE_URL\} for vulnerabilities and I found \{ISSUES_COUNT\} issues that you should take a closer look at!/i",
					'{ISSUES_COUNT} vulnerabilities were identified for {SITE_URL} during a Malware Scan. See details for each issue below.',
					$subject
				);
				$subject = preg_replace( '/(\R+){ISSUES_LIST}/i', "\n\n{ISSUES_LIST}", $subject );

				if ( $model->configs['template']['found']['body'] !== $subject ) {
					$is_updated = true;

					$model->configs['template']['found']['body'] = $subject;
				}
			}

			if ( ! empty( $model->configs['template']['not_found']['body'] ) ) {
				$subject = $model->configs['template']['not_found']['body'];
				$subject = $this->update_malware_scan_send_body_common( $subject );
				$subject = preg_replace(
					"/I(’|'|\\\')ve finished scanning \{SITE_URL\} for vulnerabilities and I found nothing\. Well done for running such a tight ship!/i",
					'No vulnerabilities have been found for {SITE_URL}.',
					$subject
				);
				$subject = preg_replace(
					"/(\R+)Keep up the good work! With regular security scans and a well-hardened installation you\'ll be just fine\./i",
					'',
					$subject
				);

				if ( $model->configs['template']['not_found']['body'] !== $subject ) {
					$is_updated = true;

					$model->configs['template']['not_found']['body'] = $subject;
				}
			}

			if ( true === $is_updated ) {
				$model->save();
			}
		}
	}

	/**
	 * Update Malware Scan email template common parts.
	 *
	 * @param string $subject
	 *
	 * @since 2.7.0
	 * @return string
	 */
	private function update_malware_scan_send_body_common( $subject ) {
		$subject = preg_replace( '/(\R+)WP Defender here, reporting back from the front\./i', '', $subject );
		return preg_replace( '/(\R+)Stay Safe,(\R+)WP Defender(\R+)WPMU DEV Superhero$/i', '', $subject );
	}

	/**
	 * Upgrade to 2.7.0.
	 *
	 * @since 2.7.0
	 */
	private function upgrade_2_7_0() {
		$malware_report = wd_di()->get( Malware_Report::class );
		$scan_settings  = wd_di()->get( Scan_Settings::class );
		// Migrate data from Malware_Report to Scan settings.
		$scan_settings->scheduled_scanning = \WP_Defender\Model\Notification::STATUS_ACTIVE === $malware_report->status;
		$scan_settings->frequency          = $malware_report->frequency;
		$scan_settings->day                = $malware_report->day;
		$scan_settings->day_n              = $malware_report->day_n;
		$scan_settings->time               = $malware_report->time;
		$scan_settings->save();

		// Remove Black Friday notice.
		delete_site_option( 'wp_defender_show_black_friday' );
		// Add the "What's new" modal.
		update_site_option( Feature_Modal::FEATURE_SLUG, true );
		$this->update_2fa_send_body();
		$this->update_malware_scan_send_body();
	}

	/**
	 * Update in_house recipients empty role.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	private function update_in_house_recipients_empty_role() {
		$models = array(
			wd_di()->get( Audit_Report::class ),
			wd_di()->get( Firewall_Notification::class ),
			wd_di()->get( Firewall_Report::class ),
			wd_di()->get( Malware_Notification::class ),
			wd_di()->get( Malware_Report::class ),
			wd_di()->get( Tweak_Reminder::class ),
		);

		foreach ( $models as $model ) {
			if ( ! empty( $model->in_house_recipients ) && is_array( $model->in_house_recipients ) ) {
				$is_updated = false;

				foreach ( $model->in_house_recipients as &$recipient ) {
					if ( empty( $recipient['role'] ) ) {
						$is_updated = true;
						$recipient['role'] = $this->get_current_user_role( $recipient['id'] );
					}
				}

				if ( true === $is_updated ) {
					$model->save();
				}
			}
		}
	}

	/**
	 * Delete temp geolite file from /wp-admin directory.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	private function delete_tmp_geolite_file() {
		$pattern = ABSPATH . 'wp-admin/geolite2-country*.tar.gz';
		array_map( 'unlink', glob( $pattern ) );
	}

	/**
	 * Migrate old keys to checked options.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	private function update_2fa_methods() {
		$settings = wd_di()->get( Two_Fa_Settings::class );
		if ( $settings->enabled ) {
			$service = wd_di()->get( Two_Fa_Component::class );
			$query   = new \WP_User_Query(
				array(
					'blog_id'    => 0,
					'meta_key'   => 'defenderAuthOn',
					'meta_value' => true,
					'fields'     => 'ID',
				)
			);
			if ( $query->get_total() > 0 ) {
				// The data is independent of the user's data.
				$providers = $service->get_providers();
				$totp_slug = \WP_Defender\Component\Two_Factor\Providers\Totp::$slug;
				// Add TOTP slug.
				$enabled_providers = array( $totp_slug );
				// If 'Enable lost phone' option is checked then we add the slug of the Email provider too.
				if ( true === $settings->lost_phone ) {
					$enabled_providers[] = \WP_Defender\Component\Two_Factor\Providers\Fallback_Email::$slug;
				}
				// Add slugs to the list of the enabled providers.
				$enabled_providers = array_intersect( $enabled_providers, array_keys( $providers ) );
				foreach ( $query->get_results() as $user_id ) {
					// If 2FA module is configured for the current user
					if ( $service->is_enabled_otp_for_user( $user_id ) ) {
						update_user_meta( $user_id, Two_Fa_Component::ENABLED_PROVIDERS_USER_KEY, $enabled_providers );
						// Set TOTP as the default auth provider.
						update_user_meta( $user_id, Two_Fa_Component::DEFAULT_PROVIDER_USER_KEY, $totp_slug );
					}
				}
			}
		}
	}

	/**
	 * Upgrade to 2.8.0.
	 *
	 * @since 2.8.0
	 * @return void
	 */
	private function upgrade_2_8_0() {
		// Add the modal "What's new".
		update_site_option( Feature_Modal::FEATURE_SLUG, true );
		$this->update_in_house_recipients_empty_role();
		$this->delete_tmp_geolite_file();
		$this->update_2fa_methods();
	}

	/**
	 * Add column country_iso_code and index to table.
	 *
	 * @since 2.8.1
	 * @return void
	 */
	private function add_country_iso_code_column() {
		if ( ! function_exists( 'maybe_add_column' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		global $wpdb;

		$table_name  = $wpdb->base_prefix . 'defender_lockout_log';
		$column_name = 'country_iso_code';
		$create_ddl  = "ALTER TABLE {$table_name} ADD {$column_name} CHAR(2) DEFAULT NULL";

		maybe_add_column( $table_name, $column_name, $create_ddl );

		$index_ddl = "CREATE INDEX {$column_name} ON {$table_name} ({$column_name})";

		$prev_val = $wpdb->hide_errors();
		$wpdb->query( $index_ddl );
		$wpdb->show_errors( $prev_val );
	}

	/**
	 * Upgrade to 2.8.1.
	 *
	 * @since 2.8.1
	 * @return void
	 */
	private function upgrade_2_8_1() {
		$this->add_country_iso_code_column();
	}
}
