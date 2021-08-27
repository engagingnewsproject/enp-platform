<?php
/**
 * Performance disabled meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $run_url  URL to performance module.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p><?php esc_html_e( 'Run a Google PageSpeed test and get itemized insight (with fixes) on where you can improve your website’s performance.', 'wphb' ); ?></p>

<a href="<?php echo esc_url( $run_url ); ?>" class="sui-button sui-button-blue" id="performance-scan-website">
	<?php esc_html_e( 'Run Test', 'wphb' ); ?>
</a>
