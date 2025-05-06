<?php
/**
 * Manages the display of Breadcrumbs.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Defender\Component;

/**
 * Use Breadcrumbs on different plugin pages.
 *
 * @since 5.2.0
 */
class Breadcrumbs extends Component {

	/**
	 * Get the feature's slug.
	 *
	 * @return string
	 */
	private function get_feature_slug(): string {
		return 'wd_' . \WP_Defender\Model\Setting\Session_Protection::get_module_slug() . '_breadcrumbs';
	}

	/**
	 * Update the feature's meta key.
	 *
	 * @return void
	 */
	public function update_meta_key(): void {
		update_user_meta( get_current_user_id(), $this->get_feature_slug(), 1 );
	}

	/**
	 * Get the feature's meta key.
	 *
	 * @return bool
	 */
	public function get_meta_key(): bool {
		return (bool) get_user_meta( get_current_user_id(), $this->get_feature_slug(), true );
	}

	/**
	 * Delete the feature's meta key.
	 *
	 * @return void
	 */
	public function delete_meta_key(): void {
		global $wpdb;

		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $this->get_feature_slug() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery
	}
}