<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;

/**
 * Class Prevent_Enum_Users
 * @package WP_Defender\Component\Security_Tweaks
 */
class Prevent_Enum_Users extends Component {
	public $slug = 'prevent-enum-users';
	public $resolved = false;

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		return $this->resolved;
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
	 * This is for un-do stuff that has been done in @process.
	 *
	 * @return bool
	 */
	public function revert() {
		return true;
	}

	/**
	 * Shield up.
	 *
	 * @return void
	 */
	public function shield_up() {
		$this->resolved = true;

		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		if ( empty( $_SERVER['QUERY_STRING'] ) ) {
			return;
		}

		$this->maybe_block( $_SERVER['QUERY_STRING'] );

		add_filter( 'redirect_canonical', [ $this, 'maybe_block' ] );
	}

	/**
	 * Maybe block the request if it's trying to access the author page with query param.
	 *
	 * @param string $request
	 *
	 * @return string
	 */
	public function maybe_block( $request ) {
		$message = __( 'Sorry, you are not allowed to access this page', 'wpdef' );

		if ( preg_match( '/author=([0-9]*)/i', $request ) ) {
			wp_die( $message );
		}

		if ( preg_match( '/\?author=([0-9]*)(\/*)/i', $request ) ) {
			wp_die( $message );
		}

		return $request;
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'slug'             => $this->slug,
			'title'            => __( 'Prevent user enumeration', 'wpdef' ),
			'errorReason'      => __( 'User enumeration is currently allowed.', 'wpdef' ),
			'successReason'    => __( 'User enumeration is currently blocked, nice work!', 'wpdef' ),
			'misc'             => [],
			'bulk_description' => __( 'To brute force your login,  hackers and bots can simply type the query string ?author=1, ?author=2 and so on, which will redirect the page to /author/username/ - bam, the bot now has your usernames to begin brute force attacks with. We can add a .htaccess file to your site to prevent the redirection.', 'wpdef' ),
			'bulk_title'       => __( 'Prevent user enumeration', 'wpdef' )
		];
	}
}
