<?php
/**
 * Logger class.
 *
 * This one will not use Filesystem class,
 * because it is used for creating new files only.
 *
 * @package Hummingbird\Core
 * @author: WPMUDEV, Anton Vanyukov (vanyukov)
 */

namespace Hummingbird\Core;

use Exception;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Logger
 */
class Logger {

	/**
	 * Plugin instance
	 *
	 * @since 1.9.2
	 *
	 * @var null|Logger
	 */
	private static $instance = null;

	/**
	 * Registered log files.
	 *
	 * @since  1.7.2
	 *
	 * @access private
	 * @var    array $files
	 */
	private $files = array();

	/**
	 * Log directory.
	 *
	 * @since  1.7.2
	 *
	 * @access private
	 * @var    string $log_dir
	 */
	private $log_dir;

	/**
	 * Registered modules.
	 *
	 * @since  1.9.2
	 * @access private
	 * @var    array $modules
	 */
	private $modules = array();

	/**
	 * Logger status.
	 *
	 * @since  1.7.2
	 *
	 * @access private
	 * @var    WP_Error|bool $status
	 */
	private $status = false;

	/**
	 * Return the plugin instance
	 *
	 * @return Logger
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Logger constructor.
	 *
	 * @since  1.7.2
	 *
	 * @access private
	 */
	private function __construct() {
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
		}

		$this->create_log_dir();

		add_action( 'wp_loaded', array( $this, 'process_actions' ) );

		// Add cron schedule to clean out outdated logs.
		add_action( 'wphb_clear_logs', array( $this, 'clear_logs' ) );
		add_action( 'admin_init', array( $this, 'check_cron_schedule' ) );
	}

	/**
	 * Register module.
	 *
	 * @since  1.9.2
	 *
	 * @param  string $module  Module slug.
	 */
	public function register_module( $module ) {
		if ( in_array( $module, $this->modules, true ) ) {
			return;
		}

		$this->modules[] = $module;

		$this->prepare_file( $module );
	}

	/**
	 * Prepare filename.
	 *
	 * @since  1.7.2
	 *
	 * @access private
	 *
	 * @param  string $module  Module slug.
	 */
	private function prepare_file( $module ) {
		$file = $module . '-debug.log';

		// Only the minification module has a per/site configuration.
		if ( 'minify' === $module ) {
			$file = $this->get_domain_prefix() . $file;
		}

		$this->files[ $module ] = $this->log_dir . $file;
	}

	/**
	 * Get site url to prefix the log file.
	 *
	 * @since  1.7.2
	 *
	 * @access private
	 *
	 * @return string
	 */
	private function get_domain_prefix() {
		if ( ! is_multisite() ) {
			return '';
		}

		$blog = get_blog_details();

		if ( '/' === $blog->path ) {
			return $blog->domain . '-';
		} elseif ( defined( 'SUBDOMAIN_INSTALL' ) && ! SUBDOMAIN_INSTALL ) {
			return $blog->domain . '-' . str_replace( '/', '', $blog->path ) . '-';
		}

		return $blog->path . '-';
	}

	/**
	 * Check if log directory is already create, if not - create it.
	 *
	 * @since  1.7.2
	 *
	 * @access private
	 */
	private function create_log_dir() {
		$this->log_dir = WP_CONTENT_DIR . '/wphb-logs/';

		if ( is_dir( $this->log_dir ) && is_writeable( $this->log_dir ) ) {
			return;
		}

		if ( ! mkdir( $this->log_dir ) ) {
			$error        = error_get_last();
			$this->status = new WP_Error( 'log-dir-error', $error['message'] );
		}
	}

	/**
	 * Attempt to write file.
	 *
	 * @since  1.7.2
	 *
	 * @access private
	 *
	 * @param  string $mode     Accepts any mode from the list: http://php.net/manual/en/function.fopen.php.
	 * @param  string $message  String to write to file.
	 * @param  string $module   Module slug.
	 */
	private function write_file( $mode, $message = '', $module = '' ) {
		/**
		 * Return if log directory is not exist.
		 * Some log requests come after running Hummingbird\Core\Logger::cleanup() by WP_Hummingbird::flush_cache();
		 * At this moment log directory is not exist.
		 *
		 * @since 2.5.0
		 */
		if ( ! file_exists( $this->log_dir ) ) {
			return;
		}

		try {
			$fp = fopen( $this->files[ $module ], $mode );
			flock( $fp, LOCK_EX );
			fwrite( $fp, $message );
			flock( $fp, LOCK_UN );
			fclose( $fp );
		} catch ( Exception $e ) {
			$this->status = new WP_Error( 'log-write-error', $e->getMessage() );
		}
	}

	/**
	 * Cleanup on uninstall.
	 *
	 * @since 1.7.2
	 */
	public static function cleanup() {
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
		}

		$log_dir = WP_CONTENT_DIR . '/wphb-logs/';

		// If no directory is present - exit.
		if ( ! is_dir( $log_dir ) ) {
			return;
		}

		try {
			$dir = opendir( $log_dir );
			while ( false !== ( $file = readdir( $dir ) ) ) {
				if ( ( '.' === $file ) || ( '..' === $file ) ) {
					continue;
				}

				$full = $log_dir . $file;
				if ( is_dir( $full ) ) {
					rmdir( $full );
				} else {
					unlink( $full );
				}
			}

			closedir( $dir );
			rmdir( $log_dir );
		} catch ( Exception $e ) {
			error_log( '[' . current_time( 'mysql' ) . '] - Unable to clean Hummingbird log directory. Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Check if module should log or not.
	 *
	 * @since  1.7.2
	 *
	 * @param string $module  Module to log for.
	 *
	 * @return bool
	 */
	private function should_log( $module ) {
		// Don't log if there's an error.
		if ( is_wp_error( $this->status ) ) {
			return false;
		}

		// See if the module is registered to log.
		if ( ! in_array( $module, $this->modules, true ) ) {
			return false;
		}

		$do_log = false;
		switch ( $module ) {
			case 'minify':
				// Log for minification only if debug is enabled.
				$options = Utils::get_module( 'minify' )->get_options();

				if ( $options['log'] ) {
					$do_log = true;
				}
				break;
			default:
				// Default to logging only when wp debug is set.
				$debug     = defined( 'WP_DEBUG' ) && WP_DEBUG;
				$debug_log = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;

				if ( $debug && $debug_log ) {
					$do_log = true;
				}
				break;
		}

		return $do_log;
	}

	/**
	 * Main logging function.
	 *
	 * @since 1.7.2
	 *
	 * @param mixed  $message  Data to write to log.
	 * @param string $module   Module slug.
	 */
	public function log( $message, $module ) {
		if ( ! $this->should_log( $module ) ) {
			return;
		}

		if ( ! is_string( $message ) || is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}

		$message = '[' . date( 'c' ) . '] ' . $message . PHP_EOL;

		$this->write_file( 'a', $message, $module );
	}

	/**
	 * Getter method for $file.
	 *
	 * @since 1.9.2
	 *
	 * @param string $module  Module slug.
	 *
	 * @return string
	 */
	public function get_file( $module ) {
		return $this->files[ $module ];
	}

	/**
	 * Process logger actions.
	 *
	 * Accepts module name (slug) and action. So far only 'download' actions is supported.
	 *
	 * @since 1.9.2
	 */
	public function process_actions() {
		if ( ! isset( $_GET['logs'] ) || ! isset( $_GET['module'] ) || ! check_admin_referer( 'wphb-log-action' ) ) { // Input var ok.
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['logs'] ) );   // Input var ok.
		$module = sanitize_text_field( wp_unslash( $_GET['module'] ) ); // Input var ok.

		// Not called by a registered module.
		if ( ! in_array( $module, $this->modules, true ) ) {
			return;
		}

		// Only allow these actions.
		if ( ! in_array( $action, array( 'download' ), true ) ) {
			return;
		}

		if ( method_exists( $this, $action ) ) {
			call_user_func( array( $this, $action ), $module );
		}
	}

	/**
	 * Download logs.
	 *
	 * @since 1.9.2
	 *
	 * @param string $module  Module slug.
	 */
	private function download( $module ) {
		if ( 'page_cache' === $module ) {
			$content = file_get_contents( WP_CONTENT_DIR . '/wphb-logs/page-caching-log.php' );
			/* Remove <?php die(); ?> from file */
			$content = substr( $content, 15 );
		} else {
			$content = file_get_contents( $this->files[ $module ] );
		}

		// No file - exit.
		if ( ! $content ) {
			return;
		}

		header( 'Content-Description: Hummingbird log download' );
		header( 'Content-Type: text/plain' );
		header( "Content-Disposition: attachment; filename={$module}.log" );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . strlen( $content ) );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		echo $content;
		exit;
	}

	/**
	 * Clear log file.
	 *
	 * @since 1.9.2
	 *
	 * @param string $module  Module slug.
	 *
	 * @return bool
	 */
	public function clear( $module ) {
		if ( 'page_cache' === $module ) {
			$file = WP_CONTENT_DIR . '/wphb-logs/page-caching-log.php';
		} else {
			$file = $this->files[ $module ];
		}

		if ( file_exists( $file ) ) {
			wp_delete_file( $file );
			return true;
		}

		return false;
	}

	/**
	 * Check if the logger cron is scheduled to run.
	 *
	 * @since 1.9.2
	 */
	public function check_cron_schedule() {
		if ( ! wp_next_scheduled( 'wphb_clear_logs' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wphb_clear_logs' );
		}
	}

	/**
	 * Clear out lines that are older than 30 days.
	 *
	 * @since 1.9.2
	 */
	public function clear_logs() {
		$now = date( 'c' );

		foreach ( $this->modules as $slug ) {
			if ( 'page_cache' === $slug ) {
				$file = WP_CONTENT_DIR . '/wphb-logs/page-caching-log.php';
			} else {
				$file = $this->get_file( $slug );
			}

			if ( ! file_exists( $file ) ) {
				continue;
			}

			$content         = file( $file );
			$size_of_content = count( $content );

			$delete = false;
			foreach ( $content as $i => $line ) {
				// If the line does not start with '[' (it's probably not a new entry).
				$first_char = substr( $line, 0, 1 );
				if ( '[' !== $first_char ) {
					// If not marked for delete - skip.
					if ( ! $delete ) {
						continue;
					}

					// Delete.
					unset( $content[ $i ] );
				}

				/**
				 * Get the date from entry. Items can be an array it two cases - if there's a valid date, or if the line
				 * contained something like [header] in the start. Cannot make assumptions just on the fact it's an array.
				 */
				preg_match( '/\[(.*)\]/', $line, $items );

				// If, for some reason, can't get the date, or it's not the size of an ISO 8601 date.
				if ( ! isset( $items[1] ) || 25 !== strlen( $items[1] ) ) {
					// If not marked for delete - skip.
					if ( ! $delete ) {
						continue;
					}

					// Delete.
					unset( $content[ $i ] );
				} else {
					// It looks like it's a valid date string, compare with today.
					$time_diff = strtotime( $now ) - strtotime( $items[1] );

					// We don't need to continue on, because if this entry is not older than 30 days, the next one will not be as well.
					if ( $time_diff < MONTH_IN_SECONDS ) {
						break;
					}

					$delete = true;
					unset( $content[ $i ] );

				}
			}

			// Nothing changed - do nothing.
			if ( count( $content ) === $size_of_content ) {
				continue;
			}

			// Glue back together and write back to file.
			$content = implode( '', $content );
			if ( 'page_cache' === $slug ) {
				global $wphb_fs;

				if ( ! $wphb_fs && class_exists( 'Filesystem' ) ) {
					$wphb_fs = Filesystem::instance();
				}

				if ( $wphb_fs ) {
					$wphb_fs->write( $file, $content );
				}
			} else {
				$this->write_file( 'w', $content, $slug );
			}
		}
	}

}
