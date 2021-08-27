<?php
/**
 * Minify module.
 *
 * @package Hummingbird\Core\Modules
 */

namespace Hummingbird\Core\Modules;

use Hummingbird\Core\Filesystem;
use Hummingbird\Core\Module;
use Hummingbird\Core\Settings;
use WP_Customize_Manager;
use WP_Scripts;
use WP_Styles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Minify
 */
class Minify extends Module {

	/**
	 * List of groups to be processed at the end of the request
	 *
	 * @var array
	 */
	private $group_queue = array();

	/**
	 * Source collector.
	 *
	 * @var Minify\Sources_Collector
	 */
	public $sources_collector;

	/**
	 * Error controller.
	 *
	 * @var Minify\Errors_Controller
	 */
	public $errors_controller;

	/**
	 * Houskeeper module.
	 *
	 * @var Minify\Housekeeper
	 */
	public $housekeeper;

	/**
	 * Minify scanner.
	 *
	 * @var Minify\Scanner
	 */
	public $scanner;

	/**
	 * Counter that will name scripts/styles slugs
	 *
	 * @var int
	 */
	private static $counter = 0;

	/**
	 * Assets that have been already parsed.
	 *
	 * @var array $done
	 */
	public $done = array(
		'scripts' => array(),
		'styles'  => array(),
	);

	/**
	 * Assets that go to footer.
	 *
	 * @var array $to_footer
	 */
	public $to_footer = array(
		'styles'  => array(),
		'scripts' => array(),
	);

	/**
	 * Exclusion list.
	 *
	 * @since 2.7.2  Added 'lodash' script. It has an inlined script 'window.lodash = _.noConflict();' that prevents
	 *               errors in browser console. Without that line, many core WordPress scripts will error out.
	 * @see https://incsub.atlassian.net/browse/HUM-404
	 *
	 * @var array $exclude_combine
	 */
	private $exclude_combine = array( 'lodash' );

	/**
	 * Google fonts collection.
	 *
	 * @since 3.0.0
	 * @var array $fonts
	 */
	private $fonts = array();

	/**
	 * Initializes the module. Always executed even if the module is deactivated.
	 *
	 * We need the scanner module to be always active, because HB uses is_scanning to detect
	 * if there is a scan going on.
	 */
	public function init() {
		$this->scanner = new Minify\Scanner();

		add_filter( 'wp_hummingbird_is_active_module_minify', array( $this, 'minify_module_status' ) );

		add_filter( 'wphb_block_resource', array( $this, 'filter_resource_block' ), 10, 5 );
		add_filter( 'wphb_minify_resource', array( $this, 'filter_resource_minify' ), 10, 4 );
		add_filter( 'wphb_combine_resource', array( $this, 'filter_resource_combine' ), 10, 3 );
		add_filter( 'wphb_defer_resource', array( $this, 'filter_resource_defer' ), 10, 3 );
		add_filter( 'wphb_inline_resource', array( $this, 'filter_resource_inline' ), 10, 3 );
		add_filter( 'wphb_preload_resource', array( $this, 'filter_resource_preload' ), 10, 3 );
		add_filter( 'wphb_async_resource', array( $this, 'filter_resource_async' ), 10, 3 );
		add_filter( 'wphb_send_resource_to_footer', array( $this, 'filter_resource_to_footer' ), 10, 3 );
		add_filter( 'wphb_cdn_resource', array( $this, 'filter_resource_cdn' ), 10, 3 );

		// Remove files from AO UI.
		add_filter( 'wphb_minification_display_enqueued_file', array( $this, 'exclude_from_ao_ui' ), 10, 3 );

		// Remove -rtl from CDN links.
		add_filter( 'style_loader_tag', array( $this, 'remove_rtl_prefix_on_cdn' ) );
	}

	/**
	 * Initializes Minify module
	 */
	public function init_module_action() {
		$this->housekeeper = new Minify\Housekeeper();
		$this->housekeeper->init();

		$this->errors_controller = new Minify\Errors_Controller();
		$this->sources_collector = new Minify\Sources_Collector();
	}

	/**
	 * Delete files attached to a minify group.
	 *
	 * @param int $post_id  Post ID.
	 */
	public function on_delete_post( $post_id ) {
		$group = Minify\Minify_Group::get_instance_by_post_id( $post_id );

		if ( ( $group instanceof Minify\Minify_Group ) && $group->file_id ) {
			if ( $group->get_file_path() && file_exists( $group->get_file_path() ) ) {
				wp_delete_file( $group->get_file_path() );
			}
			wp_cache_delete( 'wphb_minify_groups' );
		}
	}

	/**
	 * Execute the module actions. Executed when module is active.
	 */
	public function run() {
		global $wp_customize, $pagenow;

		$this->init_module_action();

		add_action( 'init', array( $this, 'register_cpts' ) );
		add_action( 'before_delete_post', array( $this, 'on_delete_post' ), 10 );
		// Process the queue through WP Cron.
		add_action( 'wphb_minify_process_queue', array( $this, 'process_queue' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_critical_css' ), 5 );

		// Disable module on login pages.
		add_action( 'login_init', array( $this, 'disable_minify_on_page' ) );

		$avoid_minify = filter_input( INPUT_GET, 'avoid-minify', FILTER_VALIDATE_BOOLEAN );
		if ( $avoid_minify || 'wp-login.php' === $pagenow ) {
			$this->disable_minify_on_page();
		}

		if ( is_admin() || is_customize_preview() || ( $wp_customize instanceof WP_Customize_Manager ) ) {
			return;
		}

		// Only minify on front.
		add_filter( 'print_styles_array', array( $this, 'filter_styles' ), 5 );
		add_filter( 'print_scripts_array', array( $this, 'filter_scripts' ), 5 );
		add_action( 'wp_footer', array( $this, 'trigger_process_queue_cron' ), 10000 );

		add_filter( 'wp_resource_hints', array( $this, 'prefetch_cdn_dns' ), 99, 2 );

		// Google fonts optimization.
		$this->fonts = Settings::get_setting( 'fonts', 'minify' );
		if ( $this->fonts ) {
			add_filter( 'wp_resource_hints', array( $this, 'prefetch_fonts' ), 10, 2 );
			add_filter( 'style_loader_tag', array( $this, 'preload_fonts' ), 10, 3 );
		}
	}

	/**
	 * Disable module on login pages. Fixes conflicts with Defender masked login and LoginPress.
	 *
	 * @since 2.7.1
	 */
	public function disable_minify_on_page() {
		add_filter( 'wp_hummingbird_is_active_module_' . $this->get_slug(), '__return_false' );
	}

	/**
	 * Register a new CPT for Assets groups
	 */
	public static function register_cpts() {
		$labels = array(
			'name'          => 'WPHB Minify Groups',
			'singular_name' => 'WPHB Minify Group',
		);

		$args = array(
			'labels'             => $labels,
			'description'        => 'WPHB Minify Groups (internal use)',
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array(),
		);
		register_post_type( 'wphb_minify_group', $args );
	}

	/**
	 * Used in tests.
	 *
	 * @return array
	 */
	public function get_queue_to_process() {
		return $this->group_queue;
	}

	/**
	 * Filter styles
	 *
	 * @param array $handles  List of styles slugs.
	 *
	 * @return array
	 */
	public function filter_styles( $handles ) {
		return $this->filter_enqueues_list( $handles, 'styles' );
	}

	/**
	 * Filter scripts
	 *
	 * @param array $handles  List of scripts slugs.
	 *
	 * @return array
	 */
	public function filter_scripts( $handles ) {
		return $this->filter_enqueues_list( $handles, 'scripts' );
	}

	/**
	 * Filter the sources
	 *
	 * We'll collect those styles/scripts that are going to be
	 * processed by WP Hummingbird and return those that will
	 * be processed by WordPress
	 *
	 * @param array  $handles  List of scripts/styles slugs.
	 * @param string $type     scripts|styles.
	 *
	 * @return array List of handles that will be processed by WordPress
	 */
	public function filter_enqueues_list( $handles, $type ) {
		if ( ! $this->is_active() ) {
			// Asset optimization is not active, return the handles.
			return $handles;
		}

		if ( $this->errors_controller->is_server_error() ) {
			// There seem to be an error in our severs, do not minify.
			return $handles;
		}

		if ( 'styles' === $type ) {
			global $wp_styles;
			$wp_dependencies = $wp_styles;
		} elseif ( 'scripts' === $type ) {
			global $wp_scripts;
			$wp_dependencies = $wp_scripts;
		} else {
			return $handles;
		}

		// Nothing to do, return the handles.
		if ( empty( $handles ) ) {
			return $handles;
		}

		$return_to_wp = array();

		// Collect the handles information to use in admin later.
		foreach ( $handles as $key => $handle ) {
			/**
			 * Not registered for some reason - return to WP.
			 *
			 * This has been added and removed from time to time. Not sure if this is the best way to do it, so I
			 * will try to history of commits.
			 *
			 * @since 2.7.1  Reverted the previous fix.
			 * @see https://incsub.atlassian.net/browse/HUM-294
			 *
			 * @since 2.7.2  Brought this back in a new updated way.
			 * @see https://incsub.atlassian.net/browse/HUM-482
			 */
			if ( ! isset( $wp_dependencies->registered[ $handle ] ) ) {
				$return_to_wp = array_merge( $return_to_wp, array( $handle ) );
				unset( $handles[ $key ] );
				continue;
			}

			// Only show items that have a handle and a source.
			if ( ! empty( $wp_dependencies->registered[ $handle ]->src ) ) {
				$this->sources_collector->add_to_collection( $wp_dependencies->registered[ $handle ], $type );
			}

			// If we aren't in footer, remove handles that need to go to footer.
			if ( self::is_in_header() && ! self::is_in_footer() && isset( $wp_dependencies->groups[ $handle ] ) && $wp_dependencies->groups[ $handle ] ) {
				$this->to_footer[ $type ][] = $handle;
				unset( $handles[ $key ] );
			}
		}

		$handles = array_values( $handles );

		if ( self::is_in_footer() && ! empty( $this->to_footer[ $type ] ) ) {
			// This is done to remove script, that are dequeued later.
			$this->to_footer[ $type ] = array_intersect( $this->to_footer[ $type ], $handles );
			// Header sent us some handles to be moved to footer.
			$handles = array_unique( array_merge( $handles, $this->to_footer[ $type ] ) );
		}

		// Group dependencies by attributes like args, extra, etc.
		$_groups = $this->group_dependencies_by_attributes( $handles, $wp_dependencies, $type );

		// Create a Groups list object.
		$groups_list = new Minify\Minify_Groups_List( $type );
		array_map( array( $groups_list, 'add_group' ), $_groups );

		unset( $_groups );

		/**
		 * WARNING: This is dangerous, it can fall into an infinite loop if not treated with love and care.
		 * I've added a safety mechanism to try and counter infinite loops.
		 */
		$loop_counter = 0;
		$loop_limit   = apply_filters( 'wphb_group_split_loop_limit', 300 );
		do {
			$loop_counter++;
			$needs_additional_splitting = $this->maybe_split_groups( $groups_list, $type );

			if ( $loop_limit === $loop_counter ) {
				set_transient( 'wphb_infinite_loop_warning', true, 3600 );
				error_log( '[Hummingbird] Minify group infinite loop detected. Safety mechanism invoked, breaking out of loop.' );
				break;
			}
		} while ( $needs_additional_splitting );

		// Set the groups handles, as we need all of them before processing.
		foreach ( $groups_list->get_groups() as $group ) {
			$handles = $group->get_handles();
			if ( count( $handles ) === 1 ) {
				// Just one handle, let's keep the handle name as the group ID.
				$group->group_id = $handles[0];
			} else {
				$group->group_id = 'wphb-' . ++self::$counter;
			}
			foreach ( $handles as $handle ) {
				$this->done[ $type ][] = $handle;
			}
		}

		if ( 'scripts' === $type ) {
			$this->attach_scripts_localization( $groups_list, $wp_dependencies );
		}
		$this->attach_inline_attribute( $groups_list, $wp_dependencies );

		// Parse dependencies, load files and mark groups as ready,process or only-handles
		// Watch out! Groups must not be changed after this point!
		$groups_list->preprocess_groups();

		/**
		 * Minify group.
		 *
		 * @var Minify\Minify_Group $group
		 */
		foreach ( $groups_list->get_groups() as $group ) {
			$group_status = $groups_list->get_group_status( $group->hash );
			$deps         = $groups_list->get_group_dependencies( $group->hash );

			// The group has its file and is ready to be enqueued.
			if ( 'ready' === $group_status ) {
				$group->enqueue( self::is_in_footer(), $deps );
				$return_to_wp = array_merge( $return_to_wp, array( $group->group_id ) );
			} else {
				// The group has not yet a file attached or it cannot be processed for some reason.
				foreach ( $group->get_handles() as $handle ) {
					$group->enqueue_one_handle( $handle, self::is_in_footer(), $deps );
					$return_to_wp = array_merge( $return_to_wp, array( $handle ) );
				}

				if ( 'process' === $group_status ) {
					// Add the group to the queue to be processed later.
					if ( $group->should_process_group() ) {
						$this->group_queue[] = $group;
					}
				}
			}
		}

		return $return_to_wp;
	}

	/**
	 * Try to split the groups. Recursive function.
	 *
	 * The idea behind this is that when groups are split, we need to check those new groups if they need to be
	 * split even further.
	 *
	 * This might be a minor performance hog on larger installs with a lot of settings in asset optimization.
	 * I have tested on a relatively small site (29 assets) with three assets set to be split up, and did not notice
	 * a significant difference in performance. This whole part took 1.59ms (xdebug enabled, worst score out of several
	 * runs) compared to 1.47ms without recursive functionality (best score out of several runs). Which is, worst
	 * case scenario, about 0.12ms per extra split run.
	 *
	 * @since 3.1.0
	 *
	 * @param Minify\Minify_Groups_List $groups_list  Group list.
	 * @param string                    $type         Scripts|styles.
	 *
	 * @return bool  True when we need to do another pass, false when nothing else to split.
	 */
	private function maybe_split_groups( &$groups_list, $type ) {
		// Time to split the groups if we're not combining some of them.
		foreach ( $groups_list->get_groups() as $group ) {
			/**
			 * Minify group.
			 *
			 * @var Minify\Minify_Group $group
			 */
			$dont_enqueue_list = $group->get_dont_enqueue_list();
			if ( $dont_enqueue_list ) {
				// There are one or more handles that should not be enqueued.
				$group->remove_handles( $dont_enqueue_list );
				if ( 'styles' === $type ) {
					wp_dequeue_style( $dont_enqueue_list );
				} else {
					wp_dequeue_script( $dont_enqueue_list );
				}
			}

			// No need to split a single group.
			$handles = $group->get_handles();
			if ( 1 === count( $handles ) ) {
				continue;
			}

			$dont_combine_list = $group->get_dont_combine_list();
			if ( $dont_combine_list ) {
				$split_group = $this->get_splitted_group_structure_by( 'combine', $group );
				$groups_list->split_group( $group->hash, $split_group );
				return true;
			}

			$defer = $group->get_defer_list();
			if ( 'scripts' === $type && $defer && $handles !== $defer ) {
				$split_group = $this->get_splitted_group_structure_by( 'defer', $group );
				$groups_list->split_group( $group->hash, $split_group );
				return true;
			}

			$async = $group->get_async_list();
			if ( 'scripts' === $type && $async && $handles !== $async ) {
				$split_group = $this->get_splitted_group_structure_by( 'async', $group );
				$groups_list->split_group( $group->hash, $split_group );
				return true;
			}

			$inline = $group->get_inline_list();
			if ( 'styles' === $type && $inline && $handles !== $inline ) {
				$split_group = $this->get_splitted_group_structure_by( 'inline', $group );
				$groups_list->split_group( $group->hash, $split_group );
				return true;
			}

			$preload = $group->get_preload_list();
			if ( $preload && $handles !== $preload ) {
				$split_group = $this->get_splitted_group_structure_by( 'preload', $group );
				$groups_list->split_group( $group->hash, $split_group );
				return true;
			}
		}

		return false;
	}

	/**
	 * Create a new group structure based on $by parameter
	 *
	 * This will allow later to split groups into new groups based on combination/deferring...
	 *
	 * @param string              $by     combine|defer|minify...
	 * @param Minify\Minify_Group $group  Minify group.
	 * @param bool                $value  Value to apply if the handle should be done.
	 *
	 * @return array New structure
	 */
	private function get_splitted_group_structure_by( $by, $group, $value = true ) {
		$handles = $group->get_handles();

		// Here we'll save sources that don't need to be minified/combine/deferred...
		// Then we'll extract those handles from the group and we'll create
		// a new group for them keeping the groups order.
		$group_todos = array();
		foreach ( $handles as $handle ) {
			$value                  = absint( $value );
			$not_value              = absint( ! $value );
			$group_todos[ $handle ] = $group->should_do_handle( $handle, $by ) ? $value : $not_value;
		}

		// Now split groups if needed based on $by value
		// We need to keep always the order, ALWAYS
		// This will save the new split group structure.
		$split_group = array();

		$last_status = null;
		foreach ( $group_todos as $handle => $status ) {

			// Last minify status will be the first one by default.
			if ( is_null( $last_status ) ) {
				$last_status = $status;
			}

			// Set the split groups to the last element.
			end( $split_group );
			if ( $last_status === $status && 0 !== $status ) {
				$current_key = key( $split_group );
				if ( ! $current_key ) {
					// Current key can be NULL, set to 0.
					$current_key = 0;
				}

				if ( ! isset( $split_group[ $current_key ] ) || ! is_array( $split_group[ $current_key ] ) ) {
					$split_group[ $current_key ] = array();
				}

				$split_group[ $current_key ][] = $handle;
			} else {
				// Create a new group.
				$split_group[] = array( $handle );
			}

			$last_status = $status;
		}

		return $split_group;
	}

	/**
	 * Group dependencies by alt, title, rtl, conditional and args attributes.
	 *
	 * This is a very-very fragile function. When making changes, please provide a detailed comment on why a
	 * change has been made.
	 *
	 * @param array                $handles          Handles array.
	 * @param WP_Scripts|WP_Styles $wp_dependencies  List of dependencies.
	 * @param string               $type             Asset type: 'scripts' or 'styles'.
	 *
	 * @return array
	 */
	private function group_dependencies_by_attributes( $handles, $wp_dependencies, $type ) {
		$groups                    = array();
		$prev_differentiators_hash = false;

		foreach ( $handles as $handle ) {
			$registered_dependency = isset( $wp_dependencies->registered[ $handle ] ) ? $wp_dependencies->registered[ $handle ] : false;
			if ( ! $registered_dependency ) {
				continue;
			}

			if ( ! self::is_in_footer() ) {
				/**
				 * Filter the resource (move to footer)
				 *
				 * @usedby wphb_filter_resource_to_footer()
				 *
				 * @var bool $send_resource_to_footer
				 * @var string $handle Source slug
				 * @var string $type scripts|styles
				 * @var string $source_url Source URL
				 */
				if ( apply_filters( 'wphb_send_resource_to_footer', false, $handle, $type, $wp_dependencies->registered[ $handle ]->src ) ) {
					// Move this to footer, do not take this handle in account for this iteration.
					$this->to_footer[ $type ][] = $handle;
					continue;
				}
			}

			/**
			 * We'll group by these extras $wp_style->extras and $wp_style->args (args is no more than a string, confusing)
			 * If previous group has the same values, we'll add this dep it to that group
			 * otherwise, a new group will be created.
			 */
			$group_extra_differentiators = array( 'alt', 'title', 'rtl', 'conditional' );
			$group_differentiators       = array( 'args' );

			// We'll create a hash for all differentiators.
			$differentiators_hash = array();
			foreach ( $group_extra_differentiators as $differentiator ) {
				if ( isset( $registered_dependency->extra[ $differentiator ] ) ) {
					if ( is_bool( $registered_dependency->extra[ $differentiator ] ) && $registered_dependency->extra[ $differentiator ] ) {
						$differentiators_hash[] = 'true';
					} elseif ( is_bool( $registered_dependency->extra[ $differentiator ] ) && ! $registered_dependency->extra[ $differentiator ] ) {
						$differentiators_hash[] = 'false';
					} else {
						$differentiators_hash[] = (string) $registered_dependency->extra[ $differentiator ];
					}
				} else {
					$differentiators_hash[] = '';
				}
			}

			foreach ( $group_differentiators as $differentiator ) {
				if ( isset( $registered_dependency->$differentiator ) ) {
					if ( is_bool( $registered_dependency->$differentiator ) && $registered_dependency->$differentiator ) {
						$differentiators_hash[] = 'true';
					} elseif ( is_bool( $registered_dependency->$differentiator ) && ! $registered_dependency->$differentiator ) {
						$differentiators_hash[] = 'false';
					} else {
						$differentiators_hash[] = (string) $registered_dependency->$differentiator;
					}
				} else {
					$differentiators_hash[] = '';
				}
			}

			$differentiators_hash = implode( '-', $differentiators_hash );

			// Now compare the hash with the previous one
			// If they are the same, do not create a new group.
			if ( $differentiators_hash !== $prev_differentiators_hash ) {
				$new_group = new Minify\Minify_Group();
				$new_group->set_type( $type );
				foreach ( $registered_dependency->extra as $key => $value ) {
					$new_group->add_extra( $key, $value );
				}

				// We'll treat this later.
				$new_group->delete_extra( 'after' );
				$new_group->delete_extra( 'before' );
				$new_group->delete_extra( 'data' );

				$new_group->set_args( $registered_dependency->args );

				/**
				 * A little bit of explanation behind this. Originally, we were only checking to see if the
				 * $registered_dependency->src was present. But at some point there were conflicts with themes/plugins
				 * that were enqueueing an asset with an empty source (just to inline something). That was first noticed
				 * with WP core mediaelement, with a fix introduced in 2.0. Then later on in 2.0.1 this lead to a more
				 * general approach of checking if there were some extra attributes for the asset.
				 *
				 * @since 2.0.0  This is not a perfect fix, but it works. 'mediaelement' script does not have a source
				 *               file, but has an inline script with _wpmejsSettings variable. Without it, media
				 *               elements do not function properly. So we do not exclude such a script.
				 * @since 2.0.1  Instead of checking for 'mediaelement', we check if there are extra attributes
				 *               with $registered_dependency->extra
				 */
				if ( $registered_dependency->src || 0 < count( $registered_dependency->extra ) ) {
					$new_group->add_handle( $handle, $registered_dependency->src, $registered_dependency->ver );

					// Add dependencies.
					$new_group->add_handle_dependency( $handle, $wp_dependencies->registered[ $handle ]->deps );
				}

				$groups[] = $new_group;
			} else {
				end( $groups );
				$last_key = key( $groups );
				$groups[ $last_key ]->add_handle( $handle, $registered_dependency->src );
				// Add dependencies.
				$groups[ $last_key ]->add_handle_dependency( $handle, $registered_dependency->deps );
				reset( $groups );
			}

			$prev_differentiators_hash = $differentiators_hash;
		}

		// Remove group without handles.
		$return = array();
		foreach ( $groups as $key => $group ) {
			if ( $group->get_handles() ) {
				$return[ $key ] = $group;
			}
		}

		return $return;
	}

	/**
	 * Attach inline scripts/styles to groups
	 *
	 * Extract all deps that has inline scripts/styles (added by wp_add_inline_script/style functions)
	 * then it will add those extras to the groups
	 *
	 * @param Minify\Minify_Groups_List $groups_list      Group list.
	 * @param WP_Scripts|WP_Styles      $wp_dependencies  List of dependencies.
	 */
	private function attach_inline_attribute( &$groups_list, $wp_dependencies ) {
		$registered = $wp_dependencies->registered;
		$extras     = wp_list_pluck( $registered, 'extra' );
		$after      = wp_list_pluck( array_filter( $extras, array( $this, 'filter_after_after_attribute' ) ), 'after' );
		$before     = wp_list_pluck( array_filter( $extras, array( $this, 'filter_after_before_attribute' ) ), 'before' );

		array_map(
			function( $group ) use ( $groups_list, $after, $before ) {
					/**
					 * Minify group.
					 *
					 * @var Minify\Minify_Group $group
					 */
					array_map(
						function( $handle ) use ( $after, $group, $before ) {
							if ( isset( $after[ $handle ] ) ) {
								// Add!
								$group->add_after( $after[ $handle ] );
							}
							if ( isset( $before[ $handle ] ) ) {
								// Add!
								$group->add_before( $before[ $handle ] );
							}
						},
						$group->get_handles()
					);
			},
			$groups_list->get_groups()
		);
	}

	/**
	 * Attach localization scripts to groups
	 *
	 * @param Minify\Minify_Groups_List $groups_list      Group list.
	 * @param WP_Scripts|WP_Styles      $wp_dependencies  List of dependencies.
	 */
	private function attach_scripts_localization( &$groups_list, $wp_dependencies ) {
		$registered = $wp_dependencies->registered;
		$extra      = wp_list_pluck( $registered, 'extra' );
		$data       = wp_list_pluck(
			array_filter(
				$extra,
				function( $attr ) {
					if ( isset( $attr['data'] ) ) {
						return $attr['data'];
					}
					return false;
				}
			),
			'data'
		);

		array_map(
			function( $group ) use ( $groups_list, $data ) {
					/**
					 * Minify group.
					 *
					 * @var Minify\Minify_Group $group
					 */
					array_map(
						function( $handle ) use ( $data, $group ) {
							if ( isset( $data[ $handle ] ) ) {
								$group->add_data( $data[ $handle ] ); // Add!
							}
						},
						$group->get_handles()
					);
			},
			$groups_list->get_groups()
		);
	}

	/**
	 * Filter a list of dependencies returning their 'after' attribute inside 'extra' list
	 *
	 * @internal
	 *
	 * @param array $attr  Attributes array.
	 *
	 * @return bool
	 */
	public function filter_after_after_attribute( $attr ) {
		if ( isset( $attr['after'] ) ) {
			return $attr['after'];
		}
		return false;
	}

	/**
	 * Filter a list of dependencies returning their 'before' attribute inside 'extra' list
	 *
	 * @internal
	 *
	 * @param array $attr  Attributes array.
	 *
	 * @return bool
	 */
	public function filter_after_before_attribute( $attr ) {
		if ( isset( $attr['before'] ) ) {
			return $attr['before'];
		}
		return false;
	}

	/**
	 * Return if we are processing the header
	 *
	 * @return bool
	 * @since 2.6.0
	 */
	public static function is_in_header() {
		return doing_action( 'wp_head' ) || doing_action( 'wp_print_header_scripts' );
	}

	/**
	 * Return if we are processing the footer
	 *
	 * @return bool
	 */
	public static function is_in_footer() {
		return doing_action( 'wp_footer' ) || doing_action( 'wp_print_footer_scripts' );
	}

	/**
	 * Trigger the action to process the queue
	 */
	public function trigger_process_queue_cron() {
		// Trigger que the queue through WP CRON so we don't waste load time.
		$this->sources_collector->save_collection();

		$queue = $this->get_queue_to_process();
		$this->add_items_to_persistent_queue( $queue );
		$queue = $this->get_pending_persistent_queue();
		if ( empty( $queue ) ) {
			return;
		}

		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			$this->process_queue();
		} else {
			self::schedule_process_queue_cron();
		}
	}

	/**
	 * Process the queue: Minify and combine files
	 */
	public function process_queue() {
		// Process the queue.
		if ( get_transient( 'wphb-processing' ) ) {
			// Still processing. Try again.
			if ( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
				self::schedule_process_queue_cron();
			}
			return;
		}

		$queue = $this->get_pending_persistent_queue();

		set_transient( 'wphb-processing', true, 60 );
		// Process 10 groups max in a request.
		$count = 0;

		$new_queue = $queue;
		foreach ( $queue as $key => $item ) {
			if ( $count >= 8 ) {
				break;
			}
			if ( ! ( $item instanceof Minify\Minify_Group ) ) {
				continue;
			}

			if ( $item->should_generate_file() ) {
				$result = $item->process_group();
				if ( is_wp_error( $result ) ) {
					$this->errors_controller->add_server_error( $result );
				}
			}
			$this->remove_item_from_persistent_queue( $item->hash );
			unset( $new_queue[ $key ] );
			$count++;
		}

		if ( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
			if ( ! empty( $new_queue ) ) {
				// Still needs processing.
				self::schedule_process_queue_cron();
			}
		}

		if ( empty( $new_queue ) ) {
			// Finish processing.
			delete_transient( 'wphb-processing' );
			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
				/**
				 * Unfortunately, during cron we are not able to detect the first page load, so it will get cached.
				 * Page load -> page caching and cron are triggered at the same time, but this is the limitation,
				 * that page cache will not have the wphb-processing transient at this stage. To counter this,
				 * we will purge all cache when Asset Optimization is done.
				 *
				 * @since 3.0.0
				 * @see Page_Cache::cache_request() for transient check without cron.
				 */
				do_action( 'wphb_clear_page_cache' );
			}
		} else {
			// Refresh transient.
			set_transient( 'wphb-processing', true, 60 );
		}
	}

	/**
	 * Schedule queue process through WP Cron.
	 */
	public static function schedule_process_queue_cron() {
		if ( ! wp_next_scheduled( 'wphb_minify_process_queue' ) ) {
			wp_schedule_single_event( time(), 'wphb_minify_process_queue' );
		}
	}

	/**
	 * Save a list of groups to a persistent option in database
	 *
	 * If a timeout happens during groups processing, we won't loose
	 * the data needed to process the rest of groups
	 *
	 * @param array $items  Array of items.
	 */
	private function add_items_to_persistent_queue( $items ) {
		if ( empty( $items ) ) {
			// Nothing to be added.
			return;
		}
		$current_queue = $this->get_pending_persistent_queue();
		if ( empty( $current_queue ) ) {
			update_option( 'wphb_process_queue', $items );
		} else {
			$updated              = false;
			$current_queue_hashes = wp_list_pluck( $current_queue, 'hash' );
			foreach ( $items as $item ) {
				if ( ! in_array( $item->hash, $current_queue_hashes, true ) ) {
					$updated         = true;
					$current_queue[] = $item;
				}
			}
			if ( $updated ) {
				update_option( 'wphb_process_queue', $current_queue );
			}
		}
	}

	/**
	 * Remove a group from the persistent queue
	 *
	 * @param string $hash  Item hash.
	 */
	private function remove_item_from_persistent_queue( $hash ) {
		$queue = $this->get_pending_persistent_queue();
		$items = wp_list_filter(
			$queue,
			array(
				'hash' => $hash,
			)
		);

		if ( ! $items ) {
			return;
		}

		$keys = array_keys( $items );
		foreach ( $keys as $key ) {
			unset( $queue[ $key ] );
		}

		$queue = array_values( $queue );

		if ( empty( $queue ) ) {
			$this->delete_pending_persistent_queue();
			return;
		}

		update_option( 'wphb_process_queue', $queue );
	}

	/**
	 * Get the list of groups that are yet pending to be processed
	 */
	public function get_pending_persistent_queue() {
		return get_option( 'wphb_process_queue', array() );
	}

	/**
	 * Deletes the persistent queue completely
	 */
	public function delete_pending_persistent_queue() {
		delete_option( 'wphb_process_queue' );
		wp_cache_delete( 'wphb_process_queue', 'options' );
	}

	/**
	 * Implement abstract parent method for clearing cache.
	 *
	 * Clear the module cache.
	 *
	 * @param bool $reset_settings   If set to true will set Asset Optimization settings to default (that includes files positions).
	 * @param bool $reset_minify     Reset minify settings.
	 * @param bool $keep_collection  Keep collections. If removed, will require to visit the homepage.
	 *
	 * @return bool
	 */
	public function clear_cache( $reset_settings = true, $reset_minify = true, $keep_collection = false ) {
		$this->clear_files();

		if ( $reset_settings ) {
			// This one when cleared will trigger a new scan.
			if ( ! $keep_collection ) {
				Minify\Sources_Collector::clear_collection();
			}

			$options         = $this->get_options();
			$default_options = Settings::get_default_settings();

			// Reset the minification settings.
			if ( $reset_minify ) {
				$options['dont_minify']  = $default_options['minify']['dont_minify'];
				$options['dont_combine'] = $default_options['minify']['dont_combine'];
			}
			$options['block']    = $default_options['minify']['block'];
			$options['position'] = $default_options['minify']['position'];
			$options['defer']    = $default_options['minify']['defer'];
			$options['inline']   = $default_options['minify']['inline'];
			$options['fonts']    = $default_options['minify']['fonts'];
			$options['preload']  = $default_options['minify']['preload'];
			$options['async']    = $default_options['minify']['async'];
			$this->update_options( $options );
		}

		// Clear the pending process queue.
		self::clear_pending_process_queue();

		$this->scanner->reset_scan();

		Minify\Errors_Controller::clear_errors();

		return true;
	}

	/**
	 * Clear pending queue.
	 */
	public static function clear_pending_process_queue() {
		delete_transient( 'wphb_infinite_loop_warning' );
		delete_option( 'wphb_process_queue' );
		wp_cache_delete( 'wphb_process_queue', 'options' );
		delete_transient( 'wphb-processing' );
	}

	/**
	 * Disable minification module.
	 */
	public function disable() {
		$this->toggle_service( false );
		$this->clear_cache();

		// Delete notices if they are there.
		delete_option( 'wphb-minification-files-scanned' );
		delete_site_option( 'wphb-notice-minification-optimized-show' );

		// Clear cron events.
		if ( wp_next_scheduled( 'wphb_minify_clear_files' ) ) {
			wp_clear_scheduled_hook( 'wphb_minify_clear_files' );
		}
	}

	/**
	 * Reset to default settings for minification module.
	 *
	 * @since 2.6.0
	 */
	public function reset_minification_settings() {
		$default        = Settings::get_default_settings();
		$minify_default = $default[ $this->get_slug() ];

		// Settings that need to be reset.
		$ao_settings = array( 'do_assets', 'view', 'type', 'use_cdn', 'nocdn' );

		// These settings are only valid for single sites or network admin.
		if ( ! is_multisite() || is_network_admin() ) {
			$ao_settings = array_merge( $ao_settings, array( 'file_path', 'log' ) );
		}

		$ao_settings_default = array_intersect_key( $minify_default, array_flip( $ao_settings ) );

		// Get current settings for minify.
		$minify_settings = $this->get_options();
		$minify_settings = array_merge( $minify_settings, $ao_settings_default );

		// Reset Critical css.
		self::save_css( '' );

		$this->update_options( $minify_settings );
	}

	/**
	 * *************************
	 * FILTERS
	 ***************************/

	/**
	 * Filter module status.
	 *
	 * @param bool $current  Current status.
	 *
	 * @return bool
	 */
	public function minify_module_status( $current ) {
		$options = $this->get_options();

		if ( false === $options['enabled'] ) {
			return false;
		}

		if ( is_multisite() ) {
			$current = $options['minify_blog'];
		} else {
			$current = $options['enabled'];
		}

		return $current;
	}

	/**
	 * Filter blocker resources.
	 *
	 * @param bool   $value   Current value.
	 * @param string $handle  Resource handle.
	 * @param string $type    Script or style.
	 *
	 * @return bool
	 */
	public function filter_resource_block( $value, $handle, $type ) {
		$options = $this->get_options();
		$blocked = $options['block'][ $type ];
		if ( in_array( $handle, $blocked, true ) ) {
			return true;
		}

		return $value;
	}

	/**
	 * Filter minified resources.
	 *
	 * @param bool   $value   Current value.
	 * @param string $handle  Resource handle.
	 * @param string $type    Script or style.
	 * @param string $url     Script URL.
	 *
	 * @return bool
	 */
	public function filter_resource_minify( $value, $handle, $type, $url ) {
		$options = $this->get_options();
		$minify  = $options['dont_minify'][ $type ];
		if ( is_array( $minify ) && in_array( $handle, $minify, true ) ) {
			return false;
		}

		// Filter already minified resources.
		if ( preg_match( '/\.min\.(css|js)/', basename( $url ) ) ) {
			return false;
		}

		return $value;
	}

	/**
	 * Filter combine resources.
	 *
	 * @param bool   $value   Current value.
	 * @param string $handle  Resource handle.
	 * @param string $type    Script or style.
	 *
	 * @return bool
	 */
	public function filter_resource_combine( $value, $handle, $type ) {
		$options = $this->get_options();
		$combine = $options['dont_combine'][ $type ];
		if ( in_array( $handle, $combine, true ) ) {
			return false;
		}

		if ( in_array( $handle, $this->exclude_combine, true ) ) {
			return false;
		}

		return $value;
	}

	/**
	 * Filter defer resources.
	 *
	 * @param bool   $value   Current value.
	 * @param string $handle  Resource handle.
	 * @param string $type    Script or style.
	 *
	 * @return bool
	 */
	public function filter_resource_defer( $value, $handle, $type ) {
		$options = $this->get_options();
		$defer   = $options['defer'][ $type ];
		if ( ! in_array( $handle, $defer, true ) ) {
			return $value;
		}

		return true;
	}

	/**
	 * Filter inline resources.
	 *
	 * @param bool   $value   Current value.
	 * @param string $handle  Resource handle.
	 * @param string $type    Script or style.
	 *
	 * @return bool
	 */
	public function filter_resource_inline( $value, $handle, $type ) {
		$options = $this->get_options();
		$defer   = $options['inline'][ $type ];
		if ( ! in_array( $handle, $defer, true ) ) {
			return $value;
		}

		return true;
	}

	/**
	 * Filter move to footer resources.
	 *
	 * @param bool   $value   Current value.
	 * @param string $handle  Resource handle.
	 * @param string $type    Script or style.
	 *
	 * @return bool
	 */
	public function filter_resource_to_footer( $value, $handle, $type ) {
		$options   = $this->get_options();
		$to_footer = $options['position'][ $type ];
		if ( array_key_exists( $handle, $to_footer ) && 'footer' === $to_footer[ $handle ] ) {
			return true;
		}

		return $value;
	}

	/**
	 * Filter out assets from using CDN.
	 *
	 * @since 2.4.0
	 *
	 * @param bool   $value    Current CDN status.
	 * @param array  $handles  Array of handles (or single handle).
	 * @param string $type     Scripts or styles.
	 *
	 * @return bool
	 */
	public function filter_resource_cdn( $value, $handles, $type ) {
		$options = Settings::get_setting( 'nocdn', 'minify' );
		foreach ( $handles as $handle ) {
			if ( in_array( $handle, $options[ $type ], true ) ) {
				$value = false;
			}
		}

		return $value;
	}

	/**
	 * Exclude files from the AO list.
	 *
	 * @since 2.7.2
	 *
	 * @param bool         $action  Exclude or not.
	 * @param array|string $handle  Handle.
	 * @param string       $type    Asset type: styles or scripts.
	 *
	 * @return bool
	 */
	public function exclude_from_ao_ui( $action, $handle, $type ) {
		if ( is_array( $handle ) && isset( $handle['handle'] ) ) {
			$handle = $handle['handle'];
		}

		if ( 'scripts' === $type && in_array( $handle, $this->exclude_combine, true ) ) {
			return false;
		}

		return $action;
	}

	/**
	 * Filter preload resources.
	 *
	 * @since 3.1.0
	 *
	 * @param bool   $value   Current value.
	 * @param string $handle  Resource handle.
	 * @param string $type    Script or style.
	 *
	 * @return bool
	 */
	public function filter_resource_preload( $value, $handle, $type ) {
		$options = $this->get_options();
		if ( ! in_array( $handle, $options['preload'][ $type ], true ) ) {
			return $value;
		}

		return true;
	}

	/**
	 * Filter async resources.
	 *
	 * @since 3.1.0
	 *
	 * @param bool   $value   Current value.
	 * @param string $handle  Resource handle.
	 * @param string $type    Script or style.
	 *
	 * @return bool
	 */
	public function filter_resource_async( $value, $handle, $type ) {
		$options = $this->get_options();
		if ( ! in_array( $handle, $options['async'][ $type ], true ) ) {
			return $value;
		}

		return true;
	}

	/**
	 * *************************
	 * HELPER FUNCTIONS
	 ***************************/

	/**
	 * Clear cache for selected file.
	 *
	 * @since 1.9.2
	 *
	 * @param string $handle  Handle.
	 * @param string $type    Type.
	 */
	public function clear_file( $handle, $type ) {
		$groups = Minify\Minify_Group::get_groups_from_handle( $handle, $type );

		foreach ( $groups as $group ) {
			wp_delete_post( $group->file_id );
		}
	}

	/**
	 * Clear minified group files
	 */
	public function clear_files() {
		$groups = Minify\Minify_Group::get_minify_groups();

		foreach ( $groups as $group ) {
			// This will also delete the file. See WP_Hummingbird\Core\Modules\Minify::on_delete_post().
			wp_delete_post( $group->ID );
		}

		wp_cache_delete( 'wphb_minify_groups' );

		// Clear all the page cache.
		do_action( 'wphb_clear_page_cache' );
	}

	/**
	 * Get all resources collected
	 *
	 * This collection is displayed in minification admin page
	 */
	public function get_resources_collection() {
		$collection = Minify\Sources_Collector::get_collection();
		$posts      = Minify\Minify_Group::get_minify_groups();
		foreach ( $posts as $post ) {
			$group = Minify\Minify_Group::get_instance_by_post_id( $post->ID );
			if ( ! $group ) {
				continue;
			}
			foreach ( $group->get_handles() as $handle ) {
				if ( isset( $collection[ $group->type ][ $handle ] ) ) {
					$collection[ $group->type ][ $handle ]['original_size']   = $group->get_handle_original_size( $handle );
					$collection[ $group->type ][ $handle ]['compressed_size'] = $group->get_handle_compressed_size( $handle );
				}
			}
		}

		return $collection;
	}

	/**
	 * Init minification scan.
	 */
	public function init_scan() {
		$this->clear_cache( false );

		// Activate minification if is not.
		$this->toggle_service( true );

		// Init scan.
		$this->scanner->init_scan();
	}

	/**
	 * Toggle minification.
	 *
	 * @param bool $value   Value for minification. Accepts boolean value: true or false.
	 * @param bool $network Value for network. Default: false.
	 */
	public function toggle_service( $value, $network = false ) {
		$options = $this->get_options();

		if ( is_multisite() ) {
			if ( $network ) {
				// Updating for the whole network.
				$options['enabled'] = $value;
				// If deactivated for whole network, also deactivate CDN.
				if ( false === $value ) {
					$options['use_cdn'] = false;
					$options['log']     = false;
				}
			} else {
				// Updating on subsite.
				if ( ! $options['enabled'] ) {
					// Asset optimization is turned down for the whole network, do not activate it per site.
					$options['minify_blog'] = false;
				} else {
					$options['minify_blog'] = $value;
				}
			}
		} else {
			$options['enabled'] = $value;
		}

		$this->update_options( $options );
	}

	/**
	 * Toggle CDN helper function.
	 *
	 * @param bool $value  CDN status to set.
	 */
	public function toggle_cdn( $value ) {
		$options            = $this->get_options();
		$options['use_cdn'] = $value;
		$this->update_options( $options );
	}

	/**
	 * Get CDN status.
	 *
	 * @since  1.5.2
	 * @return bool
	 */
	public function get_cdn_status() {
		$options = $this->get_options();
		return $options['use_cdn'];
	}

	/**
	 * Enqueue critical CSS file (css above the fold).
	 *
	 * @since 1.8
	 */
	public function enqueue_critical_css() {
		$assets_dir = Filesystem::critical_assets_dir();
		$file       = $assets_dir['path'] . 'critical.css';

		if ( ! file_exists( $file ) ) {
			return;
		}

		$content = file_get_contents( $file );
		if ( empty( $content ) ) {
			return;
		}

		$url = $assets_dir['url'] . 'critical.css';

		wp_register_style( 'wphb-critical-css', $url, array(), filemtime( $file ) );
		wp_enqueue_style( 'wphb-critical-css' );
	}

	/**
	 * Get css file content for critical css file.
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public static function get_css() {
		$assets_dir = Filesystem::critical_assets_dir();
		$file       = $assets_dir['path'] . 'critical.css';

		if ( file_exists( $file ) ) {
			return file_get_contents( $file );
		}

		return '';
	}

	/**
	 * Save critical css file (css above the fold).
	 *
	 * @since 1.8
	 *
	 * @param string $content  CSS content.
	 *
	 * @return array
	 */
	public static function save_css( $content ) {
		if ( ! is_string( $content ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unsupported content', 'wphb' ),
			);
		}

		$fs = Filesystem::instance();

		if ( is_wp_error( $fs->status ) ) {
			return array(
				'success' => false,
				'message' => __( 'Error saving file', 'wphb' ),
			);
		}

		$assets_dir = Filesystem::critical_assets_dir();
		$file       = $assets_dir['path'] . 'critical.css';
		$content    = trim( $content );
		if ( ! empty( $content ) ) {
			$status = $fs->write( $file, $content );
			if ( is_wp_error( $status ) ) {
				return array(
					'success' => false,
					'message' => $status->get_error_message(),
				);
			}
		} else {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}

		return array(
			'success' => true,
			'message' => __( 'Settings updated', 'wphb' ),
		);
	}

	/**
	 * Return a list of fields used on the wp_postmeta table.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	public static function get_postmeta_fields() {
		return array(
			'_handles',
			'_handle_urls',
			'_handle_versions',
			'_extra',
			'_args',
			'_type',
			'_dont_minify',
			'_dont_combine',
			'_dont_enqueue',
			'_defer',
			'_inline',
			'_preload',
			'_async',
			'_handle_dependencies',
			'_handle_original_sizes',
			'_handle_compressed_sizes',
			'_hash',
			'_file_id',
			'_url',
			'_expires',
		);
	}

	/**
	 * CDN does not support -rtl suffixes, so we remove those from style links
	 *
	 * @since 2.7.2
	 *
	 * @param string $rtl_tag  Style tag.
	 *
	 * @return string
	 */
	public function remove_rtl_prefix_on_cdn( $rtl_tag ) {
		// If not from Hummingbird CDN - skip.
		if ( false === strpos( $rtl_tag, 'hb.wpmucdn.com' ) ) {
			return $rtl_tag;
		}

		// If does not contain -rtl prefix - skip.
		if ( false === strpos( $rtl_tag, '-rtl.' ) ) {
			return $rtl_tag;
		}

		return str_replace( '-rtl.', '.', $rtl_tag );
	}

	/**
	 * Replace Google fonts with a preloaded version.
	 *
	 * @since 3.0.0
	 *
	 * @param string $tag     The link tag for the enqueued style.
	 * @param string $handle  The style's registered handle.
	 * @param string $href    The stylesheet's source URL.
	 *
	 * @return string
	 */
	public function preload_fonts( $tag, $handle, $href ) {
		if ( ! in_array( $handle, $this->fonts, true ) ) {
			return $tag;
		}

		$fonts  = '<link rel="preload" as="style" href="' . $href . '" />';
		$fonts .= str_replace( "media='all'", "media='print' onload='this.media=\"all\"'", $tag );

		return $fonts;
	}

	/**
	 * Prefetch Google fonts.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $hints          URLs to print for resource hints.
	 * @param string $relation_type  The relation type the URLs are printed for, e.g. 'preconnect' or 'prerender'.
	 *
	 * @return array
	 */
	public function prefetch_fonts( $hints, $relation_type ) {
		if ( 'preconnect' !== $relation_type ) {
			return $hints;
		}

		// TODO: we need a way to add crossorigin attribute.

		if ( ! in_array( 'https://fonts.gstatic.com', $hints, true ) ) {
			$hints[] = 'https://fonts.gstatic.com';
		}

		if ( ! in_array( 'https://fonts.googleapis.com', $hints, true ) ) {
			$hints[] = 'https://fonts.googleapis.com';
		}

		return $hints;
	}

	/**
	 * Add CDN URL to header for better speed.
	 *
	 * @since 3.1.0
	 *
	 * @param array  $hints          URLs to print for resource hints.
	 * @param string $relation_type  The relation type the URLs are printed.
	 *
	 * @return array
	 */
	public function prefetch_cdn_dns( $hints, $relation_type ) {
		// Add only if CDN active.
		if ( 'dns-prefetch' === $relation_type && $this->get_cdn_status() ) {
			$hints[] = '//hb.wpmucdn.com';
		}

		return $hints;
	}

}
