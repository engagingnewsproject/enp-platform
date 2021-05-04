<?php

namespace WP_Defender;

use WP_Defender\Component\Config\Config_Hub_Helper;

class Upgrader {

	/**
	 * Migrate old security headers from security tweaks. Trigger it once time
	 */
	public function migrate_security_headers() {
		$model   = new \WP_Defender\Model\Setting\Security_Headers();
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
						if ( 'allow-from' === $mode ) {
							$model->sh_xframe_mode = 'allow-from';
							if ( isset( $header_data['values'] ) && ! empty( $header_data['values'] ) ) {
								$urls                  = explode( ' ', $header_data['values'] );
								$model->sh_xframe_urls = implode( PHP_EOL, $urls );
							}
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
					//Save
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
		$feature_version = '2.4';

		if ( version_compare( $current_version, $feature_version, '<' ) ) {
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
			$config_component = wd_di()->get( \WP_Defender\Component\Backup_Settings::class );
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

	/**
	 *
	 * Migrate value of scan setting from 'integrity_check' to 'check_core'.
	 *
	 * @since 2.4.7
	 * @return void
	 */
	private function migrate_scan_integrity_check() {
		$model             = new \WP_Defender\Model\Setting\Scan();
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

		if ( version_compare( $db_version, '2.4.7', '<' ) ) {
			$this->index_database();
			$this->migrate_scan_integrity_check();
		}
		if ( version_compare( $db_version, '2.4.10', '<' ) ) {
			$this->upgrade_2_4_10();
		}

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
	 * Add index to defender_lockout
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
	 *
	 * @since 2.4.10
	 */
	private function upgrade_2_4_10() {
		$service         = wd_di()->get( \WP_Defender\Component\Backup_Settings::class );
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
			//Remove deprecated 'data' key inside Security tweaks
			if ( isset( $config['configs']['security_tweaks']['data'] ) ) {
				unset( $configs[ $key ]['configs']['security_tweaks']['data'] );
				$is_updated = true;
			}
			//Remove deprecated keys in 'issues'
			if ( isset( $config['configs']['security_tweaks']['issues'] ) ) {
				foreach ( $config['configs']['security_tweaks']['issues'] as $iss_key => $issue ) {
					if ( in_array( $issue, $deprecated_keys, true ) ) {
						unset( $configs[ $key ]['configs']['security_tweaks']['issues'][ $iss_key ] );
						$is_updated = true;
					}
				}
			}
			//in 'ignore'
			if ( isset( $config['configs']['security_tweaks']['ignore'] ) ) {
				foreach ( $config['configs']['security_tweaks']['ignore'] as $ign_key => $issue ) {
					if ( in_array( $issue, $deprecated_keys, true ) ) {
						unset( $configs[ $key ]['configs']['security_tweaks']['ignore'][ $ign_key ] );
						$is_updated = true;
					}
				}
			}
			//and in 'fixed'
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
}
