<?php
/**
 * Uptime meta box.
 *
 * @package Hummingbird
 *
 * @var object    $uptime_stats    Last stats report.
 * @var string    $error           Error message.
 * @var string    $retry_url       Run uptime URL.
 * @var string    $support_url     Support URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( $error && ( ! strpos( $error, 'down for maintenance' ) ) ) : ?>
	<?php
	$this->admin_notices->show_inline(
		$error,
		'error',
		sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
			esc_html__( '%1$sTry again%2$s', 'wphb' ),
			'<a href="' . esc_url( $retry_url ) . '" class="sui-button sui-button-blue">',
			'</a>'
		) . sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
			esc_html__( '%1$sSupport%2$s', 'wphb' ),
			'<a href="' . esc_url( $support_url ) . '" target="_blank" class="sui-button sui-button-blue">',
			'</a>'
		)
	);
	?>
<?php elseif ( strpos( $error, 'down for maintenance' ) ) : ?>
	<div class="sui-block-content-center">
		<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
			<img class="sui-image sui-image-center"
				 src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-uptime-disabled@1x.png' ); ?>"
				 srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-uptime-disabled@2x.png' ); ?> 2x">
		<?php endif; ?>

		<p>
			<?php
			_e(
				'Uptime monitors your server response time and lets you know when your website is down<br>
			or too slow for your visitors. This service is currently under maintenance as we build a<br>
			brand new monitoring service. Check back soon!',
				'wphb'
			);
			?>
		</p>
	</div>
<?php else : ?>
	<p>
		<?php
		esc_html_e(
			'Server response time is the amount of time it takes for a web server to
		respond to a request from a browser. The longer it takes, the longer your visitors wait for the page
		to start loading.',
			'wphb'
		);
		?>
	</p>
	<?php if ( null === $uptime_stats->response_time && ! is_wp_error( $uptime_stats ) ) : ?>
		<?php $this->admin_notices->show_inline( esc_html__( 'We don’t have any data feeding in yet. It can take an hour or two for this graph to populate with data so feel free to check back soon!', 'wphb' ), 'info' ); ?>
	<?php endif; ?>

	<input type="hidden" id="uptime-chart-json" value="<?php echo esc_attr( $uptime_stats->chart_json ); ?>">
	<div class="uptime-chart wphb-uptime-graph" id="uptime-chart">
		<span class="loader i-wpmu-dev-loader"></span>
	</div>

	<input type="hidden" id="downtime-chart-json" value="<?php echo esc_attr( $downtime_chart_json ); ?>">
	<div class="downtime-chart" id="downtime-chart">
		<span class="loader i-wpmu-dev-loader"></span>
	</div>

	<div class="downtime-chart-key">
		<span class="response-time-key"><?php esc_html_e( 'Response Time', 'wphb' ); ?></span>
		<span class="uptime-key"><?php esc_html_e( 'Uptime', 'wphb' ); ?></span>
		<span class="downtime-key"><?php esc_html_e( 'Downtime', 'wphb' ); ?></span>
		<span class="unknown-key"><?php esc_html_e( 'Unknown', 'wphb' ); ?></span>
	</div>
<?php endif; ?>
