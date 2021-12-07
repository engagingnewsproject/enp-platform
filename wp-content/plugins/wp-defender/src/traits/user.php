<?php

namespace WP_Defender\Traits;

use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Notification;

trait User {
	/**
	 * Get user display
	 *
	 * @param  null  $user_id
	 *
	 * @return string|void
	 */
	public function get_user_display( $user_id = null ) {
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
	 * @param  null  $user_id
	 *
	 * @return bool|mixed|string|\WP_User|null
	 */
	public function get_user_login( $user_id = null ) {
		$user = $this->get_user( $user_id );
		if ( ! is_object( $user ) ) {
			return $user;
		}

		return $user->user_login;
	}

	/**
	 * @param  null  $user_id
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
	 * @param  null  $user_id
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
	 * @param  null|int|\WP_User  $user_id
	 *
	 * @return bool|mixed|\WP_User|null
	 */
	private function get_user( $user_id ) {
		if ( $user_id instanceof \WP_User ) {
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
				$user = $cache;
			} else {
				$user = get_user_by( 'id', $user_id );
				if ( is_object( $user ) ) {
					Array_Cache::set( $user_id, $user, 'user' );
				}
			}
		}

		return $user;
	}

	/**
	 * Return the default user for recipient, should be the current user
	 * @return array
	 */
	public function get_default_recipient() {
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
}