<?php
/**
 * This file contains the logic for updating the WP Defender plugin.
 *
 * @package WP_Defender
 */

namespace WP_Defender;

use wpdb;
use WP_User_Query;
use WP_Filesystem_Base;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\User;
use WP_Defender\Component\Crypt;
use WP_Defender\Component\Firewall;
use WP_Defender\Component\Webauthn;
use WP_Defender\Model\Notification;
use Safe\Exceptions\SodiumException;
use WP_Defender\Component\Feature_Modal;
use WP_Defender\Controller\Data_Tracking;
use WP_Defender\Component\Backup_Settings;
use WP_Defender\Traits\Defender_Bootstrap;
use WP_Defender\Component\Legacy_Versions;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Model\Setting\Security_Headers;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Setting\Global_Ip_Lockout;
use WP_Defender\Component\Config\Config_Adapter;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Integrations\MaxMind_Geolocation;
use WP_Defender\Traits\Webauthn as Webauthn_Trait;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Notification\Tweak_Reminder;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Component\Two_Fa as Two_Fa_Component;
use WP_Defender\Component\Two_Factor\Providers\Totp;
use WP_Defender\Model\Setting\Two_Fa as Two_Fa_Settings;
use WP_Defender\Component\Security_Tweaks\Security_Key;
use WP_Defender\Model\Notification\Malware_Notification;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Component\Two_Factor\Providers\Fallback_Email;
use WP_Defender\Helper\Analytics\Firewall as Firewall_Analytics;
use WP_Defender\Model\Setting\Blacklist_Lockout as Model_Blacklist_Lockout;
use WP_Defender\Model\Setting\Firewall as Model_Firewall;
use WP_Defender\Component\User_Agent as User_Agent_Service;
use function WP_Filesystem;

/**
 * Upgrade the WP Defender plugin to the latest version.
 */
class Upgrader {

	use User;
	use Webauthn_Trait;
	use IO;
	use Defender_Bootstrap;

	/**
	 * Migrate old security headers from security tweaks. Trigger it once time.
	 *
	 * @return void
	 */
	public function migrate_security_headers(): void {
		$model  = wd_di()->get( Security_Headers::class );
		$option = get_site_option( $model->get_table() );

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
	 * If user upgrade from an older version to the latest version.
	 *
	 * @param  mixed $current_version  The current version.
	 *
	 * @return void
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
	 * Migrate configs for latest versions.
	 *
	 * @param  mixed $current_version  The current version.
	 *
	 * @return void
	 * @since 2.4
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
			$adapter       = wd_di()->get( Config_Adapter::class );
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
						// Import config 'strings' and the active tag if a config has it.
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

	/**
	 * Upgrade to version 2.4.2.
	 *
	 * @return void
	 */
	private function upgrade_2_4_2(): void {
		// Update Scan settings.
		$model_settings = wd_di()->get( Scan_Settings::class );
		$option         = get_site_option( $model_settings->get_table() );
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
			$tweak_sec_key = wd_di()->get( Security_Key::class );

			if ( is_array( $old_settings ) && isset( $old_settings['data']['securityReminderDuration'] )
				&& in_array(
					$old_settings['data']['securityReminderDuration'],
					$tweak_sec_key->reminder_frequencies(),
					true
				)
			) {
				$tweak_sec_key->update_option( 'reminder_duration', $old_settings['data']['securityReminderDuration'] );
			}
		}
	}

	/**
	 * Migrate value of scan setting from 'integrity_check' to 'check_core'.
	 *
	 * @return void
	 * @since 2.4.7
	 */
	private function migrate_scan_integrity_check(): void {
		$model             = new Scan_Settings();
		$model->check_core = (bool) $model->integrity_check;
		$model->save();
	}

	/**
	 * Run an upgrade/installation.
	 *
	 * @return void
	 * @throws SodiumException If the encryption fails.
	 */
	public function run() {
		// Sometimes multiple requests come at the same time. So we will only count the web requests.
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) {
			return;
		}

		$db_version = get_site_option( 'wd_db_version' );
		if ( empty( $db_version ) ) {
			update_site_option( 'wd_db_version', DEFENDER_DB_VERSION );
			update_site_option( Feature_Modal::FEATURE_SLUG, true );

			return;
		}

		if ( DEFENDER_DB_VERSION === $db_version ) {
			return;
		}
		$this->create_database_tables();
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
		if ( version_compare( $db_version, '2.5.6', '<' ) ) {
			$this->upgrade_2_5_6();
		}
		if ( version_compare( $db_version, '2.6.1', '<' ) ) {
			$this->upgrade_2_6_1();
		}
		if ( version_compare( $db_version, '2.7.0', '<' ) ) {
			$this->upgrade_2_7_0();
		}
		if ( version_compare( $db_version, '2.8.0', '<' ) ) {
			$this->upgrade_2_8_0();
		}
		if ( version_compare( $db_version, '2.8.3', '<' ) ) {
			$this->upgrade_2_8_3();
		}
		if ( version_compare( $db_version, '3.2.0', '<' ) ) {
			$this->upgrade_3_2_0();
		}
		if ( version_compare( $db_version, '3.3.0', '<' ) ) {
			$this->upgrade_3_3_0();
		}
		if ( version_compare( $db_version, '3.3.1', '<' ) ) {
			$this->upgrade_3_3_1();
		}
		if ( version_compare( $db_version, '3.3.3', '<' ) ) {
			$this->upgrade_3_3_3();
		}
		if ( version_compare( $db_version, '3.5.0', '<' ) ) {
			$this->upgrade_3_5_0();
		}
		if ( version_compare( $db_version, '3.8.0', '<' ) ) {
			$this->upgrade_3_8_0();
		}
		if ( version_compare( $db_version, '3.8.2', '<' ) ) {
			$this->upgrade_3_8_2();
		}
		if ( version_compare( $db_version, '3.9.0', '<' ) ) {
			$this->upgrade_3_9_0();
		}
		if ( version_compare( $db_version, '3.11.0', '<' ) ) {
			$this->upgrade_3_11_0();
		}
		if ( version_compare( $db_version, '3.12.0', '<' ) ) {
			$this->upgrade_3_12_0();
		}
		if ( version_compare( $db_version, '4.0.0', '<' ) ) {
			$this->upgrade_4_0_0();
		}
		if ( version_compare( $db_version, '4.1.0', '<' ) ) {
			$this->upgrade_4_1_0();
		}
		if ( version_compare( $db_version, '4.2.0', '<' ) ) {
			$this->upgrade_4_2_0();
		}
		if ( version_compare( $db_version, '4.5.1', '<' ) ) {
			$this->upgrade_4_5_1();
		}
		if ( version_compare( $db_version, '4.6.0', '<' ) ) {
			$this->upgrade_4_6_0();
		}
		if ( version_compare( $db_version, '4.7.2', '<' ) ) {
			$this->upgrade_4_7_2();
		}
		if ( version_compare( $db_version, '4.8.2', '<' ) ) {
			$this->upgrade_4_8_2();
		}
		if ( version_compare( $db_version, '4.9.0', '<' ) ) {
			$this->upgrade_4_9_0();
		}
		if ( version_compare( $db_version, '5.0.0', '<' ) ) {
			$this->upgrade_5_0_0();
		}
		if ( version_compare( $db_version, '5.0.2', '<' ) ) {
			$this->upgrade_5_0_2();
		}
		if ( version_compare( $db_version, '5.1.1', '<' ) ) {
			$this->upgrade_5_1_1();
		}
		if ( version_compare( $db_version, '5.2.0', '<' ) ) {
			$this->upgrade_5_2_0();
		}
		if ( version_compare( $db_version, '5.3.0', '<' ) ) {
			$this->upgrade_5_3_0();
		}
		if ( version_compare( $db_version, '5.3.1', '<' ) ) {
			$this->upgrade_5_3_1();
		}
		if ( version_compare( $db_version, '5.4.0', '<' ) ) {
			$this->upgrade_5_4_0();
		}
		if ( version_compare( $db_version, '5.5.0', '<' ) ) {
			$this->upgrade_5_5_0();
		}
		if ( version_compare( $db_version, '5.6.0', '<' ) ) {
			$this->upgrade_5_6_0();
		}
		// This is not a new installation. Make a mark.
		defender_no_fresh_install();
		// Don't run any function below this line.
		update_site_option( 'wd_db_version', DEFENDER_DB_VERSION );
	}

	/**
	 * Index necessary columns.
	 * Sometimes this function call twice that's why we have to check index already exists or not.
	 * `dbDelta` not work on `ALTER TABLE` query, so we had to use $wpdb->query().
	 *
	 * @return void
	 * @since 2.4.7
	 */
	private function index_database(): void {
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
	 * @param  wpdb $wpdb  WordPress Database object.
	 *
	 * @return void
	 * @since 2.4.7
	 */
	private function add_index_to_defender_email_log( $wpdb ) {
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->base_prefix}defender_email_log WHERE Key_name = 'source';", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( is_array( $result ) ) {
			return;
		}

		$wpdb->query( "ALTER TABLE {$wpdb->base_prefix}defender_email_log ADD INDEX `source` (`source`);" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Add index to defender_audit_log.
	 *
	 * @param  wpdb $wpdb  WordPress Database object.
	 *
	 * @return void
	 * @since 2.4.7
	 */
	private function add_index_to_defender_audit_log( $wpdb ) {
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->base_prefix}defender_audit_log WHERE Key_name = 'event_type';", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( is_array( $result ) ) {
			return;
		}

		$wpdb->query( "ALTER TABLE {$wpdb->base_prefix}defender_audit_log ADD INDEX `event_type` (`event_type`), ADD INDEX `action_type` (`action_type`), ADD INDEX `user_id` (`user_id`), ADD INDEX `context` (`context`), ADD INDEX `ip` (`ip`);" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Add index to defender_scan_item.
	 *
	 * @param  wpdb $wpdb  WordPress Database object.
	 *
	 * @return void
	 * @since 2.4.7
	 */
	private function add_index_to_defender_scan_item( $wpdb ) {
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->base_prefix}defender_scan_item WHERE Key_name = 'type';", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( is_array( $result ) ) {
			return;
		}

		$wpdb->query( "ALTER TABLE {$wpdb->base_prefix}defender_scan_item ADD INDEX `type` (`type`), ADD INDEX `status` (`status`);" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Add index to defender_lockout_log.
	 *
	 * @param  wpdb $wpdb  WordPress Database object.
	 *
	 * @return void
	 * @since 2.4.7
	 */
	private function add_index_to_defender_lockout_log( $wpdb ) {
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->base_prefix}defender_lockout_log WHERE Key_name = 'ip';", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( is_array( $result ) ) {
			return;
		}

		$wpdb->query( "ALTER TABLE {$wpdb->base_prefix}defender_lockout_log ADD INDEX `ip` (`ip`), ADD INDEX `type` (`type`), ADD INDEX `tried` (`tried`);" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Add index to defender_lockout.
	 *
	 * @param  wpdb $wpdb  WordPress Database object.
	 *
	 * @return void
	 * @since 2.4.7
	 */
	private function add_index_to_defender_lockout( $wpdb ) {
		// Check index already exists or not.
		$result = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->base_prefix}defender_lockout WHERE Key_name = 'ip';", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( is_array( $result ) ) {
			return;
		}

		$wpdb->query( "ALTER TABLE {$wpdb->base_prefix}defender_lockout ADD INDEX `ip` (`ip`), ADD INDEX `status` (`status`), ADD INDEX `attempt` (`attempt`), ADD INDEX `attempt_404` (`attempt_404`);" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Upgrade to 2.4.10.
	 *
	 * @return void
	 * @since 2.4.10
	 */
	private function upgrade_2_4_10(): void {
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
					$model         = new Model\Setting\Security_Tweaks();
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
	 * @return void
	 * @since 2.5.0
	 */
	private function upgrade_2_5_0(): void {
		$model = wd_di()->get( Security_Headers::class );
		// Directive ALLOW-FROM is deprecated. If header directive is ALLOW-FROM then set 'sameorigin'.
		if ( isset( $model->sh_xframe_mode ) && 'allow-from' === $model->sh_xframe_mode ) {
			$model->sh_xframe_mode = 'sameorigin';
			$model->save();
		}
		/**
		 * Uncheck 'File change detection' option if there was checked only child 'Scan theme files' option and save
		 * settings. Also remove items for 'Scan theme files' without run Scan.
		 */
		$scan_settings = new Scan_Settings();
		if (
			$scan_settings->integrity_check
			&& ! $scan_settings->check_core
			&& ! $scan_settings->check_plugins
		) {
			$scan_settings->integrity_check = false;
			$scan_settings->save();
		}
	}

	/**
	 * Forces the addition of default lockout exclusions to the Notfound Lockout settings.
	 *
	 * @return void
	 */
	private function force_nf_lockout_exclusions(): void {
		$nf_settings       = new Notfound_Lockout();
		$allowlist         = $nf_settings->get_lockout_list( 'allowlist' );
		$default_allowlist = array( '.css', '.js', '.map' );
		$is_save           = false;
		if ( ! empty( $allowlist ) ) {
			foreach ( $default_allowlist as $item ) {
				if ( ! in_array( $item, $allowlist, true ) ) {
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
	 * @return void
	 * @since 2.5.6
	 */
	private function upgrade_2_5_6(): void {
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
	}

	/**
	 * Updates the body of the scan error email template.
	 *
	 * @param  mixed $model  The model containing the email template configuration.
	 *
	 * @return void
	 */
	private function update_scan_error_send_body( $model ): void {
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
	 * @return void
	 * @since 2.6.1
	 */
	private function upgrade_2_6_1(): void {
		// Update the title of the basic config.
		$config_component = wd_di()->get( Backup_Settings::class );
		$configs          = $config_component->get_configs();
		if ( ! empty( $configs ) ) {
			foreach ( $configs as $k => $config ) {
				if ( 0 === strcmp( $config['name'], esc_html__( 'Basic config', 'wpdef' ) ) ) {
					$config['name'] = esc_html__( 'Basic Config', 'wpdef' );

					update_site_option( $k, $config );
					delete_site_transient( Config_Hub_Helper::CONFIGS_TRANSIENT_KEY );
					break;
				}
			}
		}
		// Update scan emails for 'When failed to scan' option.
		$malware_notification = wd_di()->get( Malware_Notification::class );
		$this->update_scan_error_send_body( $malware_notification );
		$malware_report = wd_di()->get( Malware_Report::class );
		$this->update_scan_error_send_body( $malware_report );
	}

	/**
	 * Update 2FA email template.
	 *
	 * @return void
	 */
	private function update_2fa_send_body(): void {
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
	 * @return void
	 * @since 2.7.0
	 */
	private function update_malware_scan_send_body(): void {
		$models = array(
			wd_di()->get( Malware_Notification::class ),
			wd_di()->get( Malware_Report::class ),
		);

		foreach ( $models as $model ) {
			$is_updated = false;
			if ( ! empty( $model->configs['template']['error']['body'] ) ) {
				$subject = $this->replace_scan_email_content_error( $model->configs['template']['error']['body'] );

				if ( $model->configs['template']['error']['body'] !== $subject ) {
					$is_updated = true;

					$model->configs['template']['error']['body'] = $subject;
				}
			}

			if ( ! empty( $model->configs['template']['found']['body'] ) ) {
				$subject = $this->replace_scan_email_content_issue_found( $model->configs['template']['found']['body'] );

				if ( $model->configs['template']['found']['body'] !== $subject ) {
					$is_updated = true;

					$model->configs['template']['found']['body'] = $subject;
				}
			}

			if ( ! empty( $model->configs['template']['not_found']['body'] ) ) {
				$subject = $this->replace_scan_email_content_issue_not_found( $model->configs['template']['not_found']['body'] );

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
	 * @param  string $subject  Email subject.
	 *
	 * @return string
	 * @since 2.7.0
	 */
	private function update_malware_scan_send_body_common( $subject ): string {
		$subject = preg_replace( '/(\R+)WP Defender here, reporting back from the front\./i', '', $subject );

		return preg_replace( '/(\R+)Stay Safe,(\R+)WP Defender(\R+)WPMU DEV Superhero(\s*)$/i', '', $subject );
	}

	/**
	 * Upgrade to 2.7.0.
	 *
	 * @return void
	 * @since 2.7.0
	 */
	private function upgrade_2_7_0(): void {
		$malware_report = wd_di()->get( Malware_Report::class );
		$scan_settings  = wd_di()->get( Scan_Settings::class );
		// Migrate data from Malware_Report to Scan settings.
		$scan_settings->scheduled_scanning = Notification::STATUS_ACTIVE === $malware_report->status;
		$scan_settings->frequency          = $malware_report->frequency;
		$scan_settings->day                = $malware_report->day;
		$scan_settings->day_n              = (int) $malware_report->day_n;
		$scan_settings->time               = $malware_report->time;
		$scan_settings->save();

		$this->update_2fa_send_body();
		$this->update_malware_scan_send_body();
	}

	/**
	 * Update in_house recipients empty role.
	 *
	 * @return void
	 * @since 2.8.0
	 */
	private function update_in_house_recipients_empty_role(): void {
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
						$is_updated        = true;
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
	 * @return void
	 * @since 2.8.0
	 */
	private function delete_tmp_geolite_file(): void {
		$pattern = ABSPATH . 'wp-admin/geolite2-country*.tar.gz';
		array_map( 'wp_delete_file', glob( $pattern ) );
	}

	/**
	 * Migrate old keys to checked options.
	 *
	 * @return void
	 * @since 2.8.0
	 */
	private function update_2fa_methods(): void {
		$settings = wd_di()->get( Two_Fa_Settings::class );
		if ( $settings->enabled ) {
			$service = wd_di()->get( Two_Fa_Component::class );
			$query   = new WP_User_Query(
				array(
					'blog_id'    => 0,
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => Totp::TOTP_AUTH_KEY,
							'value' => true,
						),
					),
					'fields'     => 'ID',
				)
			);
			if ( $query->get_total() > 0 ) {
				// The data is independent of the user's data.
				$providers = $service->get_providers();
				$totp_slug = Totp::$slug;
				// Add TOTP slug.
				$enabled_providers = array( $totp_slug );
				// If 'Enable lost phone' option is checked then we add the slug of the Email provider too.
				if ( true === $settings->lost_phone ) {
					$enabled_providers[] = Fallback_Email::$slug;
				}
				// Add slugs to the list of the enabled providers.
				$enabled_providers = array_intersect( $enabled_providers, array_keys( $providers ) );
				foreach ( $query->get_results() as $user_id ) {
					// If 2FA module is configured for the current user.
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
	 * @return void
	 * @since 2.8.0
	 */
	private function upgrade_2_8_0(): void {
		$this->update_in_house_recipients_empty_role();
		$this->delete_tmp_geolite_file();
		$this->update_2fa_methods();
	}

	/**
	 * Add column country_iso_code and index to table.
	 *
	 * @return void
	 * @since 2.8.1
	 */
	private function add_country_iso_code_column(): void {
		if ( ! function_exists( 'maybe_add_column' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		global $wpdb;

		$table_name  = $wpdb->base_prefix . 'defender_lockout_log';
		$column_name = 'country_iso_code';
		$create_ddl  = "ALTER TABLE {$table_name} ADD {$column_name} CHAR(2) DEFAULT NULL";

		maybe_add_column( $table_name, $column_name, $create_ddl );

		$prev_val = $wpdb->hide_errors();
		$wpdb->query( "CREATE INDEX `country_iso_code` ON {$wpdb->base_prefix}defender_lockout_log (`country_iso_code`)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->show_errors( $prev_val );
	}

	/**
	 * Upgrade to 2.8.3.
	 *
	 * @return void
	 * @since 2.8.3
	 */
	private function upgrade_2_8_3(): void {
		$this->add_country_iso_code_column();
	}

	/**
	 * Upgrade to 3.2.0.
	 *
	 * @return void
	 * @since 3.2.0
	 */
	private function upgrade_3_2_0(): void {
		$model = wd_di()->get( Two_Fa_Settings::class );

		if ( ! empty( $model->app_title ) ) {
			$model->app_title = wp_specialchars_decode( $model->app_title, ENT_QUOTES );
		}

		$model->custom_graphic_type = Two_Fa_Settings::CUSTOM_GRAPHIC_TYPE_UPLOAD;
		$model->save();
	}

	/**
	 * Upgrade to 3.3.0.
	 *
	 * @return void
	 * @since 3.3.0
	 */
	private function upgrade_3_3_0(): void {
		$this->update_webauthn_user_handle();
		$this->add_ua_lockout_to_firewall_notification();
	}

	/**
	 * Update webauthn 'userHandle' to make it independent of salt keys.
	 *
	 * @return void
	 * @since 3.3.0
	 */
	private function update_webauthn_user_handle(): void {
		global $wpdb;
		$service = wd_di()->get( Webauthn::class );

		if ( is_multisite() ) {
			$offset = 0;
			$limit  = 100;
			$blogs  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} LIMIT %d, %d", $offset, $limit ),
				ARRAY_A
			);
			while ( ! empty( $blogs ) && is_array( $blogs ) ) {
				foreach ( $blogs as $blog ) {
					switch_to_blog( $blog['blog_id'] );

					$this->update_webauthn_user_handle_core( $service );

					restore_current_blog();
				}
				$offset += $limit;
				$blogs   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} LIMIT %d, %d", $offset, $limit ),
					ARRAY_A
				);
			}
		} else {
			$this->update_webauthn_user_handle_core( $service );
		}
	}

	/**
	 * Core method for updating webauthn 'userHandle' to make it independent of salt keys.
	 *
	 * @param  object $service  Webauthn service instance.
	 *
	 * @return void
	 * @since 3.3.0
	 */
	private function update_webauthn_user_handle_core( object $service ) {
		$data = get_option( $this->option_prefix . $service::CREDENTIAL_OPTION_KEY );
		$data = is_string( $data ) ? json_decode( $data, true ) : array();
		delete_option( $this->option_prefix . $service::CREDENTIAL_OPTION_KEY );
		if ( ! is_array( $data ) || 0 === count( $data ) ) {
			return;
		}

		$users = get_users( array( 'fields' => array( 'ID', 'user_login', 'display_name' ) ) );

		foreach ( $users as $user ) {
			if ( empty( $data ) ) {
				break;
			}

			$user_credentials = array();
			foreach ( $data as $key => $item ) {
				$old_hash = hash( 'sha256', $user->user_login . '-' . $user->display_name . '-' . AUTH_SALT );
				// This is not obfuscation. Just encode the hashed value into a base64 string.
				$old_base64_hash = base64_encode( $old_hash ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				$old_base64_hash = preg_replace( '/\=+$/', '', $old_base64_hash );

				if (
					isset( $item['credential_source']['userHandle'] ) &&
					$item['credential_source']['userHandle'] === $old_base64_hash
				) {
					$new_hash = $this->get_user_hash( $user->user_login );
					// This is not obfuscation. Just encode the hashed value into a base64 string.
					$new_base64_hash                         = base64_encode( $new_hash ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					$new_base64_hash                         = preg_replace( '/\=+$/', '', $new_base64_hash );
					$item['user']                            = $new_hash;
					$item['credential_source']['userHandle'] = $new_base64_hash;
					$user_credentials[ $key ]                = $item;
					unset( $data[ $key ] );
				}
			}

			if ( ! empty( $user_credentials ) ) {
				$service->setCredentials( (int) $user->ID, $user_credentials );
			}
		}
	}

	/**
	 * Add the ability to send notifications about UA-lockout.
	 *
	 * @return void
	 */
	private function add_ua_lockout_to_firewall_notification(): void {
		$model = wd_di()->get( Firewall_Notification::class );
		if ( ! isset( $model->configs['ua_lockout'] ) ) {
			$model->configs['ua_lockout'] = false;
			$model->save();
		}
	}

	/**
	 * Upgrade to 3.3.1: extra security steps.
	 *
	 * @return void
	 */
	private function upgrade_3_3_1(): void {
		// Update tokens.
		$query = new WP_User_Query(
			array(
				'blog_id'    => 0,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => Two_Fa_Component::TOKEN_USER_KEY,
						'compare' => 'EXISTS',
					),
				),
				'fields'     => 'ID',
			)
		);
		if ( $query->get_total() > 0 ) {
			// Hashed tokens.
			foreach ( $query->get_results() as $user_id ) {
				$token = bin2hex( Crypt::random_bytes( 32 ) );
				update_user_meta( $user_id, Two_Fa_Component::TOKEN_USER_KEY, wp_hash( $user_id . $token ) );
			}
		}
		// Update fallback codes.
		$query = new WP_User_Query(
			array(
				'blog_id'    => 0,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => Fallback_Email::FALLBACK_BACKUP_CODE_KEY,
						'compare' => 'EXISTS',
					),
				),
				'fields'     => 'ID',
			)
		);
		if ( $query->get_total() > 0 ) {
			// Hashed codes.
			foreach ( $query->get_results() as $user_id ) {
				$backup_code = get_user_meta( $user_id, Fallback_Email::FALLBACK_BACKUP_CODE_KEY, true );
				if ( ! empty( $backup_code ) && isset( $backup_code['code'], $backup_code['time'] ) ) {
					update_user_meta(
						$user_id,
						Fallback_Email::FALLBACK_BACKUP_CODE_KEY,
						array(
							'code' => wp_hash( $backup_code['code'] ),
							'time' => $backup_code['time'],
						)
					);
				}
			}
		}
	}

	/**
	 * Upgrade to 3.3.3: update 2FA flow for secret keys.
	 *
	 * @return void
	 * @throws SodiumException If the encryption fails.
	 * @since 3.3.3
	 */
	private function upgrade_3_3_3(): void {
		// Create a file with a random key if it doesn't exist.
		if ( ( new Crypt() )->create_key_file() ) {
			// Get user data.
			$query = new WP_User_Query(
				array(
					'blog_id'    => 0,
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => Totp::TOTP_SODIUM_SECRET_KEY,
							'compare' => 'EXISTS',
						),
					),
					'fields'     => 'ID',
				)
			);
			if ( $query->get_total() > 0 ) {
				$service = wd_di()->get( Two_Fa_Component::class );
				foreach ( $query->get_results() as $user_id ) {
					// If we have the old states (cleartext, pub key) then re-encrypt via Sodium and save it.
					if ( ! $service->maybe_update( $user_id ) ) {
						// Otherwise just encrypt it via Sodium.
						$plaintext = defender_generate_random_string( Totp::TOTP_LENGTH, TOTP::TOTP_CHARACTERS );
						$new_key   = Crypt::get_encrypted_data( $plaintext );
						if ( ! is_wp_error( $new_key ) ) {
							// Remove an old key.
							delete_user_meta( $user_id, Totp::TOTP_SECRET_KEY );
							// Update a new one.
							update_user_meta( $user_id, Totp::TOTP_SODIUM_SECRET_KEY, $new_key );
						}
					}
				}
			}
		}
	}

	/**
	 * Upgrade to 3.5.0: Delete config transient.
	 *
	 * @return void
	 */
	private function upgrade_3_5_0(): void {
		delete_site_transient( Config_Hub_Helper::CONFIGS_TRANSIENT_KEY );
	}

	/**
	 * Upgrade to 3.8.0: Remove permanently banned ip record from `defender_lockout` table.
	 *
	 * @return void
	 * @since 3.8.0
	 */
	private function upgrade_3_8_0(): void {
		global $wpdb;

		$blacklist_settings = new Model_Blacklist_Lockout();
		$blacklist          = $blacklist_settings->get_list();

		$offset = 0;
		$length = 100;
		$table  = $wpdb->base_prefix . 'defender_lockout';
		// Variable within condition is for comparison.
		while ( $blacklist_chunk = array_slice( $blacklist, $offset, $length ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			$sql = "DELETE FROM {$table} WHERE status = 'blocked' AND ip IN (" . implode( ', ', array_fill( 0, count( $blacklist_chunk ), '%s' ) ) . ')';

			$query = call_user_func_array(
				array( $wpdb, 'prepare' ),
				array_merge( array( $sql ), $blacklist_chunk )
			);
			// SQL is prepared here. So we don't need to prepare it again.
			$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

			$offset += $length;
		}

		$this->update_malware_notification_config();
		$this->update_malware_notification_setting();
	}

	/**
	 * Update malware notification config.
	 *
	 * @return void
	 * @since 3.8.0
	 */
	private function update_malware_notification_config(): void {
		$service = wd_di()->get( Backup_Settings::class );
		$configs = Config_Hub_Helper::get_configs( $service );

		foreach ( $configs as $key => $config ) {
			$is_updated = false;

			if ( isset( $config['configs']['scan']['email_subject_issue_found'] ) ) {
				$subject = $this->replace_scan_email_subject_issue_found( $config['configs']['scan']['email_subject_issue_found'] );
				$configs[ $key ]['configs']['scan']['email_subject_issue_found'] = $subject;
				$is_updated = true;
			}
			if ( isset( $config['configs']['scan']['email_content_issue_found'] ) ) {
				$content = $this->replace_scan_email_content_issue_found( $config['configs']['scan']['email_content_issue_found'] );
				$content = $this->replace_scan_email_content_issue_found_new( $content );
				$configs[ $key ]['configs']['scan']['email_content_issue_found'] = $content;
				$is_updated = true;
			}

			if ( isset( $config['configs']['scan']['email_content_issue_not_found'] ) ) {
				$content = $this->replace_scan_email_content_issue_not_found( $config['configs']['scan']['email_content_issue_not_found'] );
				$configs[ $key ]['configs']['scan']['email_content_issue_not_found'] = $content;
				$is_updated = true;
			}

			if ( isset( $config['configs']['scan']['email_content_error'] ) ) {
				$content = $this->replace_scan_email_content_error( $config['configs']['scan']['email_content_error'] );
				$configs[ $key ]['configs']['scan']['email_content_error'] = $content;
				$is_updated = true;
			}

			if ( true === $is_updated ) {
				update_site_option( $key, $configs[ $key ] );
				Config_Hub_Helper::update_on_hub( $configs[ $key ] );
			}
		}

		delete_site_transient( Config_Hub_Helper::CONFIGS_TRANSIENT_KEY );
	}

	/**
	 * Update malware notification setting.
	 *
	 * @return void
	 * @since 3.8.0
	 */
	private function update_malware_notification_setting(): void {
		$models = array(
			wd_di()->get( Malware_Notification::class ),
			wd_di()->get( Malware_Report::class ),
		);

		foreach ( $models as $model ) {
			$is_updated = false;

			if ( isset( $model->configs['template']['found']['subject'] ) ) {
				$subject                                        = $this->replace_scan_email_subject_issue_found( $model->configs['template']['found']['subject'] );
				$model->configs['template']['found']['subject'] = $subject;
				$is_updated                                     = true;
			}
			if ( isset( $model->configs['template']['found']['body'] ) ) {
				$content                                     = $this->replace_scan_email_content_issue_found( $model->configs['template']['found']['body'] );
				$content                                     = $this->replace_scan_email_content_issue_found_new( $content );
				$model->configs['template']['found']['body'] = $content;
				$is_updated                                  = true;
			}

			if ( isset( $model->configs['template']['not_found']['body'] ) ) {
				$content = $this->replace_scan_email_content_issue_not_found( $model->configs['template']['not_found']['body'] );
				$model->configs['template']['not_found']['body'] = $content;
				$is_updated                                      = true;
			}

			if ( isset( $model->configs['template']['error']['body'] ) ) {
				$content                                     = $this->replace_scan_email_content_error( $model->configs['template']['error']['body'] );
				$model->configs['template']['error']['body'] = $content;
				$is_updated                                  = true;
			}

			if ( true === $is_updated ) {
				$model->save();
			}
		}
	}

	/**
	 * Replace scan issue found email subject.
	 *
	 * @param  string $subject  Email subject.
	 *
	 * @return string
	 * @since 3.8.0
	 */
	private function replace_scan_email_subject_issue_found( string $subject ): string {
		$subject = str_replace(
			esc_html__( 'Scan of {SITE_URL} complete.', 'wpdef' ),
			esc_html__( 'Malware Scan of {SITE_URL} is complete.', 'wpdef' ),
			$subject
		);

		return str_replace(
			esc_html__( '{ISSUES_COUNT} issues found.', 'wpdef' ),
			esc_html__( '{ISSUES_COUNT} issue(s) found.', 'wpdef' ),
			$subject
		);
	}

	/**
	 * Replace scan issue found email content.
	 *
	 * @param  string $content  Email content.
	 *
	 * @return string
	 * @since 3.8.0
	 */
	private function replace_scan_email_content_issue_found( string $content ): string {
		$content = $this->update_malware_scan_send_body_common( $content );
		$content = preg_replace(
			"/I(’|'|\\\')ve finished scanning \{SITE_URL\} for vulnerabilities and I found \{ISSUES_COUNT\} issues that you should take a closer look at!/i",
			'{ISSUES_COUNT} vulnerabilities were identified for {SITE_URL} during a Malware Scan. See details for each issue below.',
			$content
		);

		return preg_replace( '/(\R+){ISSUES_LIST}/i', "\n\n{ISSUES_LIST}", $content );
	}

	/**
	 * Replace scan issue found email with new content.
	 *
	 * @param  string $content  Email content.
	 *
	 * @return string
	 * @since 3.8.0
	 */
	private function replace_scan_email_content_issue_found_new( string $content ): string {
		$content = str_replace(
			esc_html__( '{ISSUES_COUNT} vulnerabilities were identified for {SITE_URL} during a Malware Scan.', 'wpdef' ),
			esc_html__( 'Malware Scan identified {ISSUES_COUNT} issue(s) on {SITE_URL}.', 'wpdef' ),
			$content
		);

		return str_replace(
			esc_html__( 'See details for each issue below.', 'wpdef' ),
			esc_html__( 'The identified issue(s) is/are listed below.', 'wpdef' ),
			$content
		);
	}

	/**
	 * Replace scan issue not found email content.
	 *
	 * @param  string $content  Email content.
	 *
	 * @return string
	 * @since 3.8.0
	 */
	private function replace_scan_email_content_issue_not_found( string $content ): string {
		$content = $this->update_malware_scan_send_body_common( $content );
		$content = preg_replace(
			"/I(’|'|\\\')ve finished scanning \{SITE_URL\} for vulnerabilities and I found nothing\. Well done for running such a tight ship!/i",
			'No vulnerabilities have been found for {SITE_URL}.',
			$content
		);

		return preg_replace(
			"/(\R+)Keep up the good work! With regular security scans and a well-hardened installation you\'ll be just fine\./i",
			'',
			$content
		);
	}

	/**
	 * Replace scan error email content.
	 *
	 * @param  string $content  Email content.
	 *
	 * @return string
	 * @since 3.8.0
	 */
	private function replace_scan_email_content_error( string $content ): string {
		$content = $this->update_malware_scan_send_body_common( $content );

		return str_replace( 'I couldn', 'We couldn', $content );
	}

	/**
	 * Upgrade to 3.8.2: Check XSS in existing configs.
	 *
	 * @return void
	 */
	private function upgrade_3_8_2(): void {
		$service = wd_di()->get( Backup_Settings::class );
		$configs = Config_Hub_Helper::get_configs( $service );

		foreach ( $configs as $key => $config ) {
			$is_updated = false;

			if ( isset( $config['name'] ) ) {
				$prev_name = $config['name'];
				// Sanitize data.
				$new_name = sanitize_text_field( $config['name'] );
				if ( $prev_name !== $new_name ) {
					$is_updated = true;
				}
				$prev_desc = $config['description'];
				$new_desc  = empty( $config['description'] ) ? '' : sanitize_textarea_field( $config['description'] );
				if ( $prev_desc !== $new_desc ) {
					$is_updated = true;
				}
			}

			if ( true === $is_updated ) {
				update_site_option( $key, $configs[ $key ] );
				Config_Hub_Helper::update_on_hub( $configs[ $key ] );
			}
		}

		delete_site_transient( Config_Hub_Helper::CONFIGS_TRANSIENT_KEY );
	}

	/**
	 * Upgrade to 3.9.0.
	 *
	 * @return void
	 */
	private function upgrade_3_9_0(): void {
		global $wpdb;

		$prev_val = $wpdb->hide_errors();
		// Changes for Lockout table.
		$wpdb->query( "ALTER TABLE{$wpdb->base_prefix}defender_lockout MODIFY COLUMN ip VARCHAR(45)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		// Changes for Lockout log table.
		$wpdb->query( "ALTER TABLE{$wpdb->base_prefix}defender_lockout_log MODIFY COLUMN ip VARCHAR(45)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		// Changes for Audit log table.
		$wpdb->query( "ALTER TABLE{$wpdb->base_prefix}defender_audit_log MODIFY COLUMN ip VARCHAR(45)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->show_errors( $prev_val );
	}

	/**
	 * Upgrade to 3.11.0.
	 *
	 * @return void
	 */
	private function upgrade_3_11_0(): void {
		// Move Global IP settings.
		$option = get_site_option( wd_di()->get( Model_Blacklist_Lockout::class )->get_table() );
		if ( ! empty( $option ) && is_string( $option ) ) {
			$old_settings = json_decode( $option, true );
			if ( is_array( $old_settings ) && isset( $old_settings['global_ip_list'] ) ) {
				$model_global_ip          = wd_di()->get( Global_Ip_Lockout::class );
				$model_global_ip->enabled = (bool) $old_settings['global_ip_list'];
				$model_global_ip->save();
			}
		}
	}

	/**
	 * Upgrade to 3.12.0.
	 *
	 * @return void
	 */
	private function upgrade_3_12_0(): void {
		delete_site_transient( Config_Hub_Helper::CONFIGS_TRANSIENT_KEY );

		$service = wd_di()->get( Backup_Settings::class );
		$configs = Config_Hub_Helper::get_configs( $service );

		foreach ( $configs as $key => &$config ) {
			if ( ! is_array( $config ) ) {
				continue;
			}

			$config['is_removable'] = $config['is_removable'] ?? true;

			// Update config data.
			update_site_option( $key, $config );
		}

		set_site_transient(
			Config_Hub_Helper::CONFIGS_TRANSIENT_KEY,
			$configs,
			Config_Hub_Helper::CONFIGS_TRANSIENT_TIME
		);
		update_site_option( Config_Hub_Helper::CONFIGS_TRANSIENT_TIME_KEY, time() );

		// Clear Global IP schedule hook to update event frequency.
		wp_clear_scheduled_hook( 'wpdef_fetch_global_ip_list' );
	}

	/**
	 * Upgrade to 4.0.0: Creates quarantine table.
	 *
	 * @return void
	 */
	private function upgrade_4_0_0(): void {
		$bootstrap = wd_di()->get( Bootstrap::class );
		$bootstrap->create_table_quarantine();
	}

	/**
	 * Upgrade to 4.1.0.
	 *
	 * @return void
	 */
	private function upgrade_4_1_0() {
		// Remove Maxmind DB directory from multisite subsites.
		if ( is_multisite() ) {
			global $wpdb, $wp_filesystem;

			if ( is_null( $wp_filesystem ) ) {
				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}
				WP_Filesystem();
			}
			if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
				return;
			}

			$main_site_id = get_main_site_id();
			$offset       = 0;
			$limit        = 100;
			$blogs        = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT blog_id FROM {$wpdb->blogs} WHERE blog_id != %d LIMIT %d, %d",
					$main_site_id,
					$offset,
					$limit
				),
				ARRAY_A
			);
			while ( ! empty( $blogs ) && is_array( $blogs ) ) {
				foreach ( $blogs as $blog ) {
					switch_to_blog( $blog['blog_id'] );

					$maxmind_dir = $this->get_tmp_path() . DIRECTORY_SEPARATOR . MaxMind_Geolocation::DB_DIRECTORY;
					$wp_filesystem->delete( $maxmind_dir, true );

					restore_current_blog();
				}
				$offset += $limit;
				$blogs   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"SELECT blog_id FROM {$wpdb->blogs} WHERE blog_id != %d LIMIT %d, %d",
						$main_site_id,
						$offset,
						$limit
					),
					ARRAY_A
				);
			}
		}
	}

	/**
	 * Upgrade to 4.2.0.
	 *
	 * @return void
	 */
	private function upgrade_4_2_0(): void {
		// Add the Tracking modal.
		update_site_option( Data_Tracking::TRACKING_SLUG, true );
	}

	/**
	 * Upgrade to 4.5.1.
	 *
	 * @return void
	 */
	private function upgrade_4_5_1(): void {
		$service = wd_di()->get( Firewall::class );
		$service->auto_switch_ip_detection_option();
		$service->maybe_show_misconfigured_ip_detection_option_notice();
	}

	/**
	 * Upgrade to 4.6.0.
	 *
	 * @return void
	 */
	private function upgrade_4_6_0(): void {
		// Create Unlockout table.
		$bootstrap = wd_di()->get( Bootstrap::class );
		$bootstrap->create_table_unlockout();
	}

	/**
	 * Upgrade to 4.7.2.
	 *
	 * @return void
	 */
	private function upgrade_4_7_2(): void {
		$model = wd_di()->get( Two_Fa_Settings::class );

		if ( isset( $model->email_body ) ) {
			$model->email_body = 'Hi {{display_name}},

Your temporary password is {{passcode}}
To complete your login, copy and paste the temporary password into the Password field on the login screen.';
			$model->save();
		}
	}

	/**
	 * Upgrade to 4.8.2.
	 *
	 * @return void
	 */
	private function upgrade_4_8_2(): void {
		$xff = defender_get_data_from_request( 'HTTP_X_FORWARDED_FOR', 's' );
		if (
			! ( is_string( $xff ) && 0 < strlen( $xff ) ) &&
			Firewall::is_switched_ip_detection_notice( Firewall::IP_DETECTION_XFF_SHOW_SLUG )
		) {
			delete_site_option( Firewall::IP_DETECTION_XFF_SHOW_SLUG );
		}
	}

	/**
	 * Upgrade to 4.9.0.
	 *
	 * @return void
	 */
	private function upgrade_4_9_0(): void {
		$model = wd_di()->get( Model_Firewall::class );

		$model->ip_detection_type = 'manual';
		$model->save();

		// Add tracking.
		$firewall_analytics = wd_di()->get( Firewall_Analytics::class );
		$detection_method   = Firewall_Analytics::get_detection_method_label(
			$model->ip_detection_type,
			$model->http_ip_header
		);

		$firewall_analytics->track_feature(
			Firewall_Analytics::EVENT_IP_DETECTION,
			array( Firewall_Analytics::PROP_IP_DETECTION => $detection_method )
		);
	}

	/**
	 * Upgrade.
	 *
	 * @return void
	 */
	private function upgrade_5_0_0(): void {
		update_site_option( \WP_Defender\Component\IP\Antibot_Global_Firewall::NOTICE_SLUG, true );
	}

	/**
	 * Upgrade to 5.0.2: Clear the blocklist count. Also set the whitelist server public IP.
	 *
	 * @return void
	 */
	private function upgrade_5_0_2(): void {
		delete_site_transient( 'wpdef_antibot_global_firewall_db_blocklist_count' );
		wd_di()->get( Firewall::class )->set_whitelist_server_public_ip();
	}

	/**
	 * Upgrade to 5.1.1: Migrate IP detection option to Automatic from Manual > All headers.
	 *
	 * @return void
	 */
	private function upgrade_5_1_1(): void {
		$model = wd_di()->get( Model_Firewall::class );
		if ( 'manual' === $model->ip_detection_type && '' === $model->http_ip_header ) {
			$model->ip_detection_type = 'automatic';
			$model->http_ip_header    = 'REMOTE_ADDR';
			$model->save();
			wd_di()->get( \WP_Defender\Component\Smart_Ip_Detection::class )->smart_ip_detection_ping();
			// Add tracking.
			$firewall_analytics = wd_di()->get( Firewall_Analytics::class );
			$detection_method   = Firewall_Analytics::get_detection_method_label( 'automatic', '' );

			$firewall_analytics->track_feature(
				Firewall_Analytics::EVENT_IP_DETECTION,
				array( Firewall_Analytics::PROP_IP_DETECTION => $detection_method )
			);
		}
	}
	/**
	 * Update UA blocklist.
	 *
	 * @return void
	 */
	private function update_ua_blocklist(): void {
		$settings  = wd_di()->get( User_Agent_Lockout::class );
		$blacklist = $settings->get_lockout_list( 'blocklist', false );
		if ( empty( $blacklist ) ) {
			return;
		}
		$blacklist           = array_filter(
			$blacklist,
			function ( $agent ) {
				return false === stripos( $agent, 'ahrefsbot' ) && false === stripos( $agent, 'semrushbot' );
			}
		);
		$settings->blacklist = implode( "\n", $blacklist ); // Convert back to string.
		$settings->save();
	}

	/**
	 * Upgrade to 5.2.0.
	 *
	 * @return void
	 */
	private function upgrade_5_2_0(): void {
		$this->update_ua_blocklist();
		// Remove the prev Breadcrumbs.
		wd_di()->get( \WP_Defender\Controller\Strong_Password::class )->remove_data();
		// Add the "What's new" modal.
		update_site_option( Feature_Modal::FEATURE_SLUG, true );
	}

	/**
	 * Upgrade to 5.3.0.
	 *
	 * @return void
	 */
	private function upgrade_5_3_0(): void {
		// Add the "What's new" modal.
		update_site_option( Feature_Modal::FEATURE_SLUG, true );
		// Add composite index to the defender_lockout table.
		global $wpdb;
		// Check if the index already exists.
		$wpdb->query( "SHOW INDEX FROM {$wpdb->base_prefix}defender_lockout_log WHERE Key_name = 'idx_ip_date'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( 0 === $wpdb->num_rows ) {
			// If the index does not exist, create it.
			$prev_val = $wpdb->hide_errors();
			$wpdb->query( "ALTER TABLE {$wpdb->base_prefix}defender_lockout_log ADD INDEX idx_ip_date (ip, date DESC);" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->show_errors( $prev_val );
		}
	}

	/**
	 * Upgrade to 5.3.1.
	 *
	 * @return void
	 */
	private function upgrade_5_3_1(): void {
		delete_site_transient( \WP_Defender\Component\IP\Antibot_Global_Firewall::BLOCKLIST_STATS_KEY );
	}

	/**
	 * Improve UA Blocklist.
	 *
	 * @return void
	 */
	private function improve_ua_blocklist(): void {
		$settings         = wd_di()->get( User_Agent_Lockout::class );
		$blocklist_custom = $settings->get_lockout_list( 'blocklist' );
		if ( empty( $blocklist_custom ) ) {
			return;
		}
		// Get 'Blocklist Presets', check and remove duplicates on 'Custom User Agents'.
		$blocklist_presets = User_Agent_Service::get_nested_keys_of_blocklist_presets();
		$common_result     = array_intersect( $blocklist_custom, $blocklist_presets );
		if ( ! empty( $common_result ) ) {
			$blocklist_custom = User_Agent_Service::check_and_remove_duplicates(
				$blocklist_custom,
				$common_result
			);
			// Convert back to string.
			$settings->blacklist = implode( PHP_EOL, $blocklist_custom );
			// Enable option with nested suboptions.
			$settings->blocklist_presets       = true;
			$settings->blocklist_preset_values = $common_result;
		}
		// The same, but for 'Script Presets'.
		$script_presets = array_keys( User_Agent_Service::get_script_presets() );
		$common_result  = array_intersect( $blocklist_custom, $script_presets );
		if ( ! empty( $common_result ) ) {
			$blocklist_custom = User_Agent_Service::check_and_remove_duplicates(
				$blocklist_custom,
				$common_result
			);
			// Convert back to string.
			$settings->blacklist = implode( PHP_EOL, $blocklist_custom );
			// Enable option with nested suboptions.
			$settings->script_presets       = true;
			$settings->script_preset_values = $common_result;
		}
		$settings->save();
	}

	/**
	 * Upgrade to 5.4.0.
	 *
	 * @return void
	 */
	private function upgrade_5_4_0(): void {
		update_site_option( Feature_Modal::FEATURE_SLUG, true );

		$this->improve_ua_blocklist();
	}

	/**
	 * Upgrade to 5.5.0.
	 *
	 * @return void
	 */
	private function upgrade_5_5_0(): void {
		update_site_option( Feature_Modal::FEATURE_SLUG, true );
	}

	/**
	 * Change lockout log mentions from fake_bot to malicious_bot. Also move BotTrap settings.
	 *
	 * @return void
	 */
	private function change_to_malicious_bot(): void {
		global $wpdb;

		$table_name = $wpdb->base_prefix . 'defender_lockout_log';

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $table_name SET type = %s, log = %s WHERE type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'malicious_bot',
				'Lockout occurred: Bot ignored robots.txt rules.',
				'bot_trap'
			)
		);
		// Clear schedule as the name has changed to 'wpdef_rotate_malicious_bot_secret_hash'.
		wp_clear_scheduled_hook( 'wpdef_rotate_bot_trap_secret_hash' );
		// Move the BotTrap settings to new Malicious Bot settings.
		$settings = wd_di()->get( User_Agent_Lockout::class );
		if ( isset( $settings->bot_trap_enabled ) ) {
			$settings->malicious_bot_enabled               = $settings->bot_trap_enabled;
			$settings->malicious_bot_lockout_type          = $settings->bot_trap_lockout_type;
			$settings->malicious_bot_lockout_duration      = $settings->bot_trap_lockout_duration;
			$settings->malicious_bot_lockout_duration_unit = $settings->bot_trap_lockout_duration_unit;
			$settings->save();
		}
	}

	/**
	 * Upgrade to 5.6.0.
	 *
	 * @return void
	 */
	private function upgrade_5_6_0(): void {
		$this->change_to_malicious_bot();
		// Remove the prev Breadcrumbs.
		wd_di()->get( \WP_Defender\Component\Breadcrumbs::class )->delete_previous_meta();
		// Add the "What's new" modal.
		update_site_option( Feature_Modal::FEATURE_SLUG, true );
	}
}