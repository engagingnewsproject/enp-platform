<?php
/**
 * Handles checks and operations related to the PHP version used by the server.
 *
 * @package WP_Defender\Component\Security_Tweaks
 */

namespace WP_Defender\Component\Security_Tweaks;

/**
 * Handles checks and operations related to the PHP version used by the server.
 */
class PHP_Version extends Abstract_Security_Tweaks {

	/**
	 * Identifier slug for the component.
	 *
	 * @var string
	 */
	public string $slug = 'php-version';
	/**
	 * Minimum PHP version required.
	 *
	 * @var string|null
	 */
	public $min_php = null;
	/**
	 * Recommended stable PHP version.
	 *
	 * @var string|null
	 */
	public $stable_php = null;

	/**
	 * Current PHP version of the server.
	 *
	 * @var string
	 */
	public $current_php = PHP_VERSION;

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
	public function process(): bool {
		return true;
	}

	/**
	 * This is for un-do stuff that has to be done in @process.
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
	 * Check wheter the issue is resolved or not.
	 *
	 * @return bool
	 */
	private function is_resolved() {
		$this->set_php_versions();

		return version_compare( $this->current_php, $this->min_php, '>' );
	}

	/**
	 * Setup PHP versions.
	 *
	 * @return void
	 */
	private function set_php_versions() {
		if ( ! function_exists( 'wp_check_php_version' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$info = wp_check_php_version();

		if ( ! $info ) {
			$this->min_php    = 'n/a';
			$this->stable_php = 'n/a';

			return;
		}

		$supported_php    = apply_filters(
			"defender_{$this->slug}_supported_php",
			array(
				'7.4',
				'8.0',
				'8.1',
				'8.2',
			)
		);
		$this->stable_php = $info['recommended_version'];
		$position         = array_search( $this->stable_php, $supported_php, true );

		if ( false !== $position && ! empty( $supported_php[ $position ] ) ) {
			$this->min_php = $supported_php[ $position ];
		}
	}

	/**
	 * Retrieve the tweak's label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return esc_html__( 'Update PHP to latest version', 'wpdef' );
	}

	/**
	 * Get the error reason.
	 *
	 * @return string
	 */
	public function get_error_reason(): string {
		$this->set_php_versions();

		return sprintf(
			/* translators: %s: Min PHP version. %s: Min PHP version. */
			esc_html__(
				'PHP versions older than %1$s are no longer supported. For security and stability we strongly recommend you upgrade your PHP version to version %2$s or newer as soon as possible.',
				'wpdef'
			),
			$this->min_php,
			$this->min_php
		);
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array(): array {
		$this->set_php_versions();

		return array(
			'slug'          => $this->slug,
			'title'         => $this->get_label(),
			'errorReason'   => $this->get_error_reason(),
			'successReason' => esc_html__( 'You have the latest version of PHP installed, good stuff!', 'wpdef' ),
			'misc'          => array(
				'php_version'        => $this->current_php,
				'min_php_version'    => $this->min_php,
				'stable_php_version' => $this->stable_php,
			),
		);
	}
}