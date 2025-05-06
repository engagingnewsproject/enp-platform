<?php
/**
 * Malware scan upsell.
 *
 * @package WP_Defender\Traits
 * @since 4.7.2
 */

namespace WP_Defender\Traits;

trait Scan_Upsell {
	/**
	 * Get scan upsell details.
	 *
	 * @param string $location The location of the upsell.
	 *
	 * @return array
	 */
	public function get_scan_upsell( string $location ): array {
		/* translators: 1: Opening anchor tag, 2: Closing anchor tag */
		$anc_link = __( 'Let our experts meticulously clean your sites, remove malware and set up optimal security configurations for ongoing protection. %1$sGet expert services%2$s.', 'wpdef' );

		switch ( $location ) {
			case 'dashboard':
				return array(
					'url'         => 'https://wpmudev.com/hub2/services/?utm_source=defender-pro&utm_medium=plugin&utm_campaign=defender_services_dash_upsell',
					'description' => $anc_link,
				);
			case 'scan':
				return array(
					'url'         => 'https://wpmudev.com/hub2/services/?utm_source=defender-pro&utm_medium=plugin&utm_campaign=defender_services_mwscanning_upsell',
					'description' => $anc_link,
				);
			case 'email':
				return array(
					'url'         => 'https://wpmudev.com/hub2/services/?utm_source=defender-pro&utm_medium=email&utm_campaign=defender_services_email_upsell',
					'description' => $anc_link,
				);
			default:
				return array(
					'url'         => '',
					'description' => '',
				);
		}
	}
}