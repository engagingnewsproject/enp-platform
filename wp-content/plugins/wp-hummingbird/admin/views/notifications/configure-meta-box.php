<?php
/**
 * Notifications configure meta box.
 *
 * @since 3.1.1
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box-body">
	<p><?php esc_html_e( 'Activate and schedule notifications and reports in one place. Automate your workflow with daily, weekly or monthly reports sent directly to your inbox.', 'wphb' ); ?></p>
</div>

<div class="sui-box-settings-row sui-disabled">
	<table class="sui-table sui-table-flushed">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Notifications', 'wphb' ); ?></th>
			<th class="sui-hidden-xs"><?php esc_html_e( 'Type', 'wphb' ); ?></th>
			<th class="sui-hidden-xs"><?php esc_html_e( 'Status', 'wphb' ); ?></th>
			<th class="sui-hidden-xs"><?php esc_html_e( 'Recipients', 'wphb' ); ?></th>
			<th><?php esc_html_e( 'Schedule', 'wphb' ); ?></th>
		</tr>
		</thead>

		<tbody>
		<tr>
			<td class="sui-table-item-title">
				<span class="sui-icon-calendar sui-hidden-xs" aria-hidden="true"></span>
				<?php esc_html_e( 'Performance Test', 'wphb' ); ?>
			</td>
			<td class="sui-hidden-xs"><?php esc_html_e( 'Reporting', 'wphb' ); ?></td>
			<td class="sui-hidden-xs"><span class="sui-tag sui-tag-purple sui-tag-sm"><?php esc_html_e( 'Pro', 'wphb' ); ?></span></td>
			<td colspan="2"><?php esc_html_e( 'Schedule performance tests and receive customized results by email.', 'wphb' ); ?></td>
		</tr>
		<tr>
			<td class="sui-table-item-title">
				<span class="sui-icon-mail sui-hidden-xs" aria-hidden="true"></span>
				<?php esc_html_e( 'Uptime', 'wphb' ); ?>
			</td>
			<td class="sui-hidden-xs"><?php esc_html_e( 'Notification', 'wphb' ); ?></td>
			<td class="sui-hidden-xs"><span class="sui-tag sui-tag-purple sui-tag-sm"><?php esc_html_e( 'Pro', 'wphb' ); ?></span></td>
			<td colspan="2"><?php esc_html_e( 'Receive an email when this website is unavailable.', 'wphb' ); ?></td>
		</tr>
		<tr>
			<td class="sui-table-item-title">
				<span class="sui-icon-calendar sui-hidden-xs" aria-hidden="true"></span>
				<?php esc_html_e( 'Uptime', 'wphb' ); ?>
			</td>
			<td class="sui-hidden-xs"><?php esc_html_e( 'Reporting', 'wphb' ); ?></td>
			<td class="sui-hidden-xs"><span class="sui-tag sui-tag-purple sui-tag-sm"><?php esc_html_e( 'Pro', 'wphb' ); ?></span></td>
			<td colspan="2"><?php esc_html_e( 'Schedule uptime reports and receive results by email.', 'wphb' ); ?></td>
		</tr>
		</tbody>
	</table>
</div>

<div class="sui-box-settings-row sui-upsell-row">
	<img class="sui-image sui-upsell-image" alt="<?php esc_attr_e( 'Scheduled notifications', 'wphb' ); ?>"
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-minify-summary.png' ); ?>"
		srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-minify-summary.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/graphic-hb-minify-summary@2x.png' ); ?> 2x">


	<div class="sui-upsell-notice">
		<p>
			<?php esc_html_e( 'Stay on top of potential performance issues with scheduled automatic reports, sent directly to your inbox. Get reports as part of a WPMU DEV membership. Try it out for free.', 'wphb' ); ?>
			<br/>
			<a href="<?php echo esc_url( Utils::get_link( 'plugin', 'notifications' ) ); ?>" class="sui-button sui-button-purple" style="margin-top: 10px" target="_blank">
				<?php esc_html_e( 'Upgrade to Pro', 'wphb' ); ?>
			</a>
		</p>
	</div>
</div>