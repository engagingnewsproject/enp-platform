<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;

/**
 * Class WP_Version
 * @package WP_Defender\Component\Security_Tweaks
 */
class WP_Version extends Component {
	public $slug = 'wp-version';

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		return $this->is_resolved();
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 *
	 * @return bool
	 */
	public function process() {
		return true;
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 *
	 * @return bool
	 */
	public function revert() {
		return true;
	}

	/**
	 * Shield up.
	 *
	 * @return bool
	 */
	public function shield_up() {
		return true;
	}

	/**
	 * Check whether the issue is resolved or not.
	 *
	 * @return bool
	 */
	private function is_resolved() {
		global $wp_version;

		return version_compare( $wp_version, $this->get_latest_version(), '=' );
	}

	/**
	 * Get the latest WordPress version.
	 *
	 * @return string|false on failure
	 */
	private function get_latest_version() {
		if ( ! function_exists( 'get_core_updates' ) ) {
			include_once ABSPATH . 'wp-admin/includes/update.php';
		}

		$data = get_core_updates();

		if ( false === $data ) {
			wp_version_check( [], true );
			$data = get_core_updates();
		}

		//For bool value and empty array.
		if ( empty( $data ) ) {
			return false;
		}

		return reset( $data )->version;
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'slug'             => $this->slug,
			'title'            => __( 'Update WordPress to latest version', 'wpdef' ),
			'errorReason'      => sprintf( __( 'Your current WordPress version is out of date, which means you could be missing out on the latest security patches in v%s', 'wpdef' ), $this->get_latest_version() ),
			'successReason'    => __( 'You are using the latest version of WordPress, great job!', 'wpdef' ),
			'misc'             => [
				'latest_wp'       => $this->get_latest_version(),
				'core_update_url' => network_admin_url( 'update-core.php' )
			],
			'bulk_description' => __( 'Your current WordPress version is out of date, which means you could be missing out on the latest update. We will upgrade WordPress version to the latest.', 'wpdef' ),
			'bulk_title'       => __( 'WordPress Version', 'wpdef' )
		];
	}
}
