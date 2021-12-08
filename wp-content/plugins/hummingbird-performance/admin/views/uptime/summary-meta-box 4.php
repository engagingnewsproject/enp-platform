<?php
/**
 * Uptime meta box.
 *
 * @package Hummingbird
 *
 * @var object $uptime_stats       Last stats report.
 * @var string $data_range_text    Human readable data range text.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branded_image = apply_filters( 'wpmudev_branding_hero_image', '' );
?>

<?php if ( $branded_image ) : ?>
	<div class="sui-summary-image-space" aria-hidden="true" style="background-image: url('<?php echo esc_url( $branded_image ); ?>')"></div>
<?php else : ?>
	<div class="sui-summary-image-space" aria-hidden="true"></div>
<?php endif; ?>
<div class="sui-summary-segment">
	<div class="sui-summary-details">
		<span class="sui-summary-large">
			<?php
			if ( $uptime_stats && ! is_wp_error( $uptime_stats ) ) :
				if ( 0 === round( (int) $uptime_stats->availability, 1 ) || null === $uptime_stats->response_time ) :
					echo esc_html( '100%' );
				else :
					echo esc_html( round( (int) $uptime_stats->availability, 1 ) ) . '%';
				endif;
			endif;
			?>
		</span>
		<span class="sui-summary-sub"><?php echo esc_html__( 'Website availability in the last ', 'wphb' ) . esc_html( $data_range_text ); ?></span>
		<span class="sui-summary-detail">
			<?php
			if ( $uptime_stats && ! is_wp_error( $uptime_stats ) ) :
				echo $uptime_stats->response_time ? esc_html( $uptime_stats->response_time ) : esc_html( 'Waiting on data...' );
			endif;
			?>
		</span>
		<span class="sui-summary-sub"><?php esc_html_e( 'Average server response time during the reporting period', 'wphb' ); ?></span>
	</div>
</div>
<div class="sui-summary-segment">
	<ul class="sui-list">
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Outages', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<?php if ( is_object( $uptime_stats ) && (int) $uptime_stats->outages > 0 ) : ?>
					<?php echo (int) $uptime_stats->outages; ?>
				<?php else : ?>
					<?php esc_html_e( 'None', 'wphb' ); ?>
				<?php endif; ?>
			</span>
		</li>
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Downtime', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<?php if ( isset( $uptime_stats->period_downtime ) ) : ?>
					<?php echo esc_html( $uptime_stats->period_downtime ); ?>
				<?php else : ?>
					<?php esc_html_e( 'None', 'wphb' ); ?>
				<?php endif; ?>
			</span>
		</li>
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Up Since', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<?php
				$site_date = '';
				if ( is_object( $uptime_stats ) && $uptime_stats->up_since ) {
					$gmt_date  = date( 'Y-m-d H:i:s', $uptime_stats->up_since );
					$site_date = get_date_from_gmt( $gmt_date, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
				}
				if ( empty( $site_date ) ) {
					esc_html_e( 'Website is reported down', 'wphb' );
				} else {
					echo esc_html( $site_date );
				}
				?>
			</span>
		</li>
	</ul>
</div>
