<?php
/**
 * Clear page cache network-wide
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
			id="ccnw-modal"
			class="sui-modal-content"
			aria-live="polite"
			aria-modal="true"
			aria-labelledby="ccnw-modal-title"
			aria-describedby="ccnw-modal-description"
	>
		<div id="ccnw-slide-one" class="sui-box sui-modal-slide sui-loaded sui-active" data-modal-size="sm">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button type="button" class="sui-button-icon sui-button-float--right" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text">
						<?php esc_html_e( 'Close this dialog window', 'wphb' ); ?>
					</span>
				</button>
				<h3 class="sui-box-title sui-lg" id="ccnw-modal-title">
					<?php esc_html_e( 'Clear page cache', 'wphb' ); ?>
				</h3>
				<p class="sui-description" id="ccnw-modal-description">
					<?php esc_html_e( 'Do you want to clear the page cache on all subsites at once?', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-body sui-content-center sui-spacing-bottom--50">
				<button type="button" class="sui-button sui-button-ghost" data-modal-close>
					<?php esc_html_e( 'Cancel', 'wphb' ); ?>
				</button>

				<button type="button" id="ccnw-clear-now" class="sui-button" onclick="window.WPHB_Admin.getModule( 'caching' ).clearNetworkCache()">
					<?php esc_html_e( 'Clear now', 'wphb' ); ?>
				</button>
			</div>
		</div>

		<div id="ccnw-slide-two" class="sui-box sui-modal-slide" data-modal-size="sm">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button type="button" class="sui-button-icon sui-button-float--right" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text">
						<?php esc_html_e( 'Close this dialog window', 'wphb' ); ?>
					</span>
				</button>
				<h3 class="sui-box-title sui-lg">
					<?php esc_html_e( 'Clear page cache', 'wphb' ); ?>
				</h3>
				<p class="sui-description">
					<?php esc_html_e( 'Do you want to clear the page cache on all subsites at once?', 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-box-body sui-content-center sui-spacing-bottom--50">
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
					<button class="sui-button-icon sui-tooltip" type="button" data-tooltip="<?php esc_attr_e( 'Cancel', 'wphb' ); ?>">
						<span class="sui-icon-close" aria-hidden="true"></span>
					</button>
				</div>

				<div class="sui-progress-state">
					<span class="sui-progress-state-text"><?php esc_html_e( 'Clearing cache...', 'wphb' ); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if ( isset( $_GET['update'] ) && 'open-ccnw' === $_GET['update'] ) : ?>
	<script type="text/javascript">
		document.addEventListener( 'DOMContentLoaded', function () {
			window.SUI.openModal( 'ccnw-modal', 'wpbody', 'ccnw-clear-now' );
		} );
	</script>
<?php endif; ?>
