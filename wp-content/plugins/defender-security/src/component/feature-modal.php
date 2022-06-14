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
	const FEATURE_SLUG    = 'wd_show_feature_auth_methods';
	const FEATURE_VERSION = '2.8.0';

	/**
	 * Get modals that are displayed on the Dashboard page.
	 *
	 * @return array
	 * @since 2.7.0 Use one template for Welcome modal and dynamic data.
	 */
	public function get_dashboard_modals() {
		$title = sprintf(
		/* translators: %s: count */
			__( 'New: Authentication Method and Updated %s Lost Phone Settings!', 'wpdef' ),
			'<br/>'
		);
		$desc  = __( 'Several new features and improvements are included in 2.8.0, including a new two-factor authentication method using backup codes, and updated Lost Phone settings.', 'wpdef' );

		return array(
			'show_welcome_modal' => $this->display_last_modal( self::FEATURE_SLUG ),
			'welcome_modal'      => array(
				'title'        => $title,
				'desc'         => $desc,
				'banner_1x'    => defender_asset_url( '/assets/img/modal/welcome-modal.png' ),
				'banner_2x'    => defender_asset_url( '/assets/img/modal/welcome-modal@2x.png' ),
				'banner_alt'   => __( 'Modal for authentication methods', 'wpdef' ),
				'button_title' => __( 'Got it', 'wpdef' ),
				// Additional information.
				'additional_text' => $this->additional_text(),
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
				'slug' => 'wd_show_feature_scheduled_scanning',
				'vers' => '2.7.0',
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

	/**
	 * Get additional text.
	 *
	 * @return string
	 */
	private function additional_text() {
		$text = '<ul class="list-disc list-inside m-0">';
		$text .= '<li class="mb-30px relative">';
		$text .= '<strong class="text-base text-gray-500 absolute left-10px">';
		$text .= __( 'Backup codes authentication', 'wpdef' );
		$text .= '</strong>';
		$text .= '<span class="sui-description mt-0">';
		$text .= __( 'If you lose your phone or otherwise can’t sign in via two-factor authentication app, you can use generated backup codes to sign in to your site.', 'wpdef' );
		$text .= '</span>';
		$text .= '</li>';
		$text .= '<li class="sui-no-margin-bottom relative">';
		$text .= '<strong class="text-base text-gray-500 absolute left-10px">';
		$text .= __( 'Lost Phone feature location change', 'wpdef' );
		$text .= '</strong>';
		$text .= '<span class="sui-description mt-0">';
		$text .= sprintf(
		/* translators: %s: count */
			__( 'The Lost Phone functionality has been moved from the 2FA Plugin page to the Two-Factor Authentication section of the <a href="%s">User > Profile</a> page as a third AUTH method.', 'wpdef' ),
			network_admin_url( 'profile.php' )
		);
		$text .= '</span>';
		$text .= '</li>';
		$text .= '</ul>';

		return $text;
	}
}
