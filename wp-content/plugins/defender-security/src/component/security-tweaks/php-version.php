<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;

/**
 * Class PHP_Version
 * @package WP_Defender\Component\Security_Tweaks
 */
class PHP_Version extends Component {
	public $slug = 'php-version';
	public $min_php = null;
	public $stable_php = null;
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
			$info = defender_wp_check_php_version();
		} else {
			$info = wp_check_php_version();
		}

		if ( ! $info ) {
			$this->min_php    = 'n/a';
			$this->stable_php = 'n/a';

			return;
		}

		$supported_php    = apply_filters( "defender_{$this->slug}_supported_php", [ '7.2', '7.3', '7.4', '8.0' ] );
		$this->stable_php = $info['recommended_version'];
		$position         = array_search( $this->stable_php, $supported_php );

		if ( false !== $position && ! empty( $supported_php[ $position ] ) ) {
			$this->min_php = $supported_php[ $position ];
		}
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		$this->set_php_versions();

		return [
			'slug'            => $this->slug,
			'title'           => __( 'Update PHP to latest version', 'wpdef' ),
			'errorReason'     => sprintf( __( "PHP versions older than %s are no longer supported. For security and stability we strongly recommend you upgrade your PHP version to version %s or newer as soon as possible.", 'wpdef' ), $this->min_php, $this->min_php ),
			'successReason'   => __( 'You have the latest version of PHP installed, good stuff!', 'wpdef' ),
			'misc'            => [
				'php_version'        => $this->current_php,
				'min_php_version'    => $this->min_php,
				'stable_php_version' => $this->stable_php,
			],
		];
	}
}
