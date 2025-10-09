<?php
/**
 * Manages the display of "What's New" modals  dashboard.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Traits\Defender_Dashboard_Client;

/**
 * Use different actions for "What's new" modals.
 *
 * @since 2.5.5
 */
class Feature_Modal extends Component {
	use Defender_Dashboard_Client;

	/**
	 * Feature data for the last active "What's new" modal.
	 */
	public const FEATURE_SLUG    = 'wd_show_feature_fake_bots';
	public const FEATURE_VERSION = '5.6.0';

	/**
	 * Get modals that are displayed on the Dashboard page.
	 *
	 * @param  bool $force_hide  The modal is not displayed in every version, so we need a flag that will control the
	 *                       display process.
	 *
	 * @return array
	 * @since 2.7.0 Use one template for Welcome modal and dynamic data.
	 */
	public function get_dashboard_modals( $force_hide = false ): array {
		$is_displayed = $force_hide ? false : $this->display_last_modal( self::FEATURE_SLUG );
		$current_user = wp_get_current_user();

		return array(
			'show_welcome_modal' => $is_displayed,
			'welcome_modal'      => array(
				'user_name' => esc_html( $current_user->display_name ),
				'banner_1x' => defender_asset_url( '/assets/img/modal/welcome-modal.png' ),
				'banner_2x' => defender_asset_url( '/assets/img/modal/welcome-modal@2x.png' ),
			),
		);
	}

	/**
	 * Display modal if:
	 * plugin version has important changes,
	 * plugin settings have been reset before -> this is not fresh install,
	 * Whitelabel > Documentation, Tutorials and What’s New Modal > checked Show tab OR Whitelabel is disabled.
	 *
	 * @param  string $key  The feature slug to check.
	 *
	 * @return bool
	 */
	protected function display_last_modal( $key ): bool {
		$info = defender_white_label_status();

		return (bool) get_site_option( $key ) && ! $info['hide_doc_link'];
	}

	/**
	 * Upgrades site options related to feature modals based on the database version.
	 */
	public function upgrade_site_options(): void {
		$db_version    = get_site_option( 'wd_db_version' );
		$feature_slugs = array(
			// Important slugs to display Onboarding, e.g. after the click on Reset settings.
			array(
				'slug' => 'wp_defender_shown_activator',
				'vers' => '2.4.0',
			),
			array(
				'slug' => 'wp_defender_is_free_activated',
				'vers' => '2.4.0',
			),
			// The latest feature.
			array(
				'slug' => 'wd_show_feature_abandoned_plugin',
				'vers' => '5.5.0',
			),
			// The current feature.
			array(
				'slug' => self::FEATURE_SLUG,
				'vers' => self::FEATURE_VERSION,
			),
		);
		foreach ( $feature_slugs as $feature ) {
			if ( version_compare( $db_version, $feature['vers'], '==' ) ) {
				// The current feature.
				update_site_option( $feature['slug'], true );
			} else {
				// Old one.
				delete_site_option( $feature['slug'] );
			}
		}
	}

	/**
	 * Get additional text.
	 *
	 * @return string
	 */
	private function additional_text(): string {
		return '';
	}

	/**
	 * Delete welcome modal key.
	 *
	 * @return void
	 */
	public static function delete_modal_key(): void {
		delete_site_option( self::FEATURE_SLUG );
	}
}