<?php
/**
 * Class Gravatar is responsible for handling Gravatar Cache.
 *
 * @since 1.6.0
 */

namespace Hummingbird\Core\Modules;

use Hummingbird\Core\Filesystem;
use Hummingbird\Core\Module;
use Hummingbird\Core\Settings;
use WP_Comment;
use WP_Error;
use WP_Post;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Gravatar
 *
 * @package Hummingbird\Core\Modules
 */
class Gravatar extends Module {

	/**
	 * Last error.
	 *
	 * @since 1.6.0
	 * @var   WP_Error $error
	 */
	public $error = false;

	/**
	 * Initialize module.
	 *
	 * @since 1.6.0
	 */
	public function init() {
		add_filter( 'wp_hummingbird_is_active_module_gravatar', array( $this, 'module_status' ) );
	}

	/**
	 * Execute module actions
	 *
	 * @since 1.6.0
	 */
	public function run() {
		global $wphb_fs;

		// Init filesystem.
		if ( ! $wphb_fs ) {
			$wphb_fs = Filesystem::instance();
		}

		// If error - save error status and exit.
		if ( is_wp_error( $wphb_fs->status ) ) {
			$this->error = $wphb_fs->status;
			return;
		}

		// Everything else is only for frontend.
		if ( is_admin() && !( wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'get_comments_template' === $_REQUEST['action'] ) ) {
			return;
		}

		add_filter( 'get_avatar_data', array( $this, 'get_avatar_data' ), 10, 2 );
	}

	/**
	 * Implement abstract parent method for clearing cache.
	 *
	 * Delete cached files.
	 *
	 * @return bool
	 * @since  1.6.0
	 * @since  1.7.1 name changed from delete_files to clear_cache
	 */
	public function clear_cache() {
		/* @var Filesystem $wphb_fs */
		global $wphb_fs;

		return $wphb_fs->purge( 'gravatar' );
	}

	/**
	 * Activate module.
	 *
	 * @since 1.9.0
	 */
	public function enable() {
		Settings::update_setting( 'enabled', true, $this->get_slug() );
	}

	/**
	 * Deactivate module.
	 *
	 * @since 1.9.0
	 */
	public function disable() {
		Settings::update_setting( 'enabled', false, $this->get_slug() );
	}

	/**
	 * Fetch remote avatar and save to local cache
	 *
	 * @access private
	 * @see Requests_Utility_CaseInsensitiveDictionary for $remote_avatar['headers']->getAll().
	 * @param  mixed $id_or_email  The Gravatar to retrieve a URL for. Accepts a user_id, gravatar md5 hash,
	 *                             user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param  int   $size         Size of avatar.
	 * @since  1.6.0
	 * @return bool|WP_Error       Returns true if file write is ok, WP_Error on error.
	 */
	private function get_remote_avatar( $id_or_email, $size ) {
		/* @var Filesystem $wphb_fs */
		global $wphb_fs;

		$gravatar = get_avatar_data(
			$id_or_email,
			array(
				'size' => $size,
			)
		);

		if ( true === $gravatar['found_avatar'] ) {
			$remote_avatar = wp_remote_get( $gravatar['url'] );
			/**
			 * TODO: if png is used here, then we need to use png in get_cached_avatar().
			$header = $remote_avatar['headers']->getAll();
			switch ( $header['content-type'] ) {
				case 'image/jpeg':
				default:
					$extension = '.jpg';
					break;
				case 'image/png':
					$extension = '.png';
					break;
			}
			*/

			/**
			 * Filename.
			 * Format: [md5_hash]x[size].[extension]
			 * Example: 0973085bb3339de14706edda7bc62581x100.jpg
			 */
			$email_hash = $this->get_email_hash( $id_or_email );
			$file       = $email_hash . 'x' . $size . '.jpg';
			return $wphb_fs->write( $file, $remote_avatar['body'], true );
		} else {
			return new WP_Error( 'gravatar-not-found', __( 'Error fetching Gravatar. Gravatar not found.', 'wphb' ) );
		}
	}

	/**
	 * Calculate email hash
	 *
	 * @access private
	 * @param  mixed $id_or_email  The Gravatar to retrieve a URL for. Accepts a user_id, gravatar md5 hash,
	 *                             user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @return string              Email hash.
	 * @since  1.6.0
	 */
	private function get_email_hash( $id_or_email ) {
		$email_hash = '';
		$user       = $email = false;

		// Process the user identifier.
		if ( is_numeric( $id_or_email ) ) {
			$user = get_user_by( 'id', absint( $id_or_email ) );
		} elseif ( is_string( $id_or_email ) ) {
			if ( strpos( $id_or_email, '@md5.gravatar.com' ) ) {
				// MD5 hash.
				list( $email_hash ) = explode( '@', $id_or_email );
			} else {
				// Email address.
				$email = $id_or_email;
			}
		} elseif ( $id_or_email instanceof WP_User ) {
			// User Object.
			$user = $id_or_email;
		} elseif ( $id_or_email instanceof WP_Post ) {
			// Post Object.
			$user = get_user_by( 'id', (int) $id_or_email->post_author );
		} elseif ( $id_or_email instanceof WP_Comment ) {
			/**
			 * Filters the list of allowed comment types for retrieving avatars.
			 *
			 * @since 3.0.0
			 *
			 * @param array $types An array of content types. Default only contains 'comment'.
			 */
			$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment', 'pingback' ) );
			if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types, true ) ) {
				$args['url'] = false;
				/** This filter is documented in wp-includes/link-template.php */
				return apply_filters( 'get_email_hash', false, $id_or_email, $args );
			}

			if ( ! empty( $id_or_email->user_id ) ) {
				$user = get_user_by( 'id', (int) $id_or_email->user_id );
			}
			if ( ( ! $user || is_wp_error( $user ) ) && ! empty( $id_or_email->comment_author_email ) ) {
				$email = $id_or_email->comment_author_email;
			}
		}

		if ( ! $email_hash ) {
			if ( $user ) {
				$email = $user->user_email;
			}

			if ( $email ) {
				$email_hash = md5( strtolower( trim( $email ) ) );
			}
		}

		return $email_hash;
	}

	/**
	 * Get cached avatar.
	 *
	 * @param  string $image        Image source.
	 * @param  mixed  $id_or_email  The Gravatar to retrieve a URL for. Accepts a user_id, gravatar md5 hash,
	 *                              user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param  int    $size         Image size.
	 * @param  bool   $default      Not used. URL for an image, defaults to the "Mystery Person".
	 * @param  string $alt          Alternate text to use in the avatar image tag.
	 * @param  array  $args         Arguments passed to get_avatar_url(), after processing.
	 * @return string $image        Image source.
	 * @since  1.6.0
	 * @deprecated 1.6.1
	 */
	public function get_cached_avatar( $image, $id_or_email, $size, $default, $alt, $args ) {
		/* @var Filesystem $wphb_fs */
		global $wphb_fs;

		$email_hash = $this->get_email_hash( $id_or_email );

		// Avatar file names for normal and retina.
		$images = array(
			'normal' => array(
				'file' => $email_hash . 'x' . $size . '.jpg',
				'size' => $size,
			),
			'retina' => array(
				'file' => $email_hash . 'x' . ( $size * 2 ) . '.jpg',
				'size' => $size * 2,
			),
		);

		foreach ( $images as $img ) {
			// Try to save the avatar.
			if ( $wphb_fs->find( $img['file'], true ) ) {
				break;
			}

			$file_write = $this->get_remote_avatar( $id_or_email, $img['size'] );
			// If error creating file - log and return original image.
			if ( is_wp_error( $file_write ) ) {
				$this->log( $file_write->get_error_message() );
				$this->error = $file_write;
				return $image;
			}
		}

		$gravatar_dir = trailingslashit( substr( $images['normal']['file'], 0, 3 ) );

		$src    = $wphb_fs->baseurl . $gravatar_dir . $images['normal']['file'];
		$srcset = $wphb_fs->baseurl . $gravatar_dir . $images['retina']['file'];

		$class = array( 'avatar', 'avatar-' . (int) $size, 'photo' );

		if ( $args['class'] ) {
			if ( is_array( $args['class'] ) ) {
				$class = array_merge( $class, $args['class'] );
			} else {
				$class[] = $args['class'];
			}
		}

		$avatar = sprintf(
			"<img alt='%s' src='%s' srcset='%s' class='%s' height='%d' width='%d'/>",
			esc_attr( $alt ),
			esc_url( $src ),
			esc_attr( "$srcset 2x" ),
			esc_attr( join( ' ', $class ) ),
			(int) $size,
			(int) $size
		);

		return $avatar;
	}

	/**
	 * Get avatar url.
	 *
	 * @since  1.6.1
	 * @param  array $args        Arguments passed to get_avatar_data(), after processing.
	 * @param  mixed $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
	 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @return mixed
	 */
	public function get_avatar_data( $args, $id_or_email ) {
		/* @var Filesystem $wphb_fs */
		global $wphb_fs;

		$email_hash = $this->get_email_hash( $id_or_email );

		if ( ! $args['found_avatar'] ) {
			return $args;
		}

		// Try to save the avatar.
		$file = $email_hash . 'x' . $args['size'] . '.jpg';

		if ( ! $wphb_fs->find( $file, true ) && isset( $args['url'] ) ) {
			$remote_avatar = wp_remote_get( $args['url'] );
			// If error fetching avatar.
			if ( is_wp_error( $remote_avatar ) ) {
				$this->error = $remote_avatar;
				return $args;
			}
			// Write index.html file to directory.
			$wphb_fs->write( 'index.html', '', true );
			// Write gravatar file.
			$file_write = $wphb_fs->write( $file, $remote_avatar['body'], true );

			// If error creating file - log and return original image.
			if ( is_wp_error( $file_write ) ) {
				$this->log( $file_write->get_error_message() );
				$this->error = $file_write;
				return $args;
			}
		}

		$gravatar_dir = trailingslashit( substr( $file, 0, 3 ) );
		$args['url']  = $wphb_fs->baseurl . $gravatar_dir . $file;

		return $args;
	}

	/**
	 * Get module status.
	 *
	 * @param bool $current  Current status.
	 *
	 * @return bool
	 */
	public function module_status( $current ) {
		$options = $this->get_options();
		if ( ! $options['enabled'] ) {
			return false;
		}

		return $current;
	}

}
