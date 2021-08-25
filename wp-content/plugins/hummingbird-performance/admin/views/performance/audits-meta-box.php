<?php
/**
 * Performance summary meta box.
 *
 * @package Hummingbird
 *
 * @var stdClass $audits        Audits details.
 * @var bool     $is_dismissed  Is report dismissed.
 * @var array    $maps          Audits mapped to metrics.
 * @var bool     $passed        Default audit status.
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-border-frame with-padding wphb-audits-filter sui-hidden">
	<p class="sui-description">
		<?php esc_html_e( 'Display audits relevant to', 'wphb' ); ?>
	</p>
	<div class="sui-side-tabs">
		<div class="sui-tabs-menu">
			<label for="audits_filter-all" class="sui-tab-item active">
				<input type="radio" name="audits_filter" value="all" id="audits_filter-all" checked="checked">
				<?php esc_html_e( 'All', 'wphb' ); ?>
			</label>
			<?php foreach ( $maps as $label => $relative ) : ?>
				<label for="audits_filter-<?php echo esc_attr( $label ); ?>" class="sui-tab-item">
					<input type="radio" name="audits_filter" value="<?php echo esc_attr( $label ); ?>" id="audits_filter-<?php echo esc_attr( $label ); ?>">
					<?php echo esc_html( strtoupper( $label ) ); ?>
				</label>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<strong id="wphb-opportunities">
	<?php esc_html_e( 'Opportunities', 'wphb' ); ?>
</strong>
<p>
	<?php
	printf( /* translators: %1$s - <strong>, %2$s - </strong> */
		esc_html__( 'Each suggestion in this section is an opportunity to improve your page load speed and estimates how much faster the page will load if the improvement is implemented. Although they are %1$snot directly affect%2$s your Performance Score, improving the audits here can help as a starting point for overall performance score gains.', 'wphb' ),
		'<strong>',
		'</strong>'
	);
	?>
</p>

<?php if ( ! isset( $audits->opportunities ) || empty( $audits->opportunities ) ) : ?>
	<?php $this->admin_notices->show_inline( esc_html__( 'Nice! All tests passed.', 'wphb' ) ); ?>
<?php else : ?>
	<div class="sui-accordion">
		<div class="sui-accordion-header">
			<div><?php esc_html_e( 'Audits', 'wphb' ); ?></div>
			<div><?php esc_html_e( 'Score', 'wphb' ); ?></div>
			<div>&nbsp;</div>
		</div>
		<?php
		foreach ( $audits->opportunities as $rule => $rule_result ) {
			$relevant_metrics = \Hummingbird\Core\Modules\Performance::get_relevant_metrics( $rule );
			$this->view(
				'performance/audit-template',
				compact( 'is_dismissed', 'rule', 'rule_result', 'passed', 'relevant_metrics' )
			);
		}
		?>
	</div>
<?php endif; ?>

<strong id="wphb-diagnostics">
	<?php esc_html_e( 'Diagnostics', 'wphb' ); ?>
</strong>
<p>
	<?php
	printf( /* translators: %1$s - <strong>, %2$s - </strong> */
		esc_html__( 'This section provides additional information about how your page adheres to best practices of web development. These improvements may %1$snot directly impact%2$s your performance score, however, can help as a starting point for overall performance score gains.', 'wphb' ),
		'<strong>',
		'</strong>'
	);
	?>
</p>

<?php if ( ! isset( $audits->diagnostics ) || empty( $audits->diagnostics ) ) : ?>
	<?php $this->admin_notices->show_inline( esc_html__( 'Nice! All tests passed.', 'wphb' ) ); ?>
<?php else : ?>
	<div class="sui-accordion sui-accordion-flushed">
		<div class="sui-accordion-header">
			<div><?php esc_html_e( 'Audits', 'wphb' ); ?></div>
			<div><?php esc_html_e( 'Score', 'wphb' ); ?></div>
			<div>&nbsp;</div>
		</div>
		<?php
		foreach ( $audits->diagnostics as $rule => $rule_result ) {
			$relevant_metrics = \Hummingbird\Core\Modules\Performance::get_relevant_metrics( $rule );
			$this->view(
				'performance/audit-template',
				compact( 'is_dismissed', 'rule', 'rule_result', 'passed', 'relevant_metrics' )
			);
		}
		?>
	</div>
<?php endif; ?>

<strong id="wphb-passed">
	<?php esc_html_e( 'Passed Audits', 'wphb' ); ?>
</strong>
<p>
	<?php esc_html_e( 'This section lists the audits with a score of 90 or more. There are still opportunities to improve the overall performance score by aiming for a score of 100 for all the passed audits.', 'wphb' ); ?>
</p>

<div class="sui-accordion sui-accordion-flushed sui-no-margin-bottom">
	<div class="sui-accordion-header">
		<div><?php esc_html_e( 'Audits', 'wphb' ); ?></div>
		<div><?php esc_html_e( 'Score', 'wphb' ); ?></div>
		<div>&nbsp;</div>
	</div>
	<?php
	$passed = true;
	foreach ( $audits->passed as $rule => $rule_result ) {
		$relevant_metrics = \Hummingbird\Core\Modules\Performance::get_relevant_metrics( $rule );
		$this->view(
			'performance/audit-template',
			compact( 'is_dismissed', 'rule', 'rule_result', 'passed', 'relevant_metrics' )
		);
	}
	?>
</div>

<?php if ( ! Utils::is_member() ) : ?>
	<?php $this->modal( 'membership' ); ?>
<?php endif; ?>
