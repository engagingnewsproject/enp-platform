<?php
/**
 * Performance meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var object $report           Latest report.
 * @var string $performance_url  Url to performance module.
 */

use Hummingbird\Core\Modules\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box-body wphb-metrics-widget">
	<strong><?php esc_html_e( 'Score Metrics', 'wphb' ); ?></strong>
	<span class="status-text">
		<a href="<?php echo esc_url( $performance_url ); ?>">
			<?php esc_html_e( 'More details', 'wphb' ); ?>
		</a>
	</span>
	<span class="sui-description">
		<?php esc_html_e( 'Your performance score is calculated based on how your site performs on each of the following metrics.', 'wphb' ); ?>
	</span>

	<table class="sui-table">
		<?php foreach ( $report->metrics as $rule => $rule_result ) : ?>
			<?php $score = isset( $rule_result->score ) ? $rule_result->score : 0; ?>
			<tr class="wphb-performance-report-item" data-performance-url="<?php echo esc_attr( $performance_url . '#' . $rule ); ?>">
				<td>
					<strong><?php echo esc_html( $rule_result->title ); ?></strong>
				</td>
				<td>
					<div class="sui-circle-score sui-grade-<?php echo esc_attr( Performance::get_impact_class( absint( $score * 100 ) ) ); ?> sui-tooltip" data-tooltip="<?php echo absint( $score * 100 ); ?>/100" data-score="<?php echo absint( $score * 100 ); ?>"></div>
				</td>
				<td>
					<span><?php echo isset( $rule_result->displayValue ) ? esc_html( $rule_result->displayValue ) : esc_html__( 'N/A', 'wphb' ); ?></span>
					<span aria-hidden="true" class="sui-icon-<?php echo esc_attr( Performance::get_impact_class( absint( $score * 100 ), 'icon' ) ); ?> sui-<?php echo esc_attr( Performance::get_impact_class( absint( $score * 100 ) ) ); ?> sui-md"></span>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
</div>
