<?php
/**
 * The changelog modal base content.
 *
 * @package WPMUDEV_Dashboard
 */

defined( 'WPINC' ) || die();

?>

<div class="<?php echo esc_attr( WPMUDEV_Dashboard::$sui_version ); ?>">
	<div class="wpmudev-dashboard-changelog-wrap">
		<div class="sui-modal sui-modal-lg">
			<div role="dialog" id="wpmudev-dashboard-changelog" class="sui-modal-content sui-content-fade-out" aria-modal="true" aria-labelledby="wpmudev-dashboard-changelog-title">
				<div class="sui-box">
					<div class="sui-box-header">
						<h3 id="wpmudev-dashboard-changelog-title" class="sui-box-title">
							<?php esc_attr_e( 'Loading..', 'wpmudev' ); ?>
						</h3>

						<button class="sui-button-icon sui-button-float--right" id="wpmudev-dashboard-changelog-close" data-modal-close>
							<span class="sui-icon-close" aria-hidden="true"></span>
							<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close', 'wpmudev' ); ?></span>
						</button>
					</div>
					<div class="sui-box-body sui-content-center" id="wpmudev-dashboard-changelog-loader">
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					</div>
					<div class="sui-box-body" id="wpmudev-dashboard-changelog-content" style="display: none;"></div>
				</div>
			</div>
		</div>
	</div>
</div>