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
	 * Check if user is a paid one in WPMU DEV.
	 *
	 * @return bool
	 */
	public function is_member() {
		if (
			class_exists( 'WPMUDEV_Dashboard' )
			&& method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_projects' )
		) {
			if ( method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_status' ) ) {
				$type = \WPMUDEV_Dashboard::$api->get_membership_status();
			} elseif ( method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_type' ) ) {
				$type = \WPMUDEV_Dashboard::$api->get_membership_type();
			} else {
				return false;
			}

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

	/**
	 * Bring the plugin menu title.
	 *
	 * @return string Menu title.
	 */
	public function get_menu_title() {
		if ( $this->is_pro() ) {
			$menu_title = esc_html__( 'Defender Pro', 'wpdef' );
		} else {
			// Check if it's Pro but user logged the WPMU DEV Dashboard out.
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$menu_title = file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . WP_DEFENDER_PRO_PATH )
							&& is_plugin_active( WP_DEFENDER_PRO_PATH )
				? esc_html__( 'Defender Pro', 'wpdef' )
				: esc_html__( 'Defender', 'wpdef' );
		}

		return $menu_title;
	}

	/**
	 * Return icon svg image.
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		ob_start();
		?>
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M9.99999 2.08899L3 4.21792V9.99502H9.99912V18.001H10C13.47 18.001 17 13.9231 17 11.0045V9.99501H9.99999V2.08899ZM10 0L1 2.73862V11.0045C1 15.1125 5.49 20 10 20C14.51 20 19 15.1225 19 11.0045V2.73862L10 0Z" fill="#F0F6FC" fill-opacity="0.6"/>
		</svg>
		<?php
		$svg = ob_get_clean();

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}
}
