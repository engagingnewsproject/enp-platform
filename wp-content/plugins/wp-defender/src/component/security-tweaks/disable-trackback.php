<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;

/**
 * Class Disable_Trackback
 * @package WP_Defender\Component\Security_Tweaks
 */
class Disable_Trackback extends Component {
	public $slug = 'disable-trackback';
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
	 * @return bool|\WP_Error
	 */
	public function process() {
		return $this->update_site_trackback();
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 *
	 * @return bool|\WP_Error
	 */
	public function revert() {
		return $this->update_site_trackback( true );
	}

	/**
	 * Update site ping status and pingback flag.
	 *
	 * @param bool $revert Set true to revert changes. Default value: false.
	 *
	 * @return bool
	 */
	private function update_site_trackback( $revert = false ) {
		global $wpdb;

		$flag          = $revert ? 1 : 0;
		$status        = $revert ? 'open' : 'closed';
		$ping_status   = 'default_ping_status';
		$pingback_flag = 'default_pingback_flag';

		if ( ! is_multisite() ) {
			update_option( $ping_status, $status );
			update_option( $pingback_flag, $flag );

			$this->change_posts_ping_status( $wpdb, $revert );

			return true;
		}

		$blog_ids = wp_list_pluck( get_sites(), 'blog_id' );

		foreach ( $blog_ids as $blog_id ) {
			update_blog_option( $blog_id, $ping_status, $status );
			update_blog_option( $blog_id, $pingback_flag, $flag );

			$this->change_posts_ping_status( $wpdb, $revert, $blog_id );
		}

		return true;
	}

	/**
	 * Update the post ping status.
	 *
	 * @param bool $revert  Set to true to revert changes.
	 * @param int  $blog_id The blog id if multisite.
	 *
	 * @return void
	 */
	private function change_posts_ping_status( $wpdb, $revert = false, $blog_id = 0 ) {
		$ping_status         = $revert ? 'open' : 'closed';
		$post_type_to_ignore = [ 'wd_ip_lockout', 'wd_iplockout_log' ];
		$post_type_to_ignore = "'" . implode( "','", $post_type_to_ignore ) . "'";

		if ( $blog_id ) {
			$wpdb->set_blog_id( $blog_id );
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET `ping_status` = %s WHERE `post_status` != %s AND `post_type` NOT IN(%s)", $ping_status, 'inherit', $post_type_to_ignore ) );
		} else {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET `ping_status` = %s WHERE `post_status` != %s AND `post_type` NOT IN(%s)", $ping_status, 'inherit', $post_type_to_ignore ) );
		}
	}

	/**
	 * Shield up.
	 *
	 * @return void
	 */
	public function shield_up() {
		$this->resolved = true;
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'slug'             => $this->slug,
			'title'            => __( 'Disable trackbacks and pingbacks', 'wpdef' ),
			'errorReason'      => __( 'Trackbacks and pingbacks are currently enabled.', 'wpdef' ),
			'successReason'    => __( 'Trackbacks and pingbacks are disabled, nice work!', 'wpdef' ),
			'misc'             => [],
			'bulk_description' => __( 'Trackbacks and pingbacks can lead to DDos attacks and tons of spam comments. If you don’t require this feature, we recommend turning it off.', 'wpdef' ),
			'bulk_title'       => 'Trackbacks and pingbacks'
		];
	}
}