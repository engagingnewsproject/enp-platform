<?php
/**
 * Performance test modal.
 *
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="run-performance-onboard-modal"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="performance-test-title"
		aria-describedby="performance-test-desc"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60 sui-spacing-sides--70">
				<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
					<figure class="sui-box-banner" aria-hidden="true">
						<img src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-modal-quicksetup.png' ); ?>" alt=""
							srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-modal-quicksetup.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-modal-quicksetup@2x.png' ); ?> 2x">
					</figure>
				<?php endif; ?>

				<h3 id="performance-test-title" class="sui-box-title sui-lg">
					<?php
					printf(
						/* translators: $s - user name */
						esc_html__( 'Hey, %s!', 'wphb' ),
						Utils::get_current_user_name()
					);
					?>
				</h3>

				<p id="performance-test-desc" class="sui-description">
					<?php esc_html_e( 'Welcome to Hummingbird, the hottest Performance plugin for WordPress! We recommend running a quick performance test before you start tweaking things. Alternatively you can skip this step if you’d prefer to start customizing.', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-body sui-content-center wphb-performance-scan-modal">
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
		</div>
	</div>
</div>
