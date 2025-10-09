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

	private const BREADCRUMBS_PREFIX_KEY = 'wd_';
	private const BREADCRUMBS_SUFFIX_KEY = '_breadcrumbs';

	/**
	 * Get the feature's slug.
	 *
	 * @return string
	 */
	private function get_feature_slug(): string {
		return self::BREADCRUMBS_PREFIX_KEY
			. \WP_Defender\Component\Fake_Bot_Detection::SCENARIO_FAKE_BOT . self::BREADCRUMBS_SUFFIX_KEY;
	}

	/**
	 * Get the previous feature's slug.
	 *
	 * @return string
	 */
	private function get_previous_feature_slug(): string {
		return self::BREADCRUMBS_PREFIX_KEY
		. \WP_Defender\Model\Scan_Item::TYPE_PLUGIN_OUTDATED . self::BREADCRUMBS_SUFFIX_KEY;
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

	/**
	 * Delete the previous feature's meta key.
	 *
	 * @return void
	 */
	public function delete_previous_meta() {
		global $wpdb;

		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $this->get_previous_feature_slug() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery
	}
}