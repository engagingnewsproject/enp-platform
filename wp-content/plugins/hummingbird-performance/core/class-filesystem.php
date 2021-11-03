<?php
/**
 * Singleton class Filesystem.
 *
 * Manages the file system actions for caching modules.
 *
 * @since 1.6.0
 * @package Hummingbird\Core
 * @author: WPMUDEV, Ignacio Cruz (igmoweb), Anton Vanyukov (vanyukov)
 */

namespace Hummingbird\Core;

use Hummingbird\Core\Modules\Minify\Minify_Group;
use WP_Error;
use WP_Filesystem_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Filesystem
 */
class Filesystem {

	/**
	 * If filesystem is ok.
	 *
	 * @since  1.6.0
	 * @access private
	 * @var bool $status
	 */
	public $status = false;

	/**
	 * Gravatar cache directory.
	 *
	 * @since 1.7.0
	 * @var string $gravatar_dir
	 */
	public $gravatar_dir;

	/**
	 * Page cache directory.
	 *
	 * @since 1.7.0
	 * @var string
	 */
	public $cache_dir;

	/**
	 * Base url for Gravatar links.
	 *
	 * @since 1.6.0
	 * @var string $baseurl
	 */
	public $baseurl;

	/**
	 * Filesystem singleton instance.
	 *
	 * @since  1.6.0
	 * @access private
	 * @var Filesystem $_instance
	 */
	private static $instance;

	/**
	 * Base dir for files.
	 *
	 * @since 1.6.0
	 * @since 1.7.0 changed from private to public
	 * @var string $basedir
	 */
	public $basedir;

	/**
	 * Stores the domain of the site in multisite network.
	 *
	 * @since  1.7.0
	 * @access private
	 * @var string $site
	 */
	private $site;

	/**
	 * Use WP_Filesystem API.
	 *
	 * @since 1.7.2
	 * @var bool $fs_api
	 */
	private $fs_api = false;

	/**
	 * Filesystem constructor.
	 *
	 * Initiate file system for read/write operations.
	 *
	 * @since  1.6.0
	 * @access private
	 */
	private function __construct() {
		$this->status = $this->init_fs();

		if ( is_multisite() ) {
			$blog = get_blog_details();

			if ( '/' === $blog->path ) {
				$this->site = trailingslashit( $blog->domain );
			} else {
				$this->site = $blog->path;
			}
		}

		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
		}

		$this->basedir      = WP_CONTENT_DIR . '/wphb-cache/';
		$this->gravatar_dir = WP_CONTENT_DIR . '/wphb-cache/gravatar/';
		$this->cache_dir    = WP_CONTENT_DIR . '/wphb-cache/cache/';
		$this->baseurl      = trailingslashit( content_url() ) . 'wphb-cache/gravatar/';
	}

	/**
	 * Get Filesystem singleton instance.
	 *
	 * @since  1.6.0
	 * @return Filesystem
	 */
	public static function instance() {
		if ( ! is_object( self::$instance ) ) {
			self::$instance = new Filesystem();
		}

		return self::$instance;
	}

	/**
	 * Initiate file system for read/write operations
	 *
	 * @since  1.6.0
	 *
	 * @return bool|WP_Error  Return true if everything is ok.
	 */
	private function init_fs() {
		// Need to include file.php for frontend.
		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Removes CRITICAL Uncaught Error: Call to undefined function submit_button() in wp-admin/includes/file.php:1287.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		// Check if the user has write permissions.
		$access_type = get_filesystem_method();
		if ( 'direct' === $access_type ) {
			$this->fs_api = true;

			// You can safely run request_filesystem_credentials() without any issues
			// and don't need to worry about passing in a URL.
			$credentials = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, null );

			// Initialize the Filesystem API.
			if ( ! WP_Filesystem( $credentials ) ) {
				// Some problems, exit.
				ob_start();
				printf( /* translators: %1$s - code tag, %2$s - closing code tag, still having trouble link */
					esc_html__( "Hummingbird has encountered an unexpected error while writing a file. To find out more, enable the WordPress debug log by adding the following line to your site’s wp-config.php file:%1\$sdefine('WP_DEBUG', true);%2\$s", 'wphb' ),
					'<br><code>',
					'</code><br><br>'
				);
				echo esc_html( '&nbsp;' );
				Utils::still_having_trouble_link();
				$text = ob_get_clean();

				return new WP_Error( 'fs-error', $text );
			}
		} else {
			// Don't have direct write access.
			$this->fs_api = false;
		}

		// Can not write to wp-content directory.
		if ( defined( WP_CONTENT_DIR ) && ! is_writeable( WP_CONTENT_DIR ) ) {
			return new WP_Error(
				'fs-error',
				sprintf( /* translators: %1$s - opening a tag, %2$s - closing a tag */
					esc_html__( 'Your site’s wp-content directory is not writable. Please ensure the folder has the correct read and write %1$spermissions%2$s to ensure caching functions successfully.', 'wphb' ),
					'<a href="https://wordpress.org/support/article/changing-file-permissions/#permission-scheme-for-wordpress" target="_blank">',
					'</a>'
				)
			);
		}

		return true;
	}

	/**
	 * Native php directory removal (used when WP_Filesystem is not available);
	 *
	 * @since  1.7.2
	 *
	 * @access private
	 * @param string $path                 Path to delete.
	 * @param bool   $skip_subdirectories  Skip subdirectories.
	 *
	 * @return bool
	 */
	private function native_dir_delete( $path, $skip_subdirectories = false ) {
		if ( is_wp_error( $this->status ) ) {
			return false;
		}

		// Use direct filesystem php functions.
		$dir = @opendir( $path );

		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}

			$full = trailingslashit( $path ) . $file;
			if ( is_dir( $full ) && ! $skip_subdirectories ) {
				$this->native_dir_delete( $full );
			} elseif ( ! is_dir( $full ) ) {
				@unlink( $full );
			}
		}

		closedir( $dir );

		// Remove empty directories or if allowed.
		if ( 2 === count( scandir( $path ) ) || ! $skip_subdirectories ) {
			@rmdir( $path );
		}

		return true;
	}

	/**
	 * Resolves minify module's asset path or url, taking into account user-defined path
	 *
	 * @since 2.6.0
	 *
	 * @param bool|string $user_defined_path  Optional. The path to resolve. Default to user-defined path in Minify Settings.
	 * @param string      $mode               Optional. Specify 'path' or 'url'. Default: 'path'.
	 *
	 * @return string
	 */
	public function resolve_minify_asset_path( $user_defined_path = false, $mode = 'path' ) {
		$upload  = wp_upload_dir();
		$basedir = 'path' === $mode ? $upload['basedir'] : $upload['baseurl'];

		if ( false === $user_defined_path ) {
			$user_defined_path = Settings::get_setting( 'file_path', 'minify' );
		}

		// Check if user defined a custom path.
		if ( ! isset( $user_defined_path ) || empty( $user_defined_path ) ) {
			return $basedir . '/hummingbird-assets';
		}

		if ( strpos( $user_defined_path, '/' ) === 0 ) { // root relative path.
			return str_replace( '//', '/', ABSPATH . $user_defined_path );
		}

		return trailingslashit( $basedir ) . str_replace( './', '/', $user_defined_path );
	}

	/**
	 * Resolves directory info for critical assets like critical.css
	 * Default path can be overridden by using global constants in wp-config.php.
	 *
	 * @since 2.6.0
	 * @return array
	 */
	public static function critical_assets_dir() {
		static $info;
		if ( isset( $info ) ) {
			return $info;
		}

		if ( defined( 'WPHB_CRITICAL_ASSETS_PATH' ) && ! empty( WPHB_CRITICAL_ASSETS_PATH ) ) {
			$main_path = ABSPATH . trim( WPHB_CRITICAL_ASSETS_PATH, '\/ ' ) . '/';
			if ( is_multisite() ) {
				$blog_id     = get_current_blog_id();
				$assets_path = $main_path . 'sites/' . $blog_id . '/';
			} else {
				$assets_path = $main_path;
			}
		} else {
			$upload      = wp_upload_dir();
			$assets_path = $upload['basedir'] . '/wphb-critical-assets/';
		}

		$rel_path = str_replace( ABSPATH, '', $assets_path );
		if ( is_multisite() ) {
			$assets_url = get_site_url( get_main_site_id(), $rel_path );
		} else {
			$assets_url = site_url( $rel_path );
		}

		$info = array(
			'path' => $assets_path,
			'url'  => trailingslashit( $assets_url ),
		);
		return $info;
	}


	/**
	 * Delete everything in selected folder.
	 *
	 * @since  1.6.0
	 * @since  1.7.2  Added if $this->fs_api check.
	 * @since  1.9    Added $ao_module. If set to true will use the $dir path without $this->basedir
	 * @since  2.7.3  Added $skip_subdirectories. @see https://incsub.atlassian.net/browse/HUM-497
	 *
	 * @param  string $dir                  Directory in wp-content/wphb-cache/ to purge file from.
	 * @param  bool   $ao_module            Asset Optimization module.
	 * @param  bool   $skip_subdirectories  Skip subdirectories.
	 *
	 * @return bool
	 */
	public function purge( $dir = 'cache', $ao_module = false, $skip_subdirectories = false ) {
		if ( is_wp_error( $this->status ) ) {
			return false;
		}

		if ( $dir ) {
			$dir = trailingslashit( $dir );
		}

		// Default behavior - use basedir path.
		if ( ! $ao_module ) {
			$path = $this->basedir . $dir;
		} else {
			$path = trailingslashit( $this->resolve_minify_asset_path() );
		}

		// If directory not found - exit.
		if ( ! is_dir( $path ) ) {
			return true;
		}

		// Use WP_Filesystem API to delete files.
		if ( $this->fs_api ) {
			/**
			 * WP_Filesystem global.
			 *
			 * @type WP_Filesystem_Base $wp_filesystem
			 */
			global $wp_filesystem;

			// Delete all content inside the directory.
			foreach ( $wp_filesystem->dirlist( $path ) as $asset ) {
				// Skip subdirectories.
				if ( $skip_subdirectories && isset( $asset['type'] ) && 'd' === $asset['type'] ) {
					continue;
				}

				if ( ! $wp_filesystem->delete( $path . $asset['name'], true, $asset['type'] ) ) {
					return false;
				}
			}

			// Delete the directory itself if empty, or we can remove the dir.
			if ( ( empty( $wp_filesystem->dirlist( $path ) ) || ! $skip_subdirectories ) && ! $wp_filesystem->delete( $path ) ) {
				return false;
			}
		} else {
			// Use direct filesystem php functions.
			if ( ! $this->native_dir_delete( $path, $skip_subdirectories ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Clean up during uninstall.
	 *
	 * @since  1.6.0
	 * @since  1.7.2  Added if $this->fs_api check.
	 *
	 * @return bool
	 */
	public function clean_up() {
		if ( is_wp_error( $this->status ) ) {
			return false;
		}

		// Use WP_Filesystem API.
		if ( $this->fs_api ) {
			/**
			 * WP_Filesystem global.
			 *
			 * @type WP_Filesystem_Base $wp_filesystem
			 */
			global $wp_filesystem;

			if ( ! $wp_filesystem->delete( $this->basedir, true ) ) {
				return false;
			}
		} else {
			// Use direct filesystem php functions.
			if ( ! $this->native_dir_delete( $this->basedir ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Find file in the filesystem.
	 *
	 * @since  1.6.0
	 * @since  1.7.2  Added if $this->fs_api check.
	 *
	 * @param  string $file      File to find.
	 * @param  bool   $gravatar  To search for gravatar or page cache.
	 *
	 * @return bool
	 */
	public function find( $file, $gravatar = false ) {
		if ( is_wp_error( $this->status ) ) {
			return false;
		}

		$path = $this->cache_dir . $this->site;
		if ( $gravatar ) {
			// If Gravatar cache, we need to use first three letters of hash as a directory.
			$hash = trailingslashit( substr( $file, 0, 3 ) );
			$path = $this->gravatar_dir . $hash;
		}

		// Use WP_Filesystem API.
		if ( $this->fs_api ) {
			/**
			 * WP_Filesystem global.
			 *
			 * @type WP_Filesystem_Base $wp_filesystem
			 */
			global $wp_filesystem;
			return $wp_filesystem->exists( $path . $file );
		} else {
			// Use direct filesystem php functions.
			return file_exists( $path . $file );
		}
	}

	/**
	 * Write file to selected folder.
	 *
	 * @since  1.6.0
	 * @since  1.7.2  Added if $this->fs_api check.
	 *
	 * @param  string $file      Name of the file.
	 * @param  string $content   File contents.
	 * @param  bool   $gravatar  To search for gravatar or page cache.
	 *
	 * @return bool|WP_Error
	 */
	public function write( $file, $content = '', $gravatar = false ) {
		if ( is_wp_error( $this->status ) ) {
			return false;
		}

		// Determine path for Gravatar module.
		if ( $gravatar ) {
			// If Gravatar cache, we need to use first three letters of hash as a directory.
			$hash = '';
			// No need for a hash if we're just adding a blank index.html file.
			if ( 'index.html' !== $file ) {
				$hash = trailingslashit( substr( $file, 0, 3 ) );
			}

			$path = $this->gravatar_dir . $hash;
		} else {
			// Determine path for page caching module.
			$path = trailingslashit( dirname( $file ) );
			// Remove directory from file.
			$file = basename( $file );
		}

		// Use WP_Filesystem API.
		if ( $this->fs_api ) {
			/**
			 * WP_Filesystem global.
			 *
			 * @type WP_Filesystem_Base $wp_filesystem
			 */
			global $wp_filesystem;

			// Check if cache folder exists. If not - create it.
			if ( ! $wp_filesystem->exists( $path ) ) {
				if ( ! @wp_mkdir_p( $path ) ) {
					return new WP_Error(
						'fs-dir-error',
						sprintf(
							/* translators: %s: directory */
							__( 'Error creating directory %s.', 'wphb' ),
							esc_html( $path )
						)
					);
				}
			}

			// Create the file.
			if ( ! $wp_filesystem->put_contents( $path . $file, $content, FS_CHMOD_FILE ) ) {
				return new WP_Error(
					'fs-file-error',
					sprintf(
						/* translators: %s: file */
						__( 'Error uploading file %s.', 'wphb' ),
						esc_html( $file )
					)
				);
			}
		} else {
			// Use direct filesystem php functions.
			// Check if cache folder exists. If not - create it.
			if ( ! is_dir( $path ) ) {
				if ( ! @wp_mkdir_p( $path ) ) {
					return new WP_Error(
						'fs-dir-error',
						sprintf(
							/* translators: %s: directory */
							__( 'Error creating directory %s.', 'wphb' ),
							esc_html( $path )
						)
					);
				}
			}

			// Create the file.
			$file = fopen( $path . $file, 'w' );
			if ( ! fwrite( $file, $content ) ) {
				return new WP_Error(
					'fs-file-error',
					sprintf(
						/* translators: %s: file */
						__( 'Error uploading file %s.', 'wphb' ),
						esc_html( $file )
					)
				);
			} elseif ( $file ) {
				fclose( $file );
			}
		}

		return true;
	}

	/**
	 * Upload file to custom directory.
	 *
	 * This is similar to wp_upload_bits(), but the directory structure is changed.
	 *
	 * @since 1.9
	 *
	 * @param string $name  Filename.
	 * @param mixed  $bits  File content.
	 *
	 * @used-by Minify_Group::insert_group()
	 *
	 * @return array
	 */
	public static function handle_file_upload( $name, $bits ) {
		if ( empty( $name ) ) {
			return array(
				'error' => __( 'Empty filename', 'wphb' ),
			);
		}

		$wp_filetype = wp_check_filetype( $name );
		if ( ! $wp_filetype['ext'] && ! current_user_can( 'unfiltered_upload' ) ) {
			return array(
				'error' => __( 'Sorry, this file type is not permitted for security reasons.', 'wphb' ),
			);
		}

		$upload = wp_upload_dir();

		if ( false !== $upload['error'] ) {
			return $upload;
		}

		$user_defined_path = Settings::get_setting( 'file_path', 'minify' );

		$basedir = $upload['basedir'];
		$baseurl = $upload['baseurl'];

		// Check if user defined a custom path.
		if ( ! isset( $user_defined_path ) || empty( $user_defined_path ) ) {
			$custom_subdir = '/hummingbird-assets';
			$custom_dir    = $upload['basedir'] . $custom_subdir;
		} else {
			/**
			 * Possible variations:
			 * 1. some/path    => /wp-content/uploads/{$path}
			 * 2. /some/path   => {$path}
			 * 3. ./some/path  => /wp-content/uploads/{$path}
			 */
			if ( '/' === $user_defined_path[0] ) { // root relative path.
				$custom_subdir = $user_defined_path;
				$basedir       = ABSPATH;
				$baseurl       = site_url();
				$custom_dir    = $basedir . $user_defined_path;
				$custom_dir    = str_replace( '//', '/', $custom_dir );
			} else {
				$user_defined_path = str_replace( './', '/', $user_defined_path );

				// Prepend / to relative paths.
				$prepend = '';
				if ( '/' !== $user_defined_path[0] ) {
					$prepend = '/';
				}

				$custom_subdir = $prepend . $user_defined_path;
				$custom_dir    = $upload['basedir'] . $custom_subdir;
			}
		}

		/**
		 * We really should not be generating unique file names, because there are instances, when WP will
		 * generate a bunch of similar files.
		 * TODO: For now, we're going to remove similar files. But better to just remove the wp_unique_filename function.
		 */
		if ( file_exists( trailingslashit( $custom_dir ) . $name ) ) {
			@unlink( trailingslashit( $custom_dir ) . $name );
		}

		$filename = wp_unique_filename( $custom_dir, $name );

		$new_file = trailingslashit( $custom_dir ) . $filename;
		if ( ! wp_mkdir_p( dirname( $new_file ) ) ) {
			if ( 0 === strpos( $basedir, ABSPATH ) ) {
				$error_path = str_replace( ABSPATH, '', $basedir ) . $custom_subdir;
			} else {
				$error_path = basename( $basedir ) . $custom_subdir;
			}

			return array(
				'error' => sprintf(
					/* translators: %s: directory path */
					__( 'Unable to create directory %s. Is its parent directory writable by the server?', 'wphb' ),
					$error_path
				),
			);
		}

		$ifp = @fopen( $new_file, 'wb' );
		if ( ! $ifp ) {
			return array(
				/* translators: %s: file name with path */
				'error' => sprintf( __( 'Could not write file %s', 'wphb' ), $new_file ),
			);
		}

		@fwrite( $ifp, $bits );
		fclose( $ifp );
		clearstatcache();

		// Set correct file permissions.
		$stat  = @stat( dirname( $new_file ) );
		$perms = $stat['mode'] & 0007777;
		$perms = $perms & 0000666;
		@chmod( $new_file, $perms );
		clearstatcache();

		// Compute the URL.
		$url = $baseurl . trailingslashit( $custom_subdir ) . $filename;

		return array(
			'file'  => $new_file,
			'url'   => $url,
			'type'  => $wp_filetype['type'],
			'error' => false,
		);
	}

}
