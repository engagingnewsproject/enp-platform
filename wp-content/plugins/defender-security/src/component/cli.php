<?php

namespace WP_Defender\Component;

use Faker\Factory;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Traits\Formats;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Cli
 * @package WP_Defender\Component
 */
class Cli {
	use Formats {
		calculate_date_interval as protected;
		format_bytes_into_readable as protected;
		format_date_time as protected;
		get_date as protected;
		get_days_of_week as protected;
		get_times as protected;
		get_timezone_string as protected;
		local_to_utc as protected;
		moment_datetime_format_from as protected;
		persistent_hub_datetime_format as protected;
		time_since as protected;
	}

	/**
	 *
	 * This is a helper for scan module.
	 * #Options
	 * <command>
	 * : Value can be run - Perform a scan, or (un)ignore|delete|resolve to do the relevant task,
	 * or clear_logs to remove completed schedule logs.
	 *
	 * [--type=<type>]
	 * : Default is all, or core_integrity|plugin_integrity|vulnerability|suspicious_code
	 *
	 * @param $args
	 * @param $options
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function scan( $args, $options ) {
		if ( empty( $args ) ) {
			\WP_CLI::error( 'Invalid command' );
		}
		list( $command ) = $args;
		switch ( $command ) {
			case 'run':
				$this->scan_all();
				break;
			case 'clear_logs':
				$this->scan_clear_logs();
				break;
			default:
				$commands = array(
					'ignore',
					'unignore',
					'resolve',
					'delete',
				);
				if ( in_array( $command, $commands, true ) ) {
					\WP_CLI::confirm(
						'This can cause your site get fatal error and can\'t restore back unless you have a backup, are you sure to continue?',
						$options
					);
					$this->scan_task( $command, $options );
				} else {
					\WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				}
				break;
		}
	}

	/**
	 * Scan different modules with different options.
	*/
	private function scan_task( $task, $options ) {
		$type = isset( $options['type'] ) ? $options['type'] : null;
		switch ( $type ) {
			case 'core_integrity':
				$type = Scan_Item::TYPE_INTEGRITY;
				break;
			case 'plugin_integrity':
				$type = Scan_Item::TYPE_PLUGIN_CHECK;
				break;
			case 'vulnerability':
				$type = Scan_Item::TYPE_VULNERABILITY;
				break;
			case 'suspicious_code':
				$type = Scan_Item::TYPE_SUSPICIOUS;
				break;
			default:
				\WP_CLI::error( sprintf( 'Unknown scan type %s', $type ) );
				break;
		}
		$active = \WP_Defender\Model\Scan::get_active();
		if ( is_object( $active ) ) {
			return \WP_CLI::error( 'A scan is running, you need to wait till it complete to continue' );
		}
		$model = \WP_Defender\Model\Scan::get_last();
		if ( ! is_object( $model ) ) {
			return;
		}
		switch ( $task ) {
			case 'ignore':
				$issues = $model->get_issues( $type, Scan_Item::STATUS_ACTIVE );
				foreach ( $issues as $issue ) {
					$model->ignore_issue( $issue->id );
					\WP_CLI::log( sprintf( 'Ignoring file: %s', $issue->raw_data['file'] ) );
				}
				\WP_CLI::log( sprintf( 'Ignored %s items', count( $issues ) ) );
				break;
			case 'unignore':
				$issues = $model->get_issues( $type, Scan_Item::STATUS_IGNORE );
				foreach ( $issues as $issue ) {
					$model->unignore_issue( $issue->id );
					\WP_CLI::log( sprintf( 'Unignoring file: %s', $issue->raw_data['file'] ) );
				}
				\WP_CLI::log( sprintf( 'Unignored %s items', count( $issues ) ) );
				break;
			case 'resolve':
				$items    = $model->get_issues( $type, Scan_Item::STATUS_ACTIVE );
				$resolved = array();
				foreach ( $items as $item ) {
					if (
						in_array(
							$item->type,
							array( Scan_Item::TYPE_INTEGRITY, Scan_Item::TYPE_PLUGIN_CHECK ),
							true
						)
					) {
						\WP_CLI::log( sprintf( 'Reverting %s to original', $item->raw_data['file'] ) );
						$ret = $item->resolve();
						if ( ! is_wp_error( $ret ) ) {
							$resolved[] = $item;
						} else {
							return \WP_CLI::error( $ret->get_error_message() );
						}
					} elseif ( Scan_Item::TYPE_SUSPICIOUS === $item->type ) {
						// If this is content, we will try to delete them.
						$whitelist  = array(
							//wordfence waf
							ABSPATH . '/wordfence-waf.php',
							// Any files inside plugins, if removed, can cause fatal error.
							WP_CONTENT_DIR . '/plugins/',
							// Any files inside themes.
							WP_CONTENT_DIR . '/themes/',
						);
						$path       = $item->raw_data['file'];
						$can_delete = true;
						$current    = '';
						foreach ( $whitelist as $value ) {
							$current = $value;
							if ( strpos( $value, $path ) > 0 ) {
								// Ignore this.
								$can_delete = false;
								break;
							}
						}
						if ( false === $can_delete ) {
							\WP_CLI::log( sprintf( 'Ignore file %s as it is in %s', $path, $current ) );
						} else {
							if ( @unlink( $path ) ) {
								\WP_CLI::log( sprintf( 'Delete file %s', $path ) );
								$model->remove_issue( $item->id );
								$resolved[] = $item;
							} else {
								return \WP_CLI::error( sprintf( "Can't delete file %s", $path ) );
							}
						}
					}
				}
				\WP_CLI::log( sprintf( 'Resolved %s items', count( $resolved ) ) );
				break;
			case 'delete':
				$items   = $model->get_issues( $type, Scan_Item::STATUS_ACTIVE );
				$deleted = array();
				foreach ( $items as $item ) {
					$path = $item->raw_data['file'];
					if ( @unlink( $path ) ) {
						\WP_CLI::log( sprintf( 'Delete file %s', $path ) );
						$model->remove_issue( $item->id );
						$deleted[] = $item;
					} else {
						return \WP_CLI::error( sprintf( "Can't delete file %s", $path ) );
					}
				}
				\WP_CLI::log( sprintf( 'Deleted %s items', count( $deleted ) ) );
				break;
			default:
				break;
		}
	}

	/**
	 * Generate dummy data, use in cypress & unit test.
	 * DO NOT USE IN PRODUCTION.
	 *
	 * @param $args
	 * @param $options
	 */
	public function seed( $args, $options ) {
		if ( empty( $args ) ) {
			\WP_CLI::error( 'Invalid command' );
		}
		list( $command ) = $args;
		switch ( $command ) {
			case 'scan:core':
				file_put_contents( ABSPATH . 'wp-load.php', '//this make different', FILE_APPEND );
				break;
			case 'audit:logs':
				$faker = Factory::create();
				for ( $i = 0; $i < 500; $i ++ ) {
					$log            = new Audit_Log();
					$log->timestamp = mt_rand( strtotime( '-31 days' ), time() );
				}
				break;
			case 'ip:logs':
				// We will generate randomly 10k logs in 3 months.
				$types        = array(
					Lockout_Log::AUTH_FAIL,
					Lockout_Log::AUTH_LOCK,
					Lockout_Log::ERROR_404,
					Lockout_Log::LOCKOUT_404,
					Lockout_Log::LOCKOUT_UA,
				);
				$is_lock      = array(
					Lockout_Log::AUTH_LOCK,
					Lockout_Log::LOCKOUT_404,
					Lockout_Log::LOCKOUT_UA,
				);
				$faker        = Factory::create();
				$range        = array(
					'today midnight' => array( 'now', 100 ),
					'-6 days'        => array( 'yesterday', 50 ),
					'-30 days'       => array( '-7 days', 70 ),
				);
				$counter      = array(
					'last_24_hours' => 0,
					'last_30_days'  => 0,
					'login_lockout' => 0,
					'404_lockout'   => 0,
					'ua_lockout'    => 0,
				);
				$last_lockout = 0;
				foreach ( $range as $date => $to ) {
					list( $to, $count ) = $to;
					for ( $i = 0; $i < $count; $i ++ ) {
						$model          = new Lockout_Log();
						$model->ip      = $faker->ipv4;
						$model->type    = $types[ array_rand( $types ) ];
						$model->log     = $faker->sentence( 20 );
						$model->date    = $faker->dateTimeBetween( $date, $to )->getTimestamp();
						$model->blog_id = 1;
						$model->tried   = $faker->userName;// phpcs:ignore
						$model->save();
						if ( ( $model->date > $last_lockout ) ) {
							$last_lockout = $model->date;
						}
						if ( in_array( $model->type, $is_lock, true ) ) {
							$counter['last_30_days'] += 1;
							if ( $model->date > strtotime( 'yesterday midnight' ) ) {
								$counter['last_24_hours'] += 1;
							}
							if ( $model->date > strtotime( '-6 days', strtotime( 'today midnight' ) ) ) {
								if ( Lockout_Log::AUTH_LOCK === $model->type ) {
									$counter['login_lockout'] += 1;
								} elseif ( Lockout_Log::LOCKOUT_404 === $model->type ) {
									$counter['404_lockout'] += 1;
								} else {
									$counter['ua_lockout'] += 1;
								}
							}
						}
					}
				}
				$counter['last_lockout'] = $this->format_date_time( $last_lockout );
				echo json_encode( $counter );
				break;
			default:
				break;
		}
	}

	/**
	 * Clean up dummy data.
	 *
	 * @param $args
	 * @param $options
	 */
	public function unseed( $args, $options ) {
		if ( empty( $args ) ) {
			\WP_CLI::error( 'Invalid command' );
		}
		list( $command ) = $args;
		switch ( $command ) {
			case 'scan:core':
				$content = file_get_contents( ABSPATH . 'wp-load.php' );
				file_put_contents( ABSPATH . 'wp-load.php', str_replace( '//this make different', '', $content ) );
				break;
			case 'scan:suspicious':
				@unlink( WP_CONTENT_DIR . '/false-positive.php' );
				break;
			default:
				break;
		}
	}

	/**
	 *
	 * Clears the audit log from Database.
	 *
	 * <command> reset
	 * This command must have this command
	 *
	 * syntax: wp defender audit <command>
	 * example: wp defender audit reset
	 *
	 * @param $args
	 * @param $options
	 */
	public function audit( $args, $options ) {
		if ( empty( $args ) ) {
			\WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
			\WP_CLI::runcommand( 'defender audit --help' );

			return;
		}
		list( $command ) = $args;
		switch ( $command ) {
			case 'reset':
				Audit_Log::truncate();
				delete_site_option( 'wd_audit_fetch_checkpoint' );

				\WP_CLI::log( 'All clear' );
				break;
			default:
				\WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
				\WP_CLI::runcommand( 'defender audit --help' );
				break;
		}
	}

	private function scan_all() {
		\WP_CLI::log( 'Check if there is a scan ongoing...' );
		$scan = \WP_Defender\Model\Scan::get_active();
		if ( ! is_object( $scan ) ) {
			\WP_CLI::log( 'No active scan, creating...' );
			$scan = \WP_Defender\Model\Scan::create();
			if ( is_wp_error( $scan ) ) {
				return \WP_CLI::error( $scan->get_error_message() );
			}
		} else {
			\WP_CLI::log( 'Continue from last scan' );
		}
		$handler = new Scan();
		$ret     = false;
		while ( $handler->process() === false ) {}
		\WP_CLI::success( 'All done!' );
	}

	/**
	 *
	 * This is a helper for Security header actions.
	 * #Options
	 * <command>
	 * : Value can be run - Check headers, or activate|deactivate all headers
	 *
	 * [--type=<type>]
	 * : Default is all
	 *
	 * ## EXAMPLES
	 * wp defender security_headers check
	 *
	 * @param $args
	 * @param $options
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function security_headers( $args, $options ) {
		if ( empty( $args ) ) {
			\WP_CLI::error( 'Invalid command.' );
		}
		$model = new \WP_Defender\Model\Setting\Security_Headers();
		if ( ! is_object( $model ) ) {
			\WP_CLI::error( 'Invalid model.' );

			return;
		}
		list( $command ) = $args;
		switch ( $command ) {
			case 'check':
				$i = 1;
				foreach ( $model->get_headers() as $header ) {
					$state = true === $header->check() ? 'enabled' : 'disabled';
					\WP_CLI::log( sprintf( '#%s - %s is %s', $i, $header->get_title(), $state ) );
					$i ++;
				}
				\WP_CLI::success( 'Checking is ready.' );
				break;
			case 'activate':
				foreach ( $model->get_headers() as $header ) {
					$model->{$header::$rule_slug} = true;
				}
				$model->save();
				\WP_CLI::log( 'Activating is ready.' );
				break;
			case 'deactivate':
				foreach ( $model->get_headers() as $header ) {
					$model->{$header::$rule_slug} = false;
				}
				$model->save();
				\WP_CLI::log( 'Deactivating is ready.' );
				break;
			default:
				\WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				break;
		}
	}

	/**
	 * This is a helper command to reset plugin settings.
	 * #Options
	 * <command>
	 * Only allowed value is reset.
	 *
	 * syntax: wp defender settings <command>
	 * example: wp defender settings reset
	 *
	 * @param $args
	 * @param $options
	 */
	public function settings( $args, $options ) {
		if ( empty( $args ) ) {
			\WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
			\WP_CLI::runcommand( 'defender settings --help' );

			return;
		}

		list( $command ) = $args;
		switch ( $command ) {
			case 'reset':
				\WP_CLI::confirm(
					'This will completely reset the plugin settings, are you sure to continue?',
					$options
				);
				// Analog Settings > Reset Settings.
				wd_di()->get( \WP_Defender\Controller\Advanced_Tools::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Audit_Logging::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Dashboard::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Security_Tweaks::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Scan::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Firewall::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Firewall_Logs::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Login_Lockout::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Nf_Lockout::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Mask_Login::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Notification::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Tutorial::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Two_Factor::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Blocklist_Monitor::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Main_Setting::class )->remove_settings();
				\WP_CLI::log( 'All cleared!' );
				break;
			default:
				\WP_CLI::log( sprintf( 'Unknown command %s, use correct arguments. See below...', $command ) );
				\WP_CLI::runcommand( 'defender settings --help' );
				break;
		}
	}

	/**
	 *
	 * This clears the firewall data or unlocks the IP from block list.
	 *
	 * <command> clear|unblock
	 *
	 * <args_1> Allowed values are: ip and files
	 * <args_2> Allowed values are: allowlist, blocklist, country_allowlist, country_blocklist and lockout
	 *
	 * syntax: wp defender firewall <command> <args_1> <args_2>
	 * example: wp defender firewall clear ip allowlist
	 * example: wp defender firewall unblock ip lockout --ips=127.0.0.1,236.211.38.221
	 *
	 * @param $args
	 * @param $options
	 */
	public function firewall( $args, $options ) {
		if ( count( $args ) <= 2 ) {
			\WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
			\WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		list( $command, $type, $field ) = $args;
		switch ( $command ) {
			case 'clear':
				$this->clear_firewall( $type, $field, $options );
				break;
			case 'unblock':
				$this->unblock_firewall( $type, $field, $options );
				break;
			default:
				\WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				break;
		}
	}

	/**
	 *
	 * This clears the mask login settings.
	 *
	 * <command> clear
	 * This command must have this command
	 *
	 * syntax: wp defender mask_login <command>
	 * example: wp defender mask_login clear
	 *
	 * @param $args
	 * @param $options
	 */
	public function mask_login( $args, $options ) {
		if ( count( $args ) < 1 ) {
			\WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
			\WP_CLI::runcommand( 'defender mask_login --help' );

			return;
		}

		list( $command ) = $args;
		switch ( $command ) {
			case 'clear':
				wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class )->delete();
				\WP_CLI::log( 'Mask login settings cleared!' );
				break;
			default:
				\WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				break;
		}
	}

	/**
	 * Clear the firewall data with different options.
	 */
	private function clear_firewall( $type, $field, $options ) {
		$type  = ! empty( $type ) ? $type : null;
		$field = ! empty( $field ) ? $field : null;

		$type_default  = array( 'ip', 'files' );
		$field_default = array( 'blocklist', 'allowlist', 'country_allowlist', 'country_blocklist' );

		if ( ! in_array( $type, $type_default, true ) ) {
			\WP_CLI::log( sprintf( 'Invalid option %s,. See below...', $type ) );
			\WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		if ( ! in_array( $field, $field_default, true ) ) {
			\WP_CLI::log( sprintf( 'Invalid option %s. See below...', $field ) );
			\WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		// Rename the field's name to original model field name.
		$original_field = $this->rename_field( $field );
		if ( 'ip' === $type ) {
			// Get the model instance.
			$model = wd_di()->get( \WP_Defender\Model\Setting\Blacklist_Lockout::class );
			$data  = $model->export();
			// Rename the field to match with the appropriate model field name.
			$mod_field = $this->is_country( $original_field ) ? $original_field : 'ip_' . $original_field;
			// Reset to default data with correct data type.
			$default_data       = $this->is_country( $original_field ) ? array() : '';
			$data[ $mod_field ] = $default_data; // empty the $field option field data
			$model->import( $data );
			$model->save();
		} elseif ( 'files' === $type ) {
			// Get the model instance
			$model                   = wd_di()->get( \WP_Defender\Model\Setting\Notfound_Lockout::class );
			$data                    = $model->export();
			// Empty the $field option field data.
			$data[ $original_field ] = '';
			$model->import( $data );
			$model->save();
		}

		\WP_CLI::log( sprintf( 'Firewall %s %s cleared', str_replace( '_', ' ', $field ), $type ) );
	}

	/**
	 * Unblock the IP(s) from block list.
	 */
	private function unblock_firewall( $type, $field, $options ) {
		$type = ! empty( $type ) ? $type : null;

		$type_default  = array( 'ip' );
		$field_default = array( 'lockout' );

		if ( ! in_array( $type, $type_default, true ) ) {
			\WP_CLI::log( sprintf( 'Invalid option %s,. See below...', $type ) );
			\WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		if ( ! in_array( $field, $field_default, true ) ) {
			\WP_CLI::log( sprintf( 'Invalid option %s. See below...', $field ) );
			\WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		if ( array_key_exists( 'ips', $options ) ) {
			$ips = array_map( 'trim', explode( ',', $options['ips'] ) );
			$models = Lockout_Ip::get_bulk( Lockout_Ip::STATUS_BLOCKED, $ips );

			foreach ( $models as $model ) {
				$model->status = Lockout_Ip::STATUS_NORMAL;
				$model->save();
			}
		} else {
			\WP_CLI::log( 'Option \'ips\' is not provided. See below...' );
			\WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		\WP_CLI::log( sprintf( 'Firewall %s %s unblocked', str_replace( '_', ' ', $field ), $type ) );
	}

	/**
	 * Check if the field is country allowlist or country blocklist.
	 */
	private function rename_field( $field ) {
		if ( ! empty( $field ) ) {
			return str_replace( array( 'allow', 'block' ), array( 'white', 'black' ), $field );
		}
		return '';
	}

	/**
	 * Check if the field is country allowlist or country blocklist.
	 */
	private function is_country( $field ) {
		return ( 'country_whitelist' === $field || 'country_blacklist' === $field );
	}

	/**
	 *
	 * Force Bulk Password Reset.
	 *
	 * <command>
	 * : Value can be force|undo
	 *
	 * syntax: wp defender password_reset <command>
	 * example: wp defender password_reset force
	 *
	 * @param $args
	 * @param $options
	 */
	public function password_reset( $args, $options ) {
		if ( count( $args ) < 1 ) {
			\WP_CLI::log( 'Invalid command.' );

			return;
		}

		list( $command ) = $args;
		switch ( $command ) {
			case 'force':
				// Get the model instance.
				$model = wd_di()->get( \WP_Defender\Model\Setting\Password_Reset::class );
				$model->expire_force = true;
				$model->force_time = time();
				$model->save();
				$message = sprintf(
					'Passwords created before %s are required to be reset upon next login.',
					$this->format_date_time( $model->force_time )
				);
				\WP_CLI::log( $message );
				break;
			case 'undo':
				$model = wd_di()->get( \WP_Defender\Model\Setting\Password_Reset::class );
				$model->expire_force = false;
				$model->save();
				\WP_CLI::log( 'Passwords reset is no longer required.' );
				break;
			default:
				\WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				break;
		}
	}

	/**
	 * Clear completed action scheduler logs.
	 */
	private function scan_clear_logs() {
		$result  = \WP_Defender\Component\Scan::clear_logs();
		$message = isset( $result['success'] ) ?
			$result['success'] :
			(
				isset( $result['error'] ) ?
				$result['error'] :
				'Malware scan logs are cleared'
			);

		\WP_CLI::log( $message );
	}
}
