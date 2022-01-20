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
	 * Get modals that are displayed on Dashboard.
	 *
	 * @return array
	 */
	public function get_dashboard_modals() {

		return array(
			//@since 2.4.0
			'show_new_features'             => get_site_option( 'wd_show_new_feature' ),
			//@since 2.5.0
			'show_feature_password_pwned'   => get_site_option( 'wd_show_feature_password_pwned' ),
			//@since 2.5.2
			'show_feature_password_reset'   => get_site_option( 'wd_show_feature_password_reset' ),
			//@since 2.5.4
			'show_feature_google_recaptcha' => get_site_option( 'wd_show_feature_google_recaptcha' ),
			//@since 2.5.6
			'show_feature_file_extensions'  => get_site_option( 'wd_show_feature_file_extensions' ),
			//@since 2.6.0
			'show_feature_user_agent'       => get_site_option( 'wd_show_feature_user_agent' ),
			//@since 2.6.1
			'show_feature_woo_recaptcha'    => get_site_option( 'wd_show_feature_woo_recaptcha' ),
			//@since 2.6.2
			'show_feature_plugin_vulnerability' => $this->display_last_modal( 'wd_show_feature_plugin_vulnerability' ),
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
			array(
				'slug' => 'wp_defender_shown_activator',
				'vers' => '2.4.0',
			),
			array(
				'slug' => 'wp_defender_is_free_activated',
				'vers' => '2.4.0',
			),
			array(
				'slug' => 'wd_show_feature_password_pwned',
				'vers' => '2.5.0',
			),
			array(
				'slug' => 'wd_show_feature_password_reset',
				'vers' => '2.5.2',
			),
			array(
				'slug' => 'wd_show_feature_google_recaptcha',
				'vers' => '2.5.4',
			),
			array(
				'slug' => 'wd_show_feature_file_extensions',
				'vers' => '2.5.6',
			),
			array(
				'slug' => 'wd_show_feature_user_agent',
				'vers' => '2.6.0',
			),
			array(
				'slug' => 'wd_show_feature_woo_recaptcha',
				'vers' => '2.6.1',
			),
			array(
				'slug' => 'wd_show_feature_plugin_vulnerability',
				'vers' => '2.6.2',
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
