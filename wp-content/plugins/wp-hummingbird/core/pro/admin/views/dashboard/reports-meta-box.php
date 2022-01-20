<?php
/**
 * Reports meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var bool   $database_reports           Database reports status.
 * @var string $database_reports_next      Next scheduled database report.
 * @var string $notifications_url          Notifications module link.
 * @var bool   $performance_reports        Performance reports status.
 * @var string $performance_reports_next   Next scheduled performance report.
 * @var bool   $uptime_enabled             Uptime module status.
 * @var bool   $uptime_notifications       Uptime notifications status.
 * @var bool   $uptime_reports             Uptime reports status.
 * @var string $uptime_notifications_next  Next scheduled uptime notification.
 * @var string $uptime_reports_next        Next scheduled uptime report.
 * @var string $uptime_url                 Uptime module URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php esc_html_e( 'Automate your workflow with daily, weekly or monthly reports and notifications sent directly to your inbox.', 'wphb' ); ?>
</p>

<table class="sui-table sui-flushed">
	<thead>
	<tr>
		<th><?php esc_html_e( 'Notifications', 'wphb' ); ?></th>
		<th width="20%"><?php esc_html_e( 'Status', 'wphb' ); ?></th>
		<th>&nbsp;</th>
	</tr>
	</thead>
	<tbody>
		<tr>
			<td>
				<strong><?php esc_html_e( 'Performance Test Reporting', 'wphb' ); ?></strong>
			</td>
			<td>
				<a href="<?php echo esc_url( $notifications_url ); ?>#performance-reports">
					<?php if ( $performance_reports ) : ?>
						<span class="sui-tag sui-tag-sm sui-tag-blue"><?php esc_html_e( 'Enabled', 'wphb' ); ?></span>
					<?php else : ?>
						<span class="sui-tag sui-tag-sm"><?php esc_html_e( 'Disabled', 'wphb' ); ?></span>
					<?php endif; ?>
				</a>
			</td>
			<td>
				<?php if ( $performance_reports ) : ?>
					<?php echo esc_html( $performance_reports_next ); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( $notifications_url ); ?>#performance-reports" role="button" class="sui-button-icon sui-button-blue sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_attr_e( 'Enable notification', 'wphb' ); ?>">
						<span class="sui-icon-plus" aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Enable notification', 'wphb' ); ?>></span>
					</a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				<strong><?php esc_html_e( 'Uptime Notification', 'wphb' ); ?></strong>
			</td>
			<td>
				<a href="<?php echo esc_url( $notifications_url ); ?>#uptime-notifications">
					<?php if ( ! $uptime_enabled ) : ?>
						<span class="sui-tag sui-tag-sm sui-tag-disabled"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
					<?php elseif ( $uptime_notifications ) : ?>
						<span class="sui-tag sui-tag-sm sui-tag-blue"><?php esc_html_e( 'Enabled', 'wphb' ); ?></span>
					<?php else : ?>
						<span class="sui-tag sui-tag-sm"><?php esc_html_e( 'Disabled', 'wphb' ); ?></span>
					<?php endif; ?>
				</a>
			</td>
			<td>
				<?php if ( ! $uptime_enabled ) : ?>
					<a href="<?php echo esc_url( $uptime_url ); ?>" role="button" class="sui-button-icon sui-button-blue sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_attr_e( 'Activate feature', 'wphb' ); ?>">
						<span class="sui-icon-plus" aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Activate feature', 'wphb' ); ?>></span>
					</a>
				<?php elseif ( $uptime_notifications ) : ?>
					<?php echo esc_html( $uptime_notifications_next ); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( $notifications_url ); ?>#uptime-notifications" role="button" class="sui-button-icon sui-button-blue sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_attr_e( 'Enable notification', 'wphb' ); ?>">
						<span class="sui-icon-plus" aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Enable notification', 'wphb' ); ?>></span>
					</a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				<strong><?php esc_html_e( 'Uptime Reporting', 'wphb' ); ?></strong>
			</td>
			<td>
				<a href="<?php echo esc_url( $notifications_url ); ?>#uptime-reports">
					<?php if ( ! $uptime_enabled ) : ?>
						<span class="sui-tag sui-tag-sm sui-tag-disabled"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
					<?php elseif ( $uptime_reports ) : ?>
						<span class="sui-tag sui-tag-sm sui-tag-blue"><?php esc_html_e( 'Enabled', 'wphb' ); ?></span>
					<?php else : ?>
						<span class="sui-tag sui-tag-sm"><?php esc_html_e( 'Disabled', 'wphb' ); ?></span>
					<?php endif; ?>
				</a>
			</td>
			<td>
				<?php if ( ! $uptime_enabled ) : ?>
					<a href="<?php echo esc_url( $uptime_url ); ?>" role="button" class="sui-button-icon sui-button-blue sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_attr_e( 'Activate feature', 'wphb' ); ?>">
						<span class="sui-icon-plus" aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Activate feature', 'wphb' ); ?>></span>
					</a>
				<?php elseif ( $uptime_reports ) : ?>
					<?php echo esc_html( $uptime_reports_next ); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( $notifications_url ); ?>#uptime-reports" role="button" class="sui-button-icon sui-button-blue sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_attr_e( 'Enable notification', 'wphb' ); ?>">
						<span class="sui-icon-plus" aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Enable notification', 'wphb' ); ?>></span>
					</a>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				<strong><?php esc_html_e( 'Database Cleanup Notification', 'wphb' ); ?></strong>
			</td>
			<td>
				<a href="<?php echo esc_url( $notifications_url ); ?>#database-reports">
					<?php if ( $database_reports ) : ?>
						<span class="sui-tag sui-tag-sm sui-tag-blue"><?php esc_html_e( 'Enabled', 'wphb' ); ?></span>
					<?php else : ?>
						<span class="sui-tag sui-tag-sm"><?php esc_html_e( 'Disabled', 'wphb' ); ?></span>
					<?php endif; ?>
				</a>
			</td>
			<td>
				<?php if ( $database_reports ) : ?>
					<?php echo esc_html( $database_reports_next ); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( $notifications_url ); ?>#database-reports" role="button" class="sui-button-icon sui-button-blue sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_attr_e( 'Enable notification', 'wphb' ); ?>">
						<span class="sui-icon-plus" aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_html_e( 'Enable notification', 'wphb' ); ?>></span>
					</a>
				<?php endif; ?>
			</td>
		</tr>
	</tbody>
</table>

<p class="sui-description" style="text-align: center">
	<?php
	printf(
		/* translators: %1$s: opening a tag, %2$s: closing a tag */
		esc_html__( 'You can also set scheduled pdf reports for your clients via %1$sThe Hub%2$s.', 'wphb' ),
		'<a href="https://wpmudev.com/hub2/" target="_blank">',
		'</a>'
	);
	?>
</p>