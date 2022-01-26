<?php
/**
 * Handle Dashboard based functionalities of WPMUDEV class.
 *
 * @package WP_Defender\Behavior
 */

namespace WP_Defender\Traits;

/**
 * Traits to handle Dashboard based functionalities of WPMUDEV class.
 */
trait Defender_Dashboard_Client {
	/**
	 * Get membership status.
	 *
	 * @return bool
	 */
	public function is_pro() {
		return $this->get_apikey() !== false;
	}

	/**
	 * Bring the plugin menu title.
	 *
	 * @return string Menu title.
	 */
	public function get_menu_title() {
		if ( $this->is_pro() ) {
			$menu_title = esc_html__( 'Defender Pro', 'wpdef' );
		} else {
			// Check if it's Pro but user logged the WPMU Dashboard out.
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$menu_title = file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'wp-defender/wp-defender.php' )
			&& is_plugin_active( 'wp-defender/wp-defender.php' )
				? esc_html__( 'Defender Pro', 'wpdef' )
				: esc_html__( 'Defender', 'wpdef' );
		}

		return $menu_title;
	}

	/**
	 * Check if user is a paid one in WPMU DEV.
	 *
	 * @return bool
	 */
	public function is_member() {
		if (
			class_exists( 'WPMUDEV_Dashboard' )
			&& method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_projects' )
			&& method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_type' )
		) {
			$type     = \WPMUDEV_Dashboard::$api->get_membership_type();
			$projects = \WPMUDEV_Dashboard::$api->get_membership_projects();
			$def_pid  = 1081723;

			if (
				( 'unit' === $type && in_array( $def_pid, $projects, true ) )
				|| ( 'single' === $type && $def_pid === $projects )
			) {
				return true;
			}

			if ( function_exists( 'is_wpmudev_member' ) ) {
				return is_wpmudev_member();
			}

			return false;
		}

		return false;
	}

	/**
	 * Check if user is a WPMU DEV admin.
	 *
	 * @since 2.6.3
	 *
	 * @return bool
	 */
	public function is_wpmu_dev_admin() {
		if (
			class_exists( 'WPMUDEV_Dashboard' )
			&& method_exists( 'WPMUDEV_Dashboard_Site', 'allowed_user' )
		) {
			$user_id = get_current_user_id();

			return \WPMUDEV_Dashboard::$site->allowed_user( $user_id );
		}

		return false;
	}
}
