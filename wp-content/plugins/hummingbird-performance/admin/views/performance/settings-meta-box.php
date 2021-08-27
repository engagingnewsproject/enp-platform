<?php
/**
 * Performance report settings meta box.
 *
 * @package Hummingbird
 *
 * @var bool  $dismissed      Report dismissed status.
 * @var array $hub            Hub widget settings.
 * @var bool  $subsite_tests  Sub-site tests status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$this->modal( 'dismiss-report' );

?>

<form method="post" class="settings-frm">
	<?php if ( ! is_multisite() || ( is_multisite() && $subsite_tests ) ) : ?>
		<div class="sui-box-settings-row">
			<div class="sui-box-settings-col-1">
				<span class="sui-settings-label">
					<?php esc_html_e( 'Ignore Current Score', 'wphb' ); ?>
				</span>
				<span class="sui-description">
					<?php esc_html_e( 'If you don’t wish to see your current performance test results, you can ignore them here.', 'wphb' ); ?>
				</span>
			</div>
			<div class="sui-box-settings-col-2">
				<a class="sui-button sui-button-ghost" id="dismiss-report" data-modal-open="dismiss-report-modal" data-modal-open-focus="dismiss_report" data-modal-mask="true" <?php disabled( $dismissed ); ?>>
					<span class="sui-icon-eye-hide" aria-hidden="true"></span>
					<?php esc_html_e( 'Ignore Results', 'wphb' ); ?>
				</a>

				<span class="sui-description">
					<?php esc_html_e( 'Note: You can re-run the test anytime to check your performance score again.', 'wphb' ); ?>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( is_multisite() && is_network_admin() ) : ?>
		<input type="hidden" name="network_admin" value="1" />
		<div class="sui-box-settings-row">
			<div class="sui-box-settings-col-1">
				<span class="sui-settings-label">
					<?php esc_html_e( 'Subsites', 'wphb' ); ?>
				</span>
				<span class="sui-description">
					<?php esc_html_e( 'Choose the minimum user role required to run the performance tests on your subsites.', 'wphb' ); ?>
				</span>
			</div>
			<div class="sui-box-settings-col-2">
				<div class="sui-side-tabs">
					<div class="sui-tabs-menu">
						<label for="subsite_tests-false" class="sui-tab-item <?php echo ! $subsite_tests || 'super-admins' === $subsite_tests ? 'active' : ''; ?>">
							<input type="radio" name="subsite-tests" value="super-admins" id="subsite_tests-false" <?php checked( $subsite_tests, 'super-admins' ); ?>>
							<?php esc_html_e( 'Super Admin', 'wphb' ); ?>
						</label>

						<label for="subsite_tests-true" class="sui-tab-item <?php echo true === $subsite_tests ? 'active' : ''; ?>">
							<input type="radio" name="subsite-tests" value="true" id="subsite_tests-true" <?php checked( $subsite_tests, true ); ?>>
							<?php esc_html_e( 'Subsite Admin', 'wphb' ); ?>
						</label>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
