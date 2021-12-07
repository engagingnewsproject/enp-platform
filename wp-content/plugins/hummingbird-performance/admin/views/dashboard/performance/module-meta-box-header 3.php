<?php
/**
 * Performance meta box header on dashboard page.
 *
 * @package Hummingbird
 *
 * @var object        $last_report    Performance report object.
 * @var string        $title          Performance module title.
 * @var string        $scan_link      Link to run new performance scan.
 * @var bool|integer  $can_run_scan   True if a new test is available or the time in minutes remaining for next test.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h3 class="sui-box-title"><?php echo esc_html( $title ); ?></h3>
<div class="sui-actions-right">
	<?php if ( true === $can_run_scan ) : ?>
		<a href="<?php echo esc_url( $scan_link ); ?>" class="sui-button sui-button-blue" id="performance-run-test">
			<?php esc_html_e( 'Run Test', 'wphb' ); ?>
		</a>
		<?php
	else :
		$tooltip = sprintf(
			/* translators: %d: number of minutes. */
			_n(
				'Hummingbird is just catching her breath - you can run another test in %d minute',
				'Hummingbird is just catching her breath - you can run another test in %d minutes',
				$can_run_scan,
				'wphb'
			),
			number_format_i18n( $can_run_scan )
		);
		?>
		<span class="sui-tooltip sui-tooltip-constrained" data-tooltip="<?php echo esc_attr( $tooltip ); ?>">
			<a href="#" class="sui-button sui-button-blue" aria-hidden="true" disabled="disabled">
				<?php esc_html_e( 'Run Test', 'wphb' ); ?>
			</a>
		</span>
	<?php endif; ?>
</div>
