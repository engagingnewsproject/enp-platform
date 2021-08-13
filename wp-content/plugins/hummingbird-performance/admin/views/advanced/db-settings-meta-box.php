<?php
/**
 * Advanced tools database cleanup settings meta box.
 *
 * @package Hummingbird
 * @since 1.8
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box-settings-row sui-disabled">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label">
			<?php esc_html_e( 'Schedule Cleanups', 'wphb' ); ?>
			<?php if ( ! Utils::is_member() ) : ?>
				<span class="sui-tag sui-tag-pro"><?php esc_html_e( 'Pro', 'wphb' ); ?></span>
			<?php endif; ?>
		</span>
		<span class="sui-description">
			<?php esc_html_e( 'Schedule Hummingbird to automatically clean your database daily, weekly or monthly.', 'wphb' ); ?>
		</span>
	</div><!-- end col-third -->
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<label for="scheduled_cleanup" class="sui-toggle">
				<input type="checkbox" name="scheduled_cleanup" id="scheduled_cleanup" aria-labelledby="scheduled_cleanup-label">
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="scheduled_cleanup-label" class="sui-toggle-label"><?php esc_html_e( 'Enable scheduled cleanups', 'wphb' ); ?></span>
			</label>
		</div>
	</div>
</div>

<div class="sui-box-settings-row sui-upsell-row">
	<img class="sui-image sui-upsell-image" alt="<?php esc_attr_e( 'Scheduled automated database cleanup', 'wphb' ); ?>"
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-db-upsell.png' ); ?>"
		srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-db-upsell.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hb-graphic-db-upsell@2x.png' ); ?> 2x">

	<div class="sui-upsell-notice">
		<p>
			<?php
			printf( /* translators: %1$s - <a>, %2$s - </a> */
				esc_html__( 'Regular cleanups of your database ensures you’re regularly removing extra bloat which can slow down your host server. Upgrade to Hummingbird Pro as part of a WPMU DEV membership to unlock this feature today! %1$sLearn more%2$s.', 'wphb' ),
				'<a href="' . esc_url( Utils::get_link( 'plugin', 'hummingbird_dbcleanup_schedule_upsell_link' ) ) . '" target="_blank">',
				'</a>'
			)
			?>
			<br>
			<a class="sui-button sui-button-purple" href="<?php echo esc_url( Utils::get_link( 'plugin', 'hummingbird_dbcleanup_schedule_upsell_link' ) ); ?>" target="_blank" style="margin-top: 10px;">
				<?php esc_html_e( 'Upgrade to pro', 'wphb' ); ?>
			</a>
		</p>
	</div>
</div>
