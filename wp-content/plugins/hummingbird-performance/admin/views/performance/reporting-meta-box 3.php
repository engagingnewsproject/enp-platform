<?php
/**
 * Performance tests reporting meta box.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="sui-box-body">
	<p><?php esc_html_e( 'Enable scheduled performance tests and get the customized results emailed directly to your inbox.', 'wphb' ); ?></p>
</div>
<div class="sui-box-settings-row sui-disabled">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label">
			<?php esc_html_e( 'Configure', 'wphb' ); ?>
		</span>
		<span class="sui-description">
			<?php esc_html_e( 'Choose from daily, weekly or monthly email reports.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<label for="chk1" class="sui-toggle">
				<input type="hidden" name="email-notifications" value="0" />
				<input type="checkbox" name="email-notifications" id="chk1" value="1" aria-labelledby="email-notifications-label">
				<span class="sui-toggle-slider" aria-hidden="true"></span>
				<span id="email-notifications-label" class="sui-toggle-label">
					<?php esc_html_e( 'Send scheduled performance reports', 'wphb' ); ?>
				</span>
			</label>
		</div>
	</div>
</div>

<div class="sui-box-settings-row sui-upsell-row">
	<img class="sui-image sui-upsell-image"
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-upsell-minify.png' ); ?>"
		srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-upsell-minify@2x.png' ); ?> 2x"
		alt="<?php esc_attr_e( 'Scheduled automated performance tests', 'wphb' ); ?>">
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %1$s - upsell link start, %2$s - closing a tag */
			esc_html__( 'Schedule automated performance tests and receive customized email reports direct to your inbox. Get reporting as part of a WPMU DEV membership with 24/7 support and lots of handy site management tools. %1$sTry Pro for FREE today!%2$s', 'wphb' ),
			'<a href="' . esc_url( \Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_test_upsell_link' ) ) . '" target="_blank">',
			'</a>'
		),
		'sui-upsell-notice'
	);
	?>
</div>
