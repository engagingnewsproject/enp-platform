<?php
/**
 * Analytics widget template.
 *
 * @package templates
 */

$data     = array();
$days_ago = 7; // Show a week of stats here.
if ( WPMUDEV_Dashboard::$api->is_analytics_allowed() ) {
	if ( is_network_admin() || ! is_multisite() ) {
		$data = WPMUDEV_Dashboard::$api->analytics_stats_overall( $days_ago );
	} else {
		$data = WPMUDEV_Dashboard::$api->analytics_stats_overall( $days_ago, get_current_blog_id() );
	}
}

$stats_defaults = array(
	'pageviews'   => array(
		'value'     => 0,
		'direction' => '',
		'change'    => '0',
	),
	'visits'      => array(
		'value'     => 0,
		'direction' => '',
		'change'    => '0',
	),
	'visit_time'  => array(
		'value'     => 0,
		'direction' => '',
		'change'    => '0',
	),
	'bounce_rate' => array(
		'value'     => 0,
		'direction' => '',
		'change'    => '0',
	),
	'gen_time'    => array(
		'value'     => 0,
		'direction' => '',
		'change'    => '0',
	),
);
$data_defaults  = array(
	'overall' => array(
		'totals' => array(),
	),
);
if ( is_bool( $data ) ) {
	$data = array();
}

$data       = wp_parse_args( $data, $data_defaults );
$stats      = $data['overall']['totals'];
$stats      = wp_parse_args( $stats, $stats_defaults );
$have_stats = intval( $stats['pageviews']['value'] ) || intval( $stats['visits']['value'] ) ||
              intval( $stats['visit_time']['value'] ) || intval( $stats['bounce_rate']['value'] ) ||
              intval( $stats['gen_time']['value'] );
?>

<div class="sui-box">

	<?php // Title area. ?>
	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<i class="sui-icon-graph-line" aria-hidden="true"></i>
			<?php esc_html_e( 'Analytics', 'wpmudev' ); ?>
		</h2>
		<?php if ( 'free' === $membership_data['membership'] ) : ?>
			<div class="sui-actions-left">
				<span class="sui-tag sui-tag-purple sui-dashboard-expired-pro-tag">
					<?php esc_html_e( 'Pro', 'wpmudev' ); ?>
				</span>
			</div>
		<?php endif; ?>
	</div>
	<?php // Body area. ?>

	<div class="sui-box-body">
		<?php // Body area, description. ?>
		<?php if ( 'free' === $membership_data['membership'] ) : ?>
		<p><?php esc_html_e( 'Add basic analytics tracking that doesn\'t require any third party integration, and display the data in the WordPress Admin Dashboard area. An active WPMU DEV membership is required.', 'wpmudev' ); ?></p>
		<?php else : ?>
		<p><?php esc_html_e( 'Add basic analytics tracking that doesn\'t require any third party integration, and display the data in the WordPress Admin Dashboard area.', 'wpmudev' ); ?></p>
		<?php endif; ?>

		<?php if ( 'free' !== $membership_data['membership'] ) : ?>
			<?php // Body area, not activated. ?>
			<?php if ( ! $analytics_enabled ) : ?>
			<a href="<?php echo esc_url( $urls->analytics_url ); ?>" class="sui-button sui-button-blue" style="margin: 10px 0;">
				<?php esc_html_e( 'ACTIVATE', 'wpmudev' ); ?>
			</a>
			<?php endif; ?>
			<?php // Body area, not enough data. ?>
			<?php if ( $analytics_enabled && false === $have_stats ) : ?>
		<div class="sui-notice sui-notice-info">
			<div class="sui-notice-content">
				<div class="sui-notice-message">
					<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
					<p><?php esc_html_e( 'We haven\'t collected enough data. Please check back soon.', 'wpmudev' ); ?></p>
				</div>
			</div>
		</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<?php // Body area, display stats. ?>
	<?php if ( 'free' !== $membership_data['membership'] && $analytics_enabled && $have_stats ) : ?>
		<?php
		$stat_attrs = array(
			array(
				'type' => 'pageviews',
				'text' => __( 'Views', 'wpmudev' ),
			),
			array(
				'type' => 'visit_time',
				'text' => __( 'Average Time', 'wpmudev' ),
			),
			array(
				'type' => 'bounce_rate',
				'text' => __( 'Bounce Rate', 'wpmudev' ),
			),
			array(
				'type' => 'visits',
				'text' => __( 'Visits', 'wpmudev' ),
			),
			array(
				'type' => 'gen_time',
				'text' => __( 'Generation Time', 'wpmudev' ),
			),
		);
		?>
	<table class="sui-table">
		<tbody>
			<?php foreach ( $stat_attrs as $a ) : ?>
				<?php $stat = $stats[ $a['type'] ]; ?>
				<?php $dir = $stat['direction']; ?>
			<tr>
				<td class="sui-table-item-title"><?php echo esc_html( $a['text'] ); ?></td>
				<td
					class="wpmud-analytics-table-change wpmud-analytics-table-<?php echo esc_attr( $dir ); ?>"
				>
					<?php if ( in_array( $dir, array( 'up', 'down' ), true ) ) : ?>
					<i class="sui-icon-arrow-<?php echo esc_attr( $dir ); ?>" aria-hidden="true"></i>
					<?php endif; ?>
					<?php echo esc_html( $stat['change'] ); ?>
				</td>
				<td class="wpmud-analytics-table-total"><?php echo esc_html( $stat['value'] ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
		<?php // Body area, links. ?>
	<div class="sui-box-footer">
			<a href="<?php echo esc_url( admin_url( '#wdpun_analytics' ) ); ?>" class="sui-button sui-button-ghost">
				<i class="sui-icon-eye" aria-hidden="true"></i>
				<?php esc_html_e( 'View full report', 'wpmudev' ); ?>
			</a>
	</div>
	<?php endif; ?>
</div>