<?php
/**
 * Helper functions for user related tasks.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

use WP_User;
use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Notification;

trait User {

	/**
	 * Super Admin role slug.
	 *
	 * @var string
	 */
	public $super_admin_slug = 'super_admin';

	/**
	 * Get user display.
	 *
	 * @param  null|int $user_id  User ID.
	 *
	 * @return string
	 */
	public function get_user_display( $user_id = null ): string {
		if ( is_null( $user_id ) ) {
			return esc_html__( 'Guest', 'wpdef' );
		}

		$user = $this->get_user( $user_id );
		if ( ! is_object( $user ) ) {
			return esc_html__( 'Guest', 'wpdef' );
		}

		return $user->display_name;
	}

	/**
	 * Check if current request is from Hub.
	 *
	 * @return bool
	 * @since 2.7.0
	 */
	protected function is_hub_request(): bool {
		return ! empty( defender_get_data_from_request( 'wpmudev-hub', 'g' ) );
	}

	/**
	 * Get source of action. This can be a request from the Hub, a logged-in user.
	 * Todo: expand for WP-CLI, REST sources.
	 *
	 * @return string
	 * @since 2.7.0
	 */
	public function get_source_of_action(): string {
		return $this->is_hub_request()
			? esc_html__( 'Hub', 'wpdef' )
			: $this->get_user_display( get_current_user_id() );
	}

	/**
	 * Get user login.
	 *
	 * @param  null|int $user_id  User ID.
	 *
	 * @return bool|mixed|string|WP_User|null
	 */
	public function get_user_login( $user_id = null ) {
		$user = $this->get_user( $user_id );
		if ( ! is_object( $user ) ) {
			return $user;
		}

		return $user->user_login;
	}

	/**
	 * Get user email.
	 *
	 * @param  null|int $user_id  User ID.
	 *
	 * @return string|null
	 */
	public function get_current_user_email( $user_id = null ) {
		$user = $this->get_user( $user_id );
		if ( ! is_object( $user ) ) {
			return $user;
		}

		return $user->user_email;
	}

	/**
	 * Get user role.
	 *
	 * @param  null|int $user_id  User ID.
	 *
	 * @return array|null
	 */
	public function get_current_user_role( $user_id = null ) {
		$user = $this->get_user( $user_id );
		if ( ! is_object( $user ) ) {
			return null;
		}

		return empty( $user->roles ) ? null : ucfirst( array_shift( $user->roles ) );
	}

	/**
	 * Todo: compare with method get_current_user_role().
	 *
	 * @param  object $user  User object.
	 *
	 * @return string
	 */
	public function get_first_user_role( $user ) {
		$role = '';
		if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
			$role = ucfirst( array_shift( $user->roles ) );
		}

		return $role;
	}

	/**
	 * Get user object by user ID.
	 *
	 * @param  null|int|WP_User $user_id  User ID or WP_User object.
	 *
	 * @return bool|mixed|WP_User|null
	 */
	private function get_user( $user_id ) {
		if ( $user_id instanceof WP_User ) {
			return $user_id;
		}

		if ( ! is_user_logged_in() ) {
			return esc_html__( 'Guest', 'wpdef' );
		}
		if ( null === $user_id ) {
			$user = wp_get_current_user();
		} else {
			$cache = Array_Cache::get( $user_id, 'user' );
			if ( null !== $cache ) {
				if ( empty( $cache->roles ) ) {
					$cache_roles = Array_Cache::get( 'roles_' . $user_id, 'user' );
					if ( null !== $cache_roles ) {
						$cache->roles = $cache_roles;
					}
				}

				$user = $cache;
			} else {
				$user = get_user_by( 'id', $user_id );
				if ( is_object( $user ) ) {
					Array_Cache::set( $user_id, $user, 'user' );
					Array_Cache::set( 'roles_' . $user_id, $user->roles, 'user' );
				}
			}
		}

		return $user;
	}

	/**
	 * Return the default user for recipient, should be the current user.
	 *
	 * @return array
	 */
	public function get_default_recipient(): array {
		// @since 3.0.0 Fix 'Guest'-line.
		if ( ! is_user_logged_in() ) {
			return array();
		}
		$user_id = get_current_user_id();

		return array(
			'name'   => $this->get_user_display( $user_id ),
			'id'     => $user_id,
			'email'  => $this->get_current_user_email( $user_id ),
			'role'   => $this->get_current_user_role( $user_id ),
			'avatar' => get_avatar_url( $this->get_current_user_email( $user_id ) ),
			'status' => Notification::USER_SUBSCRIBED,
		);
	}

	/**
	 * Get user roles.
	 * This method will include 'super_admin' role if provided user is super admin.
	 *
	 * @param  WP_User $user  User object.
	 *
	 * @return array
	 * @since 3.2.0
	 */
	public function get_roles( WP_User $user ): array {
		$user_roles = (array) $user->roles;

		// If user is super admin.
		if ( is_multisite() && is_super_admin( $user->ID ) && ! in_array(
			$this->super_admin_slug,
			$user_roles,
			true
		) ) {
			$user_roles[] = $this->super_admin_slug;
		}

		return $user_roles;
	}

	/**
	 * Get all roles editable by current user.
	 *
	 * @return array
	 * @since 3.2.0
	 */
	public function get_all_editable_roles(): array {
		$editable_roles = wp_list_pluck( get_editable_roles(), 'name' );

		if ( is_multisite() && is_super_admin() ) {
			$editable_roles = array_merge(
				array(
					$this->super_admin_slug => 'Super Admin',
				),
				$editable_roles
			);
		}

		return $editable_roles;
	}

	/**
	 * Does the current user have admin credentials?
	 *
	 * @param  int|WP_User $user  User ID or WP_User object.
	 *
	 * @return bool
	 */
	public function is_admin( $user ): bool {
		if ( $user ) {
			if ( is_multisite() ) {
				if ( user_can( $user, 'manage_network' ) ) {
					return true;
				}
			} elseif ( user_can( $user, 'manage_options' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the user has a role selected by the admin.
	 *
	 * @param WP_User|stdClass $user User object.
	 *
	 * @return bool True if the user has a selected role, false otherwise.
	 */
	private function should_enforce_for_user( $user ): bool {
		$roles = array();
		if ( ! is_multisite() ) {
			if ( $user instanceof WP_User ) {
				$roles = $this->get_roles( $user );
			} elseif ( empty( $roles ) && $user instanceof stdClass && ! empty( $user->ID ) ) {
				$user  = get_userdata( $user->ID );
				$roles = $user->roles;
			} elseif ( empty( $roles ) ) {
				$role = defender_get_data_from_request( 'role', 'p' );
				if ( ! empty( $role ) ) {
					$roles = array( $role );
				}
			}
		} elseif ( is_multisite() && isset( $user->ID ) ) {
			$blogs = get_blogs_of_user( $user->ID );
			foreach ( $blogs as $blog ) {
				// Get user roles for this blog.
				$u     = new WP_User( $user->ID, '', $blog->userblog_id );
				$roles = array_merge( $u->roles, $roles );
			}
		}

		$user_roles = $this->get_user_roles_property();

		$array_intersect = array_intersect( $user_roles, $roles );

		return ! empty( $array_intersect );
	}

	/**
	 * Retrieve the user roles property dynamically.
	 *
	 * @return array The user roles from the appropriate property.
	 */
	private function get_user_roles_property(): array {
		if ( isset( $this->model ) && property_exists( $this->model, 'user_roles' ) ) {
			return $this->model->user_roles;
		} elseif ( isset( $this->settings ) && property_exists( $this->settings, 'user_roles' ) ) {
			return $this->settings->user_roles;
		}

		return array();
	}
}