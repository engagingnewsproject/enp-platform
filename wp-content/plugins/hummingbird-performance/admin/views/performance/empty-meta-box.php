<?php
/**
 * Performance empty report meta box.
 *
 * @package Hummingbird
 */

use Hummingbird\Core\Modules\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
	<img class="sui-image" aria-hidden="true" alt=""
		src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/hummingbird-circle@1x.jpg' ); ?>" />
<?php endif; ?>

<div class="sui-message-content">
	<p>
		<?php
		esc_html_e( 'Before you can make tweaks to your website let’s find out what can be improved. Hummingbird will run a quick performance test, and then give you the tools to make drastic improvements to your website’s load time.', 'wphb' );
		?>
	</p>

	<button role="button" class="sui-button sui-button-blue" id="run-performance-test">
		<?php esc_html_e( 'Test my website', 'wphb' ); ?>
	</button>
</div>

<?php $this->modal( 'check-performance' ); ?>

<?php if ( Performance::is_doing_report() ) : // Show the progress bar if we are still checking files. ?>
	<script>
		window.addEventListener("load", function() {
			const performance = WPHB_Admin.getModule( 'performance' );
			performance.startPerformanceScan();
		});
	</script>
<?php endif; ?>
