<?php
/**
 * Purge orphaned Asset Optimization data in Plugin Health module.
 *
 * @package Hummingbird
 *
 * @since 2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-sm">
	<div
		role="dialog"
		id="site-health-orphaned-modal"
		class="sui-modal-content"
		aria-live="polite"
		aria-modal="true"
		aria-labelledby="site-health-orphaned-modal-title"
		aria-describedby="site-health-orphaned-modal-description"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button type="button" class="sui-button-icon sui-button-float--right" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text">
						<?php esc_html_e( 'Close this dialog window', 'wphb' ); ?>
					</span>
				</button>
				<h3 class="sui-box-title sui-lg" id="site-health-orphaned-modal-title">
					<?php esc_html_e( 'Delete Orphaned Metadata?', 'wphb' ); ?>
				</h3>
				<p class="sui-description" id="site-health-orphaned-modal-description">
					<?php esc_html_e( 'The orphaned asset optimization metadata includes the data in the wp_postmeta table. Do you want to delete it?', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-body">
				<div class="sui-notice sui-notice-warning sui-no-margin-bottom">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>
							<p>
								<?php esc_html_e( 'Note: Besides the data related to asset optimization, the metadata that will be deleted may also include other data related to another plugin - provided there is a match in the post ID. Due to this potential overlap, we recommend that you have an up-to-date backup available, just in case.', 'wphb' ); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="sui-notice sui-notice-error sui-margin-top sui-no-margin-bottom sui-hidden" id="site-health-orphanned-speed">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>
							<p>
								<?php esc_html_e( 'Hummingbird detected high CPU usage, removing data at a slower speed.', 'wphb' ); ?>
							</p>
						</div>
					</div>
				</div>

				<div id="site-health-orphaned-progress" class="sui-hidden sui-margin-top">
					<div class="sui-progress-block">
						<div class="sui-progress">
						<span class="sui-progress-icon" aria-hidden="true">
							<span class="sui-icon-loader sui-loading"></span>
						</span>
							<div class="sui-progress-text">
								<span>0%</span>
							</div>
							<div class="sui-progress-bar" aria-hidden="true">
								<span style="width: 0"></span>
							</div>
						</div>
						<button class="sui-button-icon sui-tooltip" type="button" data-tooltip="<?php esc_attr_e( 'Cancel', 'wphb' ); ?>" data-modal-close>
							<span class="sui-icon-close" aria-hidden="true"></span>
						</button>
					</div>

					<div class="sui-progress-state">
						<span class="sui-progress-state-text"><?php esc_html_e( 'Removing orphaned data...', 'wphb' ); ?></span>
					</div>
				</div>
			</div>

			<div class="sui-box-footer sui-flatten sui-content-separated">
				<button type="button" class="sui-button sui-button-ghost" data-modal-close>
					<?php esc_html_e( 'Cancel', 'wphb' ); ?>
				</button>

				<button type="button" class="sui-button sui-button-red" id="site-health-orphaned-clear">
					<span class="sui-loading-text">
						<span class="sui-icon-trash" aria-hidden="true"></span>
						<?php esc_html_e( 'Delete', 'wphb' ); ?>
					</span>
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>
			</div>
		</div>
	</div>
</div>
