<?php
/**
 * Minify group class.
 *
 * @package Hummingbird\Core\Modules\Minify
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 */

namespace Hummingbird\Core\Modules\Minify;

use Hummingbird\Core\Filesystem;
use Hummingbird\Core\Utils;
use stdClass;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Minify_Group
 */
class Minify_Group {

	/**
	 * List of handles
	 *
	 * @var array
	 */
	private $handles = array();

	/**
	 * List of handle URLs
	 *
	 * @var array
	 */
	private $handle_urls = array();

	/**
	 * List of handle version
	 *
	 * @var array
	 */
	private $handle_versions = array();

	/**
	 * List of extra attributes. It includes 'after' attributes
	 *
	 * @var array
	 */
	private $extra = array();

	/**
	 * Arguments.
	 *
	 * @var string
	 */
	private $args = '';

	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = '';

	/**
	 * What handles should not be minified
	 *
	 * @var array
	 */
	private $dont_minify = array();

	/**
	 * What handles should not be combined
	 *
	 * @var array
	 */
	private $dont_combine = array();

	/**
	 * What handles should not be enqueued
	 *
	 * @var array
	 */
	private $dont_enqueue = array();

	/**
	 * What handles should not be deferred
	 *
	 * @var array
	 */
	private $defer = array();

	/**
	 * What handles should not be inlined
	 *
	 * @var array
	 */
	private $inline = array();

	/**
	 * What handles should be preloaded.
	 *
	 * @var array
	 */
	private $preload = array();

	/**
	 * What handles should by loaded asynchronously.
	 *
	 * @var array
	 */
	private $async = array();

	/**
	 * Save dependencies for each handle
	 *
	 * @var array
	 */
	private $handle_dependencies = array();

	/**
	 * Save original size for each handle
	 *
	 * @var array
	 */
	private $handle_original_sizes = array();

	/**
	 * Save compressed size for each handle
	 *
	 * @var array
	 */
	private $handle_compressed_sizes = array();

	/**
	 * Unique hash for this group
	 *
	 * @var string
	 */
	public $hash;

	/**
	 * Unique group ID
	 *
	 * Normally used for wp_enqueue_* functions
	 * Needs to be set if you need to get the groups list dependencies
	 *
	 * @var string
	 */
	public $group_id = '';

	/**
	 * The file CPT ID
	 *
	 * @var int $file_id
	 */
	public $file_id = 0;

	/**
	 * Minify_Group constructor.
	 *
	 * @param array $var_values  Values.
	 */
	public function __construct( $var_values = array() ) {
		if ( $var_values ) {
			foreach ( $var_values as $var_name => $var_value ) {
				$this->$var_name = $var_value;
			}
		}

		$this->refresh_hash();
	}

	/**
	 * Get an instance of Minify_Group based on wphb_minify_group CPT ID
	 *
	 * @param int $post_id  Post ID.
	 *
	 * @return Minify_Group|false
	 */
	public static function get_instance_by_post_id( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || get_post_type( $post ) !== 'wphb_minify_group' ) {
			return false;
		}

		$_vars = get_class_vars( 'Hummingbird\\Core\\Modules\\Minify\\Minify_Group' );
		$vars  = array();
		foreach ( $_vars as $_var_name => $_var_default ) {
			$value = get_post_meta( $post_id, '_' . $_var_name, true );
			if ( false !== $value ) {
				$vars[ $_var_name ] = $value;
			} else {
				$vars[ $_var_name ] = $_var_default;
			}
		}

		return new self( $vars );
	}

	/**
	 * Return a list of WP_Posts with post_type = wphb_minify_group
	 *
	 * @return array
	 */
	public static function get_minify_groups() {
		$posts = wp_cache_get( 'wphb_minify_groups' );

		if ( false === $posts ) {
			$posts = get_posts(
				array(
					'post_type'      => 'wphb_minify_group',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
				)
			);
			wp_cache_set( 'wphb_minify_groups', $posts );
		}

		return empty( $posts ) ? array() : $posts;
	}

	/**
	 * Get the groups where a handle is
	 *
	 * @param string $handle  Handle.
	 * @param string $type    Type.
	 *
	 * @return array list of Minify_Group items
	 */
	public static function get_groups_from_handle( $handle, $type ) {
		$groups = array();
		$posts  = self::get_minify_groups();
		foreach ( $posts as $post ) {
			$group = self::get_instance_by_post_id( $post->ID );
			if ( $group && $type === $group->type && in_array( $handle, $group->get_handles(), true ) ) {
				$groups[] = $group;
			}
		}
		return $groups;
	}

	/**
	 * Add a single handle to the group
	 *
	 * @param string $handle   Handle.
	 * @param string $url      Url.
	 * @param string $version  Source version (specified by WP).
	 */
	public function add_handle( $handle, $url, $version = '' ) {
		$this->handles[]                          = $handle;
		$this->handle_urls[ $handle ]             = $url;
		$this->handle_versions[ $handle ]         = $version;
		$this->handle_dependencies[ $handle ]     = array();
		$this->handle_compressed_sizes[ $handle ] = 0;
		$this->handle_original_sizes[ $handle ]   = 0;

		/**
		 * Filter the resource (blocked or not)
		 *
		 * @usedby wphb_filter_resource_block()
		 *
		 * @var bool false
		 * @var string $handle Source slug
		 * @var string $source_url Source URL
		 * @var string $type scripts|styles @deprecated
		 */
		if ( apply_filters( 'wphb_block_resource', false, $handle, $this->type, $url, null ) ) {
			$this->should_do_handle( $handle, 'enqueue', false );
		}

		/**
		 * Filter the resource (minify or not)
		 *
		 * @usedby wphb_filter_resource_minify()
		 *
		 * @var bool $minify_resource
		 * @var string $handle Source slug
		 * @var string $source_url Source URL
		 * @var string $type scripts|styles
		 */
		if ( ! apply_filters( 'wphb_minify_resource', true, $handle, $this->type, $url ) ) {
			$this->should_do_handle( $handle, 'minify', false );
		}

		/**
		 * Filter the resource (combine or not)
		 *
		 * @usedby wphb_filter_resource_combine()
		 *
		 * @var bool false
		 * @var string $handle Source slug
		 * @var string $source_url Source URL
		 * @var string $type scripts|styles
		 */
		if ( ! apply_filters( 'wphb_combine_resource', true, $handle, $this->type, $url ) ) {
			$this->should_do_handle( $handle, 'combine', false );
		}

		if ( 'scripts' === $this->type ) {
			/**
			 * Filter the resource (defer or not)
			 *
			 * @usedby wphb_filter_resource_defer()
			 *
			 * @var bool false
			 * @var string $handle Source slug
			 * @var string $source_url Source URL
			 * @var string $type scripts|styles
			 */
			if ( apply_filters( 'wphb_defer_resource', false, $handle, $this->type, $url ) ) {
				$this->should_do_handle( $handle, 'defer', true );
			}

			/**
			 * Filter the resource (async or not)
			 *
			 * @since 3.1.0
			 *
			 * @usedby wphb_filter_resource_async()
			 *
			 * @var bool false
			 * @var string $handle  Source slug.
			 * @var string $url     Source url.
			 * @var string $type    Type: scripts|styles.
			 */
			if ( apply_filters( 'wphb_async_resource', false, $handle, $this->type, $url ) ) {
				$this->should_do_handle( $handle, 'async', true );
			}
		}

		if ( 'styles' === $this->type ) {
			/**
			 * Filter the resource (inline or not)
			 *
			 * @usedby wphb_filter_resource_inline()
			 *
			 * @var bool false
			 * @var string $handle Source slug
			 * @var string $url Source url
			 * @var string $type scripts|styles
			 */
			if ( apply_filters( 'wphb_inline_resource', false, $handle, $this->type, $url ) ) {
				$this->should_do_handle( $handle, 'inline', true );
			}
		}

		/**
		 * Filter the resource (preload or not)
		 *
		 * @since 3.1.0
		 *
		 * @usedby wphb_filter_resource_preload()
		 *
		 * @var bool false
		 * @var string $handle  Source slug.
		 * @var string $url     Source url.
		 * @var string $type    Type: scripts|styles.
		 */
		if ( apply_filters( 'wphb_preload_resource', false, $handle, $this->type, $url ) ) {
			$this->should_do_handle( $handle, 'preload', true );
		}

		$this->refresh_hash();
	}

	/**
	 * Remove a single handle from the group
	 *
	 * @param string $handle  Handle.
	 */
	public function remove_handle( $handle ) {
		$key = array_search( $handle, $this->handles, true );
		if ( $key > - 1 ) {
			unset( $this->handles[ $key ] );
			unset( $this->handle_urls[ $handle ] );
			unset( $this->handle_versions[ $handle ] );
			unset( $this->handle_compressed_sizes[ $handle ] );
			unset( $this->handle_original_sizes[ $handle ] );
			$this->remove_handle_dependencies( $handle );
			$this->should_do_handle( $handle, 'minify', true );  // This will remove the handle from $this->dont_minify.
			$this->should_do_handle( $handle, 'combine', true ); // This will remove the handle from $this->dont_combine.
			$this->should_do_handle( $handle, 'enqueue', true ); // This will remove the handle from $this->dont_enqueue.
			$this->should_do_handle( $handle, 'defer', false );  // This will remove the handle from $this->defer.
			$this->should_do_handle( $handle, 'inline', false ); // This will remove the handle from $this->inline.
			$this->should_do_handle( $handle, 'preload', false ); // This will remove the handle from $this->preload.
			$this->should_do_handle( $handle, 'async', false ); // This will remove the handle from $this->async.
			$this->handles = array_values( $this->handles );
			$this->refresh_hash();
		}
	}

	/**
	 * Check if the group should be deferred
	 *
	 * @return bool
	 */
	public function is_deferred() {
		// All assets should be deferred to defer the whole group.
		return 'scripts' === $this->type && count( $this->get_handles() ) === count( $this->defer );
	}

	/**
	 * Check is the group should be inlined.
	 *
	 * @return bool
	 */
	public function is_inlined() {
		// All assets should be inlined to inline the whole group.
		return 'styles' === $this->type && count( $this->get_handles() ) === count( $this->inline );
	}

	/**
	 * Check is the group should be preloaded.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_preloaded() {
		// All assets should be preloaded to preload the whole group.
		return count( $this->get_handles() ) === count( $this->preload );
	}

	/**
	 * Check is the group should be asynchronously loaded.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_asynced() {
		// All assets should be asynchronously loaded to async the whole group.
		return count( $this->get_handles() ) === count( $this->async );
	}

	/**
	 * Remove all handles from the group
	 *
	 * @param array $handles  Handles.
	 */
	public function remove_handles( $handles ) {
		$handles = (array) $handles;
		array_map( array( $this, 'remove_handle' ), $handles );
	}

	/**
	 * Add new dependencies for a given handle
	 *
	 * @param string       $handle  Handle.
	 * @param string|array $dep     One or several dependencies for this handle.
	 */
	public function add_handle_dependency( $handle, $dep ) {
		if ( ! isset( $this->handle_dependencies[ $handle ] ) ) {
			return;
		}

		if ( ! is_array( $dep ) ) {
			$dep = array( $dep );
		}

		$this->handle_dependencies[ $handle ] = array_merge( $this->handle_dependencies[ $handle ], $dep );
		$this->handle_dependencies[ $handle ] = array_unique( $this->handle_dependencies[ $handle ] );
	}

	/**
	 * Remove all dependencies for a handle
	 *
	 * @param string $handle  Handle.
	 */
	public function remove_handle_dependencies( $handle ) {
		if ( isset( $this->handle_dependencies[ $handle ] ) ) {
			unset( $this->handle_dependencies[ $handle ] );
		}
	}

	/**
	 * Set the original size in Kb for a handle
	 *
	 * @param string $handle  Handle.
	 * @param float  $size    Size in Kb.
	 */
	public function set_handle_original_size( $handle, $size ) {
		$this->handle_original_sizes[ $handle ] = number_format_i18n( str_replace( ',', '', $size ) / 1000, 1 );
	}

	/**
	 * Get the original size in Kb for a handle
	 *
	 * @param string $handle  Handle.
	 *
	 * @return float Original size in Kb
	 */
	public function get_handle_original_size( $handle ) {
		if ( isset( $this->handle_original_sizes[ $handle ] ) ) {
			return $this->handle_original_sizes[ $handle ];
		}
		return 0;
	}

	/**
	 * Set the compressed size in Kb for a handle
	 *
	 * @param string $handle  Handle.
	 * @param float  $size    Size in Kb.
	 */
	public function set_handle_compressed_size( $handle, $size ) {
		$this->handle_compressed_sizes[ $handle ] = number_format_i18n( str_replace( ',', '', $size ) / 1000, 1 );
	}

	/**
	 * Get the compressed size in Kb for a handle
	 *
	 * @param string $handle  Handle.
	 *
	 * @return float Compressed size in Kb
	 */
	public function get_handle_compressed_size( $handle ) {
		if ( isset( $this->handle_compressed_sizes[ $handle ] ) ) {
			return $this->handle_compressed_sizes[ $handle ];
		}
		return 0;
	}

	/**
	 * Get the list of dependencies for a handle
	 *
	 * @param string $handle  Handle.
	 *
	 * @return array|mixed
	 */
	public function get_handle_dependencies( $handle ) {
		return isset( $this->handle_dependencies[ $handle ] ) ? $this->handle_dependencies[ $handle ] : array();
	}

	/**
	 * Return the complete list of dependencies of all handles in this group
	 *
	 * @return array
	 */
	public function get_all_handles_dependencies() {
		$all_deps = array();
		foreach ( $this->handle_dependencies as $handle => $deps ) {
			$all_deps = array_merge( $all_deps, $deps );
		}

		return array_unique( $all_deps );
	}

	/**
	 * Removes handle/s but returns a new instance of Minify_Group with the same parameters
	 * but only with the specified handles
	 *
	 * @param array|string $handles  One or more handles to remove from the group.
	 *
	 * @return Minify_Group
	 */
	public function slice_handles( $handles ) {
		if ( ! is_array( $handles ) ) {
			$handles = array();
		}

		$new_group = clone $this;
		$this->remove_handles( $handles );

		// Remove those handles that we don't need in the new group.
		$new_group->remove_handles( $this->get_handles() );

		$this->refresh_hash();

		return $new_group;
	}

	/**
	 * Get the list of handles
	 *
	 * @return array
	 */
	public function get_handles() {
		return is_array( $this->handles ) ? $this->handles : array();
	}

	/**
	 * Set if a handle should be minified|combined|enqueue or not. If $value param is null, it will
	 * return a boolean indicating if that handle should be minified|combined|enqueue or not
	 *
	 * @param string    $handle  Handle.
	 * @param string    $action  minify|combine|enqueue.
	 * @param null|bool $value   Action value.
	 *
	 * @return bool|null
	 */
	public function should_do_handle( $handle, $action, $value = null ) {
		switch ( $action ) {
			case 'minify':
				$should = 'dont_minify';
				$do     = 'dont';
				break;
			case 'combine':
				$should = 'dont_combine';
				$do     = 'dont';
				break;
			case 'enqueue':
				$should = 'dont_enqueue';
				$do     = 'dont';
				break;
			case 'defer':
				$should = 'defer';
				$do     = 'do';
				break;
			case 'inline':
				$should = 'inline';
				$do     = 'do';
				break;
			case 'preload':
				$should = 'preload';
				$do     = 'do';
				break;
			case 'async':
				$should = 'async';
				$do     = 'do';
				break;
			default:
				return null;
		}

		if ( ! is_null( $value ) ) {
			$value = (bool) $value;
			if ( 'dont' === $do ) {
				// Handle should or shouldn't be minified.
				if ( ! $value && ! in_array( $handle, $this->$should, true ) ) {
					$new_should    = $this->$should;
					$new_should[]  = $handle;
					$this->$should = $new_should;
				} elseif ( $value && in_array( $handle, $this->$should, true ) ) {
					// Remove from the array.
					$new_should = $this->$should;
					$key        = array_search( $handle, $new_should, true );
					unset( $new_should[ $key ] );
					$this->$should = array_values( $new_should );
				}
			} else {
				// Handle should or shouldn't be done.
				if ( $value && ! in_array( $handle, $this->$should, true ) ) {
					$new_should    = $this->$should;
					$new_should[]  = $handle;
					$this->$should = $new_should;
				} elseif ( ! $value && in_array( $handle, $this->$should, true ) ) {
					// Remove from the array.
					$new_should = $this->$should;
					$key        = array_search( $handle, $new_should, true );
					unset( $new_should[ $key ] );
					$this->$should = array_values( $new_should );
				}
			}
		} else {
			// Return the value.
			if ( 'dont' === $do ) {
				return ! in_array( $handle, $this->$should, true );
			}

			return in_array( $handle, $this->$should, true );
		}
		return null;
	}

	/**
	 * Get a list of assets that do not need to be combined.
	 *
	 * @return array
	 */
	public function get_dont_combine_list() {
		return $this->dont_combine;
	}

	/**
	 * Get a list of assets that do not need to be enqueued.
	 *
	 * @return array
	 */
	public function get_dont_enqueue_list() {
		return $this->dont_enqueue;
	}

	/**
	 * Get a list of assets to defer.
	 *
	 * @return array
	 */
	public function get_defer_list() {
		return $this->defer;
	}

	/**
	 * Get a list of assets to inline.
	 *
	 * @return array
	 */
	public function get_inline_list() {
		return $this->inline;
	}

	/**
	 * Get a list of assets to preload.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public function get_preload_list() {
		return $this->preload;
	}

	/**
	 * Get a list of assets to async.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	public function get_async_list() {
		return $this->async;
	}

	/**
	 * In some cases (when an asset is not minified and there's just one handle)
	 * a file should not be generated and should pick the default one instead
	 */
	public function should_generate_file() {
		$cdn_status = Utils::get_module( 'minify' )->get_cdn_status();
		// Always generate file when uploading to CDN and file is not excluded from CDN.
		if ( $cdn_status && apply_filters( 'wphb_cdn_resource', $cdn_status, $this->get_handles(), $this->type ) ) {
			return true;
		}

		$handles = $this->get_handles();

		if ( count( $handles ) === 1 && ! $this->should_do_handle( $handles[0], 'minify' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return the URL for a given handle
	 *
	 * @param string $handle  Handle.
	 *
	 * @return string
	 */
	public function get_handle_url( $handle ) {
		// RTL compatibility.
		if ( 'styles' === $this->type ) {
			if ( isset( wp_styles()->text_direction ) && 'rtl' === wp_styles()->text_direction && isset( $this->extra['rtl'] ) && $this->extra['rtl'] ) {
				if ( is_bool( $this->extra['rtl'] ) || 'replace' === $this->extra['rtl'] ) {
					$suffix    = isset( $this->extra['suffix'] ) ? $this->extra['suffix'] : '';
					$file_path = str_replace( "{$suffix}.css", "-rtl{$suffix}.css", wp_styles()->_css_href( $this->handle_urls[ $handle ], '', "$handle-rtl" ) );
				} else {
					$file_path = wp_styles()->_css_href( $this->extra['rtl'], '', "$handle-rtl" );
				}

				return $file_path;
			}
		}

		return $this->handle_urls[ $handle ];
	}

	/**
	 * Add an extra attribute
	 *
	 * @param string $key    Key.
	 * @param mixed  $value  Value.
	 */
	public function add_extra( $key, $value ) {
		$this->extra[ $key ] = $value;
		$this->refresh_hash();
	}

	/**
	 * Return all extra attributes
	 *
	 * @return array
	 */
	public function get_extra() {
		return $this->extra;
	}

	/**
	 * Delete an extra attribute
	 *
	 * @param string $key  Key.
	 */
	public function delete_extra( $key ) {
		if ( isset( $this->extra[ $key ] ) ) {
			unset( $this->extra[ $key ] );
			$this->refresh_hash();
		}
	}

	/**
	 * Add an after attribute. Normally us by add_inline_script/style functions
	 *
	 * @param string|array $new_after  New after attributes.
	 */
	public function add_after( $new_after ) {
		$after = $this->get_after();

		if ( ! is_array( $new_after ) ) {
			$new_after = array( $new_after );
		}

		$after                = array_merge( $new_after, $after );
		$this->extra['after'] = $after;
		$this->refresh_hash();
	}

	/**
	 * Add a before attribute. Normally us by add_inline_script/style functions
	 *
	 * @param string|array $new_before  New before attributes.
	 */
	public function add_before( $new_before ) {
		$before = $this->get_before();

		if ( ! is_array( $new_before ) ) {
			$new_before = array( $new_before );
		}

		$before                = array_merge( $new_before, $before );
		$this->extra['before'] = $before;
		$this->refresh_hash();
	}

	/**
	 * Add an after attribute. Normally us by add_inline_script/style functions
	 *
	 * @param string|array $new_data  New data attributes.
	 */
	public function add_data( $new_data ) {
		$data = $this->get_data();

		if ( ! is_array( $new_data ) ) {
			$new_data = array( $new_data );
		}

		if ( ! is_array( $data ) ) {
			$data = array( $data );
		}

		$data                = array_unique( array_merge( $new_data, $data ) );
		$this->extra['data'] = $data;
		$this->refresh_hash();
	}

	/**
	 * Return after attribute
	 *
	 * @return array
	 */
	public function get_after() {
		return isset( $this->extra['after'] ) ? $this->extra['after'] : array();
	}

	/**
	 * Return after attribute
	 *
	 * @return array
	 */
	public function get_before() {
		return isset( $this->extra['before'] ) ? $this->extra['before'] : array();
	}

	/**
	 * Return data attribute
	 *
	 * @return array
	 */
	public function get_data() {
		$data = isset( $this->extra['data'] ) ? $this->extra['data'] : array();
		if ( ! is_array( $data ) ) {
			$data = array( $data );
		}
		return $data;
	}

	/**
	 * Return the args attribute
	 *
	 * @return string
	 */
	public function get_args() {
		return $this->args;
	}

	/**
	 * Set the args attribute value
	 *
	 * @param string $value  Value.
	 */
	public function set_args( $value ) {
		$this->args = $value;
		$this->refresh_hash();
	}

	/**
	 * Set the group type: scripts|styles
	 *
	 * @param string $type  Type.
	 */
	public function set_type( $type ) {
		$this->type = $type;
		$this->refresh_hash();
	}

	/**
	 * Checks if the group is expired by comparing expiration time set in the file
	 * and handles versions hashes
	 *
	 * @return bool
	 */
	public function is_expired() {
		return ( time() > $this->expires_on() ) || ( $this->get_file_version_hash() !== $this->get_versions_hash() );
	}

	/**
	 * Refresh the unique hash for this group
	 */
	public function refresh_hash() {
		$handles          = $this->get_handles();
		$handles_versions = $this->handle_versions;

		if ( ! is_array( $handles ) ) {
			$handles = array( $handles );
		}

		if ( ! is_array( $handles_versions ) ) {
			$handles_versions = array( $handles_versions );
		}

		$hash       = implode( '-', $handles );
		$hash      .= $this->args;
		$hash      .= $this->type;
		$hash      .= implode( '-', $handles_versions );
		$this->hash = self::hash( $hash );

		if ( $this->file_id ) {
			update_post_meta( $this->file_id, '_hash', $this->hash );
		}
	}

	/**
	 * Get version hash.
	 *
	 * @return string
	 */
	public function get_versions_hash() {
		return self::hash( $this->handle_versions );
	}

	/**
	 * Get source hash.
	 *
	 * @return string
	 */
	public function get_sources_hash() {
		if ( 'styles' === $this->type && isset( $GLOBALS['text_direction'] ) && 'rtl' === $GLOBALS['text_direction'] ) {
			if ( isset( $this->extra['rtl'] ) && 'replace' === $this->extra['rtl'] ) {
				$handles = $this->handle_urls;
				array_push( $handles, 'replace' );
				return self::hash( $handles );
			}
		}

		return self::hash( $this->handle_urls );
	}

	/**
	 * General purpose function. Returns an array hashed
	 *
	 * @param array|string $list Array of strings or single string.
	 *
	 * @return string
	 */
	private static function hash( $list ) {
		return wp_hash( maybe_serialize( $list ) );
	}


	/**
	 * Process the group. Minifies/combine... everything
	 */
	public function process_group() {
		if ( ! $this->should_generate_file() ) {
			// Nothing to process, we'll use the default handle URL instead.
			return false;
		}

		$minify_module = Utils::get_module( 'minify' );

		$handles = $this->get_handles();

		// First file is for the header.
		$files_data = array();

		foreach ( $handles as $handle ) {
			$src = $this->get_handle_url( $handle );

			if ( ! $src ) {
				$minify_module->errors_controller->add_error(
					$handle,
					$this->type,
					'empty-url',
					__( 'This file has not a linked URL, it will not be combined/minified', 'wphb' ),
					array( 'minify', 'combine' ), // Disallow minification/concat.
					array( 'minify', 'combine' )  // Disable minification/concat switchers.
				);
				continue;
			}

			$pathinfo = pathinfo( $src );
			if ( ! isset( $pathinfo['extension'] ) || ( isset( $pathinfo['extension'] ) && ! preg_match( '/(css|js)\??[a-zA-Z=0-9]*/', $pathinfo['extension'] ) ) ) {
				$minify_module->errors_controller->add_error(
					$handle,
					$this->type,
					'wrong-extension',
					__( "This file included in your output can't be minified or combined", 'wphb' ),
					array( 'minify', 'combine' ), // Disallow minification/concat.
					array( 'minify', 'combine' )  // Disable minification/concat switchers.
				);
				continue;
			}

			$minify_module->log( 'Processing group: ' . $this->group_id . ' (handles: ' . implode( ', ', $handles ) . ')' );

			// Get the full URL.
			if ( ! preg_match( '|^(https?:)?//|', $src ) ) {
				$src = site_url( $src );
			}

			$content  = false;
			$is_local = $this->is_handle_local( $handle );

			if ( $is_local ) {
				$path = Utils::src_to_path( $src );
				if ( is_file( $path ) ) {
					$content = file_get_contents( $path );
				}
			}

			if ( false === $content ) {
				// Try to get the file remotely.
				if ( ! preg_match( '/^https?:/', $src ) ) {
					// Rooted URL.
					$src = 'http:' . $src;
				}
				$request = wp_remote_get(
					$src,
					array(
						'sslverify' => false,
					)
				);
				$content = wp_remote_retrieve_body( $request );
				if ( is_wp_error( $request ) ) {
					$minify_module->log( $request->get_error_message() );
				} elseif ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
					$minify_module->log( 'Code different from 200. Truncated content:' );
					$minify_module->log( substr( $content, 0, 1000 ) );
				}
			}

			// If nothing worked do not minify and do not combine file.
			if ( empty( $content ) ) {
				$minify_module->errors_controller->add_error(
					$handle,
					$this->type,
					'empty-content',
					__( 'It looks like this file is empty', 'wphb' ),
					array( 'minify', 'combine' ) // Disallow minification/concat.
				);
				continue;
			} else {
				/**
				 * Possibly clear errors.
				 * $minification_module->errors_controller->clear_handle_error( $handle, $this->type );
				 */
				$minify_module->log( 'Asset ' . $handle . ' [' . $this->type . '] has been successfully processed.' );
			}

			/**
			 * Maybe do this?
			 * $this->set_handle_original_size( $handle, absint( mb_strlen( $content ) ) );
			 */
			// Remove BOM.
			$content = preg_replace( "/^\xEF\xBB\xBF/", '', $content );

			// Concatenate and minify scripts/styles!
			if ( 'scripts' === $this->type ) {
				$minify_module->log( 'Minify script ' . $handle );
			} elseif ( 'styles' === $this->type ) {
				$minify_module->log( 'Minify style ' . $handle );
				if ( $is_local ) {
					/**
					 * Previously we tried replacing relative URLs here.
					 * $content = self::replace_relative_urls( dirname( $path ), $content );
					 */
					$content = URI_Rewriter::prepend( $content, trailingslashit( dirname( $src ) ) );
				}

				if ( preg_match_all( '/(?<fullImport>@import\s?.*?;)/', $content, $matches ) ) {
					// We can't allow @import directives in files.
					$minify_module->errors_controller->add_error(
						$handle,
						$this->type,
						'import-not-allowed',
						__( '@import directive is not allowed in stylesheets', 'wphb' ),
						array( 'minify', 'combine' ), // Disallow minification/concat.
						array( 'minify', 'combine' ) // Disable minify/concat switchers.
					);
					continue;
				}
			}

			if ( empty( $content ) ) {
				$minify_module->log( 'Empty content after minification' );

				// Something happened to compression.
				$minify_module->errors_controller->add_error(
					$handle,
					$this->type,
					'after-compression',
					__( 'Hummingbird could not parse the content of this file', 'wphb' ),
					array( 'minify', 'combine' )
				);
			} else {
				/**
				 * Possible to clear handle error.
				 * $minification_module->errors_controller->clear_handle_error( $handle, $this->type );
				 */
				$files_data[] = array(
					'handle'  => $handle,
					'content' => apply_filters( 'wphb_minify_file_content', $content ),
					'minify'  => $this->should_do_handle( $handle, 'minify' ),
				);
			}
		}

		unset( $content );

		if ( empty( $files_data ) ) {
			return false;
		}

		/**
		 * If the files should be kept remote in CDN
		 */
		$upload_to_cdn = apply_filters( 'wphb_cdn_resource', $minify_module->get_cdn_status(), $handles, $this->type );

		/**
		 * Function that will get files minified
		 *
		 * The callback should return WP_Error or array with the file details:
		 * {
		 * 'file' => absolute file path if the file is saved locally
		 * }
		 */
		$minify_callback = apply_filters( 'wphb_minify_callback', array( 'Hummingbird\\Core\\Modules\\Minify\\Minify_Group', 'process_remote_files' ) );

		if ( ! is_callable( $minify_callback ) ) {
			return new WP_Error( 'error', __( 'Minify callback does not exist', 'wphb' ) );
		}

		// Generate the file.
		$result = call_user_func_array( $minify_callback, array( $files_data, $upload_to_cdn, $this ) );

		if ( is_wp_error( $result ) ) {
			// Something happened to compression on the API, but it's probably not a server error.
			if ( 2001 === $result->get_error_code() ) {
				$action  = 1 < count( $handles ) ? array( 'combine' ) : array( 'minify' );
				$disable = 1 < count( $handles ) ? array() : array( 'minify' );

				foreach ( $handles as $handle ) {
					$minify_module->log( 'Error compressing handle ' . $handle . ': ' . $result->get_error_message() );
					$minify_module->errors_controller->add_error( $handle, $this->type, 'after-compression', $result->get_error_message(), $action, $disable );
				}
				return false;
			}

			// Save server error (will disable minification after a certain threshold).
			return $result;
		}

		$result = (array) $result;
		$minify_module->log( 'Group optimized, hash: ' . $result['hash'] );
		self::insert_group( $this, $result );
		return true;
	}

	/**
	 * Insert group.
	 *
	 * @param Minify_Group $group  Group.
	 * @param array        $file   Asset.
	 *
	 * @return bool
	 */
	public static function insert_group( $group, $file ) {
		$post_title   = $group->get_sources_hash() . '-' . $group->type;
		$post_content = $file['response'];

		/**
		 * This get_posts is important!
		 *
		 * Try to verify if the post with the same title and content already exists, maybe there is no need to create a mew one
		 * we can just update the old version.
		 *
		 * @since 1.9.2  Added as a way to fix the unlimited posts with the same name issue.
		 */
		$post_ids = get_posts(
			array(
				'name'        => $post_title,
				'post_type'   => 'wphb_minify_group',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		$post_id  = empty( $post_ids ) ? 0 : array_shift( $post_ids );

		/**
		 * Insert the new file in posts table.
		 *
		 * The ID argument acts as an insurance that a post with the same name does not exist already.
		 * No two posts should have a similar hash, unless it is two exactly same files. In that case we will use
		 * one post for both.
		 *
		 * In any case, if the above get_posts() does not find any posts, an ID = 0 will create a new post.
		 */
		$post_id = wp_insert_post(
			array(
				'ID'           => $post_id,
				'post_title'   => $post_title,
				'post_status'  => 'publish',
				'post_type'    => 'wphb_minify_group',
				'post_content' => wp_slash( $post_content ),
			)
		);

		if ( $post_id ) {
			wp_cache_delete( 'wphb_minify_groups' );

			$group->file_id = $post_id;

			if ( isset( $file['atts'] ) ) {
				// save information about each file.
				foreach ( $file['atts'] as $item ) {
					$item = (array) $item;
					$group->set_handle_original_size( $item['handle'], $item['original-size'] );
					$group->set_handle_compressed_size( $item['handle'], $item['compressed-size'] );
				}
			}

			$expire_on = apply_filters( 'wphb_file_expiration', MONTH_IN_SECONDS * 2 ) + time(); // 2 months;
			$vars      = get_object_vars( $group );

			// Do not save this metadata.
			$exclude_vars = array( 'group_id' );

			foreach ( $vars as $var => $value ) {
				if ( in_array( $var, $exclude_vars, true ) ) {
					continue;
				}
				update_post_meta( $group->file_id, '_' . $var, $value );
			}

			if ( 'content' === $file['type'] ) {
				// Upload contents to filesystem
				// Any user can upload this as is made during front request.
				add_filter( 'upload_mimes', array( 'Hummingbird\\Core\\Modules\Minify\\Minify_Group', 'upload_mimes' ), 999 );

				$suffix = '';
				if ( 'styles' === $group->type ) {
					// We don't use global $wp_styles, because it is not there during cron.
					$wp_styles = wp_styles();

					if ( isset( $wp_styles->text_direction ) && 'rtl' === $wp_styles->text_direction && isset( $group->extra['rtl'] ) && $group->extra['rtl'] ) {
						if ( is_bool( $group->extra['rtl'] ) || 'replace' === $group->extra['rtl'] ) {
							$suffix = isset( $group->extra['suffix'] ) ? "-rtl{$group->extra['suffix']}" : '-rtl';
						}
					}
				}

				$filename = $group->hash . $suffix . '.' . ( 'scripts' === $group->type ? 'js' : 'css' );
				do_action( 'wp_hummingbird_before_upload_minify_group', $filename, $file['response'] );
				$upload = Filesystem::handle_file_upload( $filename, $file['response'] );
				do_action( 'wp_hummingbird_after_upload_minify_group', $filename, $file['response'], $upload );
				remove_filter( 'upload_mimes', array( 'Hummingbird\\Core\\Modules\Minify\\Minify_Group', 'upload_mimes' ), 999 );

				if ( is_wp_error( $upload ) ) {
					// Save error and delete post.
					wp_delete_post( $post_id, true );
					wp_cache_delete( 'wphb_minify_groups' );
					return false;
				}

				$url = empty( $suffix ) ? $upload['url'] : str_replace( $suffix, '', $upload['url'] );

				update_post_meta( $group->file_id, '_url', $url );
				update_post_meta( $group->file_id, '_path', $upload['file'] );
			} else {
				// Just save URL.
				update_post_meta( $group->file_id, '_url', $file['response'] );
			}

			update_post_meta( $group->file_id, '_expires', $expire_on );

			return true;
		}

		return false;
	}

	/**
	 * Upload mimes.
	 *
	 * @internal
	 */
	public static function upload_mimes() {
		return array(
			'js'  => 'application/javascript',
			'css' => 'text/css',
		);
	}

	/**
	 * Process remote files.
	 *
	 * @param array $files          List of files.
	 * @param bool  $upload_to_cdn  Upload to CDN.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function process_remote_files( $files, $upload_to_cdn = true ) {
		$f           = new stdClass();
		$f->contents = array();
		$f->hash     = $this->hash;
		$f->type     = $this->type;
		$f->expires  = $this->expires_on();
		$f->cdn      = $upload_to_cdn;
		foreach ( $files as $file ) {
			$c             = new stdClass();
			$c->handle     = $file['handle'];
			$c->content    = $file['content'];
			$c->minify     = $file['minify'];
			$f->contents[] = $c;
		}

		$upload = Utils::get_api()->minify->process_files( array( $f ) );

		if ( is_wp_error( $upload ) ) {
			return $upload;
		}

		if ( false !== $upload->files[0]->error && ! empty( $upload->files[0]->message ) ) {
			return new WP_Error( '2001', $upload->files[0]->message );
		}

		return $upload->files[0];
	}

	/**
	 * Delete the minified/combine file for this group
	 */
	public function delete_file() {
		if ( get_post( $this->file_id ) && 'wphb_minify_group' === get_post_type( $this->file_id ) ) {
			// This will also delete the file. See Hummingbird\Core\Modules\Minify::on_delete_post().
			wp_delete_post( $this->file_id, true );
			$this->file_id = 0;
			wp_cache_delete( 'wphb_minify_groups' );
		}
	}

	/**
	 * Return the group content saved in the linked post
	 *
	 * @used-by Minify_Group::inline_group()
	 * @return bool|string
	 */
	public function get_group_post_content() {
		if ( $this->file_id ) {
			$post = get_post( $this->file_id );
			if ( ! $post ) {
				return false;
			}

			if ( filter_var( $post->post_content, FILTER_VALIDATE_URL ) ) {
				return file_get_contents( $post->post_content );
			}

			return $post->post_content;
		}

		if ( is_array( $this->handle_urls ) && isset( $this->inline[0] ) ) {
			$handles = $this->get_handles();
			$source  = $this->get_group_src();

			if ( $this->is_handle_local( $handles[0] ) ) {
				$path = Utils::src_to_path( $source );
				if ( is_file( $path ) ) {
					return file_get_contents( $path );
				}
			} else {
				$response = wp_remote_get( set_url_scheme( $source ) );
				if ( is_wp_error( $response ) ) {
					return false;
				}

				if ( isset( $response['body'] ) ) {
					return $response['body'];
				}
			}
		}

		return false;
	}

	/**
	 * Try to find the file attached to this group and loads it into the object
	 *
	 * @return bool
	 */
	public function maybe_load_file() {
		if ( ! $this->should_generate_file() ) {
			return false;
		}

		$posts = self::get_minify_groups();

		if ( empty( $posts ) ) {
			return false;
		}

		foreach ( $posts as $post ) {
			if ( $post->post_title === $this->get_sources_hash() . '-' . $this->type ) {
				$this->file_id = $post->ID;
				return true;
			}
		}
		return false;
	}

	/**
	 * Should process group.
	 *
	 * @return bool
	 */
	public function should_process_group() {
		$minify = Utils::get_module( 'minify' );

		$handles = $this->get_handles();

		// Always process group if CDN is enabled.
		if ( $minify->get_cdn_status() && $this->is_handle_local( $handles[0] ) ) {
			return true;
		}

		$handles_count = count( $handles );
		if ( 1 === $handles_count && ! $this->should_do_handle( $handles[0], 'minify' ) ) {
			return false;
		}

		// Check if all handles should not be processed too.
		if ( count( $this->dont_minify ) === $handles_count && count( $this->dont_combine ) === $handles_count ) {
			return false;
		}

		return true;
	}

	/**
	 * Get group source.
	 *
	 * @return mixed|string
	 */
	public function get_group_src() {
		if ( ! $this->should_process_group() || 0 === $this->file_id ) {
			$handles = $this->get_handles();
			if ( $handles ) {
				return $this->get_handle_url( $handles[0] );
			}

			return '';
		}
		return get_post_meta( $this->file_id, '_url', true );
	}

	/**
	 * Get file path.
	 *
	 * @return bool|mixed
	 */
	public function get_file_path() {
		if ( ! $this->should_generate_file() ) {
			return false;
		}
		return get_post_meta( $this->file_id, '_path', true );
	}

	/**
	 * Get expires date.
	 *
	 * @return bool|mixed
	 */
	public function expires_on() {
		if ( ! $this->should_generate_file() ) {
			return false;
		}
		return get_post_meta( $this->file_id, '_expires', true );
	}

	/**
	 * Get file version hash.
	 *
	 * @return string
	 */
	public function get_file_version_hash() {
		if ( ! $this->should_generate_file() ) {
			return '';
		}
		$versions = get_post_meta( $this->file_id, '_handle_versions', true );
		if ( false === $versions ) {
			return '';
		}
		return self::hash( $versions );
	}

	/**
	 * Attach script/style inline.
	 *
	 * @since  1.7.0
	 * @access private
	 *
	 * @param bool $in_footer     Is in footer or not.
	 * @param bool $rewrite_urls  Rewrite URLs (only required for inlining assets that are not processed by minify engine).
	 *
	 * @return bool True if successful, false if not.
	 */
	private function inline_group( $in_footer, $rewrite_urls = false ) {
		// Get file content.
		$content = $this->get_group_post_content();

		if ( $rewrite_urls ) {
			$path    = $this->get_handle_url( $this->group_id );
			$content = URI_Rewriter::prepend( $content, trailingslashit( dirname( $path ) ) );
		}

		// If content is empty - return back to enqueue the file.
		if ( empty( $content ) ) {
			return false;
		}

		$type = 'text/javascript';
		$tag  = 'script';
		if ( 'styles' === $this->type ) {
			$type = 'text/css';
			$tag  = 'style';
		}

		$action = $in_footer ? 'wp_footer' : 'wp_head';

		add_action(
			$action,
			function() use ( $tag, $type, $content ) {
				echo '<' . esc_attr( $tag ) . ' type="' . esc_attr( $type ) . '">' . htmlspecialchars_decode( $content ) . '</' . esc_attr( $tag ) . '>';
			},
			999
		);

		return true;
	}

	/**
	 * Preload asset.
	 *
	 * @since 3.1.0
	 *
	 * @param string $url  Asset URL.
	 */
	private function preload_group( $url ) {
		if ( ! in_array( $this->type, array( 'scripts', 'styles' ), true ) ) {
			return;
		}

		if ( ! $this->is_preloaded() ) {
			return;
		}

		// Remove the 's' from the end of 'scripts' & 'styles'.
		$as = substr( $this->type, 0, -1 );

		add_action(
			'wp_head',
			function() use ( $as, $url ) {
				echo '<link rel="preload" as="' . esc_attr( $as ) . '" href="' . esc_url( $url ) . '">';
			},
			999
		);
	}

	/**
	 * Enqueue the new group (only one file)
	 *
	 * @param bool  $in_footer     If must be enqueued on footer.
	 * @param array $dependencies  Dependencies.
	 */
	public function enqueue( $in_footer, $dependencies ) {
		$this->preload_group( set_url_scheme( $this->get_group_src() ) );

		// Enqueue the group.
		if ( 'scripts' === $this->type ) {
			$wp_sources = wp_scripts();

			wp_dequeue_script( $this->group_id );
			wp_deregister_script( $this->group_id );

			wp_enqueue_script(
				$this->group_id,
				set_url_scheme( $this->get_group_src() ),
				$dependencies,
				null,
				$in_footer
			);

			$group_id = $this->group_id;

			if ( $this->is_deferred() ) {
				add_filter(
					'script_loader_tag',
					function( $tag, $handle ) use ( $group_id ) {
						if ( $group_id !== $handle ) {
							return $tag;
						}
						return str_replace( ' src', ' defer src', $tag );
					},
					100,
					2
				);
			}

			if ( $this->is_asynced() ) {
				add_filter(
					'script_loader_tag',
					function( $tag, $handle ) use ( $group_id ) {
						if ( $group_id !== $handle ) {
							return $tag;
						}
						return str_replace( ' src', ' async src', $tag );
					},
					100,
					2
				);
			}

			// Add extras to the dependency.
			foreach ( $this->get_extra() as $extra_key => $extra_value ) {
				if ( 'data' === $extra_key ) {
					continue;
				}
				$wp_sources->add_data( $this->group_id, $extra_key, $extra_value );
			}

			if ( $this->get_data() ) {
				$data = implode( ';;', $this->get_data() );
				$wp_sources->add_data( $this->group_id, 'data', $data );
			}

			// Make sure that single handles from this group are not enqueued.
			foreach ( $this->get_handles() as $handle ) {
				// The single handle.
				wp_dequeue_script( $handle );

				// But it could have been enqueued with a different ID by this group before
				// This would mostly happen during Unit Testing.
				wp_dequeue_script( $this->group_id . '-' . $handle );
			}

			$handles = $this->get_handles();
			// Make sure that this element is marked as done once WordPress has enqueued it.
			add_action(
				'wp_head',
				function() use ( $handles, $group_id ) {
					$wp_scripts = wp_scripts();
					if ( in_array( $group_id, $wp_scripts->done, true ) ) {
						// If the new ID is done it means that the handle is done too.
						$wp_scripts->done = array_merge( $wp_scripts->done, $handles );
					}
				},
				999
			);

			add_action(
				'wp_footer',
				function() use ( $handles, $group_id ) {
					$wp_scripts = wp_scripts();
					if ( in_array( $group_id, $wp_scripts->done, true ) ) {
						// If the new ID is done it means that the handle is done too.
						$wp_scripts->done = array_merge( $wp_scripts->done, $handles );
					}
				},
				999
			);

			$wp_sources->groups[ $this->group_id ] = $in_footer ? 1 : 0;
		} elseif ( 'styles' === $this->type ) {
			$wp_sources = wp_styles();

			wp_dequeue_style( $this->group_id );
			wp_deregister_style( $this->group_id );

			// If set to inline, try to inline.
			$inlined = false;
			if ( $this->is_inlined() ) {
				$inlined = $this->inline_group( $in_footer );
			}
			// Enqueue generated asset if not inlined.
			if ( ! $this->is_inlined() || ! $inlined ) {
				wp_enqueue_style(
					$this->group_id,
					set_url_scheme( $this->get_group_src() ),
					$dependencies,
					null,
					$this->get_args()
				);
			}

			// Add extras to the dependency.
			foreach ( $this->get_extra() as $extra_key => $extra_value ) {
				$wp_sources->add_data( $this->group_id, $extra_key, $extra_value );
			}

			// Make sure that single handles from this group are not enqueued.
			foreach ( $this->get_handles() as $handle ) {
				// The single style.
				wp_dequeue_style( $handle );

				// It could have been enqueued with a different ID by this group before
				// This would mostly happen during Unit Testing, we can remove it safely.
				wp_dequeue_style( $this->group_id . '-' . $handle );
			}

			$group_id = $this->group_id;
			$handles  = $this->get_handles();
			// Make sure that this element is marked as done once WordPress has enqueued it.
			add_action(
				'wp_head',
				function() use ( $handles, $group_id ) {
					$wp_styles = wp_styles();
					if ( in_array( $group_id, $wp_styles->done, true ) ) {
						// If the new ID is done it means that the handle is done too.
						$wp_styles->done = array_merge( $wp_styles->done, $handles );
					}
				},
				999
			);

			add_action(
				'wp_footer',
				function() use ( $handles, $group_id ) {
					$wp_styles = wp_styles();
					if ( in_array( $group_id, $wp_styles->done, true ) ) {
						// If the new ID is done it means that the handle is done too.
						$wp_styles->done = array_merge( $wp_styles->done, $handles );
					}
				},
				999
			);
		}
	}

	/**
	 * Enqueue just one handle with its original URL but will change the slug
	 *
	 * @param string $handle        Handle.
	 * @param bool   $in_footer     If must be enqueued on footer.
	 * @param array  $dependencies  List of dependencies.
	 */
	public function enqueue_one_handle( $handle, $in_footer, $dependencies = array() ) {
		$this->preload_group( set_url_scheme( $this->get_handle_url( $handle ) ) );

		if ( 'scripts' === $this->type ) {
			wp_enqueue_script(
				$handle,
				set_url_scheme( $this->get_handle_url( $handle ) ),
				$dependencies,
				null,
				$in_footer
			);

			$group_id = $this->group_id;

			if ( $this->is_deferred() ) {
				add_filter(
					'script_loader_tag',
					function( $tag, $handle ) use ( $group_id ) {
						if ( $group_id !== $handle ) {
							return $tag;
						}
						return str_replace( ' src', ' defer src', $tag );
					},
					100,
					2
				);
			}

			if ( $this->is_asynced() ) {
				add_filter(
					'script_loader_tag',
					function( $tag, $handle ) use ( $group_id ) {
						if ( $group_id !== $handle ) {
							return $tag;
						}
						return str_replace( ' src', ' async src', $tag );
					},
					100,
					2
				);
			}
		} elseif ( 'styles' === $this->type ) {
			// If set to inline, try to inline.
			$inlined = false;
			if ( $this->is_inlined() ) {
				wp_dequeue_style( $this->group_id );
				wp_deregister_style( $this->group_id );
				$inlined = $this->inline_group( $in_footer, true );
			}

			// Enqueue generated asset if not inlined.
			if ( ! $this->is_inlined() || ! $inlined ) {
				wp_enqueue_style(
					$handle,
					set_url_scheme( $this->get_handle_url( $handle ) ),
					$dependencies,
					null,
					$this->get_args()
				);
			}
		}
	}

	/**
	 * Replace relative URIs in stylesheets for their absolute URIs
	 *
	 * @param string $file_url     File.
	 * @param string $css_content  The directory of the current CSS file.
	 *
	 * @return string
	 */
	public static function replace_relative_urls( $file_url, $css_content ) {
		include_once 'class-uri-rewriter.php';
		return URI_Rewriter::rewrite( $css_content, $file_url );
	}

	/**
	 * Check if a handle is a local or external resource
	 *
	 * @param string $handle  Handle.
	 *
	 * @return bool True if the handle is a local one
	 */
	private function is_handle_local( $handle ) {
		$src = $this->get_handle_url( $handle );

		if ( ! $src ) {
			return false;
		}

		return self::is_src_local( $src );
	}

	/**
	 * Check if path is local.
	 *
	 * @since 2.7.1  Moved out from is_handle_local().
	 *
	 * @param string $src  Asset source path.
	 *
	 * @return bool
	 */
	public static function is_src_local( $src ) {
		// Check if the URL is an external one.
		$home_url = home_url();

		// Add scheme to src if it does not exist.
		if ( 0 === strpos( $src, '//' ) ) {
			$src = 'http:' . $src;
		}

		$parsed_site_url = wp_parse_url( $home_url );
		$parsed_src      = wp_parse_url( $src );

		// Probably not local but who knows.
		if ( ! $parsed_src ) {
			return false;
		}

		// '/wp-includes/js' are locals
		if ( empty( $parsed_src['host'] ) && strpos( $parsed_src['path'], '/' ) === 0 ) {
			return true;
		}

		// Hosts match.
		if ( ! empty( $parsed_src['host'] ) && $parsed_src['host'] === $parsed_site_url['host'] ) {
			return true;
		}

		if ( is_multisite() && defined( 'DOMAINMAP_BASEFILE' ) ) {
			return self::check_mapped_domain() === $parsed_src['host'];
		}

		// Not local.
		return false;
	}

	/**
	 * Support for domain mapping plugin.
	 *
	 * @since 2.0.0
	 */
	private static function check_mapped_domain() {
		$domain = wp_cache_get( 'smush_mapped_site_domain', 'smush' );

		if ( ! $domain ) {
			global $wpdb;

			$domain = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT domain FROM {$wpdb->base_prefix}domain_mapping WHERE blog_id = %d ORDER BY id ASC LIMIT 1",
					get_current_blog_id()
				)
			); // Db call ok.

			if ( null !== $domain ) {
				wp_cache_add( 'smush_mapped_site_domain', $domain, 'smush' );
			}
		}

		return $domain;
	}

}
