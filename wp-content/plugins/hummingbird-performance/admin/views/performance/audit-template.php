<?php
/**
 * Audit row template
 *
 * @since 3.1.0
 * @package Hummingbird
 *
 * @var bool     $is_dismissed      Audit is dismissed.
 * @var bool     $passed            Audit is passed.
 * @var string   $relevant_metrics  Relevant metric ID (used by filter).
 * @var string   $rule              Audit ID.
 * @var stdClass $rule_result       Audit details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$informative_audits = array( 'uses-rel-preload', 'layout-shift-elements', 'largest-contentful-paint-element' );

if ( $is_dismissed ) {
	$impact_score_class = 'dismissed';
	$impact_icon_class  = 'warning-alert';
} else {
	$impact_score_class = 'error';
	$impact_icon_class  = 'warning-alert';

	if ( $passed || ( isset( $rule_result->score ) && 90 <= $rule_result->score * 100 ) ) {
		$impact_score_class = 'success';
		$impact_icon_class  = 'check-tick';
	} elseif ( isset( $rule_result->score ) && 50 <= $rule_result->score * 100 ) {
		$impact_score_class = 'warning';
		$impact_icon_class  = 'warning-alert';
	}

	// These audits are informative only.
	if ( in_array( $rule, $informative_audits, true ) ) {
		$impact_score_class = 'default';
		$impact_icon_class  = 'info';
	}
}
?>
<div class="sui-accordion-item sui-<?php echo esc_attr( $impact_score_class ); ?>" id="<?php echo esc_attr( $rule ); ?>" data-metrics="<?php echo esc_attr( $relevant_metrics ); ?>">
	<div class="sui-accordion-item-header">
		<div class="sui-accordion-item-title">
			<span aria-hidden="true" class="sui-icon-<?php echo esc_attr( $impact_icon_class ); ?> sui-<?php echo esc_attr( $impact_score_class ); ?>"></span>
			<?php echo esc_html( $rule_result->title ); ?>
		</div>
		<div>
			<?php if ( ! in_array( $rule, $informative_audits, true ) ) : ?>
				<?php $gray_class = ! isset( $rule_result->score ) || ( isset( $rule_result->score ) && 0 === $rule_result->score ) ? 'wphb-gray-color' : ''; ?>
				<div class="sui-circle-score sui-grade-<?php echo esc_attr( $impact_score_class ) . ' ' . esc_attr( $gray_class ); ?>" data-score="<?php echo isset( $rule_result->score ) ? absint( $rule_result->score * 100 ) : 0; ?>"></div>
			<?php endif; ?>
		</div>
		<div>
			<?php if ( 'disabled' !== $impact_score_class && $this->view_exists( "performance/audits/{$rule}" ) ) : ?>
				<?php
				if ( 'server-response-time' === $rule && 1 !== $rule_result->score && isset( $rule_result->details->overallSavingsMs ) ) {
					printf( /* translators: %s - number of ms */
						esc_html__( 'Potential savings of %s ms', 'wphb' ),
						(int) $rule_result->details->overallSavingsMs
					);
				} elseif ( ! empty( $rule_result->description ) || ! empty( $rule_result->tip ) ) {
					echo isset( $rule_result->displayValue ) ? esc_html( $rule_result->displayValue ) : '';
				}
				?>
				<button class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_attr_e( 'Open item', 'wphb' ); ?>">
					<span class="sui-icon-chevron-down" aria-hidden="true"></span>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $this->view_exists( "performance/audits/{$rule}" ) ) : ?>
		<div class="sui-accordion-item-body">
			<div class="sui-box">
				<div class="sui-box-body">
					<?php
					$this->view(
						"performance/audits/{$rule}",
						array(
							'audit' => $rule_result,
						)
					);
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
