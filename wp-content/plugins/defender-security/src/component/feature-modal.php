<?php
declare( strict_types=1 );

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
	public const FEATURE_SLUG = 'wd_show_feature_yubico';
	public const FEATURE_VERSION = '3.1.0';

	/**
	 * Get modals that are displayed on the Dashboard page.
	 *
	 * @return array
	 * @since 2.7.0 Use one template for Welcome modal and dynamic data.
	 */
	public function get_dashboard_modals(): array {
		$title = sprintf(
		/* translators: %s: separator */
			__( 'New: Two-factor authentication using Yubico %s hardware keys!', 'wpdef' ),
			'<br/>'
		);
		$desc = __( "The usage of FIDO2 WebAuthn protocols in Defender 3.1.0 enables you to use Yubico's hardware keys, also known as YubiKeys, as a two-factor authentication method. This definitely hardens the security of your website as it requires a hardware (physical) key to authenticate user logins", 'wpdef' );

		return [
			'show_welcome_modal' => $this->display_last_modal( self::FEATURE_SLUG ),
			'welcome_modal' => [
				'title' => $title,
				'desc' => $desc,
				'banner_1x' => defender_asset_url( '/assets/img/modal/welcome-modal.png' ),
				'banner_2x' => defender_asset_url( '/assets/img/modal/welcome-modal@2x.png' ),
				'banner_alt' => __( 'Modal for Yubico feature', 'wpdef' ),
				'button_title' => __( 'Got it', 'wpdef' ),
				// Additional information.
				'additional_text' => $this->additional_text(),
			],
		];
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
	protected function display_last_modal( $key ): bool {
		$info = defender_white_label_status();

		return (bool) get_site_option( 'wd_nofresh_install' )
			&& (bool) get_site_option( $key )
			&& ! $info['hide_doc_link'];
	}

	public function upgrade_site_options() {
		$db_version = get_site_option( 'wd_db_version' );
		$feature_slugs = [
			// Important slugs to display Onboarding, e.g. after the click on Reset settings.
			[
				'slug' => 'wp_defender_shown_activator',
				'vers' => '2.4.0',
			],
			[
				'slug' => 'wp_defender_is_free_activated',
				'vers' => '2.4.0',
			],
			// The latest feature.
			[
				'slug' => 'wd_show_feature_biometric_login',
				'vers' => '3.0.0',
			],
			// The current feature.
			[
				'slug' => self::FEATURE_SLUG,
				'vers' => self::FEATURE_VERSION,
			],
		];
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
	private function additional_text(): string {
		$text = '<ul class="list-disc list-inside m-0">';
		$text .= '<li class="sui-no-margin-bottom relative">';
		$text .= '<strong class="text-base text-gray-500 absolute left-10px">';
		$text .= __( 'Hardware Authentication', 'wpdef' );
		$text .= '</strong>';
		$text .= '<span class="sui-description mt-0">';
		$text .= __( 'The <strong>YubiKey</strong> is a physical authentication device manufactured by Yubico to protect', 'wpdef' );
		$text .= __( ' access to computers, networks, and online services using <strong>Universal 2nd Factor</strong> and', 'wpdef' );
		$text .= __( ' <strong>FIDO2</strong> protocols developed by the <strong>FIDO Alliance</strong>.', 'wpdef' );
		$text .= __( ' By enabling our new Hardware Authentication method, your users can quickly and', 'wpdef' );
		$text .= __( ' easily verify their identity during the login process by simply inserting their YubiKey into their device and tapping it.', 'wpdef' );
		$text .= '</span>';
		$text .= '</li>';
		$text .= '</ul>';

		return $text;
	}
}
