<?php

namespace WP_Defender\Traits;

use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Notification;
use WP_User;

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
	 * @param null|int $user_id
	 *
	 * @return string
	 */
	public function get_user_display( $user_id = null ): string {
		if ( is_null( $user_id ) ) {
			return __( 'Guest', 'wpdef' );
		}

		$user = $this->get_user( $user_id );
		if ( ! is_object( $user ) ) {
			return __( 'Guest', 'wpdef' );
		}

		return $user->display_name;
	}

	/**
	 * Check if current request is from Hub.
	 *
	 * @since 2.7.0
	 * @return bool
	 */
	protected function is_hub_request(): bool {
		return isset( $_GET['wpmudev-hub'] ) && ! empty( $_GET['wpmudev-hub'] ); // phpcs:ignore
	}

	/**
	 * Get source of action. This can be a request from the Hub, a logged-in user.
	 * Todo: expand for WP-CLI, REST sources.
	 *
	 * @since 2.7.0
	 * @return string
	*/
	public function get_source_of_action(): string {
		return $this->is_hub_request()
			? __( 'Hub', 'wpdef' )
			: $this->get_user_display( get_current_user_id() );
	}

	/**
	 * @param null|int $user_id
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
	 * @param null|int $user_id
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
	 * @param null|int $user_id
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
	 * @param null|int|WP_User $user_id
	 *
	 * @return bool|mixed|WP_User|null
	 */
	private function get_user( $user_id ) {
		if ( $user_id instanceof WP_User ) {
			return $user_id;
		}

		if ( ! is_user_logged_in() ) {
			return __( 'Guest', 'wpdef' );
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
			return [];
		}
		$user_id = get_current_user_id();

		return [
			'name' => $this->get_user_display( $user_id ),
			'id' => $user_id,
			'email' => $this->get_current_user_email( $user_id ),
			'role' => $this->get_current_user_role( $user_id ),
			'avatar' => get_avatar_url( $this->get_current_user_email( $user_id ) ),
			'status' => Notification::USER_SUBSCRIBED,
		];
	}

	/**
	 * Get user roles.
	 *
	 * This method will include 'super_admin' role if provided user is super admin.
	 *
	 * @param WP_User $user
	 *
	 * @since 3.2.0
	 * @return array
	 */
	public function get_roles( WP_User $user ): array {
		$user_roles = (array) $user->roles;

		// If user is super admin
		if ( is_multisite() && is_super_admin( $user->ID ) && ! in_array( $this->super_admin_slug, $user_roles, true ) ) {
			$user_roles[] = $this->super_admin_slug;
		}

		return $user_roles;
	}

	/**
	 * Get all roles editable by current user.
	 *
	 * @since 3.2.0
	 * @return array
	 */
	public function get_all_editable_roles(): array {
		$editable_roles = wp_list_pluck( get_editable_roles(), 'name' );

		if ( is_multisite() && is_super_admin() ) {
			$editable_roles = array_merge(
				[
					$this->super_admin_slug => 'Super Admin',
				],
				$editable_roles
			);
		}

		return $editable_roles;
	}
}
