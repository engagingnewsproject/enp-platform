<?php
/**
 * Uptime downtime meta box.
 *
 * @package Hummingbird
 *
 * @var \Hummingbird\Admin\Page $this
 *
 * @var object $uptime_stats           Last stats report.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php esc_html_e( 'Here’s a snapshot of when your site went down, which means visitors couldn’t view your website.', 'wphb' ); ?>
</p>

<input type="hidden" id="downtime-chart-json" value="<?php echo esc_attr( $downtime_chart_json ); ?>">
<div class="downtime-chart" id="downtime-chart">
	<span class="loader i-wpmu-dev-loader"></span>
</div>

<div class="downtime-chart-key">
	<span class="uptime-key"><?php esc_html_e( 'Uptime', 'wphb' ); ?></span>
	<span class="downtime-key"><?php esc_html_e( 'Downtime', 'wphb' ); ?></span>
	<span class="unknown-key"><?php esc_html_e( 'Unknown', 'wphb' ); ?></span>
</div>

<?php
$this->admin_notices->show_inline_dismissible(
	'uptime-info',
	esc_html__( 'Uptime monitor will report your site as down when it takes 30+ seconds to load your homepage. Your host may report your site as online, but as far as user experience goes, slow page speeds are bad practice. Consider upgrading your hosting if your site is regularly down.', 'wphb' ),
	'sui-notice-grey sui-margin-top'
);
?>

<strong><?php esc_html_e( 'Logs', 'wphb' ); ?></strong>

<ul class="dev-list-stats dev-list-stats-standalone">
	<?php if ( ! count( $uptime_stats->events ) ) : ?>
		<?php $this->admin_notices->show_inline( esc_html__( 'No downtime has been reported during the reporting period.', 'wphb' ), 'grey' ); ?>
	<?php else : ?>
		<?php foreach ( $uptime_stats->events as $event ) : ?>
			<li class="dev-list-stats-item">
				<div>
					<span class="list-label list-label-stats">
					<?php if ( ! empty( $event->down ) && ! empty( $event->up ) ) : ?>
						<?php $down = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $event->down ) ) ); ?>
						<?php $up = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $event->up ) ) ); ?>
						<div class="wphb-pills-group">
							<span class="wphb-pills red sui-tooltip" data-tooltip="<?php echo esc_attr( $event->details ); ?>"><span class="sui-icon-chevron-down"></span> <?php echo esc_html( date_i18n( 'M j @ g:ia', $down ) ); ?></span>
							<?php
							if ( $event->downtime ) :
								echo '<span class="list-detail-stats">' . esc_html( $event->downtime ) . '</span>';
							endif;
							?>
							<img class="wphb-image-pills-divider"
								src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/downtime-splice.svg' ); ?>"
								alt="<?php esc_attr_e( 'Spacer image', 'wphb' ); ?>">
							<span class="wphb-pills green"><span class="sui-icon-chevron-up"></span> <?php echo esc_html( date_i18n( 'M j @ g:ia', $up ) ); ?></span>
						</div>
					<?php endif; ?>
					</span>
				</div>
			</li><!-- end dev-list-stats-item -->
		<?php endforeach; ?>
	<?php endif; ?>
</ul>
