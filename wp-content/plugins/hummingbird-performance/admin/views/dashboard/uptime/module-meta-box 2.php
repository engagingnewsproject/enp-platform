<?php
/**
 * Uptime meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var object $uptime_stats  Uptime stats.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p><?php esc_html_e( 'Monitor your website and get notified if/when it’s inaccessible. We’ll also watch your server response time.', 'wphb' ); ?></p>

<?php $this->admin_notices->show_inline( esc_html__( 'Your website is currently up and humming.', 'wphb' ) ); ?>

<ul class="sui-list sui-margin-top sui-no-margin-bottom">
	<li>
		<span class="sui-list-label"><?php esc_html_e( 'Availability', 'wphb' ); ?></span>
		<span class="sui-list-detail">
			<?php echo isset( $uptime_stats->availability ) ? esc_html( $uptime_stats->availability ) : esc_html__( 'Waiting for data...', 'wphb' ); ?>
		</span>
	</li>
	<li>
		<span class="sui-list-label"><?php esc_html_e( 'Downtime', 'wphb' ); ?></span>
		<span class="sui-list-detail">
			<?php echo isset( $uptime_stats->period_downtime ) ? esc_html( $uptime_stats->period_downtime ) : '-'; ?>
		</span>
	</li>
	<li>
		<span class="sui-list-label"><?php esc_html_e( 'Average response time', 'wphb' ); ?></span>
		<span class="sui-list-detail">
			<?php echo isset( $uptime_stats->response_time ) ? esc_html( $uptime_stats->response_time ) : esc_html__( 'Calculating...', 'wphb' ); ?>
		</span>
	</li>
	<li>
		<span class="sui-list-label"><?php esc_html_e( 'Last down', 'wphb' ); ?></span>
		<span class="sui-list-detail">
			<?php
			if ( isset( $uptime_stats->up_since ) ) {
				$gmt_date  = date( 'Y-m-d H:i:s', $uptime_stats->up_since );
				$site_date = get_date_from_gmt( $gmt_date, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
				echo esc_html( $site_date );
			} else {
				echo '-';
			}
			?>
		</span>
	</li>
</ul>
