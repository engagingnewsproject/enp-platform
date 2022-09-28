<?php
/**
 * Update the admin footer text for
 * Ninja Forms pages
 *
 * @since 3.6.12
 */

class NF_Admin_FooterMessage {
	/**
	 *
	 */
	public function __construct()
	{
		if (
			is_admin() &&
			isset( $_GET[ 'page' ] ) &&
			(
				'ninja-forms' === $_GET[ 'page' ] ||
				'nf-submissions' === $_GET[ 'page' ] ||
				'nf-settings' === $_GET[ 'page' ] ||
				'nf-system-status' === $_GET[ 'page' ] ||
				'nf-import-export' === $_GET[ 'page' ]
			)
		) {
			add_filter( 'admin_footer_text', [ $this, 'nf_admin_footer_text' ], 10 );
		}
	}

	/**
	 * Changes the admin footer text
	 *
	 * Adds Ninja Forms review request.
	 *
	 * @return String
	 */
	public function nf_admin_footer_text()
	{
		$text = sprintf(
			'%s <a href="https://wordpress.org/support/plugin/ninja-forms/reviews/?filter=5#new-post" target="_blank">%s</a>',
			esc_html__( 'Love Ninja Forms?', 'ninja-forms' ),
			esc_html__( 'Please rate us ★★★★★ on WordPress.org!', 'ninja-forms' )
		);

		return '<span id="footer-thankyou">' . $text . '</span>';
	}
}
