<?php
/**
 * This file contains the Cli class which is used to handle WP-CLI commands for the WP Defender plugin.
 * It provides methods to manage scans, audits, firewall settings, and more through the command line.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_CLI;
use Exception;
use Countable;
use Throwable;
use Faker\Factory;
use WP_CLI\ExitException;
use WP_Defender\Traits\Theme;
use WP_Defender\Traits\Formats;
use WP_Defender\Model\Audit_Log;
use WP_Defender\Model\Scan_Item;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Controller\Tutorial;
use WP_Defender\Controller\Dashboard;
use WP_Defender\Controller\Two_Factor;
use WP_Defender\Model\Scan as Model_Scan;
use WP_Defender\Controller\Main_Setting;
use WP_Defender\Controller\Audit_Logging;
use WP_Defender\Controller\Advanced_Tools;
use WP_Defender\Controller\Security_Tweaks;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Password_Reset;
use WP_Defender\Controller\Blocklist_Monitor;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Setting\Security_Headers;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Component\Logger\Rotation_Logger;
use function WP_CLI\Utils\format_items;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Cli
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
	use Theme;

	/**
	 * This is a helper for scan module.
	 * #Options
	 * <command>
	 * : Value can be run - Perform a scan, e.g. 'run'-command or 'run ----type=detailed' for detailed result,
	 * or (un)ignore|delete|resolve to do the relevant task,
	 * or clear_logs to remove completed schedule logs.
	 * [--type=<type>]
	 * : Default, without values, is for all items, or core_integrity|plugin_integrity|vulnerability|suspicious_code.
	 *
	 * @param  mixed $args  Command arguments.
	 * @param  mixed $options  Command options.
	 *
	 * @throws ExitException If an invalid command is provided.
	 */
	public function scan( $args, $options ) {
		if ( empty( $args ) ) {
			WP_CLI::error( 'Invalid command' );

			return;
		}
		[ $command ] = $args;
		switch ( $command ) {
			case 'run':
				$this->scan_all( $options );
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
					WP_CLI::confirm(
						'This can cause your site get fatal error and can\'t restore back unless you have a backup, are you sure to continue?',
						$options
					);
					$this->scan_task( $command, $options );
				} else {
					WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				}
				break;
		}
	}

	/**
	 * Executes tasks based on the type of scan.
	 *
	 * @param  mixed $task  The task to perform.
	 * @param  mixed $options  Command options.
	 */
	private function scan_task( $task, $options ) {
		$type = $options['type'] ?? null;
		switch ( $type ) {
			case null:
				// All items.
				$type = null;
				break;
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
				WP_CLI::error( sprintf( 'Unknown scan type %s', $type ) );
				break;
		}
		$active = Model_Scan::get_active();
		if ( is_object( $active ) ) {
			return WP_CLI::error( 'A scan is running, you need to wait till it complete to continue' );
		}
		$model = Model_Scan::get_last();
		if ( ! is_object( $model ) ) {
			return;
		}
		switch ( $task ) {
			case 'ignore':
				$issues = $model->get_issues( $type, Scan_Item::STATUS_ACTIVE );
				foreach ( $issues as $issue ) {
					$model->ignore_issue( $issue->id );
					WP_CLI::log( sprintf( 'Ignoring file: %s', $issue->raw_data['file'] ) );
				}
				WP_CLI::log( sprintf( 'Ignored %s items', count( $issues ) ) );
				break;
			case 'unignore':
				$issues = $model->get_issues( $type, Scan_Item::STATUS_IGNORE );
				foreach ( $issues as $issue ) {
					$model->unignore_issue( $issue->id );
					WP_CLI::log( sprintf( 'Unignoring file: %s', $issue->raw_data['file'] ) );
				}
				WP_CLI::log( sprintf( 'Unignored %s items', count( $issues ) ) );
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
						WP_CLI::log( sprintf( 'Reverting %s to original', $item->raw_data['file'] ) );
						$ret = $item->resolve();
						if ( ! is_wp_error( $ret ) ) {
							$resolved[] = $item;
						} else {
							return WP_CLI::error( $ret->get_error_message() );
						}
					} elseif ( Scan_Item::TYPE_SUSPICIOUS === $item->type ) {
						// If this is content, we will try to delete them.
						$whitelist  = array(
							// wordfence waf.
							ABSPATH . '/wordfence-waf.php',
							// Any files inside plugins, if removed, can cause fatal error.
							WP_CONTENT_DIR . '/plugins/',
							// Any files inside themes.
							$this->get_path_of_themes_dir(),
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
							WP_CLI::log( sprintf( 'Ignore file %s as it is in %s', $path, $current ) );
						} elseif ( wp_delete_file( $path ) ) {
							WP_CLI::log( sprintf( 'Delete file %s', $path ) );
							$model->remove_issue( $item->id );
							$resolved[] = $item;
						} else {
							return WP_CLI::error( sprintf( "Can't delete file %s", $path ) );
						}
					}
				}
				WP_CLI::log( sprintf( 'Resolved %s items', count( $resolved ) ) );
				break;
			case 'delete':
				$items   = $model->get_issues( $type, Scan_Item::STATUS_ACTIVE );
				$deleted = array();
				foreach ( $items as $item ) {
					$path = $item->raw_data['file'];
					if ( wp_delete_file( $path ) ) {
						WP_CLI::log( sprintf( 'Delete file %s', $path ) );
						$model->remove_issue( $item->id );
						$deleted[] = $item;
					} else {
						return WP_CLI::error( sprintf( "Can't delete file %s", $path ) );
					}
				}
				WP_CLI::log( sprintf( 'Deleted %s items', count( $deleted ) ) );
				break;
			default:
				break;
		}
	}

	/**
	 * Generate dummy data, use in cypress & unit test.
	 * DO NOT USE IN PRODUCTION.
	 *
	 * @param  mixed $args  Command arguments.
	 */
	public function seed( $args ) {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if ( empty( $args ) ) {
			WP_CLI::error( 'Invalid command' );

			return;
		}
		[ $command ] = $args;
		switch ( $command ) {
			case 'scan:core':
				$file_path = ABSPATH . 'wp-load.php';
				$content   = '//this make different';
				if ( $wp_filesystem->exists( $file_path ) ) {
					$content = $wp_filesystem->get_contents( $file_path ) . $content;
				}
				$wp_filesystem->put_contents( $file_path, $content );
				break;
			case 'audit:logs':
				$types = array(
					Audit_Log::EVENT_TYPE_USER,
					Audit_Log::EVENT_TYPE_SYSTEM,
					Audit_Log::EVENT_TYPE_COMMENT,
					Audit_Log::EVENT_TYPE_MEDIA,
					Audit_Log::EVENT_TYPE_SETTINGS,
					Audit_Log::EVENT_TYPE_CONTENT,
					Audit_Log::EVENT_TYPE_MENU,
				);
				$faker = Factory::create();
				for ( $i = 0; $i < 500; $i++ ) {
					$log              = new Audit_Log();
					$log->timestamp   = Crypt::random_int( strtotime( '-31 days' ), time() );
					$log->event_type  = $types[ array_rand( $types ) ];
					$log->action_type = $faker->word();
					$log->site_url    = $faker->url();
					$log->user_id     = $faker->numberBetween( 1, 1000 );
					$log->context     = $faker->word();
					$log->ip          = $faker->ipv4();
					$log->msg         = $faker->word();
					$log->blog_id     = $faker->numberBetween( 1, 100 );
					$log->synced      = $faker->numberBetween( 0, 1 );
					$log->ttl         = $faker->numberBetween( 1, 3600 );
					$log->save();
				}
				break;
			case 'ip:logs':
				// We will generate randomly 10k logs in 3 months.
				$types   = array(
					Lockout_Log::AUTH_FAIL,
					Lockout_Log::AUTH_LOCK,
					Lockout_Log::ERROR_404,
					Lockout_Log::LOCKOUT_404,
					Lockout_Log::LOCKOUT_UA,
				);
				$is_lock = array(
					Lockout_Log::AUTH_LOCK,
					Lockout_Log::LOCKOUT_404,
					Lockout_Log::LOCKOUT_UA,
				);
				$faker   = Factory::create();
				WP_CLI::log( $faker->ipv4 );
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
					[ $to, $count ] = $to;
					for ( $i = 0; $i < $count; $i++ ) {
						$model                   = new Lockout_Log();
						$model->ip               = $faker->ipv4;
						$model->type             = $types[ array_rand( $types ) ];
						$model->log              = $faker->sentence( 20 );
						$model->date             = $faker->dateTimeBetween( $date, $to )->getTimestamp();
						$model->blog_id          = 1;
						$model->tried            = $faker->userName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$model->country_iso_code = $faker->countryCode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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
				echo wp_json_encode( $counter );
				break;
			default:
				WP_CLI::error( 'Invalid command' );
				break;
		}
	}

	/**
	 * Clean up dummy data.
	 *
	 * @param  mixed $args  Command arguments.
	 */
	public function unseed( $args ) {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if ( empty( $args ) ) {
			WP_CLI::error( 'Invalid command' );

			return;
		}
		[ $command ] = $args;
		switch ( $command ) {
			case 'scan:core':
				$content = file_get_contents( ABSPATH . 'wp-load.php' );
				$wp_filesystem->put_contents( ABSPATH . 'wp-load.php', str_replace( '//this make different', '', $content ) );
				break;
			case 'scan:suspicious':
				wp_delete_file( WP_CONTENT_DIR . '/false-positive.php' );
				break;
			default:
				break;
		}
	}

	/**
	 * Clears the audit log from Database.
	 * <command> reset
	 * This command must have this command
	 * Syntax: wp defender audit <command>
	 * Example: wp defender audit reset
	 *
	 * @param  mixed $args  Command arguments.
	 */
	public function audit( $args ) {
		if ( empty( $args ) ) {
			WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
			WP_CLI::runcommand( 'defender audit --help' );

			return;
		}
		[ $command ] = $args;
		switch ( $command ) {
			case 'reset':
				Audit_Log::truncate();
				delete_site_option( 'wd_audit_fetch_checkpoint' );

				WP_CLI::log( 'All clear' );
				break;
			default:
				WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
				WP_CLI::runcommand( 'defender audit --help' );
				break;
		}
	}

	/**
	 * Starts a full scan based on the provided options.
	 *
	 * @param  array $options  Command options.
	 */
	private function scan_all( $options ) {
		$type        = $options['type'] ?? null;
		$is_detailed = false;
		switch ( $type ) {
			case null:
				// All items.
				$type = null;
				break;
			case 'detailed':
				$is_detailed = true;
				break;
			default:
				WP_CLI::error( sprintf( 'Unknown scan type %s', $type ) );
				break;
		}
		WP_CLI::log( 'Check if there is a scan ongoing...' );
		$scan = Model_Scan::get_active();
		if ( ! is_object( $scan ) ) {
			WP_CLI::log( 'No active scan, creating...' );
			$scan = Model_Scan::create();
			if ( is_wp_error( $scan ) ) {
				return WP_CLI::error( $scan->get_error_message() );
			}
		} else {
			WP_CLI::log( 'Continue from last scan' );
		}
		// Start detailed scan.
		if ( $is_detailed ) {
			$start = microtime( true );
		}
		$handler = wd_di()->get( Scan::class );
		$ret     = false;
		while ( $handler->process() === false ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedWhile
		}
		$scan = Model_Scan::get_last();
		if ( ! is_object( $scan ) || is_wp_error( $scan ) ) {
			return;
		}
		$results = $scan->to_array();
		if ( is_array( $results ) && ! empty( $results['issues_items'] ) ) {
			$count = is_array( $results['issues_items'] ) || $results['issues_items'] instanceof Countable
				? count( $results['issues_items'] )
				: 0;
			// Finish detailed scan.
			if ( $is_detailed ) {
				format_items( 'table', $results['issues_items'], array( 'type', 'short_desc', 'full_path' ) );
				WP_CLI::log( sprintf( 'Saved %d items.', $count ) );
				$finish = microtime( true ) - $start;
				WP_CLI::log( 'Scan takes ' . round( $finish, 2 ) . 's to process.' );
			} else {
				WP_CLI::log( sprintf( 'Found %d issues.', $count ) );
			}
		}
		WP_CLI::success( 'All done!' );
	}

	/**
	 * This is a helper for Security header actions.
	 * #Options
	 * <command>
	 * : Value can be run - Check headers, or activate|deactivate all headers
	 * [--type=<type>]
	 * : Default is all
	 * ## EXAMPLES
	 * wp defender security_headers check
	 *
	 * @param  mixed $args  Command arguments.
	 *
	 * @throws ExitException|Exception If an invalid command is provided.
	 */
	public function security_headers( $args ) {
		if ( empty( $args ) ) {
			WP_CLI::error( 'Invalid command.' );

			return;
		}
		$model = new Security_Headers();
		if ( ! is_object( $model ) ) {
			WP_CLI::error( 'Invalid model.' );

			return;
		}
		[ $command ] = $args;
		switch ( $command ) {
			case 'check':
				$i = 1;
				foreach ( $model->get_headers() as $header ) {
					$state = true === $header->check() ? 'enabled' : 'disabled';
					WP_CLI::log( sprintf( '#%s - %s is %s', $i, $header->get_title(), $state ) );
					++$i;
				}
				WP_CLI::success( 'Checking is ready.' );
				break;
			case 'activate':
				foreach ( $model->get_headers() as $header ) {
					$model->{$header::$rule_slug} = true;
				}
				$model->save();
				WP_CLI::log( 'Activating is ready.' );
				break;
			case 'deactivate':
				foreach ( $model->get_headers() as $header ) {
					$model->{$header::$rule_slug} = false;
				}
				$model->save();
				WP_CLI::log( 'Deactivating is ready.' );
				break;
			default:
				WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				break;
		}
	}

	/**
	 * This is a helper command to reset plugin settings.
	 * #Options
	 * <command>
	 * Only allowed value is reset.
	 * Syntax: wp defender settings <command>
	 * Example: wp defender settings reset
	 *
	 * @param  mixed $args  Command arguments.
	 * @param  mixed $options  Command options.
	 */
	public function settings( $args, $options ) {
		if ( empty( $args ) ) {
			WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
			WP_CLI::runcommand( 'defender settings --help' );

			return;
		}

		[ $command ] = $args;
		switch ( $command ) {
			case 'reset':
				WP_CLI::confirm(
					'This will completely reset the plugin settings, are you sure to continue?',
					$options
				);
				// Analog Settings > Reset Settings.
				wd_di()->get( Advanced_Tools::class )->remove_settings();
				wd_di()->get( Audit_Logging::class )->remove_settings();
				wd_di()->get( Dashboard::class )->remove_settings();
				wd_di()->get( Security_Tweaks::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Scan::class )->remove_settings();
				// Parent and submodules.
				wd_di()->get( \WP_Defender\Controller\Firewall::class )->remove_settings();

				wd_di()->get( \WP_Defender\Controller\Mask_Login::class )->remove_settings();
				wd_di()->get( \WP_Defender\Controller\Notification::class )->remove_settings();
				wd_di()->get( Tutorial::class )->remove_settings();
				wd_di()->get( Two_Factor::class )->remove_settings();
				wd_di()->get( Blocklist_Monitor::class )->remove_settings();
				wd_di()->get( Main_Setting::class )->remove_settings();
				WP_CLI::log( 'All cleared!' );
				break;
			default:
				WP_CLI::log( sprintf( 'Unknown command %s, use correct arguments. See below...', $command ) );
				WP_CLI::runcommand( 'defender settings --help' );
				break;
		}
	}

	/**
	 * This toggle the firewall submodules, clears the data, show details or unlocks the IP from block list.
	 * Syntax: wp defender firewall <command> <args_1> <args_2>
	 * <command> clear|unblock|list|activate|deactivate
	 *
	 * <args_1> Allowed values are: ip, user_agent, files and maxmind
	 * <args_2> Allowed values are: allowlist, blocklist, country_allowlist, country_blocklist, lockout and license_key
	 * Example: wp defender firewall clear ip allowlist
	 * Example: wp defender firewall unblock ip lockout --ips=127.0.0.1,236.211.38.221
	 * Example: wp defender firewall list user_agent <status>
	 * Example: wp defender firewall activate submodule <submodule>
	 * Example: wp defender firewall deactivate submodule login_protection
	 * <status> Allowed values are: all, allowlist, blocklist.
	 * <submodule> Allowed values are: login_protection, 404_detection or user_agent.
	 *
	 * @param  mixed $args  Command arguments.
	 * @param  mixed $options  Command options.
	 */
	public function firewall( $args, $options ) {
		if ( ( is_array( $args ) || $args instanceof Countable ? count( $args ) : 0 ) <= 2 ) {
			WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		[ $command, $type, $field ] = $args;
		if ( empty( $type ) || empty( $field ) ) {
			WP_CLI::log( 'Invalid option.' );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}
		switch ( $command ) {
			case 'clear':
				$this->clear_firewall( $type, $field );
				break;
			case 'unblock':
				$this->unblock_firewall( $type, $field, $options );
				break;
			case 'list':
				$this->list_firewall( $type, $field );
				break;
			case 'activate':
				$this->toggle_firewall_submodule( $type, $field, 'activate' );
				break;
			case 'deactivate':
				$this->toggle_firewall_submodule( $type, $field, 'deactivate' );
				break;
			default:
				WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				break;
		}
	}

	/**
	 * This clears the mask login settings.
	 * <command> clear
	 * This command must have this command
	 * Syntax: wp defender mask_login <command>
	 * Example: wp defender mask_login clear
	 *
	 * @param  mixed $args  Command arguments.
	 */
	public function mask_login( $args ) {
		if ( ( is_array( $args ) || $args instanceof Countable ? count( $args ) : 0 ) < 1 ) {
			WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
			WP_CLI::runcommand( 'defender mask_login --help' );

			return;
		}

		[ $command ] = $args;
		switch ( $command ) {
			case 'clear':
				wd_di()->get( \WP_Defender\Model\Setting\Mask_Login::class )->delete();
				WP_CLI::log( 'Mask login settings cleared!' );
				break;
			default:
				WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				break;
		}
	}

	/**
	 * Clears the firewall data based on the specified type and field.
	 *
	 * @param  string $type  The type of data to clear.
	 * @param  string $field  The specific field to clear.
	 */
	private function clear_firewall( $type, $field ) {
		$type_default  = array( 'ip', 'files', 'user_agent', 'maxmind' );
		$field_default = array( 'blocklist', 'allowlist', 'country_allowlist', 'country_blocklist', 'license_key' );

		if ( ! in_array( $type, $type_default, true ) ) {
			WP_CLI::log( sprintf( 'Invalid option %s. See below...', $type ) );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		if ( ! in_array( $field, $field_default, true ) ) {
			WP_CLI::log( sprintf( 'Invalid option %s. See below...', $field ) );
			WP_CLI::runcommand( 'defender firewall --help' );

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
			$default_data = $this->is_country( $original_field ) ? array() : '';
			// Empty the $field option of field data.
			$data[ $mod_field ] = $default_data;
			$model->import( $data );
			$model->save();
		} elseif ( 'files' === $type ) {
			// Get the model instance.
			$model = wd_di()->get( Notfound_Lockout::class );
			$data  = $model->export();
			// Empty the $field option of field data.
			$data[ $original_field ] = '';
			$model->import( $data );
			$model->save();
		} elseif ( 'user_agent' === $type ) {
			$model                   = wd_di()->get( User_Agent_Lockout::class );
			$data                    = $model->export();
			$data[ $original_field ] = '';
			$model->import( $data );
			$model->save();
		} elseif ( 'maxmind' === $type ) {
			try {
				$model = wd_di()->get( \WP_Defender\Model\Setting\Blacklist_Lockout::class );
				if ( ! is_null( $model->geodb_path ) && is_file( $model->geodb_path ) ) {
					wp_delete_file( $model->geodb_path );
				}
				$model->maxmind_license_key = '';
				$model->geodb_path          = null;
				$model->save();
			} catch ( Throwable $th ) {
				WP_CLI::log( $th->getMessage() );
			}
		}

		WP_CLI::log( sprintf( 'Firewall %s %s is cleared.', str_replace( '_', ' ', $field ), $type ) );
	}

	/**
	 * Unblocks the specified IPs from the firewall.
	 *
	 * @param  string $type  The type of data to unblock.
	 * @param  string $field  The specific field to unblock.
	 * @param  array  $options  Command options including IPs to unblock.
	 */
	private function unblock_firewall( $type, $field, $options ) {
		$type_default  = array( 'ip' );
		$field_default = array( 'lockout' );

		if ( ! in_array( $type, $type_default, true ) ) {
			WP_CLI::log( sprintf( 'Invalid option %s. See below...', $type ) );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		if ( ! in_array( $field, $field_default, true ) ) {
			WP_CLI::log( sprintf( 'Invalid option %s. See below...', $field ) );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		if ( array_key_exists( 'ips', $options ) ) {
			$ips    = array_map( 'trim', explode( ',', $options['ips'] ) );
			$models = Lockout_Ip::get_bulk( Lockout_Ip::STATUS_BLOCKED, $ips );

			foreach ( $models as $model ) {
				$model->status = Lockout_Ip::STATUS_NORMAL;
				$model->save();
			}
		} else {
			WP_CLI::log( 'Option \'ips\' is not provided. See below...' );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}

		WP_CLI::log( sprintf( 'Firewall %s %s unblocked', str_replace( '_', ' ', $field ), $type ) );
	}

	/**
	 * Lists details for the firewall based on the specified type and field.
	 * Example: wp defender firewall list user_agent all
	 *
	 * @param  string $type  The type of data to list.
	 * @param  string $field  The specific field to list.
	 *
	 * @since v2.6.4. Add the details for User Agent Banning.
	 */
	private function list_firewall( $type, $field ) {
		$type_default  = array( 'user_agent' );
		$field_default = array( 'all', 'allowlist', 'blocklist' );
		if ( ! in_array( $type, $type_default, true ) ) {
			WP_CLI::log( sprintf( 'Invalid option %s. See below...', $type ) );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}
		if ( ! in_array( $field, $field_default, true ) ) {
			WP_CLI::log( sprintf( 'Invalid option %s. See below...', $field ) );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}
		$model = wd_di()->get( User_Agent_Lockout::class );
		$data  = $model->export();
		if ( 'all' === $field && ! empty( $data['whitelist'] ) && ! empty( $data['blacklist'] ) ) {
			WP_CLI::log( 'ALLOWLIST:' );
			WP_CLI::log( $data['whitelist'] );
			WP_CLI::log( 'BLOCKLIST:' );
			WP_CLI::log( $data['blacklist'] );
		} elseif ( 'allowlist' === $field && ! empty( $data['whitelist'] ) ) {
			WP_CLI::log( $data['whitelist'] );
		} elseif ( 'blocklist' === $field && ! empty( $data['blacklist'] ) ) {
			WP_CLI::log( $data['blacklist'] );
		} else {
			WP_CLI::log( 'No data.' );
		}
	}

	/**
	 * Change status of Firewall submodules: login_protection, 404_detection or user_agent.
	 * Example: wp defender firewall activate submodule user_agent
	 * Example: wp defender firewall deactivate submodule login_protection
	 *
	 * @param  string $key_word  The keyword to identify the action.
	 * @param  string $submodule  The submodule to toggle.
	 * @param  string $action  The action to perform (activate or deactivate).
	 */
	private function toggle_firewall_submodule( $key_word, $submodule, $action ) {
		if ( 'submodule' !== $key_word ) {
			WP_CLI::log( sprintf( 'Invalid option %s. See below...', $key_word ) );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}
		if ( ! in_array( $submodule, array( 'login_protection', '404_detection', 'user_agent' ), true ) ) {
			WP_CLI::log( sprintf( 'Invalid option %s. See below...', $submodule ) );
			WP_CLI::runcommand( 'defender firewall --help' );

			return;
		}
		// Get submodule slug.
		if ( 'login_protection' === $submodule ) {
			$model     = wd_di()->get( Login_Lockout::class );
			$submodule = Login_Lockout::get_module_name();
		} elseif ( '404_detection' === $submodule ) {
			$model     = wd_di()->get( Notfound_Lockout::class );
			$submodule = Notfound_Lockout::get_module_name();
		} else {
			$model     = wd_di()->get( User_Agent_Lockout::class );
			$submodule = User_Agent_Lockout::get_module_name();
		}
		// Activate/deactivate submodule.
		if ( 'activate' === $action ) {
			$text = 'activated';
			// Check if the submodule is not yet activated.
			if ( true !== $model->enabled ) {
				$model->enabled = true;
				$model->save();
			}
		} else {
			$text = 'deactivated';
			// Check if the submodule is not yet deactivated.
			if ( false !== $model->enabled ) {
				$model->enabled = false;
				$model->save();
			}
		}

		WP_CLI::success( sprintf( 'Firewall "%s" has been %s.', $submodule, $text ) );
	}

	/**
	 * Rename a field to its original model field name.
	 *
	 * @param  string $field  The field name to rename.
	 *
	 * @return string The renamed field name.
	 */
	private function rename_field( $field ) {
		if ( ! empty( $field ) ) {
			return str_replace( array( 'allow', 'block' ), array( 'white', 'black' ), $field );
		}

		return '';
	}

	/**
	 * Check if the specified field is related to country settings.
	 *
	 * @param  string $field  The field to check.
	 *
	 * @return bool True if the field is related to country settings, false otherwise.
	 */
	private function is_country( $field ) {
		return ( 'country_whitelist' === $field || 'country_blacklist' === $field );
	}

	/**
	 * Force Bulk Password Reset.
	 * <command>
	 * : Value can be force|undo
	 * Syntax: wp defender password_reset <command>
	 * Example: wp defender password_reset force
	 *
	 * @param  mixed $args  Command arguments.
	 */
	public function password_reset( $args ) {
		if ( ( is_array( $args ) || $args instanceof Countable ? count( $args ) : 0 ) < 1 ) {
			WP_CLI::log( 'Invalid command.' );

			return;
		}

		[ $command ] = $args;
		switch ( $command ) {
			case 'force':
				// Get the model instance.
				$model               = wd_di()->get( Password_Reset::class );
				$model->expire_force = true;
				$model->force_time   = time();
				$model->save();
				$message = sprintf(
					'Passwords created before %s are required to be reset upon next login.',
					$this->format_date_time( $model->force_time )
				);
				WP_CLI::log( $message );
				break;
			case 'undo':
				$model               = wd_di()->get( Password_Reset::class );
				$model->expire_force = false;
				$model->save();
				WP_CLI::log( 'Passwords reset is no longer required.' );
				break;
			default:
				WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				break;
		}
	}

	/**
	 * Clear completed action scheduler logs.
	 */
	private function scan_clear_logs() {
		$scan_component = wd_di()->get( Scan::class );
		$result         = $scan_component::clear_logs();
		$message        = $result['success'] ?? $result['error'] ?? 'Malware scan logs are cleared';

		WP_CLI::log( $message );
	}

	/**
	 * Delete old logs.
	 * <command> delete
	 * This command must have this command
	 * Syntax: wp defender logs <command>
	 * Example: wp defender logs delete
	 *
	 * @param  mixed $args  Command arguments.
	 */
	public function logs( $args ) {
		if ( ( is_array( $args ) || $args instanceof Countable ? count( $args ) : 0 ) < 1 ) {
			WP_CLI::log( 'Invalid command, add necessary arguments. See below...' );
			WP_CLI::runcommand( 'defender logs --help' );

			return;
		}

		[ $command ] = $args;

		switch ( $command ) {
			case 'delete':
				$rotation_logger = wd_di()->get( Rotation_Logger::class );
				$rotation_logger->purge_old_log();
				WP_CLI::log( 'Logs older than a week have been deleted.' );
				break;
			default:
				WP_CLI::error( sprintf( 'Unknown command %s', $command ) );
				break;
		}
	}

	/**
	 * This is a helper for Google Recaptcha actions.
	 * Syntax: wp defender google_recaptcha <command>
	 * <command> activate|deactivate|clear
	 * Example: wp defender google_recaptcha activate
	 *
	 * @param  mixed $args  Command arguments.
	 */
	public function google_recaptcha( $args ) {
		if ( empty( $args ) ) {
			WP_CLI::error( 'Invalid command.' );
			WP_CLI::runcommand( 'defender google_recaptcha --help' );

			return;
		}
		$model       = wd_di()->get( \WP_Defender\Model\Setting\Recaptcha::class );
		[ $command ] = $args;
		switch ( $command ) {
			case 'activate':
				if ( true !== $model->enabled ) {
					$model->enabled = true;
					$model->save();
				}
				WP_CLI::log( 'Google reCAPTCHA is activated.' );
				break;
			case 'deactivate':
				if ( false !== $model->enabled ) {
					$model->enabled = false;
					$model->save();
				}
				$model->save();
				WP_CLI::log( 'Google reCAPTCHA is deactivated.' );
				break;
			case 'clear':
				$default_values                      = $model->get_default_values();
				$model->message                      = $default_values['message'];
				$model->language                     = 'automatic';
				$model->data_v2_checkbox             = array(
					'key'    => '',
					'secret' => '',
					'size'   => 'normal',
					'style'  => 'light',
				);
				$model->data_v2_invisible            = array(
					'key'    => '',
					'secret' => '',
				);
				$model->data_v3_recaptcha            = array(
					'key'       => '',
					'secret'    => '',
					'threshold' => '0.5',
				);
				$model->locations                    = array();
				$model->detect_woo                   = false;
				$model->woo_checked_locations        = array();
				$model->detect_buddypress            = false;
				$model->buddypress_checked_locations = array();
				$model->disable_for_known_users      = true;
				$model->save();

				WP_CLI::log( 'Google reCAPTCHA is cleared.' );
				break;
			default:
				WP_CLI::error( sprintf( 'Unknown command %s.', $command ) );
				WP_CLI::runcommand( 'defender google_recaptcha --help' );
				break;
		}
	}
}