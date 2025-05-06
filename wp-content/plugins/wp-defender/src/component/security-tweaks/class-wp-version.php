<?php
/**
 * Handles
 *
 * @package WP_Defender\Component\Security_Tweaks
 */

namespace WP_Defender\Component\Security_Tweaks;

/**
 * Class WP_Version
 */
class WP_Version extends Abstract_Security_Tweaks {

	/**
	 * Component slug name.
	 *
	 * @var string
	 */
	public string $slug = 'wp-version';

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		return $this->is_resolved();
	}

	/**
	 * Here is the code for processing.
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
	public function get_latest_version() {
		if ( ! function_exists( 'get_core_updates' ) ) {
			include_once ABSPATH . 'wp-admin/includes/update.php';
		}

		$data = get_core_updates();

		if ( empty( $data ) ) {
			wp_version_check( array(), true );
			$data = get_core_updates( array( 'dismissed' => true ) );
		}

		// For bool value and empty array.
		if ( empty( $data ) ) {
			return false;
		}

		return reset( $data )->version;
	}

	/**
	 * Retrieve the tweak's label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return esc_html__( 'Update WordPress to latest version', 'wpdef' );
	}

	/**
	 * Get the error reason.
	 *
	 * @return string
	 */
	public function get_error_reason(): string {
		return sprintf(
			/* translators: %s: WP Version */
			esc_html__(
				'Your current WordPress version is out of date, which means you could be missing out on the latest security patches in v%s',
				'wpdef'
			),
			$this->get_latest_version()
		);
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
			'successReason'    => esc_html__( 'You are using the latest version of WordPress, great job!', 'wpdef' ),
			'misc'             => array(
				'latest_wp'       => $this->get_latest_version(),
				'core_update_url' => network_admin_url( 'update-core.php' ),
			),
			'bulk_description' => esc_html__(
				'Your current WordPress version is out of date, which means you could be missing out on the latest update. We will upgrade WordPress version to the latest.',
				'wpdef'
			),
			'bulk_title'       => esc_html__( 'WordPress Version', 'wpdef' ),
		);
	}
}