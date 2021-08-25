<?php
/**
 * Minify group list.
 *
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Hummingbird\Core\Modules\Minify
 */

namespace Hummingbird\Core\Modules\Minify;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Minify_Groups_List
 */
class Minify_Groups_List {

	/**
	 * Groups.
	 *
	 * @var array List of Minify_Group objects
	 */
	private $groups = array();

	/**
	 * Type
	 *
	 * @var string styles|scripts
	 */
	private $type = '';

	/**
	 * Group dependencies.
	 *
	 * @var bool $groups_dependencies
	 */
	private $groups_dependencies = false;

	/**
	 * Save the status for every group
	 *
	 * A group can has status as:
	 * - 'process' = The group should process its file
	 * - 'ready' = The group has already a processed file and must be enqueued
	 * - 'only-handles' = The group file won't be processed and its files will be enqueued by separate
	 *
	 * @var array $group_statuses
	 */
	private $group_statuses = array();

	/**
	 * Minify_Groups_List constructor.
	 *
	 * @param string $type  Type.
	 */
	public function __construct( $type ) {
		$this->type = $type;
	}

	/**
	 * Return the type of the sources that this list manages
	 *
	 * @return string styles|scripts
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get groups.
	 *
	 * @return array
	 */
	public function get_groups() {
		return $this->groups;
	}

	/**
	 * Return a group from the list
	 *
	 * @param int|string $key_or_hash  Key or hash.
	 *
	 * @return bool|mixed
	 */
	public function get_group( $key_or_hash ) {
		$position = $this->get_group_position( $key_or_hash );

		if ( false !== $position ) {
			return $this->groups[ $position ];
		}
		return false;
	}

	/**
	 * Add a new group to the list
	 *
	 * @param Minify_Group $group  Group.
	 *
	 * @return bool
	 */
	public function add_group( $group ) {
		if ( ! ( $group instanceof Minify_Group ) ) {
			return false;
		}

		$group->set_type( $this->type );
		$this->groups[] = $group;
		return true;
	}

	/**
	 * Remove a group from the list
	 *
	 * @param string|int $key_or_hash  Key or hash.
	 *
	 * @return bool
	 */
	public function remove_group( $key_or_hash ) {
		$position = $this->get_group_position( $key_or_hash );
		if ( false !== $position ) {
			unset( $this->groups[ $position ] );
			$this->groups = array_values( $this->groups );
			return true;
		}

		return false;
	}

	/**
	 * Return a list of group hashes that are dependant of another group hash
	 *
	 * This function will search among all groups and compare if any handles of it
	 * has dependencies with any other group on the list. After that, it will return
	 * all those group hashes
	 *
	 * IMPORTANT NOTE: All group IDs ($group->group_id) should be already set
	 * if you want this function to work properly. Otherwise it will return WP_Error
	 * and self::preprocess_groups() should has been called
	 *
	 * @param string|int $key_or_hash  Key or hash.
	 *
	 * @return array|WP_Error
	 */
	public function get_group_dependencies( $key_or_hash ) {
		$group = $this->get_group( $key_or_hash );
		if ( ! $group ) {
			return array();
		}
		$this->parse_groups_dependencies();
		return $this->groups_dependencies[ $group->hash ];
	}

	/**
	 * Return group by group ID.
	 *
	 * @param int $group_id  Group ID.
	 *
	 * @return bool|Minify_Group
	 */
	public function get_group_by_group_id( $group_id ) {
		$groups = $this->get_groups();
		$result = wp_list_filter(
			$groups,
			array(
				'group_id' => $group_id,
			)
		);

		if ( $result ) {
			return $result[ key( $result ) ];
		}

		return false;
	}

	/**
	 * Split a group
	 *
	 * $new_handles_order is a multidimensional array that
	 * will tell the function how the new split groups must be
	 *
	 * for instance:
	 * Let's say that the group has the following handles:
	 *      handle-1,
	 *      handle-2,
	 *      handle-3
	 *
	 * and $new_handles_order is:
	 * array(
	 *      array(
	 *          'handle-1',
	 *          'handle-2'
	 *      ),
	 *      array(
	 *          'handle-3'
	 *      )
	 * )
	 *
	 * This will delete the original group and create two new groups with those handles
	 *
	 * The function will keep the groups order instead of adding them at the end of the list
	 *
	 * @param string|int $key_or_hash        Key or hash.
	 * @param array      $new_handles_order  New order of handles.
	 *
	 * @return bool
	 */
	public function split_group( $key_or_hash, $new_handles_order ) {
		$position = $this->get_group_position( $key_or_hash );
		if ( false === $position ) {
			return false;
		}

		if ( empty( $new_handles_order ) || ! is_array( $new_handles_order ) ) {
			return false;
		}

		/**
		 * Minify group.
		 *
		 * @var Minify_Group $group
		 */
		$group      = $this->groups[ $position ];
		$new_groups = array();
		foreach ( $new_handles_order as $handles_order ) {
			$new_groups[] = $group->slice_handles( $handles_order );
		}

		if ( ! empty( $new_groups ) ) {
			$sliced_left              = array_slice( $this->groups, 0, $position );
			$sliced_right             = array_slice( $this->groups, $position );
			$first_key_on_right_slice = key( $sliced_right );

			$sliced_left = array_merge( $sliced_left, $new_groups );

			// Remove the group from the right, we don't need it anymore.
			unset( $sliced_right[ $first_key_on_right_slice ] );

			// And merge the right side on the left too
			// and we're done!
			$this->groups = array_merge( $sliced_left, $sliced_right );

			return true;
		}

		return false;
	}

	/**
	 * Return a group position based on its key (that's actually its position)
	 * or hash
	 *
	 * @param string|int $key_or_hash  Key or hash.
	 *
	 * @return bool|mixed
	 */
	public function get_group_position( $key_or_hash ) {
		if ( isset( $this->groups[ $key_or_hash ] ) ) {
			return $key_or_hash;
		}

		$group_hashes = wp_list_pluck( $this->groups, 'hash' );
		$position     = array_search( $key_or_hash, $group_hashes, true );

		if ( false !== $position ) {
			return $position;
		}

		return false;
	}

	/**
	 * Get the status of the group.
	 *
	 * @param string $hash  Hash.
	 *
	 * @return bool|mixed
	 */
	public function get_group_status( $hash ) {
		if ( ! isset( $this->group_statuses[ $hash ] ) ) {
			return false;
		}

		return $this->group_statuses[ $hash ];
	}

	/**
	 * Set group status.
	 *
	 * @param string $hash    Hash.
	 * @param string $status  Status.
	 */
	public function set_group_status( $hash, $status ) {
		$this->group_statuses[ $hash ] = $status;
	}

	/**
	 * Mark the groups to be processed or not.
	 *
	 * Groups that need to be processed will have an asset that either requires minification, combine or should be
	 * served via CDN. Basically, anything that needs to be sent out to our API.
	 */
	public function preprocess_groups() {
		foreach ( $this->get_groups() as $group ) {
			/**
			 * Minify group.
			 *
			 * @var Minify_Group $group
			 */
			$group->maybe_load_file();
			$group_src = $group->get_group_src();

			if ( $group->should_process_group() && $group->file_id && $group_src && ! $group->is_expired() ) {
				// The group has its file and is not expired.
				$this->set_group_status( $group->hash, 'ready' );
			} elseif ( $group->should_process_group() && ( empty( $group_src ) || $group->is_expired() ) ) {
				// The group must be processed but it has no file yet.
				$this->set_group_status( $group->hash, 'process' );

				// Delete file in case there's one (but is expired).
				$group->delete_file();
			} else {
				// The group won't be processed.
				// Use the original handles and their URLs instead.
				$this->set_group_status( $group->hash, 'only-handles' );
			}
		}

		$this->parse_groups_dependencies();
	}

	/**
	 * Parse dependencies for the group.
	 *
	 * @return array|bool
	 */
	public function parse_groups_dependencies() {
		if ( false !== $this->groups_dependencies ) {
			return $this->groups_dependencies;
		}

		$deps = array();
		$self = $this;
		// Now that every is marked, let's parse dependencies
		// This cannot be undone, so do not change groups after this.
		array_map(
			function( $group ) use ( &$deps, $self ) {
				/**
				 * Minify group.
				 *
				 * @var Minify_Group $group
				 */
				$search_group_deps          = $group->get_all_handles_dependencies();
				$search_group_hash          = $group->hash;
				$deps[ $search_group_hash ] = array();

				foreach ( $self->get_groups() as $position => $g ) {
					$g_status = $self->get_group_status( $g->hash );
					$g_hash   = $g->hash;

					if ( $g_hash === $search_group_hash ) {
						// Don't search deps in the same group.
						continue;
					}

					$g_handles = $g->get_handles();
					$intersect = array_intersect( $g_handles, $search_group_deps );
					if ( ! empty( $intersect ) ) {
						// We've found dependencies.
						if ( 'ready' !== $g_status ) {
							// The group is not ready, dependencies are one or more of its handles.
							$deps[ $search_group_hash ] = array_merge(
								$deps[ $search_group_hash ],
								array_map(
									function( $handle ) use ( $g ) {
										return $g->group_id . '-' . $handle;
									},
									$intersect
								)
							);

						} else {
							$deps[ $search_group_hash ] = array_merge( $deps[ $search_group_hash ], array( $g->group_id ) );
						}
					}
				}

			},
			$this->get_groups()
		);

		$this->groups_dependencies = $deps;
		return $deps;
	}

}
