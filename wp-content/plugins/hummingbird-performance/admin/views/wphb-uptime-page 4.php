<?php
/**
 * Uptime page.
 *
 * @package Hummingbird
 *
 * @var \Hummingbird\Admin\Pages\Uptime $this
 * @var string $retry_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $this->has_meta_boxes( 'summary' ) ) {
	$this->do_meta_boxes( 'summary' );
}

if ( $this->has_meta_boxes( 'box-uptime-disabled' ) ) {
	$this->do_meta_boxes( 'box-uptime-disabled' );
} else {
	?>
	<div class="sui-row-with-sidenav">
		<?php $this->show_tabs(); ?>
		<?php if ( $error ) : ?>
			<div class="sui-box">
				<div class="sui-box-header"><?php esc_html_e( 'Uptime', 'wphb' ); ?></div>
				<div class="sui-box-body">
					<div class="sui-notice sui-notice-error">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
								<p><?php echo esc_html( $error ); ?></p>
								<p>
									<a href="<?php echo esc_url( $retry_url ); ?>" class="sui-button sui-button-blue">
										<?php esc_html_e( 'Try again', 'wphb' ); ?>
									</a>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php else : ?>
			<?php $this->do_meta_boxes( $this->get_current_tab() ); ?>
		<?php endif; ?>
	</div>
	<?php
}
?>

<?php $this->modal( 'add-recipient' ); ?>

<script>
	jQuery(document).ready( function() {
		if ( window.WPHB_Admin ) {
			window.WPHB_Admin.getModule( 'uptime' );
		}
	});
</script>
