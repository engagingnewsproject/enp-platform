<?php
/**
 * Security Tweaks Component for changing the default 'admin' username.
 *
 * @package    WP_Defender\Component\Security_Tweaks
 */

namespace WP_Defender\Component\Security_Tweaks;

use WP_User;
use WP_Error;
use Calotes\Helper\HTTP;
use Calotes\Component\Response;

/**
 * Handle the security tweak of changing the default 'admin' username to a user-defined one.
 */
class Change_Admin extends Abstract_Security_Tweaks {

	/**
	 * Slug identifier for the component.
	 *
	 * @var string
	 */
	public string $slug = 'replace-admin-username';

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check(): bool {
		return $this->is_resolved();
	}

	/**
	 * If the return is true or Response, we add it to resolve list. WP_Error if any error.
	 *
	 * @return bool|WP_Error|Response
	 */
	public function process() {
		$username = HTTP::post( 'username' );
		$is_valid = $this->validate( $username );

		if ( is_wp_error( $is_valid ) ) {
			return $is_valid;
		}

		return $this->update_username( $username );
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 *
	 * @return bool
	 */
	public function revert(): bool {
		return true;
	}

	/**
	 * Shield up.
	 *
	 * @return bool
	 */
	public function shield_up(): bool {
		return true;
	}

	/**
	 * Check whether the issue is resolved or not.
	 *
	 * @return bool
	 */
	private function is_resolved() {
		return ! $this->get_user_with_admin_username();
	}

	/**
	 * Get user with admin username.
	 *
	 * @return WP_User|false on failure
	 */
	private function get_user_with_admin_username() {
		return get_user_by( 'login', 'admin' );
	}

	/**
	 * Validate username.
	 *
	 * @param  string $username  to validate.
	 *
	 * @return bool|WP_Error on failure
	 */
	private function validate( $username ) {
		if ( empty( $username ) ) {
			return new WP_Error( 'defender_invalid_username', esc_html__( 'The username can\'t be empty!', 'wpdef' ) );
		}

		if ( 'admin' === strtolower( $username ) ) {
			return new WP_Error(
				'defender_invalid_username',
				esc_html__( 'You can\'t use admin as a username again!', 'wpdef' )
			);
		}

		if ( ! validate_username( $username ) ) {
			return new WP_Error( 'defender_invalid_username', esc_html__( 'The username is invalid!', 'wpdef' ) );
		}

		if ( username_exists( $username ) ) {
			return new WP_Error( 'defender_invalid_username', esc_html__( 'The username already exists!', 'wpdef' ) );
		}

		return true;
	}

	/**
	 * Updates the 'admin' username to a new username.
	 * Performs the update in the database and handles multisite admin updates if applicable.
	 *
	 * @param  string $username  The new username.
	 *
	 * @return bool|WP_Error|Response True on success, WP_Error on database error, Response on logout requirement.
	 */
	private function update_username( $username ) {
		global $wpdb;
		$user = $this->get_user_with_admin_username();

		$ret = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->users,
			array( 'user_login' => trim( $username ) ),
			array( 'ID' => $user->ID )
		);
		if ( ! $ret ) {
			return new WP_Error( 'update_error', $wpdb->last_error );
		}

		if ( is_multisite() ) {
			$site_admins = get_site_option( 'site_admins' );

			if ( is_array( $site_admins ) ) {
				$pos = array_search( 'admin', array_map( 'strtolower', $site_admins ), true );

				if ( false !== $pos ) {
					$site_admins[ $pos ] = $username;
					update_site_option( 'site_admins', $site_admins );
				}
			}
		}
		clean_user_cache( $user );
		// Log the user out only if it's the user with 'admin' username.
		if ( get_current_user_id() !== $user->ID ) {
			return true;
		}
		if ( defined( 'WP_DEFENDER_TESTING' ) && true === constant( 'WP_DEFENDER_TESTING' ) ) {
			// Testing.
			return true;
		}
		$interval = 5;
		$redirect = $this->get_login_url();

		return new Response(
			true,
			array(
				'message'  => sprintf(
				/* translators: 1. Redirect link. 2. Line break. 3. Interval. */
					esc_html__(
						'Your admin name has changed. You will need to %1$s.%2$s This will auto reload after %3$s seconds.',
						'wpdef'
					),
					'<a href="' . $redirect . '"><strong>' . esc_html__( 're-login', 'wpdef' ) . '</strong></a>',
					'<br>',
					'<span class="hardener-timer">' . $interval . '</span>'
				),
				'redirect' => $redirect,
				'interval' => $interval,
			)
		);
	}

	/**
	 * Get the login url.
	 *
	 * @return string
	 */
	private function get_login_url(): string {
		return wp_login_url( network_admin_url( 'admin.php?page=wdf-hardener' ) );
	}

	/**
	 * Retrieve the tweak's label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return esc_html__( 'Change default admin user account', 'wpdef' );
	}

	/**
	 * Get the error reason.
	 *
	 * @return string
	 */
	public function get_error_reason(): string {
		return esc_html__( 'You have a user account with the admin username.', 'wpdef' );
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'slug'             => $this->slug,
			'title'            => $this->get_label(),
			'errorReason'      => $this->get_error_reason(),
			'successReason'    => esc_html__(
				'You don\'t have a user account with the default admin username, great!',
				'wpdef'
			),
			'misc'             => array( 'host' => defender_get_hostname() ),
			'bulk_description' => esc_html__(
				'Using the default admin username is widely considered bad practice and opens you up to the easitest form of entry to your website. We will create new admin username for you.',
				'wpdef'
			),
			'bulk_title'       => esc_html__( 'Admin User', 'wpdef' ),
		);
	}
}