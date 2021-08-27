<?php
/**
 * Performance summary meta box.
 *
 * @package Hummingbird
 *
 * @var bool          $can_run_test      If there is no cool down period and user can run a new test.
 * @var null|stdClass $field_data        Field data object. Null, if none available.
 * @var array         $historic_data     Historic field data.
 * @var stdClass      $last_test         Last test details.
 * @var array         $links             Score metric links to web.dev.
 * @var bool          $report_dismissed  If performance report is dismissed.
 * @var string        $retry_url         URL to trigger a new performance scan.
 * @var array         $tooltips          List of metric tooltips.
 * @var string        $type              Report type: desktop or mobile.
 */

use Hummingbird\Core\Modules\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<strong>
	<?php esc_html_e( 'Score Metrics', 'wphb' ); ?>
</strong>

<p>
	<?php esc_html_e( 'See how well your website currently performs and what can be improved. Your overall performance score is calculated from the metrics below, where the pie chart represents the weight of each metric in your overall score.', 'wphb' ); ?>
</p>

<?php
if ( $report_dismissed ) {
	if ( true === $can_run_test ) {
		$buttons = sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
			esc_html__( '%1$sRun Test%2$s', 'wphb' ),
			'<a href="' . esc_url( $retry_url ) . '" class="sui-button sui-button-blue">',
			'</a>'
		);
	} else {
		$tooltip = sprintf( /* translators: %d: number of minutes. */
			_n(
				'Hummingbird is just catching her breath - you can run another test in %d minute',
				'Hummingbird is just catching her breath - you can run another test in %d minutes',
				$can_run_test,
				'wphb'
			),
			number_format_i18n( $can_run_test )
		);
		$buttons = sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
			esc_html__( '%1$sRun Test%2$s', 'wphb' ),
			'<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="' . esc_attr( $tooltip ) . '" aria-hidden="true">' .
			'<a href="#" disabled="disabled" class="sui-button sui-button-blue" aria-hidden="true">',
			'</a></span>'
		);
	}

	$this->admin_notices->show_inline(
		esc_html__( 'You have chosen to ignore this performance test. Run a new test to see new recommendations.', 'wphb' ),
		'grey',
		$buttons
	);

	$impact_score_class = 'dismissed';
	$impact_icon_class  = 'warning-alert';
}

$metrics = array(
	'speed-index'              => 0,
	'first-contentful-paint'   => 0,
	'largest-contentful-paint' => 0,
	'interactive'              => 0,
	'total-blocking-time'      => 0,
	'cumulative-layout-shift'  => 0,
);

foreach ( $last_test->metrics as $rule => $rule_result ) {
	if ( ! isset( $metrics[ $rule ] ) || ! isset( $rule_result->score ) ) {
		continue;
	}
	$metrics[ $rule ] = $rule_result->score;
}
?>

<div class="wphb-performance-metrics">
	<div class="wphb-performance-score">
		<svg class="wphb-gauge__wrapper" viewBox="-65 -65 129 129">
			<text class="wphb-gauge__percentage"><?php echo (int) $last_test->score; ?></text>
			<g class="metric metric--FCP" style="--metric-offset: 56; --i: 0;">
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--bg" stroke-dasharray="23 211"></circle>
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--fill" style="--metric-array: <?php echo (float) ( 23 * $metrics['first-contentful-paint'] ); ?> <?php echo (float) ( 234 - 23 * $metrics['first-contentful-paint'] ); ?>;"></circle>
				<text class="metric__label" x="11.07" y="-53.42">FCP</text>
				<text class="metric__value" text-anchor="end" dominant-baseline="hanging" x="11.44" y="-18.29"><?php echo (int) ( $metrics['first-contentful-paint'] * 10 ); ?>/10</text>
			</g>
			<g class="metric metric--SI" style="--metric-offset: 32; --i: 1;">
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--bg" stroke-dasharray="23 211"></circle>
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--fill" style="--metric-array: <?php echo (float) ( 23 * $metrics['speed-index'] ); ?> <?php echo (float) ( 234 - 23 * $metrics['speed-index'] ); ?>;"></circle>
				<text class="metric__label" x="42.48" y="-33">SI</text>
				<text class="metric__value" text-anchor="end" dominant-baseline="hanging" x="16.58" y="-13.32"><?php echo (int) ( $metrics['speed-index'] * 10 ); ?>/10</text>
			</g>
			<g class="metric metric--LCP" style="--metric-offset: 8; --i: 2;">
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--bg" stroke-dasharray="58 177"></circle>
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--fill" style="--metric-array: <?php echo (float) ( 58 * $metrics['largest-contentful-paint'] ); ?> <?php echo (float) ( 234 - 58 * $metrics['largest-contentful-paint'] ); ?>;"></circle>
				<text class="metric__label" dominant-baseline="hanging" x="49.07" y="23.42">LCP</text>
				<text class="metric__value" text-anchor="end" x="19.44" y="9.29"><?php echo (int) ( $metrics['largest-contentful-paint'] * 25 ); ?>/25</text>
			</g>
			<g class="metric metric--TTI" style="--metric-offset: -51; --i: 3;">
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--bg" stroke-dasharray="23 211"></circle>
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--fill" style="--metric-array: <?php echo (float) ( 23 * $metrics['interactive'] ); ?> <?php echo (float) ( 234 - 23 * $metrics['interactive'] ); ?>;"></circle>
				<text class="metric__label" text-anchor="end" dominant-baseline="hanging" x="0.70" y="54.70">TTI</text>
				<text class="metric__value" x="-10.04" y="19.04"><?php echo (int) ( $metrics['interactive'] * 10 ); ?>/10</text>
			</g>
			<g class="metric metric--TBT" style="--metric-offset: -75; --i: 4;">
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--bg" stroke-dasharray="67 170"></circle>
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--fill" style="--metric-array: <?php echo (float) ( 67 * $metrics['total-blocking-time'] ); ?> <?php echo (float) ( 234 - 67 * $metrics['total-blocking-time'] ); ?>;"></circle>
				<text class="metric__label" text-anchor="end" x="-52.42" y="20.07">TBT</text>
				<text class="metric__value" dominant-baseline="hanging" x="-19.54" y="1.44"><?php echo (int) ( $metrics['total-blocking-time'] * 30 ); ?>/30</text>
			</g>
			<g class="metric metric--CLS" style="--metric-offset: -143; --i: 5;">
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--bg" stroke-dasharray="34 200"></circle>
				<circle class="wphb-gauge speed-metric-gauge wphb-gauge--fill" style="--metric-array: <?php echo (float) ( 34 * $metrics['cumulative-layout-shift'] ); ?> <?php echo (float) ( 234 - 34 * $metrics['cumulative-layout-shift'] ); ?>;"></circle>
				<text class="metric__label" text-anchor="end" x="-22.37" y="-49.48">CLS</text>
				<text class="metric__value" dominant-baseline="hanging" x="-12" y="-18"><?php echo (int) ( $metrics['cumulative-layout-shift'] * 15 ); ?>/15</text>
			</g>
		</svg>
	</div>

	<div class="wphb-metrics-table">
		<?php
		foreach ( $last_test->metrics as $rule => $rule_result ) {
			if ( ! isset( $metrics[ $rule ] ) ) {
				continue;
			}

			$score = isset( $rule_result->score ) ? $rule_result->score : 0;

			if ( ! $report_dismissed ) {
				$impact_score_class = Performance::get_impact_class( absint( $score * 100 ) );
				$impact_icon_class  = Performance::get_impact_class( absint( $score * 100 ), 'icon' );
			}
			?>
			<div>
				<span class="wphb-metric-info">
					<span class="sui-tooltip sui-tooltip-constrained sui-tooltip-right-mobile" style="--tooltip-width: 300px; --tooltip-width-mobile: 200px;" data-tooltip="<?php echo esc_attr( $tooltips[ $rule ] ); ?>">
						<span aria-hidden="true" class="sui-icon-<?php echo esc_attr( $impact_icon_class ); ?> sui-<?php echo esc_attr( $impact_score_class ); ?> sui-md"></span>
					</span>
					<strong><?php echo esc_html( $rule_result->title ); ?></strong>
					<a href="<?php echo esc_url( $links[ $rule ] ); ?>" target="_blank">
						<span class="sui-icon-open-new-window sui-md sui-info" aria-hidden="true"></span>
					</a>
				</span>
				<span class="wphb-metric-value">
					<?php if ( ! empty( $rule_result->description ) || ! empty( $rule_result->tip ) ) : ?>
						<?php echo isset( $rule_result->displayValue ) ? esc_html( $rule_result->displayValue ) : esc_html__( 'N/A', 'wphb' ); ?>
					<?php endif; ?>
				</span>
			</div>
			<?php
		}
		?>
	</div>
</div>

<strong>
	<?php esc_html_e( 'Historic Field Data', 'wphb' ); ?>
</strong>

<p>
	<?php
	printf( /* translators: %1$s - starting a tag, %2$s - ending a tag */
		esc_html__( 'The field data is a historical report about how a particular URL has performed, and represents anonymized performance data from users in the real-world on a variety of devices and network conditions. We use %1$sChrome User Experience Report%2$s to generate insights about the real users’ experience with your webpage over the last 30 days.', 'wphb' ),
		'<a href="https://developers.google.com/web/tools/chrome-user-experience-report/" target="_blank">',
		'</a>'
	);
	?>
</p>


<?php if ( ! $field_data ) : ?>
	<?php
	$this->admin_notices->show_inline(
		esc_html__( 'The Chrome User Experience Report does not have sufficient real-world speed data for this page. Note: This report can take months to populate and is aimed at well established websites.', 'wphb' ),
		'grey'
	);
	?>
<?php else : ?>
	<div class="sui-row">
		<div class="sui-col">
			<div class="wphb-border-frame">
				<div class="table-header">
					<strong><?php esc_html_e( 'First Contentful Paint (FCP)', 'wphb' ); ?></strong>
					<?php
					switch ( $field_data->FIRST_CONTENTFUL_PAINT_MS->category ) {
						case 'FAST':
							echo '<span class="sui-icon-check-tick sui-success sui-md" aria-hidden="true"></span>';
							break;
						case 'AVERAGE':
							echo '<span class="sui-icon-warning-alert sui-warning sui-md" aria-hidden="true"></span>';
							break;
						case 'SLOW':
						default:
							echo '<span class="sui-icon-warning-alert sui-error sui-md" aria-hidden="true"></span>';
							break;
					}
					?>
				</div>

				<div class="table-content sui-padding-left sui-padding-right sui-padding-top">
					<p class="sui-description">
						<?php esc_html_e( 'FCP is the point when the browser renders the first bit of content from the DOM - text, an image, SVG, or even a canvas element.', 'wphb' ); ?>
					</p>
				</div>

				<div class="table-content sui-padding-top sui-padding-left sui-padding-right">
					<strong><?php esc_html_e( 'Category', 'wphb' ); ?></strong>
					<span><?php echo esc_html( ucfirst( strtolower( $field_data->FIRST_CONTENTFUL_PAINT_MS->category ) ) ); ?></span>
				</div>

				<div class="table-content sui-padding-left sui-padding-right">
					<strong><?php esc_html_e( 'Avg. FCP', 'wphb' ); ?></strong>
					<span>
					<?php
					/* translators: %s - number of seconds */
					printf( '%s s', esc_html( round( $field_data->FIRST_CONTENTFUL_PAINT_MS->percentile / 1000, 1 ) ) );
					?>
				</span>
				</div>

				<hr>

				<div class="table-content sui-padding-left sui-padding-right sui-padding-top">
					<p class="sui-description">
						<?php esc_html_e( 'Following is the distribution of all the page loads into different FCP categories.', 'wphb' ); ?>
					</p>
				</div>

				<div class="sui-padding-left sui-padding-right sui-padding-bottom">
					<div id="first_contentful_paint"></div>

					<div class="performance-chart-keys">
					<span class="fast-key">
						<?php esc_html_e( 'Fast', 'wphb' ); ?><br>
						<small><?php echo absint( $historic_data['fcp_fast'] ) . '%'; ?></small>
					</span>
						<span class="average-key">
						<?php esc_html_e( 'Average', 'wphb' ); ?><br>
						<small><?php echo absint( $historic_data['fcp_average'] ) . '%'; ?></small>
					</span>
						<span class="slow-key">
						<?php esc_html_e( 'Slow', 'wphb' ); ?><br>
						<small><?php echo absint( $historic_data['fcp_slow'] ) . '%'; ?></small>
					</span>
					</div>
				</div>
			</div>
		</div>

		<div class="sui-col">
			<div class="wphb-border-frame">
				<div class="table-header">
					<strong><?php esc_html_e( 'First Input Delay (FID)', 'wphb' ); ?></strong>
					<?php
					switch ( $field_data->FIRST_INPUT_DELAY_MS->category ) {
						case 'FAST':
							echo '<span class="sui-icon-check-tick sui-success sui-md" aria-hidden="true"></span>';
							break;
						case 'AVERAGE':
							echo '<span class="sui-icon-warning-alert sui-warning sui-md" aria-hidden="true"></span>';
							break;
						case 'SLOW':
						default:
							echo '<span class="sui-icon-warning-alert sui-error sui-md" aria-hidden="true"></span>';
							break;
					}
					?>
				</div>

				<div class="table-content sui-padding-left sui-padding-right sui-padding-top">
					<p class="sui-description">
						<?php esc_html_e( 'FID measure the time that the browser takes to respond to the user\'s first interaction with your page while the page is still loading.', 'wphb' ); ?>
					</p>
				</div>

				<div class="table-content sui-padding-top sui-padding-left sui-padding-right">
					<strong><?php esc_html_e( 'Category', 'wphb' ); ?></strong>
					<span><?php echo esc_html( ucfirst( strtolower( $field_data->FIRST_INPUT_DELAY_MS->category ) ) ); ?></span>
				</div>

				<div class="table-content sui-padding-left sui-padding-right">
					<strong><?php esc_html_e( 'Avg. FID', 'wphb' ); ?></strong>
					<span>
					<?php
					/* translators: %s - number of milliseconds */
					printf( '%s ms', esc_html( $field_data->FIRST_INPUT_DELAY_MS->percentile ) );
					?>
				</span>
				</div>

				<hr>

				<div class="table-content sui-padding-left sui-padding-right sui-padding-top">
					<p class="sui-description">
						<?php esc_html_e( 'Following is the distribution of all the page loads into different FID categories.', 'wphb' ); ?>
					</p>
				</div>

				<div class="sui-padding-left sui-padding-right sui-padding-bottom">
					<div id="first_input_delay"></div>

					<div class="performance-chart-keys">
					<span class="fast-key">
						<?php esc_html_e( 'Fast', 'wphb' ); ?><br>
						<small><?php echo absint( $historic_data['fid_fast'] ) . '%'; ?></small>
					</span>
						<span class="average-key">
						<?php esc_html_e( 'Average', 'wphb' ); ?><br>
						<small><?php echo absint( $historic_data['fid_average'] ) . '%'; ?></small>
					</span>
						<span class="slow-key">
						<?php esc_html_e( 'Slow', 'wphb' ); ?><br>
						<small><?php echo absint( $historic_data['fid_slow'] ) . '%'; ?></small>
					</span>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
