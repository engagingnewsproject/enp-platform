<?php
/**
 * Responsible for managing server configurations related to security tweaks .
 *
 * @package WP_Defender\Component\Security_Tweaks\Servers
 */

namespace WP_Defender\Component\Security_Tweaks\Servers;

use WP_Error;

/**
 * Provides methods to apply and revert security rules on servers.
 */
class Apache {

	public const CACHE_APACHE_VERSION = 'defender_apache_version';

	/**
	 * Exclude file paths.
	 *
	 * @var array
	 */
	public $new_htaccess_config = array();

	/**
	 * The htaccess inside wp-content.
	 *
	 * @var string
	 */
	public $contentdir_path = null;

	/**
	 * The htaccess path inside wp-includes.
	 *
	 * @var null
	 */
	public $includedir_path = null;

	/**
	 * Service type.
	 *
	 * @var string
	 */
	private $type = null;

	/**
	 * Constructor for class.
	 *
	 * @param  string $type  The type of the security tweak.
	 */
	public function __construct( $type ) {
		$this->type = $type;
	}

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		$url = '';

		if ( 'prevent-php-executed' === $this->type ) {
			$dir = wp_upload_dir();
			$url = $dir['baseurl'] . '/wp-defender/index.php';
		}

		if ( 'protect-information' === $this->type ) {
			$url = defender_asset_url( '/languages/' . WP_DEFENDER_POT_FILENAME );
		}

		return Server::ping_test_failed( $url );
	}

	/**
	 * Retrieves a WP_Error object based on the provided file path.
	 *
	 * @param  string $file_path  The path to the file.
	 *
	 * @return WP_Error Returns a WP_Error indicating the file is not writable.
	 */
	public function get_error_by_file_path( $file_path ) {
		return new WP_Error(
			'defender_file_not_writable',
			sprintf(
			/* translators: %s - file path */
				esc_html__( 'The file %s is not writable', 'wpdef' ),
				$file_path
			)
		);
	}

	/**
	 * Process the rule.
	 *
	 * @return bool|WP_Error
	 */
	public function process() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if ( 'protect-information' === $this->type ) {
			$ht_access_path = ABSPATH . '.htaccess';
			// Create a htaccess-file if it doesn't exist.
			if ( ! is_file( $ht_access_path )
				&& false === (bool) file_put_contents( $ht_access_path, '', LOCK_EX ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			) {
				return $this->get_error_by_file_path( $ht_access_path );
			}
			// If the htaccess-file is existed.
			if ( ! $wp_filesystem->is_writable( $ht_access_path ) ) {
				return $this->get_error_by_file_path( $ht_access_path );
			}

			$ht_access_config = file( $ht_access_path );
			$ht_access_config = array_map( 'trim', $ht_access_config );
			$rules            = $this->get_rules();
			$contains_search  = array_diff( array_map( 'trim', $rules ), $ht_access_config );

			if ( count( $contains_search ) < count( $rules ) ) {
				// Search the wrapper block.
				$ht_access_content = $wp_filesystem->get_contents( $ht_access_path );
				preg_match( '/## WP Defender(.*?)## WP Defender - End ##/s', $ht_access_content, $matches );

				if ( count( $matches ) ) {
					// Remove the whole parts as its partial done.
					$ht_access_content = str_replace( $matches[0], '', $ht_access_content );
					$ht_access_config  = explode( PHP_EOL, $ht_access_content );
					$ht_access_config  = array_merge( $ht_access_config, $rules );

					return (bool) file_put_contents( $ht_access_path, implode( PHP_EOL, $ht_access_config ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				}
			}

			if ( 0 === count( $contains_search ) || ( count( $contains_search ) === count( $rules ) ) ) {
				$ht_access_config = array_merge( $ht_access_config, $rules );

				return (bool) file_put_contents( $ht_access_path, implode( PHP_EOL, $ht_access_config ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}

		if ( 'prevent-php-executed' === $this->type ) {
			if ( ! in_array( Server::get_current_server(), array( 'apache', 'litespeed' ), true ) ) {
				return new WP_Error(
					'defender_unable_to_apply_rules',
					esc_html__(
						'The rules can\'t be applied. This can be either because your host doesn\'t allow editing the file, or you\'ve selected the wrong server type.',
						'wpdef'
					)
				);
			}

			$response = $this->protect_content_directory();

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$response = $this->protect_includes_directory();

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$response = $this->protect_uploads_directory();

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$settings = array(
				'new_htaccess_config' => $this->get_new_htaccess_config(),
			);

			update_site_option( "defender_security_tweeks_{$this->type}", $settings );

			return true;
		}
	}

	/**
	 * Revert the rules.
	 *
	 * @return bool|WP_Error
	 */
	public function revert() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		if ( 'protect-information' === $this->type ) {
			$ht_access_path = ABSPATH . '.htaccess';
			// Quick exit if the file does not exist.
			if ( ! is_file( $ht_access_path ) ) {
				return true;
			}

			if ( ! $wp_filesystem->is_writable( $ht_access_path ) ) {
				return $this->get_error_by_file_path( $ht_access_path );
			}

			$ht_access_config = $wp_filesystem->get_contents( $ht_access_path );
			$rules            = $this->get_rules();

			preg_match_all( '/## WP Defender(.*?)## WP Defender - End ##/s', $ht_access_config, $matches );

			if ( is_array( $matches ) && count( $matches ) > 0 ) {
				$ht_access_config = str_replace( implode( '', $matches[0] ), '', $ht_access_config );
			} else {
				$ht_access_config = str_replace( implode( '', $rules ), '', $ht_access_config );
			}

			$ht_access_config = trim( $ht_access_config );

			return (bool) file_put_contents( $ht_access_path, $ht_access_config, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		if ( 'prevent-php-executed' === $this->type ) {
			// Content-folder.
			$response = $this->unprotect_content_directory();
			if ( is_wp_error( $response ) ) {
				return wp_send_json_error(
					array( 'message' => $response->get_error_message() )
				);
			}
			// Includes-folder.
			$response = $this->unprotect_includes_directory();
			if ( is_wp_error( $response ) ) {
				return wp_send_json_error(
					array( 'message' => $response->get_error_message() )
				);
			}
			// Uploads-folder.
			$response = $this->unprotect_upload_directory();
			if ( is_wp_error( $response ) ) {
				return wp_send_json_error(
					array( 'message' => $response->get_error_message() )
				);
			}

			return delete_site_option( "defender_security_tweeks_{$this->type}" );
		}
	}

	/**
	 * Get Apache rule depending on the version.
	 *
	 * @return array
	 */
	public function get_rules() {
		$rules = array(
			PHP_EOL . '## WP Defender - Prevent information disclosure ##' . PHP_EOL,
			'<FilesMatch "\.(md|exe|sh|bak|inc|pot|po|mo|log|sql)$">' . PHP_EOL .
			'Require all denied' . PHP_EOL .
			'</FilesMatch>' . PHP_EOL,
			'<Files robots.txt>' . PHP_EOL .
			'Require all granted' . PHP_EOL .
			'</Files>' . PHP_EOL,
			'<Files ads.txt>' . PHP_EOL .
			'Require all granted' . PHP_EOL .
			'</Files>' . PHP_EOL,
			'## WP Defender - End ##',
		);

		if ( version_compare( $this->get_version(), '2.4', '<' ) ) {
			$rules = array(
				PHP_EOL . '## WP Defender - Prevent information disclosure ##' . PHP_EOL,
				'<FilesMatch "\.(md|exe|sh|bak|inc|pot|po|mo|log|sql)$">' . PHP_EOL .
				'Order allow,deny' . PHP_EOL .
				'Deny from all' . PHP_EOL .
				'</FilesMatch>' . PHP_EOL,
				'<Files robots.txt>' . PHP_EOL .
				'Allow from all' . PHP_EOL .
				'</Files>' . PHP_EOL,
				'<Files ads.txt>' . PHP_EOL .
				'Allow from all' . PHP_EOL .
				'</Files>' . PHP_EOL,
				'## WP Defender - End ##',
			);
		}

		return $rules;
	}

	/**
	 * Get Apache rule depending on the version for instruction on browser.
	 *
	 * @return string
	 */
	public function get_rules_for_instruction() {
		$rules = '';

		if ( 'prevent-php-executed' === $this->type ) {
			$rules  = '## WP Defender - Protect PHP Executed ##' . PHP_EOL;
			$rules .= PHP_EOL;
			$rules .= '<Files *.php>' . PHP_EOL;
			$rules .= 'Require all denied' . PHP_EOL;
			$rules .= '</Files>' . PHP_EOL;
			$rules .= PHP_EOL;
			$rules .= '## WP Defender - End ##' . PHP_EOL;

			if ( version_compare( $this->get_version(), '2.4', '<' ) ) {
				$rules  = '## WP Defender - Protect PHP Executed ##' . PHP_EOL;
				$rules .= PHP_EOL;
				$rules .= '<Files *.php>' . PHP_EOL;
				$rules .= 'Order allow,deny' . PHP_EOL;
				$rules .= 'Deny from all' . PHP_EOL;
				$rules .= '</Files>' . PHP_EOL;
				$rules .= PHP_EOL;
				$rules .= '## WP Defender - End ##' . PHP_EOL;
			}
		}

		if ( 'protect-information' === $this->type ) {
			$rules  = '## WP Defender - Prevent information disclosure ##' . PHP_EOL;
			$rules .= PHP_EOL;
			$rules .= '<FilesMatch "\.(md|exe|sh|bak|inc|pot|po|mo|log|sql)$">' . PHP_EOL;
			$rules .= 'Require all denied' . PHP_EOL;
			$rules .= '</FilesMatch>' . PHP_EOL;
			$rules .= PHP_EOL;
			$rules .= '<Files robots.txt>' . PHP_EOL;
			$rules .= 'Require all granted' . PHP_EOL;
			$rules .= '</Files>' . PHP_EOL;
			$rules .= PHP_EOL;
			$rules .= '<Files ads.txt>' . PHP_EOL;
			$rules .= 'Require all granted' . PHP_EOL;
			$rules .= '</Files>' . PHP_EOL;
			$rules .= PHP_EOL;
			$rules .= '## WP Defender - End ##';

			if ( version_compare( $this->get_version(), '2.4', '<' ) ) {
				$rules  = '## WP Defender - Prevent information disclosure ##' . PHP_EOL;
				$rules .= PHP_EOL;
				$rules .= '<FilesMatch "\.(md|exe|sh|bak|inc|pot|po|mo|log|sql)$">' . PHP_EOL;
				$rules .= 'Order allow,deny' . PHP_EOL;
				$rules .= 'Deny from all' . PHP_EOL;
				$rules .= '</FilesMatch>' . PHP_EOL;
				$rules .= PHP_EOL;
				$rules .= '<Files robots.txt>' . PHP_EOL;
				$rules .= 'Allow from all' . PHP_EOL;
				$rules .= '</Files>' . PHP_EOL;
				$rules .= PHP_EOL;
				$rules .= '<Files ads.txt>' . PHP_EOL;
				$rules .= 'Allow from all' . PHP_EOL;
				$rules .= '</Files>' . PHP_EOL;
				$rules .= PHP_EOL;
				$rules .= '## WP Defender - End ##';
			}
		}

		return $rules;
	}

	/**
	 * Determine the Apache version.
	 * Most web servers have apache_get_version disabled, so we just get a simple curl of the headers.
	 *
	 * @return string
	 */
	public function get_version() {
		if ( ! function_exists( 'apache_get_version' ) ) {
			// The default support is 2.2.
			$version        = '2.2';
			$url            = home_url();
			$apache_version = get_site_transient( self::CACHE_APACHE_VERSION );

			if ( ! is_array( $apache_version ) ) {
				$apache_version = array();
			}

			if ( isset( $apache_version[ $url ] ) && ! empty( $apache_version[ $url ] ) ) {
				return strtolower( $apache_version[ $url ] );
			}
			// The default support is 2.2.
			$apache_version[ $url ] = $version;

			$server = defender_get_data_from_request( 'SERVER_SOFTWARE', 's' );
			if ( isset( $server ) ) {
				$server = explode( ' ', $server );
				if ( is_array( $server ) && count( $server ) > 1 ) {
					$server = $server[0];
					$server = explode( '/', $server );
					if ( is_array( $server ) && count( $server ) > 1 ) {
						$version                = $server[1];
						$apache_version[ $url ] = $version;
					}
				}
			}

			set_site_transient( self::CACHE_APACHE_VERSION, $apache_version, 3600 );
		} else {
			$version = apache_get_version();
			$version = explode( '/', $version );
			$version = ! empty( $version[1] ) ? $version[1] : $version[0];
		}

		return $version;
	}

	/**
	 * Protect content directory.
	 *
	 * @return void|WP_Error
	 */
	public function protect_content_directory() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$ht_access_path = $this->contentdir_path;

		if ( empty( $ht_access_path ) ) {
			$ht_access_path = WP_CONTENT_DIR . '/.htaccess';
		}
		// Create a htaccess-file if it doesn't exist.
		if ( ! is_file( $ht_access_path )
			&& false === (bool) file_put_contents( $ht_access_path, '', LOCK_EX ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		) {
			return $this->get_error_by_file_path( $ht_access_path );
		}
		// If the htaccess-file is existed.
		if ( ! $wp_filesystem->is_writable( $ht_access_path ) ) {
			return $this->get_error_by_file_path( $ht_access_path );
		}

		$exists_rules = $this->cleanup_old_rules( $wp_filesystem->get_contents( $ht_access_path ) );
		$rule         = array(
			'## WP Defender - Protect PHP Executed ##',
			'<Files *.php>',
			$this->generate_htaccess_rule( false ),
			'</Files>',
		);

		$rule[] = '## WP Defender - End ##';
		file_put_contents( $ht_access_path, $exists_rules . implode( PHP_EOL, $rule ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Protect includes directory.
	 *
	 * @return void|WP_Error
	 */
	public function protect_includes_directory() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$ht_access_path = $this->includedir_path;

		if ( empty( $ht_access_path ) ) {
			$ht_access_path = ABSPATH . WPINC . '/.htaccess';
		}
		// Create a htaccess-file if it doesn't exist.
		if ( ! is_file( $ht_access_path )
			&& false === (bool) file_put_contents( $ht_access_path, '', LOCK_EX ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		) {
			return $this->get_error_by_file_path( $ht_access_path );
		}
		// If the htaccess-file is existed.
		if ( ! $wp_filesystem->is_writable( $ht_access_path ) ) {
			return $this->get_error_by_file_path( $ht_access_path );
		}

		$exists_rules = $this->cleanup_old_rules( $wp_filesystem->get_contents( $ht_access_path ) );

		$rule = array(
			'## WP Defender - Protect PHP Executed ##',
			'<Files *.php>',
			$this->generate_htaccess_rule( false ),
			'</Files>',
			'<Files wp-tinymce.php>',
			$this->generate_htaccess_rule( true ),
			'</Files>',
			'<Files ms-files.php>',
			$this->generate_htaccess_rule( true ),
			'</Files>',
			'## WP Defender - End ##',
		);

		file_put_contents( $ht_access_path, $exists_rules . implode( PHP_EOL, $rule ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Generates the correct Apache rules for allow/deny based on the server version.
	 *
	 * @param  bool $allow  Specifies whether to generate allow or deny rules.
	 *
	 * @return string Returns the generated Apache rules as a string.
	 */
	protected function generate_htaccess_rule( $allow = true ) {
		if ( version_compare( $this->get_version(), 2.4, '>=' ) ) {
			if ( $allow ) {
				return 'Require all granted';
			} else {
				return 'Require all denied';
			}
		} elseif ( $allow ) {
			return 'Allow from all';
		} else {
			return 'Order allow,deny' . PHP_EOL .
					'Deny from all';
		}
	}

	/**
	 * Protect uploads directory.
	 * This only when user provide a custom uploads.
	 *
	 * @return void|WP_Error
	 */
	public function protect_uploads_directory() {
		if ( defined( 'UPLOADS' ) ) {
			$this->contentdir_path = ABSPATH . UPLOADS . '/.htaccess';

			return $this->protect_content_directory();
		}
	}

	/**
	 * Unprotect content directory.
	 *
	 * @return void|WP_Error
	 */
	public function unprotect_content_directory() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$ht_access_path = $this->contentdir_path;

		if ( empty( $ht_access_path ) ) {
			$ht_access_path = WP_CONTENT_DIR . '/.htaccess';
		}
		// Quick exit if the file does not exist.
		if ( ! is_file( $ht_access_path ) ) {
			return;
		}
		// If the htaccess-file is existed.
		if ( ! $wp_filesystem->is_writable( $ht_access_path ) ) {
			return $this->get_error_by_file_path( $ht_access_path );
		}

		$ht_config = $this->cleanup_old_rules( $wp_filesystem->get_contents( $ht_access_path ) );
		$ht_config = trim( $ht_config );

		file_put_contents( $ht_access_path, trim( $ht_config ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Unprotect includes-directory.
	 * Todo: can be combined with unprotect_content_directory()?
	 *
	 * @return void|WP_Error
	 */
	public function unprotect_includes_directory() {
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$ht_access_path = $this->includedir_path;

		if ( empty( $ht_access_path ) ) {
			$ht_access_path = ABSPATH . WPINC . '/.htaccess';
		}
		// Quick exit if the file does not exist.
		if ( ! is_file( $ht_access_path ) ) {
			return;
		}
		// If the htaccess-file is existed.
		if ( ! $wp_filesystem->is_writable( $ht_access_path ) ) {
			return $this->get_error_by_file_path( $ht_access_path );
		}

		$ht_config = $this->cleanup_old_rules( $wp_filesystem->get_contents( $ht_access_path ) );
		file_put_contents( $ht_access_path, trim( $ht_config ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Unprotect upload directory.
	 *
	 * @return void
	 */
	public function unprotect_upload_directory() {
		if ( defined( 'UPLOADS' ) ) {
			$this->contentdir_path = ABSPATH . UPLOADS . '/.htaccess';
			$this->unprotect_content_directory();
		}
	}

	/**
	 * Set the exclude file paths.
	 *
	 * @param  array $config  to set.
	 */
	public function set_new_htaccess_config( $config = array() ) {
		if ( ! empty( $config ) ) {
			$this->new_htaccess_config = $config;
		}
	}

	/**
	 * Get the new HT config.
	 *
	 * @return array - $new_htaccess_config
	 */
	public function get_new_htaccess_config() {
		return $this->new_htaccess_config;
	}

	/**
	 * Cleans up old rules from the existing .htaccess configuration.
	 *
	 * @param  string $exists_rules  The existing .htaccess rules.
	 *
	 * @return string Returns the cleaned-up .htaccess rules.
	 */
	public function cleanup_old_rules( $exists_rules ) {
		$pattern = '/(## WP Defender - Protect PHP Executed ##((.|\n)*)## WP Defender - End ##)/';

		if ( preg_match( $pattern, $exists_rules ) ) {
			$exists_rules = preg_replace( $pattern, '', $exists_rules );
		}

		$exists_rules = trim( $exists_rules );

		if ( strlen( $exists_rules ) ) {
			$exists_rules .= PHP_EOL;
		}

		return $exists_rules;
	}
}