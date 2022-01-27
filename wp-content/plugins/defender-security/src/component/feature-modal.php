<?php
namespace WP_Defender\Component;

use WP_Defender\Component;

/**
 * Use different actions for "What's new" modals.
 *
 * Class Feature_Modal
 * @package WP_Defender\Component
 * @since 2.5.5
 */
class Feature_Modal extends Component {
	/**
	 * Feature data for the last active "What's new" modal.
	*/
	const FEATURE_SLUG    = 'wd_show_feature_scheduled_scanning';
	const FEATURE_VERSION = '2.7.0';

	/**
	 * Get modals that are displayed on the Dashboard page.
	 *
	 * @return array
	 * @since 2.7.0 Use one template for Welcome modal and dynamic data.
	 */
	public function get_dashboard_modals() {
		$desc = __( 'You can now schedule malware scans without email notifications, automatically running regular scans on a daily, weekly, or monthly basis.', 'wpdef' );
		$desc .= '<br/>' . __( "You'll notice this change in the Malware Scanning settings.", 'wpdef' );

		return array(
			'show_welcome_modal' => $this->display_last_modal( self::FEATURE_SLUG ),
			'welcome_modal'      => array(
				'title'        => __( 'Update: Scheduled Scanning!', 'wpdef' ),
				'desc'         => $desc,
				'banner_1x'    => defender_asset_url( '/assets/img/modal/welcome-modal.png' ),
				'banner_2x'    => defender_asset_url( '/assets/img/modal/welcome-modal@2x.png' ),
				'banner_alt'   => __( 'Modal for plugin vulnerability', 'wpdef' ),
				'button_title' => __( 'Got it', 'wpdef' ),
			),
		);
	}

	/**
	 * Display modal with the latest changes if:
	 * plugin settings have been reset before -> this is not fresh install,
	 * Whitelabel > Documentation, Tutorials and What’s New Modal > checked Show tab OR Whitelabel is disabled.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	protected function display_last_modal( $key ) {
		$info = defender_white_label_status();

		return (bool) get_site_option( 'wd_nofresh_install' )
			&& (bool) get_site_option( $key )
			&& ! $info['hide_doc_link'];
	}

	public function upgrade_site_options() {
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
				'slug' => 'wd_show_feature_plugin_vulnerability',
				'vers' => '2.6.2',
			),
			// The current feature.
			array(
				'slug' => self::FEATURE_SLUG,
				'vers' => self::FEATURE_VERSION,
			),
		);
		foreach ( $feature_slugs as $feature ) {
			if ( version_compare( $db_version, $feature['vers'], '==' ) ) {
				// The current feature
				update_site_option( $feature['slug'], true );
			} else {
				// and old one.
				delete_site_option( $feature['slug'] );
			}
		}
	}
}
