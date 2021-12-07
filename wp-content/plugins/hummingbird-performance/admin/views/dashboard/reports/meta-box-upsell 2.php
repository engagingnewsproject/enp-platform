<?php
/**
 * Reports upsell notice.
 *
 * @since 3.1.2
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-upsell-notice sui-padding sui-padding-top--hidden sui-padding-bottom__desktop--hidden">
	<div class="sui-upsell-notice__image" aria-hidden="true">
		<img class="sui-image sui-upsell-image"
			src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-upsell-reports.png' ); ?>"
			srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-upsell-reports@2x.png' ); ?> 2x"
			alt="<?php esc_attr_e( 'Scheduled automated reports', 'wphb' ); ?>">
	</div>

	<div class="sui-upsell-notice__content">
		<div class="sui-notice sui-notice-purple">
			<div class="sui-notice-content">
				<div class="sui-notice-message">
					<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
					<p><?php esc_html_e( 'Schedule automatic reports and get them emailed direct to your inbox to stay on top of potential performance issues. Get Reports as part of a WPMU DEV membership.', 'wphb' ); ?></p>
					<p><a class="sui-button sui-button-purple" target="_blank" href="<?php echo esc_url( Utils::get_link( 'plugin', 'hummingbird_dash_reports_upsell_link' ) ); ?>">
							<?php esc_html_e( 'Try it out for free', 'wphb' ); ?>
						</a></p>
				</div>
			</div>
		</div>
	</div>
</div>
