<?php
/**
 * Reports meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var bool   $db_cleanup             Status of database cleanup.
 * @var string $db_frequency           Frequency of database cleanups.
 * @var string $frequency              Frequency of performance reports.
 * @var bool   $performance_is_active  Status of performance reports.
 * @var bool   $uptime                 Status of uptime reports.
 * @var string $uptime_frequency       Uptime report frequency.
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php esc_html_e( 'Automate your workflow with daily, weekly or monthly reports sent directly to your inbox.', 'wphb' ); ?>
</p>

<table class="sui-table sui-flushed">
	<tbody>
		<tr>
			<td>
				<span class="sui-icon-hummingbird" aria-hidden="true"></span>
				<strong><?php esc_html_e( 'Performance Test', 'wphb' ); ?></strong>
			</td>
			<td width="25%">
				<?php if ( ! $performance_is_active ) : ?>
					<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
				<?php else : ?>
					<span class="sui-tag sui-tag-success"><?php echo esc_html( $frequency ); ?></span>
				<?php endif; ?>
			</td>
			<td width="25%">
				<a href="<?php echo Utils::get_admin_menu_url( 'performance' ) . '&view=reports#wphb-box-reporting-summary'; ?>">
					<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
				</a>
			</td>
		</tr>
		<tr>
			<td>
				<span class="sui-icon-user-reputation-points" aria-hidden="true"></span>
				<strong><?php esc_html_e( 'Database Cleanup', 'wphb' ); ?></strong>
			</td>
			<td>
				<?php if ( ! $db_cleanup ) : ?>
					<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
				<?php else : ?>
					<span class="sui-tag sui-tag-success"><?php echo esc_html( $db_frequency ); ?></span>
				<?php endif; ?>
			</td>
			<td>
				<a href="<?php echo Utils::get_admin_menu_url( 'advanced' ) . '&view=db#wphb-box-advanced-db-settings'; ?>">
					<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
				</a>
			</td>
		</tr>
		<tr>
			<td>
				<span class="sui-icon-uptime" aria-hidden="true"></span>
				<strong><?php esc_html_e( 'Uptime', 'wphb' ); ?></strong>
			</td>
			<td>
				<?php if ( ! $uptime ) : ?>
					<span class="sui-tag sui-tag-inactive"><?php esc_html_e( 'Inactive', 'wphb' ); ?></span>
				<?php else : ?>
					<span class="sui-tag sui-tag-success"><?php echo esc_html( $uptime_frequency ); ?></span>
				<?php endif; ?>
			</td>
			<td>
				<a href="<?php echo Utils::get_admin_menu_url( 'uptime' ) . '&view=reports'; ?>">
					<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
				</a>
			</td>
		</tr>
	</tbody>
</table>

<span class="status-text">
	<?php
	printf(
		/* translators: %1$s: opening a tag, %2$s: closing a tag */
		esc_html__( 'You can also set scheduled pdf reports for your clients via %1$sThe Hub%2$s.', 'wphb' ),
		'<a href="https://wpmudev.com/hub2/" target="_blank">',
		'</a>'
	);
	?>
</span>