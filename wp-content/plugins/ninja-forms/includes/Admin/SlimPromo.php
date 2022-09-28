<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * NF_Slim_Promo Class
 *
 * @since 3.6
 */

class NF_Admin_SlimPromo
{
	/**
	 *
	 */
	public function __construct()
	{
		if ( ! $this->isNFAdminPage() ) {
			return;
		}

		if ( array_key_exists( 'nf-dismiss-promo-notice', $_REQUEST ) ) {
			$this->setTransient();
		}
	}

	public function isNFAdminPage()
	{
		if ( ! is_admin() ) {
			return false;
		}

		if ( ! \array_key_exists( 'page', $_REQUEST ) ) {
			return false;
		}

		if (
			strpos( 'nf', $_REQUEST[ 'page' ] ) === false &&
			strpos( 'ninja-forms', $_REQUEST[ 'page' ] ) === false
		) {
			return false;
		}

		return true;
	}

	/**
	 * Check if we should show the slim promo
	 *
	 * @return bool
	 */
	public function maybeShowSlimPromo()
	{
		if ( apply_filters( 'ninja_forms_disable_marketing', false ) ) {
			return false;
		}

		$nf_settings = get_option( 'ninja_forms_settings' );

		if (
			isset( $nf_settings[ 'disable_admin_notices' ] ) &&
			$nf_settings[ 'disable_admin_notices' ] == 1
		) {
			return false;
		}

		if ( get_transient('ninja_forms_disable_slim_promo') ) {
			return false;
		}

		return true;
	}

	/**
	 * Set the ninja_forms_disable_slim_promo transient
	 *
	 * @return Void
	 */
	public function setTransient()
	{
		set_transient('ninja_forms_disable_slim_promo', 1, DAY_IN_SECONDS * 90);
	}

	/**
	 * Echo the html for the notice
	 *
	 * @return Void
	 */
	public function getNoticeHtml()
	{
		echo '<div class="nf-notice-promo">
			<div class="wrap">
				<strong>
					<a href="https://ninjaforms.com/pricing/?utm_source=Ninja+Forms+Plugin&utm_medium=WP+Dashboard&utm_campaign=Membership+Banner" target="_blank">
						' . esc_html__("Tap into even more features with 50% off any Ninja Forms membership!", "ninja-forms") . '
					</a>
				</strong>

				<a href="?page=ninja-forms&nf-dismiss-promo-notice">
					<svg xmlns="http://www.w3.org/2000/svg"  fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
					</svg>

					<span class="sr-only">Dismiss</span>
				</a>
			</div>
		</div>';
	}
}
