<?php
/**
 * Reports no membership meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $title  Reports module title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box-settings-row sui-no-padding-bottom">
	<p><?php esc_html_e( 'Automate your workflow with daily, weekly or monthly reports sent directly to your inbox.', 'wphb' ); ?></p>
</div>

<div class="sui-box-settings-row sui-no-padding-bottom sui-no-padding-top sui-no-margin-bottom">
	<table class="sui-table sui-flushed">
		<tbody>
		<tr>
			<td>
				<span class="sui-icon-hummingbird" aria-hidden="true"></span>
				<strong><?php esc_html_e( 'Performance Test', 'wphb' ); ?></strong>
			</td>
			<td>
				<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
			</td>
		</tr>
		<tr>
			<td>
				<span class="sui-icon-user-reputation-points" aria-hidden="true"></span>
				<strong><?php esc_html_e( 'Database Cleanup', 'wphb' ); ?></strong>
			</td>
			<td>
				<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
			</td>
		</tr>
		<tr>
			<td>
				<span class="sui-icon-uptime" aria-hidden="true"></span>
				<strong><?php esc_html_e( 'Uptime', 'wphb' ); ?></strong>
			</td>
			<td>
				<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
			</td>
		</tr>
		</tbody>
	</table>
</div>

<div class="sui-box-settings-row sui-upsell-row sui-padding-top">
	<img class="sui-image sui-upsell-image"
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-upsell-reports.png' ); ?>"
		srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-upsell-reports@2x.png' ); ?> 2x"
		alt="<?php esc_attr_e( 'Scheduled automated reports', 'wphb' ); ?>">

	<div class="sui-upsell-notice">
		<p>
			<?php
			printf(
				/* translators: %1$s: WPMUDEV url, %2$s: </a> */
				esc_html__( 'Schedule automatic reports and get them emailed direct to your inbox to stay on top of potential performance issues. Get Reports as part of a WPMU DEV membership. %1$sTry it out for free.%2$s', 'wphb' ),
				'<br><a href="' . esc_url( \Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_dash_reports_upsell_link' ) ) . '" target="_blank">',
				'</a>'
			);
			?>
		</p>
	</div>
</div>
