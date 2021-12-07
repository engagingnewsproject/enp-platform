<?php
/**
 * Performance test modal.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-lg">
	<div
		role="dialog"
		id="run-performance-test-modal"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="runPerformanceScan"
		aria-describedby="dialogDescription"
	>
		<div class="sui-box">
			<div class="sui-box-header">
				<h3 class="sui-box-title" id="runPerformanceScan">
					<?php esc_html_e( 'Test in progress', 'wphb' ); ?>
				</h3>
			</div>

			<div class="sui-box-body wphb-performance-scan-modal">
				<p id="dialogDescription">
					<?php esc_html_e( 'Hummingbird is running a test to measure your website performance, please wait.', 'wphb' ); ?>
				</p>

				<div class="sui-progress-block">
					<div class="sui-progress">
						<span class="sui-progress-icon" aria-hidden="true">
							<span class="sui-icon-loader sui-loading"></span>
						</span>
						<div class="sui-progress-text">
							<span>0%</span>
						</div>
						<div class="sui-progress-bar" aria-hidden="true">
							<span style="width: 0;"></span>
						</div>
					</div>
				</div>

				<div class="sui-progress-state">
					<span class="sui-progress-state-text"><?php esc_html_e( 'Initializing engines...', 'wphb' ); ?></span>
				</div>
			</div>
			<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
				<img class="sui-image" alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-modal-quicksetup.png' ); ?>"
				     srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-modal-quicksetup.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-modal-quicksetup@2x.png' ); ?> 2x">
			<?php endif; ?>
		</div>
	</div>
</div>
